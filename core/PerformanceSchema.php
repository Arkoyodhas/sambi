<?php

/**
 * Menambahkan indeks komposit penting secara idempoten.
 * Kegagalan ALTER tidak menghentikan website pada hosting dengan izin terbatas.
 */
class PerformanceSchema {
    private const VERSION = '2026.07.27-2';

    public static function ensure(): void {
        static $attempted = false;
        if ($attempted) return;
        $attempted = true;

        try {
            if (setting('performance_schema_version', '') === self::VERSION) return;
            $lastAttempt = strtotime(setting('performance_schema_last_attempt', '1970-01-01 00:00:00')) ?: 0;
            if ($lastAttempt > time() - 86400) return;

            $indexes = [
                'news' => [
                    'idx_news_public' => ['status', 'deleted_at', 'published_at'],
                    'idx_news_category_public' => ['category_id', 'status', 'deleted_at', 'published_at'],
                    'idx_news_title' => ['title'],
                ],
                'service_requests' => [
                    'idx_service_ticket_deleted' => ['ticket', 'deleted_at'],
                    'idx_service_status_created' => ['status', 'created_at'],
                    'idx_service_service_created' => ['service_id', 'created_at'],
                    'idx_service_nik' => ['nik'],
                ],
                'complaints' => [
                    'idx_complaint_ticket_deleted' => ['ticket', 'deleted_at'],
                    'idx_complaint_status_created' => ['status', 'created_at'],
                    'idx_complaint_category_created' => ['category', 'created_at'],
                ],
                'documents' => [
                    'idx_documents_public_year' => ['is_public', 'is_active', 'deleted_at', 'year'],
                    'idx_documents_category' => ['category', 'deleted_at'],
                ],
                'galleries' => [
                    'idx_gallery_public' => ['is_active', 'deleted_at', 'album_id', 'year'],
                    'idx_gallery_category' => ['category', 'deleted_at'],
                ],
                'umkm_products' => [
                    'idx_umkm_public_category' => ['is_active', 'deleted_at', 'category', 'id'],
                ],
                'village_potentials' => [
                    'idx_potential_public_category' => ['is_active', 'deleted_at', 'category', 'id'],
                ],
                'menus' => [
                    'idx_menu_render' => ['location', 'is_active', 'parent_id', 'sort_order'],
                ],
                'background_music' => [
                    'idx_music_active_schedule' => ['is_active', 'start_date', 'end_date'],
                ],
                'visit_logs' => [
                    'idx_visit_date_path' => ['visited_at', 'path'],
                    'idx_visit_unique_date' => ['is_unique', 'visited_at'],
                ],
                'agendas' => [
                    'idx_agenda_public_date' => ['deleted_at', 'event_date'],
                ],
                'announcements' => [
                    'idx_announcement_public' => ['is_active', 'deleted_at', 'start_date', 'end_date'],
                ],
                'regions' => [
                    'idx_region_tree' => ['type', 'parent_id', 'sort_order'],
                ],
                'residents' => [
                    'idx_resident_public_status' => ['status_penduduk', 'deleted_at'],
                    'idx_resident_area' => ['dusun_id', 'rw_id', 'rt_id', 'status_penduduk', 'deleted_at'],
                    'idx_resident_name' => ['nama'],
                ],
                'news_comments' => [
                    'idx_news_comment_public' => ['news_id', 'is_approved', 'created_at'],
                ],
                'services' => [
                    'idx_services_public' => ['is_active', 'deleted_at', 'name'],
                ],
                'bansos_programs' => [
                    'idx_bansos_program_year' => ['tahun', 'is_active', 'nama'],
                ],
                'bansos_recipients' => [
                    'idx_bansos_recipient_program' => ['program_id', 'nama'],
                ],
                'stunting_data' => [
                    'idx_stunting_date_area' => ['tanggal_pemeriksaan', 'rw', 'rt'],
                    'idx_stunting_status_date' => ['status', 'tanggal_pemeriksaan'],
                ],
            ];

            $failed = 0;
            foreach ($indexes as $table => $tableIndexes) {
                if (!self::tableExists($table)) continue;
                foreach ($tableIndexes as $name => $columns) {
                    try {
                        self::addIndex($table, $name, $columns);
                    } catch (Throwable $e) {
                        $failed++;
                        error_log("Performance index {$table}.{$name}: " . $e->getMessage());
                    }
                }
            }

            set_setting('performance_schema_last_attempt', date('Y-m-d H:i:s'));
            if ($failed === 0) set_setting('performance_schema_version', self::VERSION);
        } catch (Throwable $e) {
            error_log('Performance indexes: ' . $e->getMessage());
        }
    }

    private static function tableExists(string $table): bool {
        return (bool) db_val(
            "SELECT 1 FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1",
            [$table]
        );
    }

    private static function addIndex(string $table, string $name, array $columns): void {
        $exists = (bool) db_val(
            "SELECT 1 FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1",
            [$table, $name]
        );
        if ($exists) return;

        // Hindari indeks duplikat bila instalasi lama memakai nama berbeda.
        $wanted = implode(',', $columns);
        $existingIndexes = db_all(
            "SELECT INDEX_NAME,
                    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX SEPARATOR ',') AS indexed_columns
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
             GROUP BY INDEX_NAME",
            [$table]
        );
        foreach ($existingIndexes as $index) {
            $actual = (string) ($index['indexed_columns'] ?? '');
            if ($actual === $wanted || str_starts_with($actual, $wanted . ',')) return;
        }

        $available = [];
        foreach ($columns as $column) {
            $hasColumn = (bool) db_val(
                "SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1",
                [$table, $column]
            );
            if (!$hasColumn) return;
            $available[] = '`' . str_replace('`', '', $column) . '`';
        }
        if (!$available) return;
        $safeTable = str_replace('`', '', $table);
        $safeName = str_replace('`', '', $name);
        db_exec("ALTER TABLE `{$safeTable}` ADD INDEX `{$safeName}` (" . implode(',', $available) . ")");
    }
}

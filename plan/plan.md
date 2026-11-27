# Desa Dawung Website — Teal Redesign & Smooth Motion Upgrade

A full visual refresh of the existing official website of Desa Dawung (Sambirejo, Sragen). It brings a bright teal look, smooth Apple-style motion, and an illustrated 3D village hero.
Everything that works today stays: all pages, menus, data, admin features, Live Chat, the citizen survey and letter requests.

## Who it's for
- **Residents (warga)**: they check news, request letters (surat), file complaints and look up village information, mostly on phones.
- **Visitors and investors**: they browse UMKM products, tourism potential, the gallery and transparency data.
- **Village staff (admin)**: they keep managing content in the existing dashboard. They also get two new things to manage: FAQ entries and the hero scenery images.

## Core features and experience
**Kept exactly as they are**
- All existing routes and sub-menus: Profil, Pemerintahan/SOTK, Berita, Layanan, Informasi (Pengumuman, Agenda, Galeri, Statistik, Dokumen, UMKM, SDGs, IDM, Bansos, Transparansi, Stunting), Pengaduan, Kontak, E-Perpus, E-Commerce, Zona Hiburan.
- All live data (Penduduk, KK, Dusun, RT/RW, Perangkat, Berita, Layanan Surat, UMKM, Potensi, Galeri), the Live Chat widget, the citizen survey pop-up and the whole admin panel's behaviour.

**Bug fixes**
- Statistic counters show a soft grey placeholder until real numbers load. They no longer flash "0".
- News excerpts no longer show raw codes such as `&ndash;` or `&nbsp;`.
- UMKM titles are shown in tidy Title Case, e.g. "Kembang Goyang" and "Kerajinan Kayu Salon Sound System". The stored data is not changed.

**New design across every page (full redesign)**
- Bright, light theme: teal `#00B589` for main buttons, active filters and trust badges. Amber `#F59E0B` for "Gratis" and category tags.
- Sections alternate between white and a soft mint tint. Cards have rounded 16–20px corners, soft shadows and generous spacing.
- Plus Jakarta Sans font, dark slate headings and readable slate body text.
- Navbar simplified to a few top-level items with tidy dropdowns, plus a pill-shaped "Ajukan Surat" button. On phones it becomes a clean slide-in menu.
- Every inner page (lists, detail pages, forms, tables, charts, maps) gets the same cards, buttons, headings and spacing. Each page keeps its current content and layout logic.

**Motion (smooth, calm, Apple-like; no bouncing, neon blobs or spinning wireframes)**
- Smooth scrolling with natural momentum across the whole site.
- Content fades and slides in gently as it enters the screen. Headings reveal line by line.
- All motion is reduced automatically for visitors who turn on "reduce motion" on their device.

**Homepage sections**
1. **Hero**: a layered illustrated landscape with drifting clouds, the distant Lawu/Sragen hills and a village silhouette. Each layer moves at a different speed as you scroll. On the right is a soft clay-style 3D mini diorama of Desa Dawung (pendopo, lush trees, fish pond). It tilts slightly with the mouse and the camera shifts as you scroll. **Phones show a still picture of the diorama.**
2. **Stats bar**: an elevated white card that overlaps the hero. Numbers roll up like an odometer when they scroll into view.
3. **Sambutan Kepala Desa**: Bapak Paimin's welcome is shown as a wide editorial card that reveals with a sliding mask. Perangkat Desa cards tilt slightly in 3D on hover.
4. **Layanan Administrasi**: the 8 service cards (Domisili, Usaha, KTP, KK, SKTM, Kelahiran, Kematian, Pindah) get 3D-style document and stamp icons that lift on hover, plus "1 Hari Kerja" and "Gratis" badges. Below them is a 5-step "Alur Pengajuan Surat" path whose line draws itself as you scroll.
5. **UMKM & Potensi**: one combined grid with filter pills (Semua, Produk UMKM, Wisata & Budidaya, E-Commerce & E-Perpus). Cards rearrange smoothly when the filter changes and tilt gently toward the cursor.
6. **Berita & Galeri**: a clean 3-column magazine layout. Photos drift slightly inside their rounded frames as you scroll.
7. **FAQ & Footer**: an expandable FAQ list above the footer. The footer, map preview and the Live Chat/WhatsApp floating button are restyled in teal.

**New admin abilities**
- **FAQ manager**: add, edit, reorder, show or hide questions and answers. It comes with editable starter Q&As about letters, complaints and office hours.
- **Hero scenery**: the illustrated sky, hills, village layer and phone diorama picture are created first as defaults. Admin can replace any of them by uploading new images.

## User flow
1. A resident opens the homepage, scrolls through the moving landscape and sees live village numbers roll up.
2. They tap "Ajukan Surat" or a service card, read the 5-step process and continue into the existing letter request form.
3. They filter UMKM or Potensi, open a product or tourism page, or read the latest news.
4. They check the FAQ. If the answer isn't there, they use Live Chat, WhatsApp or Pengaduan as they do today.
5. Admin logs in, edits FAQ entries or swaps hero scenery images, and the homepage updates.

## UI/UX feel
Bright, friendly and trustworthy, like a modern public-service app (inspired by seakun.id). Calm, premium motion similar to Apple product pages: slow easing, layered depth and nothing flashy. Fast on village mobile data. Heavy 3D runs only on capable laptops and desktops.

## Implementation phases
**Phase 1 — MVP (built now)**
- New theme and redesigned navbar and footer applied to every public page.
- All three bug fixes.
- Smooth scrolling and reveal motion site-wide.
- All 7 homepage sections, including the scroll-layered hero, the 3D diorama on desktop with a still picture on phones, odometer stats, the self-drawing letter process path, the filterable UMKM/Potensi grid and the FAQ.
- Generated default scenery images, the admin FAQ manager and the admin hero-scenery uploads.
- Every existing link, form, pop-up and data display re-checked.

**Phase 2**
- Restyle the admin dashboard in the same teal look.
- Richer detail-page motion, e.g. image transitions between news list and article.
- Dark mode option.

**Phase 3**
- Let admin pick the diorama's time of day (morning, dusk) and seasonal touches for holidays.
- Performance tuning per page, plus an offline-friendly experience for key pages.

## Assumptions
- The site is built in PHP with Bootstrap. The motion and 3D libraries requested for React (react-three-fiber/drei) are replaced by their plain-JavaScript equivalents (Three.js, GSAP + ScrollTrigger, Lenis). They are bundled locally so the site doesn't depend on outside servers.
- "Every page fully redesigned" covers the public pages only. The admin panel keeps its current look until Phase 2.
- Each page keeps its existing content, order and features. Only the look and motion change.
- The diorama and landscape are original stylised illustrations, not an exact copy of a real photo. Admin can replace the images later.
- "1 Hari Kerja" and "Gratis" badges apply to all 8 services by default.
- Starter FAQ answers are written as general sample text for admin to correct.
- The UMKM/Potensi grid on the homepage shows a preview. The full UMKM and Potensi pages remain where they are.
- The existing AOS scroll animations are replaced by the new motion system. Visible behaviour stays equivalent or smoother.
- Database changes are limited to adding FAQ entries and hero-scenery settings. No existing tables or data are changed.

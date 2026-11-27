<?php

class EntertainmentController extends Controller {

    public array $actions = [
        'profile', 'quiz', 'quiz_answer', 'game_start', 'game_complete',
        'wheel', 'nickname', 'leaderboard', 'activity',
        'poll_vote', 'story', 'story_choose', 'fact',
        'daily_box', 'profile_customize', 'challenge'
    ];

    public function index(): void {
        Entertainment::ensureSchema();

        $image = '';
        if (setting('hero_image')) $image = upload_url(setting('hero_image'));
        elseif (setting('logo')) $image = upload_url(setting('logo'));
        else $image = asset('img/pwa-512.png');

        $this->view('hiburan', [
            'pageTitle' => 'Zona Hiburan — ' . setting('village_name'),
            'metaDescription' => 'Aktivitas interaktif, kuis, game ringan, misi harian, poin, badge, cerita, fakta, dan papan peringkat.',
            'puzzleImage' => $image,
            'extraCss' => ['css/entertainment.css'],
            'extraJs' => ['js/entertainment.js'],
            'entertainmentPage' => true,
        ]);
    }

    public function profile(): void {
        $d = $this->jsonBody();
        $profile = Entertainment::profile($d['visitor_token'] ?? null);
        if (!$profile) $this->json(['ok' => false, 'error' => 'Profil pemain belum dapat disiapkan. Muat ulang halaman.']);
        $this->json([
            'ok' => true,
            'profile' => $profile,
            'leaderboard' => Entertainment::leaderboard(15),
            'leaderboard_enabled' => setting('entertainment_leaderboard_enabled', '1') === '1',
        ]);
    }

    public function quiz(): void {
        $d = $this->jsonBody();
        $this->json(Entertainment::dailyQuestion($d['visitor_token'] ?? null));
    }

    public function quiz_answer(): void {
        $d = $this->jsonBody();
        $this->json(Entertainment::answerQuiz(
            $d['visitor_token'] ?? null,
            $d['question_id'] ?? null,
            $d['answer'] ?? null
        ));
    }

    public function game_start(): void {
        $d = $this->jsonBody();
        $this->json(Entertainment::startGame(
            $d['visitor_token'] ?? null,
            $d['game'] ?? null
        ));
    }

    public function game_complete(): void {
        $d = $this->jsonBody();
        $this->json(Entertainment::submitGame(
            $d['visitor_token'] ?? null,
            $d['game'] ?? null,
            $d['score'] ?? 0,
            [
                'moves' => $d['moves'] ?? 0,
                'seconds' => $d['seconds'] ?? 0,
                'session_token' => $d['session_token'] ?? '',
            ]
        ));
    }

    public function wheel(): void {
        $d = $this->jsonBody();
        $this->json(Entertainment::spinWheel($d['visitor_token'] ?? null));
    }

    public function nickname(): void {
        $d = $this->jsonBody();
        $this->json(Entertainment::updateNickname(
            $d['visitor_token'] ?? null,
            $d['nickname'] ?? ''
        ));
    }

    public function profile_customize(): void {
        $d = $this->jsonBody();
        $this->json(Entertainment::updateProfile($d['visitor_token'] ?? null, [
            'nickname' => $d['nickname'] ?? '',
            'avatar' => $d['avatar'] ?? 'leaf',
            'frame' => $d['frame'] ?? 'simple',
            'featured_badge' => $d['featured_badge'] ?? '',
        ]));
    }

    public function activity(): void {
        $d = $this->jsonBody();
        $this->json(Entertainment::trackActivity(
            $d['visitor_token'] ?? null,
            $d['activity'] ?? '',
            $d['source'] ?? ''
        ));
    }

    public function poll_vote(): void {
        $d = $this->jsonBody();
        $this->json(Entertainment::votePoll(
            $d['visitor_token'] ?? null,
            $d['poll_id'] ?? 0,
            $d['option_id'] ?? 0
        ));
    }

    public function story(): void {
        $d = $this->jsonBody();
        $this->json(Entertainment::story($d['visitor_token'] ?? null));
    }

    public function story_choose(): void {
        $d = $this->jsonBody();
        $this->json(Entertainment::chooseStory(
            $d['visitor_token'] ?? null,
            $d['choice'] ?? '',
            !empty($d['reset'])
        ));
    }

    public function fact(): void {
        $d = $this->jsonBody();
        $this->json(Entertainment::nextFact(
            $d['visitor_token'] ?? null,
            $d['exclude_id'] ?? 0
        ));
    }

    public function daily_box(): void {
        $d = $this->jsonBody();
        $this->json(Entertainment::claimDailyBox($d['visitor_token'] ?? null));
    }

    public function challenge(): void {
        $this->jsonBody();
        $challenge = Entertainment::dailyChallenge();
        $this->json([
            'ok' => true,
            'challenge' => [
                'date' => $challenge['date'] ?? date('Y-m-d'),
                'game_config' => is_array($challenge['game_config'] ?? null) ? $challenge['game_config'] : [],
            ],
        ]);
    }

    public function leaderboard(): void {
        header('Cache-Control: no-store');
        if (!Entertainment::enabled()) {
            http_response_code(403);
            $this->json(['ok' => false, 'enabled' => false, 'error' => 'Zona Hiburan sedang dinonaktifkan.']);
        }
        $this->json([
            'ok' => true,
            'enabled' => setting('entertainment_leaderboard_enabled', '1') === '1',
            'items' => Entertainment::leaderboard(20),
        ]);
    }

    private function jsonBody(): array {
        if (!Entertainment::enabled()) {
            http_response_code(403);
            $this->json(['ok' => false, 'enabled' => false, 'error' => 'Zona Hiburan sedang dinonaktifkan.']);
        }
        header('Cache-Control: no-store');
        $endpoint = 'entertainment:' . str_replace('_', '-', debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? 'request');
        try {
            return Security::publicJsonRequest($endpoint, 30, 60, 32768);
        } catch (RuntimeException $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage()]);
        }
        return [];
    }
}

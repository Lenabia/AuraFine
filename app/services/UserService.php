<?php
namespace app\services;

use app\Models\Users;

class UserService {
    private Users $usersModel;

    public function __construct() {
        $this->usersModel = new Users();
    }

    public function getReferralsByUserId(int $userId): array {
        return $this->usersModel->getReferralsByUserId($userId);
    }

    public function getReferralLink(array $user): string {
        $code = (string)($user['referral_code'] ?? '');
        $base = BASE_URL;
        // En local (localhost/127.0.0.1), pas d'URL rewriting → utiliser index.php?action=
        $isLocal = (strpos($base, 'localhost') !== false) || (strpos($base, '127.0.0.1') !== false);
        if ($isLocal) {
            return $base . '/index.php?action=register&ref=' . urlencode($code);
        }
        // En prod: URL propre /register?ref=
        return $base . '/register?ref=' . urlencode($code);
    }
}



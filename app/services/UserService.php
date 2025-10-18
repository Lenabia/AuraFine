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

    /**
     * Récupérer les données du profil utilisateur
     */
    public function getUserProfile(int $userId): ?array {
        return $this->usersModel->getUserProfileData($userId);
    }

    /**
     * Mettre à jour le profil utilisateur
     */
    public function updateUserProfile(int $userId, array $data): bool {
        // Valider les données
        $errors = $this->validateProfileUpdate($data, $userId);
        if (!empty($errors)) {
            return false;
        }

        // Nettoyer les données
        $cleanData = $this->sanitizeProfileData($data);
        
        // Mettre à jour en base
        return $this->usersModel->update($userId, $cleanData);
    }

    /**
     * Supprimer le compte utilisateur
     */
    public function deleteUserAccount(int $userId): bool {
        return $this->usersModel->delete($userId);
    }

    /**
     * Valider les données de mise à jour du profil
     */
    public function validateProfileUpdate(array $data, int $userId): array {
        $errors = [];

        // Validation du prénom
        if (isset($data['first_name'])) {
            if (empty(trim($data['first_name']))) {
                $errors['first_name'] = 'Le prénom est obligatoire.';
            } elseif (!preg_match('/^[A-Za-zÀ-ÖØ-öø-ÿ\' -]{2,50}$/', trim($data['first_name']))) {
                $errors['first_name'] = 'Le prénom doit contenir 2 à 50 caractères (lettres, espaces, tirets, apostrophes).';
            }
        }

        // Validation du nom
        if (isset($data['last_name'])) {
            if (empty(trim($data['last_name']))) {
                $errors['last_name'] = 'Le nom est obligatoire.';
            } elseif (!preg_match('/^[A-Za-zÀ-ÖØ-öø-ÿ\' -]{2,50}$/', trim($data['last_name']))) {
                $errors['last_name'] = 'Le nom doit contenir 2 à 50 caractères (lettres, espaces, tirets, apostrophes).';
            }
        }

        // Validation de l'email
        if (isset($data['email'])) {
            $email = strtolower(trim($data['email']));
            if (empty($email)) {
                $errors['email'] = 'L\'email est obligatoire.';
            } elseif (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
                $errors['email'] = 'L\'email doit contenir un @ et un domaine valide.';
            } elseif ($this->usersModel->emailExists($email, $userId)) {
                $errors['email'] = 'Cette adresse email existe déjà.';
            }
        }

        // Validation du téléphone
        if (isset($data['phone_suffix'])) {
            $phoneSuffix = trim($data['phone_suffix']);
            if (empty($phoneSuffix)) {
                $errors['phone'] = 'Le téléphone est obligatoire.';
            } elseif (!preg_match('/^[0-9]{9}$/', $phoneSuffix)) {
                $errors['phone'] = 'Le numéro de téléphone doit contenir exactement 9 chiffres (ex: 771234567).';
            }
        }

        // Validation des IDs géographiques
        if (isset($data['cities_id']) && (!is_numeric($data['cities_id']) || $data['cities_id'] <= 0)) {
            $errors['cities_id'] = 'Veuillez sélectionner une ville valide.';
        }

        if (isset($data['neighborhoods_id']) && (!is_numeric($data['neighborhoods_id']) || $data['neighborhoods_id'] <= 0)) {
            $errors['neighborhoods_id'] = 'Veuillez sélectionner un quartier valide.';
        }

        return $errors;
    }

    /**
     * Nettoyer les données du profil
     */
    private function sanitizeProfileData(array $data): array {
        $cleanData = [];

        if (isset($data['first_name'])) {
            $cleanData['first_name'] = trim($data['first_name']);
        }
        if (isset($data['last_name'])) {
            $cleanData['last_name'] = trim($data['last_name']);
        }
        if (isset($data['email'])) {
            $cleanData['email'] = strtolower(trim($data['email']));
        }
        if (isset($data['phone_suffix'])) {
            $cleanData['phone'] = '+221' . trim($data['phone_suffix']);
        }
        if (isset($data['address_line'])) {
            $cleanData['address_line'] = trim($data['address_line']);
        }
        if (isset($data['cities_id'])) {
            $cleanData['cities_id'] = (int)$data['cities_id'];
        }
        if (isset($data['neighborhoods_id'])) {
            $cleanData['neighborhoods_id'] = (int)$data['neighborhoods_id'];
        }
        if (isset($data['delivery_zones_id'])) {
            $cleanData['delivery_zones_id'] = (int)$data['delivery_zones_id'];
        }

        return $cleanData;
    }
}



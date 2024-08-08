<?php

namespace App\Service;

class DataValidator
{
    public function validate(array $userData): bool
    {
        if (empty($userData['email']) || !filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if (empty($userData['login']['uuid']) || empty($userData['name']['first']) || empty($userData['name']['last']) || empty($userData['dob']['date']) || empty($userData['registered']['date'])) {
            return false;
        }

        if (strtolower($userData['nat']) !== 'au' || strtolower($userData['location']['country']) != 'australia') {
            return false;
        }

        try {
            new \DateTime($userData['dob']['date']);
            new \DateTime($userData['registered']['date']);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }
}
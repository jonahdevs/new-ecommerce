<?php

namespace App\Services;

/**
 * Class PhoneNormalizationService.
 */
class PhoneNormalizationService
{
    public function normalize(?string $phone, string $countryCode = '254'): ?string
    {
        if (!$phone) {
            return null;
        }

        // Remove all non-numeric characters (spaces, dashes, etc.)
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Remove leading zeros
        $phone = ltrim($phone, '0');

        // If it's 9 digits (user entered without leading 0), it's valid
        // If it's 10 digits (user entered with leading 0), we already removed it
        if (strlen($phone) === 9) {
            return '+' . $countryCode . $phone;
        }

        // If already has country code (user somehow entered 254...)
        if (str_starts_with($phone, $countryCode)) {
            return '+' . $phone;
        }

        // Invalid length
        return null;
    }

    public function formatForDisplay(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        // Remove +254 prefix for display
        $local = preg_replace('/^\+254/', '', $phone);

        // Format as: 712 345 678
        if (strlen($local) === 9) {
            return substr($local, 0, 3) . ' ' . substr($local, 3, 3) . ' ' . substr($local, 6, 3);
        }

        return $local;
    }
}

<?php

namespace App\Support;

class CountryCodes
{
    /**
     * Returns all countries ordered: East Africa first, then alphabetically.
     *
     * @return array<int, array{code: string, name: string, dial: string, flag: string}>
     */
    public static function all(): array
    {
        return [
            // ── East Africa (priority) ─────────────────────────────────────
            ['code' => 'KE', 'name' => 'Kenya',        'dial' => '+254', 'flag' => '🇰🇪'],
            ['code' => 'UG', 'name' => 'Uganda',       'dial' => '+256', 'flag' => '🇺🇬'],
            ['code' => 'TZ', 'name' => 'Tanzania',     'dial' => '+255', 'flag' => '🇹🇿'],
            ['code' => 'RW', 'name' => 'Rwanda',       'dial' => '+250', 'flag' => '🇷🇼'],
            ['code' => 'ET', 'name' => 'Ethiopia',     'dial' => '+251', 'flag' => '🇪🇹'],
            ['code' => 'SS', 'name' => 'South Sudan',  'dial' => '+211', 'flag' => '🇸🇸'],
            ['code' => 'BI', 'name' => 'Burundi',      'dial' => '+257', 'flag' => '🇧🇮'],
            ['code' => 'DJ', 'name' => 'Djibouti',     'dial' => '+253', 'flag' => '🇩🇯'],
            ['code' => 'ER', 'name' => 'Eritrea',      'dial' => '+291', 'flag' => '🇪🇷'],
            ['code' => 'SO', 'name' => 'Somalia',      'dial' => '+252', 'flag' => '🇸🇴'],
            // ── Rest of Africa ─────────────────────────────────────────────
            ['code' => 'DZ', 'name' => 'Algeria',              'dial' => '+213', 'flag' => '🇩🇿'],
            ['code' => 'AO', 'name' => 'Angola',               'dial' => '+244', 'flag' => '🇦🇴'],
            ['code' => 'BJ', 'name' => 'Benin',                'dial' => '+229', 'flag' => '🇧🇯'],
            ['code' => 'BW', 'name' => 'Botswana',             'dial' => '+267', 'flag' => '🇧🇼'],
            ['code' => 'BF', 'name' => 'Burkina Faso',         'dial' => '+226', 'flag' => '🇧🇫'],
            ['code' => 'CM', 'name' => 'Cameroon',             'dial' => '+237', 'flag' => '🇨🇲'],
            ['code' => 'CV', 'name' => 'Cape Verde',           'dial' => '+238', 'flag' => '🇨🇻'],
            ['code' => 'CF', 'name' => 'Central African Rep.', 'dial' => '+236', 'flag' => '🇨🇫'],
            ['code' => 'TD', 'name' => 'Chad',                 'dial' => '+235', 'flag' => '🇹🇩'],
            ['code' => 'KM', 'name' => 'Comoros',              'dial' => '+269', 'flag' => '🇰🇲'],
            ['code' => 'CG', 'name' => 'Congo',                'dial' => '+242', 'flag' => '🇨🇬'],
            ['code' => 'CD', 'name' => 'Congo (DRC)',           'dial' => '+243', 'flag' => '🇨🇩'],
            ['code' => 'CI', 'name' => "Côte d'Ivoire",        'dial' => '+225', 'flag' => '🇨🇮'],
            ['code' => 'EG', 'name' => 'Egypt',                'dial' => '+20',  'flag' => '🇪🇬'],
            ['code' => 'GQ', 'name' => 'Equatorial Guinea',    'dial' => '+240', 'flag' => '🇬🇶'],
            ['code' => 'GA', 'name' => 'Gabon',                'dial' => '+241', 'flag' => '🇬🇦'],
            ['code' => 'GM', 'name' => 'Gambia',               'dial' => '+220', 'flag' => '🇬🇲'],
            ['code' => 'GH', 'name' => 'Ghana',                'dial' => '+233', 'flag' => '🇬🇭'],
            ['code' => 'GN', 'name' => 'Guinea',               'dial' => '+224', 'flag' => '🇬🇳'],
            ['code' => 'GW', 'name' => 'Guinea-Bissau',        'dial' => '+245', 'flag' => '🇬🇼'],
            ['code' => 'LS', 'name' => 'Lesotho',              'dial' => '+266', 'flag' => '🇱🇸'],
            ['code' => 'LR', 'name' => 'Liberia',              'dial' => '+231', 'flag' => '🇱🇷'],
            ['code' => 'LY', 'name' => 'Libya',                'dial' => '+218', 'flag' => '🇱🇾'],
            ['code' => 'MG', 'name' => 'Madagascar',           'dial' => '+261', 'flag' => '🇲🇬'],
            ['code' => 'MW', 'name' => 'Malawi',               'dial' => '+265', 'flag' => '🇲🇼'],
            ['code' => 'ML', 'name' => 'Mali',                 'dial' => '+223', 'flag' => '🇲🇱'],
            ['code' => 'MR', 'name' => 'Mauritania',           'dial' => '+222', 'flag' => '🇲🇷'],
            ['code' => 'MU', 'name' => 'Mauritius',            'dial' => '+230', 'flag' => '🇲🇺'],
            ['code' => 'MA', 'name' => 'Morocco',              'dial' => '+212', 'flag' => '🇲🇦'],
            ['code' => 'MZ', 'name' => 'Mozambique',           'dial' => '+258', 'flag' => '🇲🇿'],
            ['code' => 'NA', 'name' => 'Namibia',              'dial' => '+264', 'flag' => '🇳🇦'],
            ['code' => 'NE', 'name' => 'Niger',                'dial' => '+227', 'flag' => '🇳🇪'],
            ['code' => 'NG', 'name' => 'Nigeria',              'dial' => '+234', 'flag' => '🇳🇬'],
            ['code' => 'RE', 'name' => 'Réunion',              'dial' => '+262', 'flag' => '🇷🇪'],
            ['code' => 'SN', 'name' => 'Senegal',              'dial' => '+221', 'flag' => '🇸🇳'],
            ['code' => 'SL', 'name' => 'Sierra Leone',         'dial' => '+232', 'flag' => '🇸🇱'],
            ['code' => 'ZA', 'name' => 'South Africa',         'dial' => '+27',  'flag' => '🇿🇦'],
            ['code' => 'SD', 'name' => 'Sudan',                'dial' => '+249', 'flag' => '🇸🇩'],
            ['code' => 'SZ', 'name' => 'Eswatini',             'dial' => '+268', 'flag' => '🇸🇿'],
            ['code' => 'TG', 'name' => 'Togo',                 'dial' => '+228', 'flag' => '🇹🇬'],
            ['code' => 'TN', 'name' => 'Tunisia',              'dial' => '+216', 'flag' => '🇹🇳'],
            ['code' => 'ZM', 'name' => 'Zambia',               'dial' => '+260', 'flag' => '🇿🇲'],
            ['code' => 'ZW', 'name' => 'Zimbabwe',             'dial' => '+263', 'flag' => '🇿🇼'],
            // ── Americas ───────────────────────────────────────────────────
            ['code' => 'US', 'name' => 'United States', 'dial' => '+1',   'flag' => '🇺🇸'],
            ['code' => 'CA', 'name' => 'Canada',         'dial' => '+1',   'flag' => '🇨🇦'],
            ['code' => 'MX', 'name' => 'Mexico',         'dial' => '+52',  'flag' => '🇲🇽'],
            ['code' => 'BR', 'name' => 'Brazil',         'dial' => '+55',  'flag' => '🇧🇷'],
            ['code' => 'AR', 'name' => 'Argentina',      'dial' => '+54',  'flag' => '🇦🇷'],
            ['code' => 'CO', 'name' => 'Colombia',       'dial' => '+57',  'flag' => '🇨🇴'],
            ['code' => 'CL', 'name' => 'Chile',          'dial' => '+56',  'flag' => '🇨🇱'],
            ['code' => 'PE', 'name' => 'Peru',           'dial' => '+51',  'flag' => '🇵🇪'],
            // ── Europe ─────────────────────────────────────────────────────
            ['code' => 'GB', 'name' => 'United Kingdom', 'dial' => '+44',  'flag' => '🇬🇧'],
            ['code' => 'DE', 'name' => 'Germany',         'dial' => '+49',  'flag' => '🇩🇪'],
            ['code' => 'FR', 'name' => 'France',          'dial' => '+33',  'flag' => '🇫🇷'],
            ['code' => 'IT', 'name' => 'Italy',           'dial' => '+39',  'flag' => '🇮🇹'],
            ['code' => 'ES', 'name' => 'Spain',           'dial' => '+34',  'flag' => '🇪🇸'],
            ['code' => 'NL', 'name' => 'Netherlands',     'dial' => '+31',  'flag' => '🇳🇱'],
            ['code' => 'PT', 'name' => 'Portugal',        'dial' => '+351', 'flag' => '🇵🇹'],
            ['code' => 'SE', 'name' => 'Sweden',          'dial' => '+46',  'flag' => '🇸🇪'],
            ['code' => 'NO', 'name' => 'Norway',          'dial' => '+47',  'flag' => '🇳🇴'],
            ['code' => 'CH', 'name' => 'Switzerland',     'dial' => '+41',  'flag' => '🇨🇭'],
            // ── Middle East ────────────────────────────────────────────────
            ['code' => 'AE', 'name' => 'UAE',            'dial' => '+971', 'flag' => '🇦🇪'],
            ['code' => 'SA', 'name' => 'Saudi Arabia',   'dial' => '+966', 'flag' => '🇸🇦'],
            ['code' => 'QA', 'name' => 'Qatar',          'dial' => '+974', 'flag' => '🇶🇦'],
            ['code' => 'KW', 'name' => 'Kuwait',         'dial' => '+965', 'flag' => '🇰🇼'],
            ['code' => 'TR', 'name' => 'Turkey',         'dial' => '+90',  'flag' => '🇹🇷'],
            // ── Asia Pacific ───────────────────────────────────────────────
            ['code' => 'IN', 'name' => 'India',       'dial' => '+91',  'flag' => '🇮🇳'],
            ['code' => 'CN', 'name' => 'China',        'dial' => '+86',  'flag' => '🇨🇳'],
            ['code' => 'JP', 'name' => 'Japan',        'dial' => '+81',  'flag' => '🇯🇵'],
            ['code' => 'SG', 'name' => 'Singapore',    'dial' => '+65',  'flag' => '🇸🇬'],
            ['code' => 'AU', 'name' => 'Australia',    'dial' => '+61',  'flag' => '🇦🇺'],
            ['code' => 'PK', 'name' => 'Pakistan',     'dial' => '+92',  'flag' => '🇵🇰'],
            ['code' => 'BD', 'name' => 'Bangladesh',   'dial' => '+880', 'flag' => '🇧🇩'],
            ['code' => 'PH', 'name' => 'Philippines',  'dial' => '+63',  'flag' => '🇵🇭'],
            ['code' => 'ID', 'name' => 'Indonesia',    'dial' => '+62',  'flag' => '🇮🇩'],
        ];
    }

    /**
     * Return dial codes sorted longest-first for reliable prefix matching.
     *
     * @return array<int, string>
     */
    public static function dialCodes(): array
    {
        $codes = array_unique(array_column(self::all(), 'dial'));
        usort($codes, fn ($a, $b) => strlen($b) - strlen($a));

        return $codes;
    }

    /**
     * Split a full E.164 number into [dial_code, local_number].
     * Returns ['+254', ''] when no match is found.
     *
     * @return array{0: string, 1: string}
     */
    public static function parse(string $phone): array
    {
        if (! str_starts_with($phone, '+')) {
            return ['+254', $phone];
        }

        foreach (self::dialCodes() as $dial) {
            if (str_starts_with($phone, $dial)) {
                return [$dial, substr($phone, strlen($dial))];
            }
        }

        return ['+254', $phone];
    }
}

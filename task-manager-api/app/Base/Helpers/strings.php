<?php

function removeMask($value): ?string
{
    if (!$value) {
        return null;
    }
    return trim(str_replace(['.', '-', '/', '(', ')', ' '], '', $value));
}

if (!function_exists('onlyNumbers')) {
    function onlyNumbers($value): string
    {
        return preg_replace('/\D/', '', $value);
    }
}

function translateDate($date): string
{
    $date = str_replace(['year'], 'ano', $date);
    $date = str_replace(['years'], 'anos', $date);
    $date = str_replace(['mons'], 'meses', $date);
    $date = str_replace(['month'], 'mês', $date);
    $date = str_replace(['mon'], 'mês', $date);
    $date = str_replace(['day'], 'dia', $date);
    return str_replace(['days'], 'dias', $date);
}

function makeMask($val, $mask): string
{
    $maskared = '';
    $k = 0;
    for ($i = 0; $i <= strlen($mask) - 1; $i++) {
        if ($mask[$i] == '#') {
            if (isset($val[$k])) $maskared .= $val[$k++];
        } else {
            if (isset($mask[$i])) $maskared .= $mask[$i];
        }
    }
    return $maskared;
}

if (!function_exists('maskEmail')) {
    function maskEmail($value): string
    {
        $parts = explode("@", $value);

        $user = $parts[0];
        $domain = $parts[1];

        $user_length = strlen($user);
        $mask_percentage = 10;

        $masked_length = (int)($user_length * ($mask_percentage / 100));

        $start = substr($user, 0, 3);
        $masked_middle = str_repeat(
            "*",
            strlen($user) - $user_length / 2 - $masked_length
        );
        return $start . $masked_middle . "@" . $domain;
    }
}

if (!function_exists('nullVal')) {
    function nullVal($value): mixed
    {
        if (boolval($value)) {
            return $value;
        }

        return null;
    }
}

if (!function_exists('cpfMask')) {
    function cpfMask($value): ?string {
        if (!$value) {
            return null;
        }
        return makeMask($value, '###.###.###-##');
    }
}

if (!function_exists('familyCodeMask')) {
    function familyCodeMask($value): ?string {
        if (!$value) {
            return null;
        }
        return makeMask($value, '#########-##');
    }
}

if (!function_exists('cnpjMask')) {
    function cnpjMask($value): mixed
    {
        return makeMask($value, '##.###.###/####-##');
    }
}

if (!function_exists('checkIfHasBlankSpaces')) {
    function checkIfHasBlankSpaces($string): string
    {
        return preg_match('/\s/', $string);
    }
}

function replaceToSqlArray($value): array|string|null
{
    if (!is_array($value)) {
        return null;
    }

    if (empty($value)) {
        return null;
    }

    return str_replace(']', '}', str_replace('[', '{', json_encode($value)));
}

if (!function_exists('random_code')) {
    /**
     * Gera uma string aleatória contendo apenas letras.
     *
     * @param int $length
     * @return string
     */
    function random_code(int $length = 10): string {
        $pool = '123456789';
        return substr(str_shuffle(str_repeat($pool, $length)), 0, $length);
    }
}

if (!function_exists('mask_email')) {
    function mask_email($email): string {
        if (!$email) {
            return '';
        }

        list($local, $domain) = explode('@', $email);

        if (strlen($local) <= 5) {
            return substr($local, 0, 1) . '****@' . $domain;
        }

        $start = substr($local, 0, 3);
        $end = substr($local, -2);

        return $start . '****' . $end . '@' . $domain;
    }
}

function removeAccents($string): ?string {
    if (is_null($string)) return null;

    $accentMapper = [
        'á' => 'a',
        'à' => 'a',
        'ã' => 'a',
        'â' => 'a',
        'ä' => 'a',
        'é' => 'e',
        'è' => 'e',
        'ê' => 'e',
        'ë' => 'e',
        'í' => 'i',
        'ì' => 'i',
        'î' => 'i',
        'ï' => 'i',
        'ó' => 'o',
        'ò' => 'o',
        'õ' => 'o',
        'ô' => 'o',
        'ö' => 'o',
        'ú' => 'u',
        'ù' => 'u',
        'û' => 'u',
        'ü' => 'u',
        'ç' => 'c',
        'Á' => 'A',
        'À' => 'A',
        'Ã' => 'A',
        'Â' => 'A',
        'Ä' => 'A',
        'É' => 'E',
        'È' => 'E',
        'Ê' => 'E',
        'Ë' => 'E',
        'Í' => 'I',
        'Ì' => 'I',
        'Î' => 'I',
        'Ï' => 'I',
        'Ó' => 'O',
        'Ò' => 'O',
        'Õ' => 'O',
        'Ô' => 'O',
        'Ö' => 'O',
        'Ú' => 'U',
        'Ù' => 'U',
        'Û' => 'U',
        'Ü' => 'U',
        'Ç' => 'C'
    ];
    return strtr($string, $accentMapper);
}

/**
 * @param $array
 * @return false|string|null
 */
function formatToJsonValue($array): false|string|null {
    if (!$array) return null;
    if (!is_array($array)) return $array;

    return json_encode($array);
}

<?php

function isValidCpf($value): bool
{
    if (! $value) {
        return false;
    }

    $valid = true;
    $cpf = str_pad(preg_replace('/[^0-9_]/', '', $value), 11, '0', STR_PAD_LEFT);
    for ($x = 0; $x < 10; $x++) {
        if ($cpf == str_repeat($x, 11)) {
            $valid = false;
        }
    }
    if ($valid) {
        if (strlen($cpf) != 11) {
            $valid = false;
        } else {
            for ($t = 9; $t < 11; $t++) {
                $d = 0;
                for ($c = 0; $c < $t; $c++) {
                    $d += $cpf[$c] * (($t + 1) - $c);
                }
                $d = ((10 * $d) % 11) % 10;
                if ($cpf[$c] != $d) {
                    $valid = false;
                    break;
                }
            }
        }
    }

    return $valid;
}

function isValidCodeFamily($value): bool
{
    if (! $value) {
        return false;
    }

    $code = trim((string) $value);

    return (bool) preg_match('/^(\d{11}|\d{9}-\d{2})$/', $code);
}

function handlerRequestToken($token): ?string
{
    $split_token = explode(
        separator: '|',
        string: $token ?? request()->bearerToken()
    );
    if (count($split_token) === 2) {
        return $split_token[1];
    }

    if (is_array($split_token) and count($split_token) === 1) {
        return $split_token[0];
    }

    return null;
}

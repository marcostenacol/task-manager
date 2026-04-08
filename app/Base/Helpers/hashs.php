<?php

if (! function_exists('setHash')) {

    function setHash(string $value, $hash_type = 'sha256'): string {
        return hash($hash_type, $value);
    }
}

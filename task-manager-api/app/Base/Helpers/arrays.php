<?php

function transformInLiteralArray($value): string
{
    if (! $value) {
        return '{}';
    }

    if (is_array($value)) {
        return '{'.implode(',', $value).'}';
    }

    return '{'.$value.'}';
}

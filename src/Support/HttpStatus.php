<?php

namespace Alexkramse\FilamentOpenapiDocs\Support;

class HttpStatus
{
    public static function color(string|int $status): string
    {
        return match (true) {
            str_starts_with((string) $status, '2')                                         => 'success',
            str_starts_with((string) $status, '3'), str_starts_with((string) $status, '4') => 'warning',
            str_starts_with((string) $status, '5')                                         => 'danger',
            default                                                                        => 'gray',
        };
    }
}

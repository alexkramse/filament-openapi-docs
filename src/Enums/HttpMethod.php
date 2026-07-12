<?php

namespace Alexkramse\FilamentOpenapiDocs\Enums;

enum HttpMethod: string
{
    case Get = 'GET';
    case Post = 'POST';
    case Put = 'PUT';
    case Patch = 'PATCH';
    case Delete = 'DELETE';
    case Options = 'OPTIONS';
    case Head = 'HEAD';
    case Trace = 'TRACE';

    public static function color(string $method): string
    {
        return self::tryFrom(strtoupper($method))?->badgeColor() ?? 'gray';
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Get => 'success',
            self::Post => 'info',
            self::Put, self::Patch => 'warning',
            self::Delete => 'danger',
            self::Options, self::Head, self::Trace => 'gray',
        };
    }
}

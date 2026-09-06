<?php

declare(strict_types=1);

namespace App\Core;

class ViewConfig
{
    private static string $basePath = '';

    public static function setBasePath(string $path): void
    {
        self::$basePath = $path;
    }

    public static function getBasePath(): string
    {
        return self::$basePath;
    }
}

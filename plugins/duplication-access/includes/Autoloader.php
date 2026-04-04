<?php

declare(strict_types=1);

namespace Duplication\Access;

final class Autoloader
{
    public static function register(string $baseDir): void
    {
        spl_autoload_register(static function (string $className) use ($baseDir): void {
            $prefix = 'Duplication\\Access\\';

            if (strpos($className, $prefix) !== 0) {
                return;
            }

            $relativeClass = substr($className, strlen($prefix));
            $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
            $filePath = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $relativePath;

            if (is_readable($filePath)) {
                require_once $filePath;
            }
        });
    }
}

<?php

declare(strict_types=1);

namespace Duplication\Core;

final class ModuleStatus
{
    public const REGISTERED = 'registered';
    public const BOOTING = 'booting';
    public const ACTIVE = 'active';
    public const INACTIVE = 'inactive';
    public const FAILED = 'failed';

    /**
     * @return string[]
     */
    public static function all(): array
    {
        return [
            self::REGISTERED,
            self::BOOTING,
            self::ACTIVE,
            self::INACTIVE,
            self::FAILED,
        ];
    }
}

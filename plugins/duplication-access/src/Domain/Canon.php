<?php

declare(strict_types=1);

namespace Duplication\Access\Domain;

final class Canon
{
    public const META_BUSINESS_STATUS = 'dp_business_status';
    public const META_GENDER = 'dp_gender';

    public const BUSINESS_STATUS_CANDIDATE = 'candidate';
    public const BUSINESS_STATUS_PARTNER = 'partner';
    public const BUSINESS_STATUS_VIP_PARTNER = 'vip_partner';

    public const GENDER_MALE = 'male';
    public const GENDER_FEMALE = 'female';
    public const GENDER_UNKNOWN = 'unknown';

    public const ACCESS_ADMIN_OVERRIDE = 'admin_override';
    public const ACCESS_MINIMUM_ACCESS_LEVEL = 'minimum_access_level';
    public const ACCESS_FEMALE_CLUB = 'female_club';

    public const LEVEL_CANDIDATE = 1;
    public const LEVEL_PARTNER = 2;
    public const LEVEL_VIP_PARTNER = 3;

    /** @return string[] */
    public static function businessStatuses(): array
    {
        return [
            self::BUSINESS_STATUS_CANDIDATE,
            self::BUSINESS_STATUS_PARTNER,
            self::BUSINESS_STATUS_VIP_PARTNER,
        ];
    }

    /** @return string[] */
    public static function genders(): array
    {
        return [
            self::GENDER_MALE,
            self::GENDER_FEMALE,
            self::GENDER_UNKNOWN,
        ];
    }

    /** @return array<string,int> */
    public static function statusLevels(): array
    {
        return [
            self::BUSINESS_STATUS_CANDIDATE => self::LEVEL_CANDIDATE,
            self::BUSINESS_STATUS_PARTNER => self::LEVEL_PARTNER,
            self::BUSINESS_STATUS_VIP_PARTNER => self::LEVEL_VIP_PARTNER,
        ];
    }

    /** @return string[] */
    public static function accessConcepts(): array
    {
        return [
            self::ACCESS_ADMIN_OVERRIDE,
            self::ACCESS_MINIMUM_ACCESS_LEVEL,
            self::ACCESS_FEMALE_CLUB,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Duplication\Webinars\Domain;

final class Canon
{
    public const POST_TYPE = 'webinar';

    public const FORMAT_STANDARD = 'standard';
    public const FORMAT_FEMALE_CLUB = 'female_club';

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_LIVE = 'live';
    public const STATUS_FINISHED = 'finished';
    public const STATUS_CANCELED = 'canceled';

    public const SOURCE_YOUTUBE_EMBED = 'youtube_embed';
    public const SOURCE_IFRAME_EMBED = 'iframe_embed';
    public const SOURCE_EXTERNAL_LINK = 'external_link';

    public const PAGE_MODE_PRE_EVENT = 'pre_event';
    public const PAGE_MODE_LIVE = 'live';
    public const PAGE_MODE_FINISHED = 'finished';
    public const PAGE_MODE_CANCELED = 'canceled';

    public const CTA_NOT_AVAILABLE = 'not_available';
    public const CTA_LOGIN_REQUIRED = 'login_required';
    public const CTA_UPGRADE_ACCESS = 'upgrade_access';
    public const CTA_FEMALE_CLUB_ONLY = 'female_club_only';
    public const CTA_ENTER_ROOM = 'enter_room';
    public const CTA_VIEW_RECORDING = 'view_recording';

    public const META_FORMAT = 'dp_webinar_format';
    public const META_STATUS = 'dp_webinar_status';
    public const META_MINIMUM_ACCESS_LEVEL = 'dp_webinar_minimum_access_level';
    public const META_SOURCE_TYPE = 'dp_webinar_source_type';
    public const META_SOURCE_VALUE = 'dp_webinar_source_value';
    public const META_PUBLIC_PAGE_ENABLED = 'dp_webinar_public_page_enabled';
    public const META_PAGE_MODE = 'dp_webinar_page_mode';

    /** @return string[] */
    public static function formats(): array
    {
        return [
            self::FORMAT_STANDARD,
            self::FORMAT_FEMALE_CLUB,
        ];
    }

    /** @return string[] */
    public static function statuses(): array
    {
        return [
            self::STATUS_SCHEDULED,
            self::STATUS_LIVE,
            self::STATUS_FINISHED,
            self::STATUS_CANCELED,
        ];
    }

    /** @return string[] */
    public static function sourceTypes(): array
    {
        return [
            self::SOURCE_YOUTUBE_EMBED,
            self::SOURCE_IFRAME_EMBED,
            self::SOURCE_EXTERNAL_LINK,
        ];
    }

    /** @return string[] */
    public static function pageModes(): array
    {
        return [
            self::PAGE_MODE_PRE_EVENT,
            self::PAGE_MODE_LIVE,
            self::PAGE_MODE_FINISHED,
            self::PAGE_MODE_CANCELED,
        ];
    }
}

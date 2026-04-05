<?php
/**
 * Plugin Name: Duplication Webinars
 * Description: Product webinar module for Duplication Platform 2.0 (canonical webinar entity, CPT, basic access service).
 * Version: 0.1.0
 * Author: Duplication Platform
 * Requires at least: 6.2
 * Requires PHP: 8.1
 * Text Domain: duplication-webinars
 */

if (! defined('ABSPATH')) {
    exit;
}

const DUPLICATION_WEBINARS_FILE = __FILE__;
const DUPLICATION_WEBINARS_PATH = __DIR__;
const DUPLICATION_WEBINARS_VERSION = '0.1.0';

require_once DUPLICATION_WEBINARS_PATH . '/includes/Autoloader.php';

\Duplication\Webinars\Autoloader::register(DUPLICATION_WEBINARS_PATH . '/src');

if (! function_exists('duplication_webinars_bootstrap')) {
    function duplication_webinars_bootstrap(): ?\Duplication\Webinars\Bootstrap
    {
        static $bootstrap = null;

        if ($bootstrap !== null) {
            return $bootstrap;
        }

        if (
            ! function_exists('duplication_core_platform_loaded')
            || ! function_exists('duplication_core_register_module')
            || ! function_exists('duplication_core_is_module_active')
            || ! function_exists('duplication_access_check')
        ) {
            return null;
        }

        $coreReady = duplication_core_platform_loaded();

        if (! (bool) ($coreReady['ok'] ?? false)) {
            return null;
        }

        $accessReady = duplication_core_is_module_active('duplication-access');

        if (! (bool) ($accessReady['ok'] ?? false)) {
            return null;
        }

        $bootstrap = new \Duplication\Webinars\Bootstrap();
        $bootstrap->boot();

        return $bootstrap;
    }
}

/**
 * Public API: get webinar by id.
 *
 * @return array<string,mixed>|null
 */
function duplication_webinars_get_webinar(int $webinarId): ?array
{
    return duplication_webinars_bootstrap()?->service()->getWebinarById($webinarId);
}

/**
 * Public API: get webinars list.
 *
 * @return array<int,array<string,mixed>>
 */
function duplication_webinars_list_webinars(array $args = []): array
{
    return duplication_webinars_bootstrap()?->service()->listWebinars($args) ?? [];
}

/**
 * Public API: get webinar status.
 */
function duplication_webinars_get_status(int $webinarId): string
{
    return duplication_webinars_bootstrap()?->service()->getStatus($webinarId)
        ?? \Duplication\Webinars\Domain\Canon::STATUS_SCHEDULED;
}

/**
 * Public API: get webinar page mode.
 */
function duplication_webinars_get_page_mode(int $webinarId): string
{
    return duplication_webinars_bootstrap()?->service()->getPageMode($webinarId)
        ?? \Duplication\Webinars\Domain\Canon::PAGE_MODE_PRE_EVENT;
}

/**
 * Public API: check webinar access for a user.
 *
 * @return array{allowed:bool,reason:string,cta:string,page_mode:string}
 */
function duplication_webinars_check_access(int $webinarId, int $userId): array
{
    return duplication_webinars_bootstrap()?->service()->checkAccessForUser($webinarId, $userId)
        ?? [
            'allowed' => false,
            'reason' => 'webinars_module_not_ready',
            'cta' => \Duplication\Webinars\Domain\Canon::CTA_NOT_AVAILABLE,
            'page_mode' => \Duplication\Webinars\Domain\Canon::PAGE_MODE_PRE_EVENT,
        ];
}

/**
 * Public API: can user enter webinar room.
 */
function duplication_webinars_can_enter_room(int $webinarId, int $userId): bool
{
    return duplication_webinars_bootstrap()?->service()->canEnterRoom($webinarId, $userId) ?? false;
}

add_action('plugins_loaded', static function (): void {
    duplication_webinars_bootstrap();
}, 30);

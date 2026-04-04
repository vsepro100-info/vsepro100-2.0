<?php
/**
 * Plugin Name: Duplication Access
 * Description: Domain access layer for Duplication Platform 2.0 (business status, gender, access checks).
 * Version: 0.1.0
 * Author: Duplication Platform
 * Requires at least: 6.2
 * Requires PHP: 8.1
 * Text Domain: duplication-access
 */

if (! defined('ABSPATH')) {
    exit;
}

const DUPLICATION_ACCESS_FILE = __FILE__;
const DUPLICATION_ACCESS_PATH = __DIR__;
const DUPLICATION_ACCESS_VERSION = '0.1.0';

require_once DUPLICATION_ACCESS_PATH . '/includes/Autoloader.php';

\Duplication\Access\Autoloader::register(DUPLICATION_ACCESS_PATH . '/src');

if (! function_exists('duplication_access_bootstrap')) {
    function duplication_access_bootstrap(): ?\Duplication\Access\Bootstrap
    {
        static $bootstrap = null;

        if ($bootstrap !== null) {
            return $bootstrap;
        }

        if (! function_exists('duplication_core_platform_loaded') || ! function_exists('duplication_core_register_module')) {
            return null;
        }

        $coreReady = duplication_core_platform_loaded();

        if (! (bool) ($coreReady['ok'] ?? false)) {
            return null;
        }

        $bootstrap = new \Duplication\Access\Bootstrap();
        $bootstrap->boot();

        return $bootstrap;
    }
}

/**
 * Public API: read canonical business status.
 */
function duplication_access_get_business_status(int $userId): string
{
    return duplication_access_bootstrap()?->service()->getBusinessStatus($userId) ?? \Duplication\Access\Domain\Canon::BUSINESS_STATUS_CANDIDATE;
}

/**
 * Public API: read canonical gender.
 */
function duplication_access_get_gender(int $userId): string
{
    return duplication_access_bootstrap()?->service()->getGender($userId) ?? \Duplication\Access\Domain\Canon::GENDER_UNKNOWN;
}

/**
 * Public API: read numeric access level by canonical status.
 */
function duplication_access_get_access_level(int $userId): int
{
    return duplication_access_bootstrap()?->service()->getAccessLevel($userId) ?? \Duplication\Access\Domain\Canon::LEVEL_CANDIDATE;
}

/**
 * Public API: check minimum access level.
 *
 * @return array{allowed:bool,reason:string}
 */
function duplication_access_check_minimum_access_level(int $userId, int $requiredLevel): array
{
    return duplication_access_bootstrap()?->service()->checkMinimumAccessLevel($userId, $requiredLevel)
        ?? ['allowed' => false, 'reason' => 'access_module_not_ready'];
}

/**
 * Public API: check female club mode.
 *
 * @return array{allowed:bool,reason:string}
 */
function duplication_access_check_female_club(int $userId): array
{
    return duplication_access_bootstrap()?->service()->checkFemaleClub($userId)
        ?? ['allowed' => false, 'reason' => 'access_module_not_ready'];
}

/**
 * Public API: check admin override by configured WP roles.
 *
 * @return array{allowed:bool,reason:string}
 */
function duplication_access_check_admin_override(int $userId): array
{
    return duplication_access_bootstrap()?->service()->checkAdminOverride($userId)
        ?? ['allowed' => false, 'reason' => 'access_module_not_ready'];
}

/**
 * Public API: unified access check.
 *
 * @param array{minimum_access_level?:int,female_club?:bool} $rule
 * @return array{allowed:bool,reason:string}
 */
function duplication_access_check(int $userId, array $rule = []): array
{
    return duplication_access_bootstrap()?->service()->checkAccess($userId, $rule)
        ?? ['allowed' => false, 'reason' => 'access_module_not_ready'];
}

add_action('plugins_loaded', static function (): void {
    duplication_access_bootstrap();
}, 20);

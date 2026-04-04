<?php
/**
 * Plugin Name: Duplication Core
 * Description: Infrastructure core for Duplication Platform 2.0 (bootstrap, module registry, diagnostics, admin shell).
 * Version: 0.1.0
 * Author: Duplication Platform
 * Requires at least: 6.2
 * Requires PHP: 8.1
 * Text Domain: duplication-core
 */

if (! defined('ABSPATH')) {
    exit;
}

const DUPLICATION_CORE_FILE = __FILE__;
const DUPLICATION_CORE_PATH = __DIR__;
const DUPLICATION_CORE_VERSION = '0.1.0';

require_once DUPLICATION_CORE_PATH . '/includes/Autoloader.php';

\Duplication\Core\Autoloader::register(DUPLICATION_CORE_PATH . '/src');

if (! function_exists('duplication_core_bootstrap')) {
    /**
     * Returns the platform bootstrap singleton.
     */
    function duplication_core_bootstrap(): \Duplication\Core\Bootstrap
    {
        static $bootstrap = null;

        if ($bootstrap === null) {
            $bootstrap = new \Duplication\Core\Bootstrap();
            $bootstrap->boot();
        }

        return $bootstrap;
    }
}

/**
 * Public API: checks whether core platform bootstrap is loaded.
 *
 * @return array{ok:bool,status:string,reason:string}
 */
function duplication_core_platform_loaded(): array
{
    return duplication_core_bootstrap()->platformLoadedCheck();
}

/**
 * Public API: register a module in the platform registry.
 *
 * @param array<string,mixed> $definition
 * @return array{ok:bool,status:string,reason:string}
 */
function duplication_core_register_module(string $moduleSlug, array $definition): array
{
    return duplication_core_bootstrap()->registerModule($moduleSlug, $definition);
}

/**
 * Public API: checks whether module is registered.
 *
 * @return array{ok:bool,status:string,reason:string}
 */
function duplication_core_is_module_registered(string $moduleSlug): array
{
    return duplication_core_bootstrap()->isModuleRegistered($moduleSlug);
}

/**
 * Public API: checks whether module is active.
 *
 * @return array{ok:bool,status:string,reason:string}
 */
function duplication_core_is_module_active(string $moduleSlug): array
{
    return duplication_core_bootstrap()->isModuleActive($moduleSlug);
}

/**
 * Public API: get current module status.
 *
 * @return array{ok:bool,status:string,reason:string}
 */
function duplication_core_get_module_status(string $moduleSlug): array
{
    return duplication_core_bootstrap()->getModuleStatus($moduleSlug);
}

/**
 * Public API: gets unavailable reason for a module.
 *
 * @return array{ok:bool,status:string,reason:string}
 */
function duplication_core_get_module_reason(string $moduleSlug): array
{
    return duplication_core_bootstrap()->getModuleReason($moduleSlug);
}

/**
 * Public API: validates module dependencies.
 *
 * @return array{ok:bool,status:string,reason:string}
 */
function duplication_core_check_module_dependencies(string $moduleSlug): array
{
    return duplication_core_bootstrap()->checkModuleDependencies($moduleSlug);
}

/**
 * Public API: base diagnostic payload for all registered modules.
 *
 * @return array<string,mixed>
 */
function duplication_core_system_diagnostics(): array
{
    return duplication_core_bootstrap()->getDiagnostics();
}

add_action('plugins_loaded', static function (): void {
    duplication_core_bootstrap();
}, 1);

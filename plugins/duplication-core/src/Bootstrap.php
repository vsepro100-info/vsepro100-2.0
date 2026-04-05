<?php

declare(strict_types=1);

namespace Duplication\Core;

use Duplication\Core\Admin\AdminShell;
use Throwable;

final class Bootstrap
{
    private ModuleRegistry $registry;

    private bool $platformLoaded = false;

    public function __construct()
    {
        $this->registry = new ModuleRegistry();
    }

    public function boot(): void
    {
        if ($this->platformLoaded) {
            return;
        }

        $this->registerModule('duplication-core', [
            'title' => 'Duplication Core',
            'dependencies' => [],
            'boot' => null,
        ]);

        $this->activateModule('duplication-core');

        if (is_admin()) {
            (new AdminShell($this))->registerHooks();
        }

        $this->platformLoaded = true;
        do_action('duplication/platform_loaded', ['module' => 'duplication-core']);
    }

    /**
     * @return array{ok:bool,status:string,reason:string}
     */
    public function platformLoadedCheck(): array
    {
        if (! $this->platformLoaded) {
            return $this->result(false, ModuleStatus::INACTIVE, 'platform_not_loaded');
        }

        return $this->result(true, ModuleStatus::ACTIVE, 'platform_loaded');
    }

    /**
     * @param array<string,mixed> $definition
     * @return array{ok:bool,status:string,reason:string}
     */
    public function registerModule(string $moduleSlug, array $definition): array
    {
        $result = $this->registry->register($moduleSlug, $definition);

        if ($result['ok']) {
            do_action('duplication/module_registered', ['module' => $moduleSlug]);
            $this->activateModule($moduleSlug);

            return $this->getModuleStatus($moduleSlug);
        }

        return $result;
    }

    /**
     * @return array{ok:bool,status:string,reason:string}
     */
    public function isModuleRegistered(string $moduleSlug): array
    {
        return $this->registry->isRegistered($moduleSlug);
    }

    /**
     * @return array{ok:bool,status:string,reason:string}
     */
    public function isModuleActive(string $moduleSlug): array
    {
        return $this->registry->isActive($moduleSlug);
    }

    /**
     * @return array{ok:bool,status:string,reason:string}
     */
    public function getModuleStatus(string $moduleSlug): array
    {
        return $this->registry->getStatus($moduleSlug);
    }

    /**
     * @return array{ok:bool,status:string,reason:string}
     */
    public function getModuleReason(string $moduleSlug): array
    {
        return $this->registry->getReason($moduleSlug);
    }

    /**
     * @return array{ok:bool,status:string,reason:string}
     */
    public function checkModuleDependencies(string $moduleSlug): array
    {
        $registeredCheck = $this->isModuleRegistered($moduleSlug);

        if (! $registeredCheck['ok']) {
            return $registeredCheck;
        }

        $module = $this->registry->all()[$moduleSlug];

        foreach ((array) $module['dependencies'] as $dependencySlug) {
            $dependencyCheck = $this->isModuleActive((string) $dependencySlug);

            if (! $dependencyCheck['ok']) {
                return $this->result(false, ModuleStatus::FAILED, 'missing_dependency:' . $dependencySlug);
            }
        }

        return $this->result(true, (string) $module['status'], 'dependencies_ok');
    }

    /**
     * @return array<string,mixed>
     */
    public function getDiagnostics(): array
    {
        $modules = [];

        foreach ($this->registry->all() as $module) {
            $modules[] = [
                'slug' => $module['slug'],
                'title' => $module['title'],
                'status' => $module['status'],
                'reason' => $module['reason'],
                'dependencies' => $module['dependencies'],
                'dependency_check' => $this->checkModuleDependencies((string) $module['slug']),
            ];
        }

        return [
            'ok' => true,
            'status' => $this->platformLoaded ? ModuleStatus::ACTIVE : ModuleStatus::INACTIVE,
            'reason' => $this->platformLoaded ? 'platform_loaded' : 'platform_not_loaded',
            'modules' => $modules,
        ];
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public function getModules(): array
    {
        return $this->registry->all();
    }

    private function activateModule(string $moduleSlug): void
    {
        $modules = $this->registry->all();

        if (! isset($modules[$moduleSlug])) {
            return;
        }

        $module = $modules[$moduleSlug];
        $module['status'] = ModuleStatus::BOOTING;
        $module['reason'] = 'module_booting';
        $this->registry->replace($moduleSlug, $module);

        try {
            $dependencyCheck = $this->checkModuleDependencies($moduleSlug);

            if (! $dependencyCheck['ok']) {
                $module['status'] = ModuleStatus::FAILED;
                $module['reason'] = $dependencyCheck['reason'];
                $this->registry->replace($moduleSlug, $module);
                do_action('duplication/module_failed', ['module' => $moduleSlug, 'reason' => $module['reason']]);

                return;
            }

            if (is_callable($module['boot'])) {
                call_user_func($module['boot'], $this);
            }

            $module['status'] = ModuleStatus::ACTIVE;
            $module['reason'] = 'module_active';
            $this->registry->replace($moduleSlug, $module);
            do_action('duplication/module_booted', ['module' => $moduleSlug]);
        } catch (Throwable $throwable) {
            $module['status'] = ModuleStatus::FAILED;
            $module['reason'] = 'boot_failed:' . $throwable->getMessage();
            $this->registry->replace($moduleSlug, $module);
            do_action('duplication/module_failed', ['module' => $moduleSlug, 'reason' => $module['reason']]);
        }
    }

    /**
     * @return array{ok:bool,status:string,reason:string}
     */
    private function result(bool $ok, string $status, string $reason): array
    {
        return [
            'ok' => $ok,
            'status' => $status,
            'reason' => $reason,
        ];
    }
}

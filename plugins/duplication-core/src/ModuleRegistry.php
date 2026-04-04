<?php

declare(strict_types=1);

namespace Duplication\Core;

final class ModuleRegistry
{
    /**
     * @var array<string,array<string,mixed>>
     */
    private array $modules = [];

    /**
     * @param array<string,mixed> $definition
     * @return array{ok:bool,status:string,reason:string}
     */
    public function register(string $moduleSlug, array $definition): array
    {
        if (isset($this->modules[$moduleSlug])) {
            return $this->result(false, ModuleStatus::REGISTERED, 'module_already_registered');
        }

        $this->modules[$moduleSlug] = [
            'slug' => $moduleSlug,
            'title' => (string) ($definition['title'] ?? $moduleSlug),
            'dependencies' => array_values(array_filter((array) ($definition['dependencies'] ?? []), 'is_string')),
            'boot' => $definition['boot'] ?? null,
            'status' => ModuleStatus::REGISTERED,
            'reason' => 'module_registered',
        ];

        return $this->result(true, ModuleStatus::REGISTERED, 'module_registered');
    }

    /**
     * @return array{ok:bool,status:string,reason:string}
     */
    public function isRegistered(string $moduleSlug): array
    {
        if (! isset($this->modules[$moduleSlug])) {
            return $this->result(false, ModuleStatus::INACTIVE, 'module_not_registered');
        }

        return $this->result(true, (string) $this->modules[$moduleSlug]['status'], 'module_registered');
    }

    /**
     * @return array{ok:bool,status:string,reason:string}
     */
    public function isActive(string $moduleSlug): array
    {
        if (! isset($this->modules[$moduleSlug])) {
            return $this->result(false, ModuleStatus::INACTIVE, 'module_not_registered');
        }

        $status = (string) $this->modules[$moduleSlug]['status'];
        $reason = (string) $this->modules[$moduleSlug]['reason'];

        return $this->result($status === ModuleStatus::ACTIVE, $status, $reason);
    }

    /**
     * @return array{ok:bool,status:string,reason:string}
     */
    public function getStatus(string $moduleSlug): array
    {
        if (! isset($this->modules[$moduleSlug])) {
            return $this->result(false, ModuleStatus::INACTIVE, 'module_not_registered');
        }

        return $this->result(true, (string) $this->modules[$moduleSlug]['status'], (string) $this->modules[$moduleSlug]['reason']);
    }

    /**
     * @return array{ok:bool,status:string,reason:string}
     */
    public function getReason(string $moduleSlug): array
    {
        if (! isset($this->modules[$moduleSlug])) {
            return $this->result(false, ModuleStatus::INACTIVE, 'module_not_registered');
        }

        return $this->result(true, (string) $this->modules[$moduleSlug]['status'], (string) $this->modules[$moduleSlug]['reason']);
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public function all(): array
    {
        return $this->modules;
    }

    /**
     * @param array<string,mixed> $module
     */
    public function replace(string $moduleSlug, array $module): void
    {
        $this->modules[$moduleSlug] = $module;
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

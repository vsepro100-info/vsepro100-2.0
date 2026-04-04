<?php

declare(strict_types=1);

namespace Duplication\Access;

use Duplication\Access\Admin\AdminPage;
use Duplication\Access\Domain\AccessService;
use Duplication\Access\Domain\Canon;
use Duplication\Access\Domain\Settings;

final class Bootstrap
{
    private AccessService $service;

    public function __construct()
    {
        $this->service = new AccessService(new Settings());
    }

    public function boot(): void
    {
        duplication_core_register_module('duplication-access', [
            'title' => 'Duplication Access',
            'dependencies' => ['duplication-core'],
            'boot' => [$this, 'onModuleBoot'],
        ]);
    }

    public function service(): AccessService
    {
        return $this->service;
    }

    public function onModuleBoot(): void
    {
        add_action('user_register', [$this, 'onWordPressUserRegister'], 20);
        add_action('duplication/user_registered', [$this, 'onPlatformUserRegistered'], 10, 1);

        if (is_admin()) {
            (new AdminPage($this->service))->registerHooks();
        }
    }

    public function onWordPressUserRegister(int $userId): void
    {
        $this->applyCanonicalDefaults($userId);
        do_action('duplication/user_registered', ['user_id' => $userId, 'source' => 'wordpress']);
    }

    /** @param array<string,mixed> $payload */
    public function onPlatformUserRegistered(array $payload): void
    {
        $userId = isset($payload['user_id']) ? (int) $payload['user_id'] : 0;

        if ($userId > 0) {
            $this->applyCanonicalDefaults($userId);
        }
    }

    private function applyCanonicalDefaults(int $userId): void
    {
        $statusRaw = get_user_meta($userId, Canon::META_BUSINESS_STATUS, true);

        if (! is_string($statusRaw) || ! in_array($statusRaw, Canon::businessStatuses(), true)) {
            $this->service->setBusinessStatus($userId, Canon::BUSINESS_STATUS_CANDIDATE);
        }

        $genderRaw = get_user_meta($userId, Canon::META_GENDER, true);

        if (! is_string($genderRaw) || ! in_array($genderRaw, Canon::genders(), true)) {
            $this->service->setGender($userId, Canon::GENDER_UNKNOWN);
        }
    }
}

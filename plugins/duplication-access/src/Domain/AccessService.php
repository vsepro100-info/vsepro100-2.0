<?php

declare(strict_types=1);

namespace Duplication\Access\Domain;

use WP_User;

final class AccessService
{
    private Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    public function getBusinessStatus(int $userId): string
    {
        $raw = get_user_meta($userId, Canon::META_BUSINESS_STATUS, true);
        $status = is_string($raw) ? $raw : '';

        if (! in_array($status, Canon::businessStatuses(), true)) {
            return Canon::BUSINESS_STATUS_CANDIDATE;
        }

        return $status;
    }

    public function getGender(int $userId): string
    {
        $raw = get_user_meta($userId, Canon::META_GENDER, true);
        $gender = is_string($raw) ? $raw : '';

        if (! in_array($gender, Canon::genders(), true)) {
            return Canon::GENDER_UNKNOWN;
        }

        return $gender;
    }

    public function getAccessLevel(int $userId): int
    {
        $status = $this->getBusinessStatus($userId);
        $levels = Canon::statusLevels();

        return $levels[$status] ?? Canon::LEVEL_CANDIDATE;
    }

    public function setBusinessStatus(int $userId, string $status): bool
    {
        if (! in_array($status, Canon::businessStatuses(), true)) {
            return false;
        }

        $previous = $this->getBusinessStatus($userId);
        update_user_meta($userId, Canon::META_BUSINESS_STATUS, $status);

        if ($previous !== $status) {
            do_action('duplication/business_status_changed', [
                'user_id' => $userId,
                'previous' => $previous,
                'current' => $status,
            ]);
        }

        return true;
    }

    public function setGender(int $userId, string $gender): bool
    {
        if (! in_array($gender, Canon::genders(), true)) {
            return false;
        }

        $previous = $this->getGender($userId);
        update_user_meta($userId, Canon::META_GENDER, $gender);

        if ($previous !== $gender) {
            do_action('duplication/user_gender_changed', [
                'user_id' => $userId,
                'previous' => $previous,
                'current' => $gender,
            ]);
        }

        return true;
    }

    /** @return array{allowed:bool,reason:string} */
    public function checkMinimumAccessLevel(int $userId, int $requiredLevel): array
    {
        $actualLevel = $this->getAccessLevel($userId);

        if ($actualLevel >= $requiredLevel) {
            return $this->emitRuleResult($userId, Canon::ACCESS_MINIMUM_ACCESS_LEVEL, true, 'minimum_access_level_passed');
        }

        return $this->emitRuleResult($userId, Canon::ACCESS_MINIMUM_ACCESS_LEVEL, false, 'minimum_access_level_not_met');
    }

    /** @return array{allowed:bool,reason:string} */
    public function checkFemaleClub(int $userId): array
    {
        if ($this->getGender($userId) === Canon::GENDER_FEMALE) {
            return $this->emitRuleResult($userId, Canon::ACCESS_FEMALE_CLUB, true, 'female_club_passed');
        }

        return $this->emitRuleResult($userId, Canon::ACCESS_FEMALE_CLUB, false, 'female_club_requires_female');
    }

    /** @return array{allowed:bool,reason:string} */
    public function checkAdminOverride(int $userId): array
    {
        $user = get_userdata($userId);

        if (! $user instanceof WP_User) {
            return $this->emitRuleResult($userId, Canon::ACCESS_ADMIN_OVERRIDE, false, 'user_not_found');
        }

        $allowedRoles = $this->settings->getAdminOverrideRoles();

        foreach ($user->roles as $role) {
            if (in_array((string) $role, $allowedRoles, true)) {
                return $this->emitRuleResult($userId, Canon::ACCESS_ADMIN_OVERRIDE, true, 'admin_override_role_matched');
            }
        }

        return $this->emitRuleResult($userId, Canon::ACCESS_ADMIN_OVERRIDE, false, 'admin_override_role_not_matched');
    }

    /**
     * @param array{minimum_access_level?:int,female_club?:bool} $rule
     * @return array{allowed:bool,reason:string}
     */
    public function checkAccess(int $userId, array $rule = []): array
    {
        $overrideCheck = $this->checkAdminOverride($userId);

        if ($overrideCheck['allowed']) {
            return $this->emitRuleResult($userId, 'unified_access', true, 'admin_override_allowed');
        }

        if (isset($rule['minimum_access_level'])) {
            $levelCheck = $this->checkMinimumAccessLevel($userId, (int) $rule['minimum_access_level']);

            if (! $levelCheck['allowed']) {
                return $this->emitRuleResult($userId, 'unified_access', false, $levelCheck['reason']);
            }
        }

        if (! empty($rule['female_club'])) {
            $femaleCheck = $this->checkFemaleClub($userId);

            if (! $femaleCheck['allowed']) {
                return $this->emitRuleResult($userId, 'unified_access', false, $femaleCheck['reason']);
            }
        }

        return $this->emitRuleResult($userId, 'unified_access', true, 'access_allowed');
    }

    private function emitRuleResult(int $userId, string $rule, bool $allowed, string $reason): array
    {
        $result = [
            'allowed' => $allowed,
            'reason' => $reason,
        ];

        do_action('duplication/access_rule_checked', [
            'user_id' => $userId,
            'rule' => $rule,
            'allowed' => $allowed,
            'reason' => $reason,
        ]);

        return $result;
    }
}

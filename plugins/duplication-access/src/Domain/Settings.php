<?php

declare(strict_types=1);

namespace Duplication\Access\Domain;

final class Settings
{
    public const OPTION_ADMIN_OVERRIDE_ROLES = 'duplication_access_admin_override_roles';

    /** @return string[] */
    public function getAdminOverrideRoles(): array
    {
        $roles = get_option(self::OPTION_ADMIN_OVERRIDE_ROLES, ['administrator']);

        if (! is_array($roles)) {
            return ['administrator'];
        }

        return array_values(array_filter(array_map('strval', $roles)));
    }

    /** @param string[] $roles */
    public function setAdminOverrideRoles(array $roles): void
    {
        $sanitized = array_values(array_filter(array_map('sanitize_key', $roles)));
        update_option(self::OPTION_ADMIN_OVERRIDE_ROLES, $sanitized);
    }
}

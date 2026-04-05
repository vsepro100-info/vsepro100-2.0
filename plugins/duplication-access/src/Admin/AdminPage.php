<?php

declare(strict_types=1);

namespace Duplication\Access\Admin;

use Duplication\Access\Domain\AccessService;
use Duplication\Access\Domain\Canon;
use Duplication\Access\Domain\Settings;

final class AdminPage
{
    private AccessService $service;

    private Settings $settings;

    public function __construct(AccessService $service)
    {
        $this->service = $service;
        $this->settings = new Settings();
    }

    public function registerHooks(): void
    {
        add_action('admin_menu', [$this, 'registerAdminMenu']);
        add_action('admin_init', [$this, 'handleSettingsSubmit']);
        add_action('show_user_profile', [$this, 'renderUserAccessFields']);
        add_action('edit_user_profile', [$this, 'renderUserAccessFields']);
        add_action('personal_options_update', [$this, 'handleUserAccessFieldsUpdate']);
        add_action('edit_user_profile_update', [$this, 'handleUserAccessFieldsUpdate']);
    }

    public function registerAdminMenu(): void
    {
        add_submenu_page(
            'duplication-platform',
            'Duplication Access',
            'Access',
            'manage_options',
            'duplication-access',
            [$this, 'renderPage']
        );
    }

    public function handleSettingsSubmit(): void
    {
        if (! isset($_POST['duplication_access_settings_submit'])) {
            return;
        }

        if (! current_user_can('manage_options')) {
            return;
        }

        check_admin_referer('duplication_access_settings');

        $rolesRaw = isset($_POST['admin_override_roles']) ? (array) $_POST['admin_override_roles'] : [];
        $roles = array_values(array_filter(array_map('sanitize_key', $rolesRaw)));

        $this->settings->setAdminOverrideRoles($roles);

        wp_safe_redirect(add_query_arg(['page' => 'duplication-access', 'updated' => '1'], admin_url('admin.php')));
        exit;
    }

    public function renderPage(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die('Access denied');
        }

        $editableRoles = function_exists('wp_roles') && wp_roles() ? wp_roles()->roles : [];
        $selectedRoles = $this->settings->getAdminOverrideRoles();

        echo '<div class="wrap">';
        echo '<h1>Duplication Access</h1>';

        if (isset($_GET['updated']) && $_GET['updated'] === '1') {
            echo '<div class="notice notice-success"><p>Settings updated.</p></div>';
        }

        echo '<h2>Canonical business status</h2>';
        echo '<ul>';
        foreach (Canon::businessStatuses() as $status) {
            echo '<li><code>' . esc_html($status) . '</code></li>';
        }
        echo '</ul>';

        echo '<h2>Canonical levels</h2>';
        echo '<ul>';
        foreach (Canon::statusLevels() as $status => $level) {
            echo '<li><code>' . esc_html($status) . '</code> = <strong>' . esc_html((string) $level) . '</strong></li>';
        }
        echo '</ul>';

        echo '<h2>Canonical gender values</h2>';
        echo '<ul>';
        foreach (Canon::genders() as $gender) {
            echo '<li><code>' . esc_html($gender) . '</code></li>';
        }
        echo '</ul>';

        echo '<h2>Defaults after registration</h2>';
        echo '<p><strong>Business status:</strong> <code>' . esc_html(Canon::BUSINESS_STATUS_CANDIDATE) . '</code></p>';
        echo '<p><strong>Gender:</strong> <code>' . esc_html(Canon::GENDER_UNKNOWN) . '</code></p>';

        echo '<h2>admin_override roles</h2>';
        echo '<form method="post">';
        wp_nonce_field('duplication_access_settings');

        foreach ($editableRoles as $roleKey => $roleData) {
            $label = isset($roleData['name']) ? (string) $roleData['name'] : (string) $roleKey;
            $checked = in_array((string) $roleKey, $selectedRoles, true) ? ' checked' : '';
            echo '<label style="display:block; margin-bottom:6px;">';
            echo '<input type="checkbox" name="admin_override_roles[]" value="' . esc_attr((string) $roleKey) . '"' . $checked . '> ';
            echo esc_html($label) . ' <code>(' . esc_html((string) $roleKey) . ')</code>';
            echo '</label>';
        }

        echo '<p><button type="submit" name="duplication_access_settings_submit" class="button button-primary">Save</button></p>';
        echo '</form>';

        echo '</div>';
    }

    public function renderUserAccessFields(\WP_User $user): void
    {
        if (! current_user_can('edit_user', $user->ID)) {
            return;
        }

        $businessStatus = $this->service->getBusinessStatus((int) $user->ID);
        $gender = $this->service->getGender((int) $user->ID);
        $accessLevel = $this->service->getAccessLevel((int) $user->ID);
        $override = $this->service->checkAdminOverride((int) $user->ID);
        $overrideState = $override['allowed'] ? 'enabled' : 'not_enabled';

        echo '<h2>Duplication Access</h2>';
        echo '<table class="form-table" role="presentation">';

        echo '<tr>';
        echo '<th><label>Current business status</label></th>';
        echo '<td><code>' . esc_html($businessStatus) . '</code></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th><label>Current access level</label></th>';
        echo '<td><strong>' . esc_html((string) $accessLevel) . '</strong> <span class="description">(derived from business status)</span></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th><label>Current gender</label></th>';
        echo '<td><code>' . esc_html($gender) . '</code></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th><label for="dp_business_status">Set business status</label></th>';
        echo '<td>';
        echo '<select name="dp_business_status" id="dp_business_status">';
        foreach (Canon::businessStatuses() as $statusOption) {
            $selected = selected($businessStatus, $statusOption, false);
            echo '<option value="' . esc_attr($statusOption) . '"' . $selected . '>' . esc_html($statusOption) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">Canonical meta key: <code>' . esc_html(Canon::META_BUSINESS_STATUS) . '</code></p>';
        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th><label for="dp_gender">Set gender</label></th>';
        echo '<td>';
        echo '<select name="dp_gender" id="dp_gender">';
        foreach (Canon::genders() as $genderOption) {
            $selected = selected($gender, $genderOption, false);
            echo '<option value="' . esc_attr($genderOption) . '"' . $selected . '>' . esc_html($genderOption) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">Canonical meta key: <code>' . esc_html(Canon::META_GENDER) . '</code></p>';
        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th><label>admin_override</label></th>';
        echo '<td><strong>' . esc_html($overrideState) . '</strong> <span class="description">(' . esc_html((string) $override['reason']) . ')</span></td>';
        echo '</tr>';

        echo '</table>';

        wp_nonce_field('duplication_access_user_fields', 'duplication_access_user_fields_nonce');
    }

    public function handleUserAccessFieldsUpdate(int $userId): void
    {
        if (! current_user_can('edit_user', $userId)) {
            return;
        }

        if (
            ! isset($_POST['duplication_access_user_fields_nonce'])
            || ! wp_verify_nonce((string) $_POST['duplication_access_user_fields_nonce'], 'duplication_access_user_fields')
        ) {
            return;
        }

        if (isset($_POST['dp_business_status'])) {
            $status = sanitize_key((string) $_POST['dp_business_status']);
            $this->service->setBusinessStatus($userId, $status);
        }

        if (isset($_POST['dp_gender'])) {
            $gender = sanitize_key((string) $_POST['dp_gender']);
            $this->service->setGender($userId, $gender);
        }
    }
}

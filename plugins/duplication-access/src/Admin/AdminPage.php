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
}

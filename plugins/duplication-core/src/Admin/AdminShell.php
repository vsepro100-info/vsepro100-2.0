<?php

declare(strict_types=1);

namespace Duplication\Core\Admin;

use Duplication\Core\Bootstrap;

final class AdminShell
{
    private Bootstrap $bootstrap;

    public function __construct(Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    public function registerHooks(): void
    {
        add_action('admin_menu', [$this, 'registerAdminMenu']);
    }

    public function registerAdminMenu(): void
    {
        add_menu_page(
            'Duplication Platform',
            'Duplication Platform',
            'manage_options',
            'duplication-platform',
            [$this, 'renderModulesPage'],
            'dashicons-admin-generic',
            58
        );

        add_submenu_page(
            'duplication-platform',
            'Platform Modules',
            'Modules',
            'manage_options',
            'duplication-platform',
            [$this, 'renderModulesPage']
        );

        add_submenu_page(
            'duplication-platform',
            'Platform Diagnostics',
            'Diagnostics',
            'manage_options',
            'duplication-platform-diagnostics',
            [$this, 'renderDiagnosticsPage']
        );
    }

    public function renderModulesPage(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die('Access denied');
        }

        $modules = $this->bootstrap->getModules();

        echo '<div class="wrap">';
        echo '<h1>Duplication Platform: Modules</h1>';
        echo '<table class="widefat striped"><thead><tr><th>Slug</th><th>Title</th><th>Status</th><th>Reason</th><th>Dependencies</th></tr></thead><tbody>';

        foreach ($modules as $module) {
            echo '<tr>';
            echo '<td>' . esc_html((string) $module['slug']) . '</td>';
            echo '<td>' . esc_html((string) $module['title']) . '</td>';
            echo '<td>' . esc_html((string) $module['status']) . '</td>';
            echo '<td>' . esc_html((string) $module['reason']) . '</td>';
            echo '<td>' . esc_html(implode(', ', (array) $module['dependencies'])) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
    }

    public function renderDiagnosticsPage(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die('Access denied');
        }

        $diagnostics = $this->bootstrap->getDiagnostics();

        echo '<div class="wrap">';
        echo '<h1>Duplication Platform: Diagnostics</h1>';
        echo '<p><strong>Platform status:</strong> ' . esc_html((string) $diagnostics['status']) . '</p>';
        echo '<p><strong>Platform reason:</strong> ' . esc_html((string) $diagnostics['reason']) . '</p>';

        echo '<table class="widefat striped"><thead><tr><th>Module</th><th>Status</th><th>Reason</th><th>Dependency Check</th></tr></thead><tbody>';

        foreach ((array) $diagnostics['modules'] as $moduleDiagnostics) {
            $dependency = (array) $moduleDiagnostics['dependency_check'];
            $dependencyText = sprintf(
                'ok=%s; status=%s; reason=%s',
                $dependency['ok'] ? 'true' : 'false',
                (string) $dependency['status'],
                (string) $dependency['reason']
            );

            echo '<tr>';
            echo '<td>' . esc_html((string) $moduleDiagnostics['slug']) . '</td>';
            echo '<td>' . esc_html((string) $moduleDiagnostics['status']) . '</td>';
            echo '<td>' . esc_html((string) $moduleDiagnostics['reason']) . '</td>';
            echo '<td>' . esc_html($dependencyText) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
    }
}

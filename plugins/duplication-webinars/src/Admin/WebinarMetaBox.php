<?php

declare(strict_types=1);

namespace Duplication\Webinars\Admin;

use Duplication\Webinars\Domain\Canon;
use Duplication\Webinars\Domain\WebinarRepository;

final class WebinarMetaBox
{
    private WebinarRepository $repository;

    public function __construct(WebinarRepository $repository)
    {
        $this->repository = $repository;
    }

    public function registerHooks(): void
    {
        add_action('add_meta_boxes', [$this, 'registerMetaBox']);
        add_action('save_post_' . Canon::POST_TYPE, [$this, 'saveMetaBox'], 10, 2);
    }

    public function registerMetaBox(): void
    {
        add_meta_box(
            'duplication-webinar-settings',
            'Duplication Webinar Settings',
            [$this, 'renderMetaBox'],
            Canon::POST_TYPE,
            'normal',
            'high'
        );
    }

    public function renderMetaBox(\WP_Post $post): void
    {
        wp_nonce_field('duplication_webinar_meta', 'duplication_webinar_meta_nonce');
        $accessLevelOptions = ['candidate', 'partner', 'vip_partner'];

        $format = $this->repository->getFormat((int) $post->ID);
        $status = $this->repository->getStatus((int) $post->ID);
        $minimumAccessLevel = $this->repository->getMinimumAccessLevel((int) $post->ID);
        $sourceType = $this->repository->getSourceType((int) $post->ID);
        $sourceValue = $this->repository->getSourceValue((int) $post->ID);
        $publicPageEnabled = $this->repository->isPublicPageEnabled((int) $post->ID);
        $pageMode = $this->repository->getPageMode((int) $post->ID);
        $publicUrl = get_permalink((int) $post->ID) ?: '';

        echo '<table class="form-table" role="presentation">';

        echo '<tr><th><label for="dp_webinar_format">Format</label></th><td>';
        echo '<select name="dp_webinar_format" id="dp_webinar_format">';
        foreach (Canon::formats() as $option) {
            echo '<option value="' . esc_attr($option) . '"' . selected($format, $option, false) . '>' . esc_html($option) . '</option>';
        }
        echo '</select>';
        echo '</td></tr>';

        echo '<tr><th><label for="dp_webinar_status">Status</label></th><td>';
        echo '<select name="dp_webinar_status" id="dp_webinar_status">';
        foreach (Canon::statuses() as $option) {
            echo '<option value="' . esc_attr($option) . '"' . selected($status, $option, false) . '>' . esc_html($option) . '</option>';
        }
        echo '</select>';
        echo '</td></tr>';

        echo '<tr><th><label for="dp_webinar_minimum_access_level">minimum_access_level</label></th><td>';
        echo '<select name="dp_webinar_minimum_access_level" id="dp_webinar_minimum_access_level">';
        foreach ($accessLevelOptions as $option) {
            echo '<option value="' . esc_attr($option) . '"' . selected($minimumAccessLevel, $option, false) . '>' . esc_html($option) . '</option>';
        }
        echo '</select>';
        echo '</td></tr>';

        echo '<tr><th><label for="dp_webinar_source_type">Source Type</label></th><td>';
        echo '<select name="dp_webinar_source_type" id="dp_webinar_source_type">';
        foreach (Canon::sourceTypes() as $option) {
            echo '<option value="' . esc_attr($option) . '"' . selected($sourceType, $option, false) . '>' . esc_html($option) . '</option>';
        }
        echo '</select>';
        echo '</td></tr>';

        echo '<tr><th><label for="dp_webinar_source_value">Source Value</label></th><td>';
        echo '<input type="text" class="large-text" name="dp_webinar_source_value" id="dp_webinar_source_value" value="' . esc_attr($sourceValue) . '">';
        echo '<p class="description">YouTube embed code / iframe URL / external URL.</p>';
        echo '</td></tr>';

        echo '<tr><th><label for="dp_webinar_public_page_enabled">public_webinar_page</label></th><td>';
        echo '<label><input type="checkbox" name="dp_webinar_public_page_enabled" id="dp_webinar_public_page_enabled" value="1"' . checked($publicPageEnabled, true, false) . '> Enabled</label>';
        echo '</td></tr>';

        echo '<tr><th><label for="dp_webinar_public_url">public_webinar_page_url</label></th><td>';
        echo '<input type="url" class="large-text" id="dp_webinar_public_url" value="' . esc_attr($publicUrl) . '" readonly>';
        echo '<p class="description">Canonical URL of webinar entity. webinar_room is rendered as page_mode on this URL.</p>';
        echo '</td></tr>';

        echo '<tr><th><label for="dp_webinar_page_mode">page_mode</label></th><td>';
        echo '<select name="dp_webinar_page_mode" id="dp_webinar_page_mode">';
        foreach (Canon::pageModes() as $option) {
            echo '<option value="' . esc_attr($option) . '"' . selected($pageMode, $option, false) . '>' . esc_html($option) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">webinar_room is mode of the same webinar URL, not separate SEO entity.</p>';
        echo '</td></tr>';

        echo '</table>';
    }

    public function saveMetaBox(int $postId, \WP_Post $post): void
    {
        if ($post->post_type !== Canon::POST_TYPE) {
            return;
        }

        if (wp_is_post_revision($postId) || wp_is_post_autosave($postId)) {
            return;
        }

        if (! current_user_can('edit_post', $postId)) {
            return;
        }

        if (
            ! isset($_POST['duplication_webinar_meta_nonce'])
            || ! wp_verify_nonce((string) $_POST['duplication_webinar_meta_nonce'], 'duplication_webinar_meta')
        ) {
            return;
        }

        $payload = [
            'format' => sanitize_key((string) ($_POST['dp_webinar_format'] ?? '')),
            'status' => sanitize_key((string) ($_POST['dp_webinar_status'] ?? '')),
            'minimum_access_level' => sanitize_key((string) ($_POST['dp_webinar_minimum_access_level'] ?? 'candidate')),
            'source_type' => sanitize_key((string) ($_POST['dp_webinar_source_type'] ?? '')),
            'source_value' => sanitize_text_field((string) ($_POST['dp_webinar_source_value'] ?? '')),
            'public_page_enabled' => ! empty($_POST['dp_webinar_public_page_enabled']),
            'page_mode' => sanitize_key((string) ($_POST['dp_webinar_page_mode'] ?? '')),
        ];

        $this->repository->saveCanonicalMeta($postId, $payload);
    }
}

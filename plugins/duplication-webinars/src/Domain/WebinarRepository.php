<?php

declare(strict_types=1);

namespace Duplication\Webinars\Domain;

final class WebinarRepository
{
    public function postType(): string
    {
        return Canon::POST_TYPE;
    }

    public function registerPostType(): void
    {
        register_post_type(Canon::POST_TYPE, [
            'labels' => [
                'name' => 'Webinars',
                'singular_name' => 'Webinar',
                'add_new_item' => 'Add Webinar',
                'edit_item' => 'Edit Webinar',
            ],
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_rest' => true,
            'menu_position' => 25,
            'menu_icon' => 'dashicons-video-alt3',
            'supports' => ['title', 'editor', 'thumbnail'],
            'has_archive' => false,
            'rewrite' => ['slug' => 'webinar'],
        ]);
    }

    public function create(array $payload): int
    {
        $postId = wp_insert_post([
            'post_type' => Canon::POST_TYPE,
            'post_status' => 'publish',
            'post_title' => (string) ($payload['title'] ?? 'Webinar'),
            'post_content' => (string) ($payload['content'] ?? ''),
        ]);

        if (! is_int($postId) || $postId <= 0) {
            return 0;
        }

        $this->saveCanonicalMeta($postId, $payload);

        return $postId;
    }

    public function saveCanonicalMeta(int $postId, array $payload): void
    {
        update_post_meta($postId, Canon::META_FORMAT, $this->sanitizeFormat((string) ($payload['format'] ?? '')));
        update_post_meta($postId, Canon::META_STATUS, $this->sanitizeStatus((string) ($payload['status'] ?? '')));
        update_post_meta($postId, Canon::META_MINIMUM_ACCESS_LEVEL, $this->sanitizeMinimumAccessLevel((string) ($payload['minimum_access_level'] ?? '')));
        update_post_meta($postId, Canon::META_SOURCE_TYPE, $this->sanitizeSourceType((string) ($payload['source_type'] ?? '')));
        update_post_meta($postId, Canon::META_SOURCE_VALUE, (string) ($payload['source_value'] ?? ''));
        update_post_meta($postId, Canon::META_PUBLIC_PAGE_ENABLED, ! empty($payload['public_page_enabled']) ? '1' : '0');

        $pageMode = isset($payload['page_mode']) ? (string) $payload['page_mode'] : $this->derivePageModeFromStatus($this->getStatus($postId));
        update_post_meta($postId, Canon::META_PAGE_MODE, $this->sanitizePageMode($pageMode));
    }

    /** @return array<string,mixed>|null */
    public function getById(int $postId): ?array
    {
        $post = get_post($postId);

        if (! $post instanceof \WP_Post || $post->post_type !== Canon::POST_TYPE) {
            return null;
        }

        return [
            'id' => (int) $post->ID,
            'title' => (string) $post->post_title,
            'content' => (string) $post->post_content,
            'status' => $this->getStatus((int) $post->ID),
            'format' => $this->getFormat((int) $post->ID),
            'minimum_access_level' => $this->getMinimumAccessLevel((int) $post->ID),
            'source_type' => $this->getSourceType((int) $post->ID),
            'source_value' => $this->getSourceValue((int) $post->ID),
            'public_page_enabled' => $this->isPublicPageEnabled((int) $post->ID),
            'page_mode' => $this->getPageMode((int) $post->ID),
            'public_url' => get_permalink((int) $post->ID) ?: '',
        ];
    }

    /** @return array<int,array<string,mixed>> */
    public function list(array $args = []): array
    {
        $queryArgs = [
            'post_type' => Canon::POST_TYPE,
            'post_status' => ['publish', 'draft', 'future', 'private'],
            'posts_per_page' => isset($args['limit']) ? max(1, (int) $args['limit']) : 20,
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        $query = new \WP_Query($queryArgs);
        $items = [];

        foreach ($query->posts as $post) {
            if (! $post instanceof \WP_Post) {
                continue;
            }

            $entity = $this->getById((int) $post->ID);

            if ($entity !== null) {
                $items[] = $entity;
            }
        }

        return $items;
    }

    public function getStatus(int $postId): string
    {
        $raw = get_post_meta($postId, Canon::META_STATUS, true);
        return $this->sanitizeStatus(is_string($raw) ? $raw : '');
    }

    public function getFormat(int $postId): string
    {
        $raw = get_post_meta($postId, Canon::META_FORMAT, true);
        return $this->sanitizeFormat(is_string($raw) ? $raw : '');
    }

    public function getMinimumAccessLevel(int $postId): string
    {
        $raw = get_post_meta($postId, Canon::META_MINIMUM_ACCESS_LEVEL, true);
        return $this->sanitizeMinimumAccessLevel(is_string($raw) ? $raw : '');
    }

    public function getSourceType(int $postId): string
    {
        $raw = get_post_meta($postId, Canon::META_SOURCE_TYPE, true);
        return $this->sanitizeSourceType(is_string($raw) ? $raw : '');
    }

    public function getSourceValue(int $postId): string
    {
        $raw = get_post_meta($postId, Canon::META_SOURCE_VALUE, true);
        return is_string($raw) ? $raw : '';
    }

    public function isPublicPageEnabled(int $postId): bool
    {
        return get_post_meta($postId, Canon::META_PUBLIC_PAGE_ENABLED, true) === '1';
    }

    public function getPageMode(int $postId): string
    {
        $raw = get_post_meta($postId, Canon::META_PAGE_MODE, true);
        $mode = $this->sanitizePageMode(is_string($raw) ? $raw : '');

        if ($mode === Canon::PAGE_MODE_PRE_EVENT && $this->getStatus($postId) !== Canon::STATUS_SCHEDULED) {
            return $this->derivePageModeFromStatus($this->getStatus($postId));
        }

        return $mode;
    }

    private function sanitizeFormat(string $format): string
    {
        return in_array($format, Canon::formats(), true) ? $format : Canon::FORMAT_STANDARD;
    }

    private function sanitizeStatus(string $status): string
    {
        return in_array($status, Canon::statuses(), true) ? $status : Canon::STATUS_SCHEDULED;
    }

    private function sanitizeSourceType(string $sourceType): string
    {
        return in_array($sourceType, Canon::sourceTypes(), true) ? $sourceType : Canon::SOURCE_EXTERNAL_LINK;
    }

    private function sanitizePageMode(string $pageMode): string
    {
        return in_array($pageMode, Canon::pageModes(), true) ? $pageMode : Canon::PAGE_MODE_PRE_EVENT;
    }

    private function sanitizeMinimumAccessLevel(string $level): string
    {
        $canonicalLevels = ['candidate', 'partner', 'vip_partner'];

        return in_array($level, $canonicalLevels, true) ? $level : 'candidate';
    }

    private function derivePageModeFromStatus(string $status): string
    {
        return match ($status) {
            Canon::STATUS_LIVE => Canon::PAGE_MODE_LIVE,
            Canon::STATUS_FINISHED => Canon::PAGE_MODE_FINISHED,
            Canon::STATUS_CANCELED => Canon::PAGE_MODE_CANCELED,
            default => Canon::PAGE_MODE_PRE_EVENT,
        };
    }
}

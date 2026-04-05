<?php

declare(strict_types=1);

namespace Duplication\Webinars;

use Duplication\Webinars\Admin\WebinarMetaBox;
use Duplication\Webinars\Domain\Canon;
use Duplication\Webinars\Domain\WebinarRepository;
use Duplication\Webinars\Domain\WebinarService;

final class Bootstrap
{
    private WebinarRepository $repository;

    private WebinarService $service;

    public function __construct()
    {
        $this->repository = new WebinarRepository();
        $this->service = new WebinarService($this->repository);
    }

    public function boot(): void
    {
        duplication_core_register_module('duplication-webinars', [
            'title' => 'Duplication Webinars',
            'dependencies' => ['duplication-core', 'duplication-access'],
            'boot' => [$this, 'onModuleBoot'],
        ]);
    }

    public function service(): WebinarService
    {
        return $this->service;
    }

    public function onModuleBoot(): void
    {
        add_action('init', [$this->repository, 'registerPostType']);
        add_action('save_post_' . $this->repository->postType(), [$this, 'onWebinarSaved'], 20, 3);
        add_filter('single_template', [$this, 'resolveSingleTemplate']);

        if (is_admin()) {
            (new WebinarMetaBox($this->repository))->registerHooks();
        }
    }

    public function onWebinarSaved(int $postId, \WP_Post $post, bool $isUpdate): void
    {
        if (wp_is_post_revision($postId) || wp_is_post_autosave($postId)) {
            return;
        }

        $entity = $this->repository->getById($postId);

        if ($entity === null) {
            return;
        }

        if ($isUpdate) {
            do_action('duplication/webinar_updated', ['webinar_id' => $postId, 'status' => $entity['status']]);
        } else {
            do_action('duplication/webinar_created', ['webinar_id' => $postId, 'status' => $entity['status']]);
        }

        $previousStatus = (string) get_post_meta($postId, '_dp_webinar_previous_status', true);
        $currentStatus = $entity['status'];

        if ($previousStatus !== $currentStatus) {
            if ($currentStatus === Canon::STATUS_LIVE) {
                do_action('duplication/webinar_started', ['webinar_id' => $postId]);
            }

            if ($currentStatus === Canon::STATUS_FINISHED) {
                do_action('duplication/webinar_finished', ['webinar_id' => $postId]);
            }

            if ($currentStatus === Canon::STATUS_CANCELED) {
                do_action('duplication/webinar_canceled', ['webinar_id' => $postId]);
            }
        }

        update_post_meta($postId, '_dp_webinar_previous_status', $currentStatus);
    }

    public function resolveSingleTemplate(string $template): string
    {
        if (! is_singular(Canon::POST_TYPE)) {
            return $template;
        }

        $singleTemplate = DUPLICATION_WEBINARS_PATH . '/templates/single-webinar.php';

        return file_exists($singleTemplate) ? $singleTemplate : $template;
    }
}

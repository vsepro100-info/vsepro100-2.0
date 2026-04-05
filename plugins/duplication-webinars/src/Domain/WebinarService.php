<?php

declare(strict_types=1);

namespace Duplication\Webinars\Domain;


final class WebinarService
{
    private WebinarRepository $repository;

    public function __construct(WebinarRepository $repository)
    {
        $this->repository = $repository;
    }

    /** @return array<string,mixed>|null */
    public function getWebinarById(int $webinarId): ?array
    {
        return $this->repository->getById($webinarId);
    }

    /** @return array<int,array<string,mixed>> */
    public function listWebinars(array $args = []): array
    {
        return $this->repository->list($args);
    }

    public function getStatus(int $webinarId): string
    {
        return $this->repository->getStatus($webinarId);
    }

    public function getPageMode(int $webinarId): string
    {
        return $this->repository->getPageMode($webinarId);
    }

    /**
     * @return array{allowed:bool,reason:string,cta:string,page_mode:string}
     */
    public function checkAccessForUser(int $webinarId, int $userId): array
    {
        $entity = $this->repository->getById($webinarId);

        if ($entity === null) {
            return [
                'allowed' => false,
                'reason' => 'webinar_not_found',
                'cta' => Canon::CTA_NOT_AVAILABLE,
                'page_mode' => Canon::PAGE_MODE_PRE_EVENT,
            ];
        }

        $pageMode = (string) $entity['page_mode'];

        if (! (bool) $entity['public_page_enabled']) {
            return [
                'allowed' => false,
                'reason' => 'public_page_disabled',
                'cta' => Canon::CTA_NOT_AVAILABLE,
                'page_mode' => $pageMode,
            ];
        }

        if ($userId <= 0) {
            return [
                'allowed' => false,
                'reason' => 'user_not_authenticated',
                'cta' => Canon::CTA_LOGIN_REQUIRED,
                'page_mode' => $pageMode,
            ];
        }

        $accessRule = [
            'minimum_access_level' => $this->accessLevelFromBusinessStatus((string) $entity['minimum_access_level']),
            'female_club' => $entity['format'] === Canon::FORMAT_FEMALE_CLUB,
        ];

        $accessCheck = duplication_access_check($userId, $accessRule);
        $allowed = (bool) ($accessCheck['allowed'] ?? false);
        $reason = (string) ($accessCheck['reason'] ?? 'access_check_failed');

        return [
            'allowed' => $allowed,
            'reason' => $reason,
            'cta' => $this->resolveCta($allowed, $reason, $pageMode),
            'page_mode' => $pageMode,
        ];
    }

    public function canEnterRoom(int $webinarId, int $userId): bool
    {
        $access = $this->checkAccessForUser($webinarId, $userId);

        if (! $access['allowed']) {
            return false;
        }

        return $access['page_mode'] === Canon::PAGE_MODE_LIVE;
    }

    /** @return array<string,mixed>|null */
    public function getPublicPageViewModel(int $webinarId, int $userId): ?array
    {
        $entity = $this->repository->getById($webinarId);

        if ($entity === null) {
            return null;
        }

        $access = $this->checkAccessForUser($webinarId, $userId);
        $canEnterRoom = $this->canEnterRoom($webinarId, $userId);

        return [
            'entity' => $entity,
            'mode_label' => $this->resolvePageModeLabel((string) $access['page_mode']),
            'is_public_page' => (bool) $entity['public_page_enabled'],
            'access' => [
                'allowed' => (bool) $access['allowed'],
                'reason' => (string) $access['reason'],
                'message' => $this->resolveAccessMessage((string) $access['reason']),
            ],
            'cta' => $this->resolveCtaView((string) $access['cta'], $entity, $canEnterRoom),
        ];
    }

    private function resolveCta(bool $allowed, string $reason, string $pageMode): string
    {
        if (! $allowed) {
            return match ($reason) {
                'user_not_authenticated' => Canon::CTA_LOGIN_REQUIRED,
                'female_club_requires_female' => Canon::CTA_FEMALE_CLUB_ONLY,
                'minimum_access_level_not_met' => Canon::CTA_UPGRADE_ACCESS,
                default => Canon::CTA_NOT_AVAILABLE,
            };
        }

        return match ($pageMode) {
            Canon::PAGE_MODE_LIVE => Canon::CTA_ENTER_ROOM,
            Canon::PAGE_MODE_FINISHED => Canon::CTA_VIEW_RECORDING,
            default => Canon::CTA_NOT_AVAILABLE,
        };
    }

    private function accessLevelFromBusinessStatus(string $status): int
    {
        return match ($status) {
            'vip_partner' => 3,
            'partner' => 2,
            default => 1,
        };
    }

    private function resolvePageModeLabel(string $pageMode): string
    {
        return match ($pageMode) {
            Canon::PAGE_MODE_LIVE => 'Live webinar page',
            Canon::PAGE_MODE_FINISHED => 'Finished webinar page',
            Canon::PAGE_MODE_CANCELED => 'Canceled webinar page',
            default => 'Upcoming webinar page',
        };
    }

    private function resolveAccessMessage(string $reason): string
    {
        return match ($reason) {
            'access_allowed' => 'Access granted.',
            'admin_override_allowed' => 'Access granted by admin override.',
            'user_not_authenticated' => 'Log in to check and use webinar access.',
            'minimum_access_level_not_met' => 'Access is limited by minimum_access_level.',
            'female_club_requires_female' => 'This female_club webinar is available only for female profiles.',
            'public_page_disabled' => 'Public page is disabled for this webinar.',
            'webinar_not_found' => 'Webinar was not found.',
            default => 'Access is currently unavailable.',
        };
    }

    /**
     * @param array<string,mixed> $entity
     * @return array{code:string,label:string,url:string,enabled:bool}
     */
    private function resolveCtaView(string $cta, array $entity, bool $canEnterRoom): array
    {
        $defaultUrl = (string) ($entity['public_url'] ?? '');

        return match ($cta) {
            Canon::CTA_LOGIN_REQUIRED => [
                'code' => Canon::CTA_LOGIN_REQUIRED,
                'label' => 'Log in',
                'url' => wp_login_url($defaultUrl),
                'enabled' => true,
            ],
            Canon::CTA_UPGRADE_ACCESS => [
                'code' => Canon::CTA_UPGRADE_ACCESS,
                'label' => 'Upgrade access level',
                'url' => '',
                'enabled' => false,
            ],
            Canon::CTA_FEMALE_CLUB_ONLY => [
                'code' => Canon::CTA_FEMALE_CLUB_ONLY,
                'label' => 'Female club only',
                'url' => '',
                'enabled' => false,
            ],
            Canon::CTA_ENTER_ROOM => [
                'code' => Canon::CTA_ENTER_ROOM,
                'label' => 'Enter webinar room',
                'url' => $canEnterRoom ? $defaultUrl . '#room' : '',
                'enabled' => $canEnterRoom,
            ],
            Canon::CTA_VIEW_RECORDING => [
                'code' => Canon::CTA_VIEW_RECORDING,
                'label' => 'View recording',
                'url' => $defaultUrl . '#recording',
                'enabled' => true,
            ],
            default => [
                'code' => Canon::CTA_NOT_AVAILABLE,
                'label' => 'Not available',
                'url' => '',
                'enabled' => false,
            ],
        };
    }
}

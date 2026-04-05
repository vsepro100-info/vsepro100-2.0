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

        $pageMode = $entity['page_mode'];

        if ($userId <= 0) {
            return [
                'allowed' => false,
                'reason' => 'user_not_authenticated',
                'cta' => Canon::CTA_LOGIN_REQUIRED,
                'page_mode' => $pageMode,
            ];
        }

        $minimumLevelCheck = $this->checkMinimumAccessLevelByCanonicalStatus($userId, (string) $entity['minimum_access_level']);

        if (! $minimumLevelCheck['allowed']) {
            return [
                'allowed' => false,
                'reason' => $minimumLevelCheck['reason'],
                'cta' => $this->resolveCta(false, $minimumLevelCheck['reason'], $pageMode),
                'page_mode' => $pageMode,
            ];
        }

        if ($entity['format'] === Canon::FORMAT_FEMALE_CLUB) {
            $femaleCheck = duplication_access_check_female_club($userId);

            if (! (bool) ($femaleCheck['allowed'] ?? false)) {
                $reason = (string) ($femaleCheck['reason'] ?? 'access_check_failed');

                return [
                    'allowed' => false,
                    'reason' => $reason,
                    'cta' => $this->resolveCta(false, $reason, $pageMode),
                    'page_mode' => $pageMode,
                ];
            }
        }

        return [
            'allowed' => true,
            'reason' => 'access_allowed',
            'cta' => $this->resolveCta(true, 'access_allowed', $pageMode),
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

    /**
     * @return array{allowed:bool,reason:string}
     */
    private function checkMinimumAccessLevelByCanonicalStatus(int $userId, string $requiredLevel): array
    {
        $adminOverride = duplication_access_check_admin_override($userId);

        if ((bool) ($adminOverride['allowed'] ?? false)) {
            return ['allowed' => true, 'reason' => 'admin_override_allowed'];
        }

        $weights = [
            'candidate' => 1,
            'partner' => 2,
            'vip_partner' => 3,
        ];

        $required = array_key_exists($requiredLevel, $weights) ? $requiredLevel : 'candidate';
        $actual = duplication_access_get_business_status($userId);
        $actualLevel = array_key_exists($actual, $weights) ? $actual : 'candidate';

        if ($weights[$actualLevel] >= $weights[$required]) {
            return ['allowed' => true, 'reason' => 'minimum_access_level_passed'];
        }

        return ['allowed' => false, 'reason' => 'minimum_access_level_not_met'];
    }
}

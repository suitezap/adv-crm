<?php

namespace SuiteZap\LawFirm\SaaS\Services;

use Illuminate\Support\Facades\DB;
use Webkul\User\Repositories\UserRepository;

/**
 * Service for managing SaaS quotas (Users, Helpers, etc).
 */
class SaasQuotaService
{
    /**
     * Configuration keys.
     */
    protected const CONFIG_USER_LIMIT = 'lawfirm.saas.users.limit';

    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * Create a new service instance.
     *
     * @param UserRepository $userRepository
     * @return void
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Check if the user creation is allowed based on the limit.
     *
     * @return bool True if allowed, False if limit reached.
     */
    public function canCreateUser(): bool
    {
        $limit = $this->getUserLimit();

        // If limit is 0 or null (not set), it's considered unlimited
        if ($limit <= 0) {
            return true;
        }

        $currentCount = $this->getUserUsage();

        return $currentCount < $limit;
    }

    /**
     * Get the configured user limit.
     *
     * @return int
     */
    public function getUserLimit(): int
    {
        $limit = core()->getConfigData(self::CONFIG_USER_LIMIT);

        return $limit !== null ? (int) $limit : 0;
    }

    /**
     * Get the current count of users.
     *
     * @return int
     */
    public function getUserUsage(): int
    {
        return $this->userRepository->count();
    }
}

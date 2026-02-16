<?php

namespace SuiteZap\LawFirm\SaaS\Services;

use Illuminate\Support\Facades\DB;

/**
 * Service for managing SaaS storage quota.
 * 
 * Provides methods to check, increment, and decrement storage usage
 * based on configuration stored in core_config table.
 */
class SaasStorageService
{
    /**
     * Configuration keys for storage management.
     */
    protected const CONFIG_LIMIT = 'lawfirm.saas.storage.limit';
    protected const CONFIG_USED = 'lawfirm.saas.storage.used';

    /**
     * Default storage limit (4GB) if not configured.
     */
    protected const DEFAULT_LIMIT = 4294967296;

    /**
     * Check if there is enough quota for a new file.
     *
     * @param int $newFileSize Size of the new file in bytes
     * @return bool True if (used + newFileSize) <= limit
     */
    public function checkQuota(int $newFileSize): bool
    {
        $limit = $this->getLimit();
        $used = $this->getUsed();

        return ($used + $newFileSize) <= $limit;
    }

    /**
     * Increment the storage usage after successful upload.
     *
     * @param int $bytes Number of bytes to add
     * @return void
     */
    public function incrementUsage(int $bytes): void
    {
        if ($bytes <= 0) {
            return;
        }

        $currentUsed = $this->getUsed();
        $newUsed = $currentUsed + $bytes;

        $this->setConfigValue(self::CONFIG_USED, (string) $newUsed);
    }

    /**
     * Decrement the storage usage after file deletion.
     * Ensures usage never goes below zero.
     *
     * @param int $bytes Number of bytes to subtract
     * @return void
     */
    public function decrementUsage(int $bytes): void
    {
        if ($bytes <= 0) {
            return;
        }

        $currentUsed = $this->getUsed();
        $newUsed = max(0, $currentUsed - $bytes);

        $this->setConfigValue(self::CONFIG_USED, (string) $newUsed);
    }

    /**
     * Get the current usage as a percentage of the limit.
     *
     * @return float Percentage from 0 to 100
     */
    public function getUsagePercentage(): float
    {
        $limit = $this->getLimit();

        // Protection against division by zero
        if ($limit <= 0) {
            return 100.0;
        }

        $used = $this->getUsed();

        return round(($used / $limit) * 100, 2);
    }

    /**
     * Get the remaining storage space in bytes.
     *
     * @return int Remaining bytes
     */
    public function getRemainingBytes(): int
    {
        return max(0, $this->getLimit() - $this->getUsed());
    }

    /**
     * Get the storage limit in bytes.
     *
     * @return int Storage limit
     */
    public function getLimit(): int
    {
        $limit = $this->getConfigValue(self::CONFIG_LIMIT);

        return $limit !== null ? (int) $limit : self::DEFAULT_LIMIT;
    }

    /**
     * Get the current storage usage in bytes.
     *
     * @return int Current usage
     */
    public function getUsed(): int
    {
        $used = $this->getConfigValue(self::CONFIG_USED);

        return $used !== null ? (int) $used : 0;
    }

    /**
     * Format bytes to human readable format.
     *
     * @param int $bytes
     * @return string Example: "1.5 GB"
     */
    public function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        return round($bytes / pow(1024, $pow), 2) . ' ' . $units[$pow];
    }

    /**
     * Get a summary of storage usage.
     *
     * @return array{used: int, limit: int, percent: float, used_formatted: string, limit_formatted: string}
     */
    public function getSummary(): array
    {
        $used = $this->getUsed();
        $limit = $this->getLimit();

        return [
            'used' => $used,
            'limit' => $limit,
            'percent' => $this->getUsagePercentage(),
            'used_formatted' => $this->formatBytes($used),
            'limit_formatted' => $this->formatBytes($limit),
        ];
    }

    /**
     * Get a configuration value from core_config table.
     *
     * @param string $code
     * @return string|null
     */
    protected function getConfigValue(string $code): ?string
    {
        $record = DB::table('core_config')
            ->where('code', $code)
            ->first();

        return $record?->value;
    }

    /**
     * Set a configuration value in core_config table.
     * Uses upsert to create or update.
     *
     * @param string $code
     * @param string $value
     * @return void
     */
    protected function setConfigValue(string $code, string $value): void
    {
        $exists = DB::table('core_config')->where('code', $code)->exists();

        if ($exists) {
            DB::table('core_config')
                ->where('code', $code)
                ->update([
                    'value' => $value,
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('core_config')->insert([
                'code' => $code,
                'value' => $value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

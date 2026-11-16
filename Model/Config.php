<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Model;

use Magento\Directory\Model\Currency;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Reads Stores > Configuration > Click&Mortar > Cube (default scope).
 */
class Config
{
    public const XML_ENABLED = 'cm_cube/general/enabled';
    public const XML_SCREENS = 'cm_cube/general/screens';
    public const XML_DAILY_GOAL = 'cm_cube/general/daily_goal';
    public const XML_TIMEZONE = 'cm_cube/general/timezone';
    public const XML_STORE_IDS = 'cm_cube/general/store_ids';
    public const XML_EXCLUDED_STATES = 'cm_cube/general/excluded_states';
    public const XML_EVENTS_LIMIT = 'cm_cube/advanced/events_limit';
    public const XML_RECORD_WINDOW = 'cm_cube/advanced/record_window_days';

    public const DEFAULT_TIMEZONE = 'Europe/Paris';
    public const SCREENS = ['today', 'compare', 'week', 'last_order', 'goal'];
    public const MAX_EVENTS = 100;

    public function __construct(private readonly ScopeConfigInterface $scopeConfig)
    {
    }

    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_ENABLED);
    }

    /**
     * @return string[]
     */
    public function getScreens(): array
    {
        $selected = $this->csv(self::XML_SCREENS);
        $screens = array_values(array_filter(self::SCREENS, static fn ($s) => in_array($s, $selected, true)));
        if ($this->getDailyGoal() <= 0) {
            $screens = array_values(array_diff($screens, ['goal']));
        }
        return $screens;
    }

    public function getDailyGoal(): float
    {
        return max(0.0, (float) str_replace(',', '.', (string) $this->scopeConfig->getValue(self::XML_DAILY_GOAL)));
    }

    public function getTimezone(): \DateTimeZone
    {
        $name = trim((string) $this->scopeConfig->getValue(self::XML_TIMEZONE));
        try {
            return new \DateTimeZone($name !== '' ? $name : self::DEFAULT_TIMEZONE);
        } catch (\Exception) {
            return new \DateTimeZone(self::DEFAULT_TIMEZONE);
        }
    }

    /**
     * @return int[] empty = all store views
     */
    public function getStoreIds(): array
    {
        $ids = array_map('intval', $this->csv(self::XML_STORE_IDS));
        $ids = array_values(array_unique(array_filter($ids, static fn ($id) => $id > 0)));
        sort($ids);
        return $ids;
    }

    /**
     * @return string[]
     */
    public function getExcludedStates(): array
    {
        $states = $this->csv(self::XML_EXCLUDED_STATES);
        sort($states);
        return $states;
    }

    public function getEventsLimit(): int
    {
        $limit = (int) $this->scopeConfig->getValue(self::XML_EVENTS_LIMIT);
        return $limit > 0 ? min($limit, self::MAX_EVENTS) : 20;
    }

    public function getRecordWindowDays(): int
    {
        $days = (int) $this->scopeConfig->getValue(self::XML_RECORD_WINDOW);
        return $days > 0 ? max(7, min($days, 1095)) : 365;
    }

    public function getBaseCurrency(): string
    {
        return (string) ($this->scopeConfig->getValue(Currency::XML_PATH_CURRENCY_BASE) ?: 'EUR');
    }

    /**
     * @return string[]
     */
    private function csv(string $path): array
    {
        $raw = (string) $this->scopeConfig->getValue($path);
        return array_values(array_filter(array_map('trim', explode(',', $raw)), static fn ($v) => $v !== ''));
    }
}

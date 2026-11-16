<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Model;

use ClickAndMortar\Cube\Model\ResourceModel\OrderStats;
use Magento\Framework\App\CacheInterface;

/**
 * Historique journalier des jours passés (aujourd'hui exclu), calculé une fois par jour local et mis en cache.
 * Sert à la série des 7 jours et au record.
 */
class History
{
    public const CACHE_TAG = 'CM_CUBE';
    private const CACHE_LIFETIME = 86400;

    /** @var array<string, array<string, array{revenue: float, orders: int}>> */
    private array $memo = [];

    public function __construct(
        private readonly OrderStats $orderStats,
        private readonly StatsCalculator $calculator,
        private readonly CacheInterface $cache,
        private readonly Config $config
    ) {
    }

    /**
     * @return array<string, array{revenue: float, orders: int}> clé = date locale, jours sans vente absents
     */
    public function days(Periods $periods): array
    {
        $window = $this->config->getRecordWindowDays();
        $storeIds = $this->config->getStoreIds();
        $states = $this->config->getExcludedStates();
        $key = 'cm_cube_history_' . sha1(implode('|', [
            $periods->timezone->getName(),
            $periods->todayDate,
            $window,
            implode(',', $storeIds),
            implode(',', $states),
        ]));
        if (isset($this->memo[$key])) {
            return $this->memo[$key];
        }

        $cached = $this->cache->load($key);
        if (is_string($cached) && $cached !== '') {
            $days = json_decode($cached, true);
            if (is_array($days)) {
                return $this->memo[$key] = $days;
            }
        }

        $from = $periods->dayStartUtc($periods->shiftDate(-$window));
        $buckets = $this->orderStats->quarterHourBuckets($from, $periods->todayStartUtc, $storeIds, $states);
        $days = $this->calculator->aggregateDays($buckets, $periods->timezone);
        $this->cache->save((string) json_encode($days), $key, [self::CACHE_TAG], self::CACHE_LIFETIME);
        return $this->memo[$key] = $days;
    }

    /**
     * @return array{date: string, revenue: float}|null
     */
    public function record(Periods $periods): ?array
    {
        return $this->calculator->record($this->days($periods));
    }
}

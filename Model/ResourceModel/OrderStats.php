<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;

/**
 * Aggregated SQL queries on sales_order. No collection is ever fully loaded:
 * each method returns one aggregate row, 15-minute buckets or at most $limit orders.
 *
 * Dates are UTC strings "YYYY-MM-DD HH:MM:SS", like sales_order.created_at.
 */
class OrderStats
{
    public function __construct(private readonly ResourceConnection $resource)
    {
    }

    /**
     * @param int[] $storeIds
     * @param string[] $excludedStates
     * @return array{revenue: float, orders: int}
     */
    public function totals(string $fromUtc, string $toUtc, array $storeIds, array $excludedStates): array
    {
        $select = $this->base($storeIds, $excludedStates)
            ->reset(Select::COLUMNS)
            ->columns([
                'revenue' => new \Zend_Db_Expr('COALESCE(SUM(o.base_grand_total), 0)'),
                'orders' => new \Zend_Db_Expr('COUNT(*)'),
            ])
            ->where('o.created_at >= ?', $fromUtc)
            ->where('o.created_at < ?', $toUtc);
        $row = $this->connection()->fetchRow($select) ?: [];
        return ['revenue' => round((float) ($row['revenue'] ?? 0), 2), 'orders' => (int) ($row['orders'] ?? 0)];
    }

    /**
     * Revenue and order count per 15-minute UTC bucket ("YYYY-MM-DD HH:MM").
     * Grouping into local days is then done in PHP, exact across DST changes
     * and for timezones offset by half an hour or a quarter of an hour.
     *
     * @param int[] $storeIds
     * @param string[] $excludedStates
     * @return array<int, array{bucket: string, revenue: string, orders: string}>
     */
    public function quarterHourBuckets(string $fromUtc, string $toUtc, array $storeIds, array $excludedStates): array
    {
        $bucket = "CONCAT(DATE_FORMAT(o.created_at, '%Y-%m-%d %H:'), LPAD(FLOOR(MINUTE(o.created_at) / 15) * 15, 2, '0'))";
        $select = $this->base($storeIds, $excludedStates)
            ->reset(Select::COLUMNS)
            ->columns([
                'bucket' => new \Zend_Db_Expr($bucket),
                'revenue' => new \Zend_Db_Expr('SUM(o.base_grand_total)'),
                'orders' => new \Zend_Db_Expr('COUNT(*)'),
            ])
            ->where('o.created_at >= ?', $fromUtc)
            ->where('o.created_at < ?', $toUtc)
            ->group('bucket');
        return $this->connection()->fetchAll($select);
    }

    /**
     * Last counted order, with its city and without any personal data.
     *
     * @param int[] $storeIds
     * @param string[] $excludedStates
     * @return array<string, mixed>|null
     */
    public function lastOrder(array $storeIds, array $excludedStates): ?array
    {
        $select = $this->summarySelect($storeIds, $excludedStates)->order('o.entity_id DESC')->limit(1);
        $row = $this->connection()->fetchRow($select);
        return $row ?: null;
    }

    /**
     * The $limit most recent orders with entity_id > $since, by ascending entity_id.
     *
     * @param int[] $storeIds
     * @param string[] $excludedStates
     * @return array<int, array<string, mixed>>
     */
    public function ordersSince(int $since, int $limit, array $storeIds, array $excludedStates): array
    {
        $select = $this->summarySelect($storeIds, $excludedStates)
            ->where('o.entity_id > ?', $since)
            ->order('o.entity_id DESC')
            ->limit($limit);
        return array_reverse($this->connection()->fetchAll($select));
    }

    /**
     * @param int[] $storeIds
     * @param string[] $excludedStates
     */
    public function countSince(int $since, array $storeIds, array $excludedStates): int
    {
        $select = $this->base($storeIds, $excludedStates)
            ->reset(Select::COLUMNS)
            ->columns(['n' => new \Zend_Db_Expr('COUNT(*)')])
            ->where('o.entity_id > ?', $since);
        return (int) $this->connection()->fetchOne($select);
    }

    /**
     * @param int[] $storeIds
     * @param string[] $excludedStates
     */
    public function maxEntityId(array $storeIds, array $excludedStates): int
    {
        $select = $this->base($storeIds, $excludedStates)
            ->reset(Select::COLUMNS)
            ->columns(['id' => new \Zend_Db_Expr('MAX(o.entity_id)')]);
        return (int) $this->connection()->fetchOne($select);
    }

    /**
     * @param int[] $storeIds
     * @param string[] $excludedStates
     */
    public function minEntityIdBetween(string $fromUtc, string $toUtc, array $storeIds, array $excludedStates): ?int
    {
        $select = $this->base($storeIds, $excludedStates)
            ->reset(Select::COLUMNS)
            ->columns(['id' => new \Zend_Db_Expr('MIN(o.entity_id)')])
            ->where('o.created_at >= ?', $fromUtc)
            ->where('o.created_at < ?', $toUtc);
        $id = $this->connection()->fetchOne($select);
        return $id === null || $id === false ? null : (int) $id;
    }

    /**
     * Revenue of the period for orders with entity_id < $beforeEntityId.
     *
     * @param int[] $storeIds
     * @param string[] $excludedStates
     */
    public function revenueBefore(
        string $fromUtc,
        string $toUtc,
        int $beforeEntityId,
        array $storeIds,
        array $excludedStates
    ): float {
        $select = $this->base($storeIds, $excludedStates)
            ->reset(Select::COLUMNS)
            ->columns(['revenue' => new \Zend_Db_Expr('COALESCE(SUM(o.base_grand_total), 0)')])
            ->where('o.created_at >= ?', $fromUtc)
            ->where('o.created_at < ?', $toUtc)
            ->where('o.entity_id < ?', $beforeEntityId);
        return round((float) $this->connection()->fetchOne($select), 2);
    }

    /**
     * @param int[] $storeIds
     * @param string[] $excludedStates
     */
    private function summarySelect(array $storeIds, array $excludedStates): Select
    {
        $address = $this->resource->getTableName('sales_order_address', 'sales');
        return $this->base($storeIds, $excludedStates)
            ->reset(Select::COLUMNS)
            ->columns([
                'entity_id' => 'o.entity_id',
                'increment_id' => 'o.increment_id',
                'amount' => 'o.base_grand_total',
                'items' => 'o.total_qty_ordered',
                'created_at' => 'o.created_at',
                'city' => new \Zend_Db_Expr("COALESCE(sa.city, ba.city, '')"),
            ])
            ->joinLeft(['sa' => $address], "sa.parent_id = o.entity_id AND sa.address_type = 'shipping'", [])
            ->joinLeft(['ba' => $address], "ba.parent_id = o.entity_id AND ba.address_type = 'billing'", []);
    }

    /**
     * @param int[] $storeIds
     * @param string[] $excludedStates
     */
    private function base(array $storeIds, array $excludedStates): Select
    {
        $select = $this->connection()->select()
            ->from(['o' => $this->resource->getTableName('sales_order', 'sales')], []);
        if ($storeIds !== []) {
            $select->where('o.store_id IN (?)', array_map('intval', $storeIds));
        }
        if ($excludedStates !== []) {
            $select->where('o.state NOT IN (?)', $excludedStates);
        }
        return $select;
    }

    private function connection(): AdapterInterface
    {
        return $this->resource->getConnection('sales');
    }
}

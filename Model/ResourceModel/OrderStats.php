<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;

/**
 * Requêtes SQL agrégées sur sales_order. Jamais de collection chargée en entier :
 * chaque méthode renvoie une ligne d'agrégat, des tranches de 15 min ou au plus $limit commandes.
 *
 * Les dates sont des chaînes UTC « AAAA-MM-JJ HH:MM:SS », comme sales_order.created_at.
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
     * CA et nombre de commandes par tranche de 15 min UTC (« AAAA-MM-JJ HH:MM »).
     * Le regroupement en journées locales se fait ensuite en PHP, exact aux changements d'heure
     * et pour les fuseaux décalés d'une demi-heure ou d'un quart d'heure.
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
     * Dernière commande comptée, avec la ville et sans aucune donnée personnelle.
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
     * Les $limit commandes les plus récentes d'entity_id > $since, par entity_id croissant.
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
     * CA de la période pour les commandes d'entity_id < $beforeEntityId.
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

<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Test\Api;

use PHPUnit\Framework\TestCase;

/**
 * End-to-end API test against a real Magento instance (the module's docker/ bench):
 * reference orders are inserted at chosen times, then REST calls are made with a real integration token.
 *
 * WARNING: empties sales_order. Never run it against anything but a test database.
 * Variables: CUBE_BASE_URL, CUBE_TOKEN, CUBE_DB_DSN (default mysql:host=db;dbname=magento), CUBE_DB_USER, CUBE_DB_PASSWORD.
 */
class CubeApiTest extends TestCase
{
    private static \PDO $pdo;
    private static \DateTimeZone $tz;
    private static string $magentoRoot;

    public static function setUpBeforeClass(): void
    {
        self::$pdo = new \PDO(
            getenv('CUBE_DB_DSN') ?: 'mysql:host=db;dbname=magento;charset=utf8mb4',
            getenv('CUBE_DB_USER') ?: 'magento',
            getenv('CUBE_DB_PASSWORD') ?: 'magento',
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
        self::$tz = new \DateTimeZone('Europe/Paris');
        self::$magentoRoot = getenv('CUBE_MAGENTO_ROOT') ?: getcwd();
        $dbName = (string) self::$pdo->query('SELECT DATABASE()')->fetchColumn();
        if (!in_array($dbName, ['magento', 'magento_test'], true)) {
            self::fail("Database \"$dbName\" refused: this test empties sales_order.");
        }
    }

    public function testStatsAndEventsEndToEnd(): void
    {
        $this->magento('config:set cm_cube/general/enabled 1');
        $this->magento('config:set cm_cube/general/timezone Europe/Paris');
        $this->magento('config:set cm_cube/general/excluded_states canceled');
        $this->magento("config:set cm_cube/general/store_ids ''");
        $this->magento('config:set cm_cube/general/daily_goal 1000');
        $this->magento('config:set cm_cube/general/screens today,compare,week,last_order,goal');
        $this->magento('config:set cm_cube/advanced/record_window_days 365');
        $this->resetOrders();

        $now = new \DateTimeImmutable('now', self::$tz);
        $todayStart = $now->setTime(0, 0);
        $elapsed = $now->getTimestamp() - $todayStart->getTimestamp();
        if ($elapsed < 300) {
            self::markTestSkipped('Run this test more than 5 minutes after midnight (Europe/Paris).');
        }
        $yesterdayStart = $todayStart->modify('-1 day');
        $yesterdaySame = new \DateTimeImmutable($yesterdayStart->format('Y-m-d') . ' ' . $now->format('H:i:s'), self::$tz);
        $at = static fn (\DateTimeImmutable $from, \DateTimeImmutable $to, float $f) =>
            $from->setTimestamp((int) ($from->getTimestamp() + ($to->getTimestamp() - $from->getTimestamp()) * $f));

        // History
        $this->order($todayStart->modify('-9 days')->setTime(15, 0), 2000.0, 1, 'Marseille');
        $this->order($todayStart->modify('-3 days')->setTime(12, 0), 1500.0, 1, 'Paris');
        $this->order($todayStart->modify('-2 days')->setTime(23, 50), 10.0, 1, 'Lille');
        // Yesterday: 2 orders before the same time, 1 after
        $this->order($at($yesterdayStart, $yesterdaySame, 0.3), 60.0, 1, 'Rennes');
        $this->order($at($yesterdayStart, $yesterdaySame, 0.7), 40.0, 1, 'Rennes');
        $lastYesterday = $this->order($at($yesterdaySame, $todayStart, 0.5), 500.0, 1, 'Rennes');
        // Today: 3 counted orders + 1 canceled
        $firstToday = $this->order($at($todayStart, $now, 0.2), 100.0, 2, 'Lyon');
        $this->order($at($todayStart, $now, 0.5), 50.0, 1, 'Nantes');
        $this->order($at($todayStart, $now, 0.6), 999.0, 9, 'Nulle-part', 'canceled');
        $lastToday = $this->order($at($todayStart, $now, 0.8), 70.0, 3, 'Bordeaux');
        $this->magento('cache:flush');

        // --- Security
        self::assertSame(401, $this->call('/V1/cm-cube/stats', null)['status']);

        // --- Stats
        $stats = $this->get('/V1/cm-cube/stats');
        self::assertSame('Europe/Paris', $stats['timezone']);
        self::assertSame('EUR', $stats['currency']);
        self::assertSame(['today', 'compare', 'week', 'last_order', 'goal'], $stats['screens']);
        self::assertEqualsWithDelta(220.0, $stats['today']['revenue'], 0.001);
        self::assertSame(3, $stats['today']['orders']);
        self::assertEqualsWithDelta(73.33, $stats['today']['average_order'], 0.001);
        self::assertEqualsWithDelta(100.0, $stats['yesterday_same_time']['revenue'], 0.001);
        self::assertSame(2, $stats['yesterday_same_time']['orders']);
        self::assertEqualsWithDelta(120.0, $stats['revenue_delta_pct'], 0.001);
        self::assertEqualsWithDelta(50.0, $stats['orders_delta_pct'], 0.001);
        self::assertEqualsWithDelta(1000.0, $stats['goal_revenue'], 0.001);
        self::assertEqualsWithDelta(22.0, $stats['goal_progress_pct'], 0.001);
        self::assertEqualsWithDelta(2000.0, $stats['record_revenue'], 0.001);
        self::assertSame($todayStart->modify('-9 days')->format('Y-m-d'), $stats['record_date']);
        self::assertFalse($stats['record_broken_today']);

        self::assertCount(7, $stats['week']);
        $series = array_column($stats['week'], 'revenue', 'date');
        self::assertEqualsWithDelta(1500.0, $series[$todayStart->modify('-3 days')->format('Y-m-d')], 0.001);
        self::assertEqualsWithDelta(10.0, $series[$todayStart->modify('-2 days')->format('Y-m-d')], 0.001);
        self::assertEqualsWithDelta(600.0, $series[$yesterdayStart->format('Y-m-d')], 0.001);
        self::assertEqualsWithDelta(220.0, $series[$todayStart->format('Y-m-d')], 0.001);
        self::assertSame($todayStart->format('Y-m-d'), $stats['week'][6]['date']);

        self::assertSame(
            ['entity_id', 'increment_id', 'amount', 'city', 'items', 'created_at'],
            array_keys($stats['last_order']),
            'No personal data in last_order'
        );
        self::assertSame($lastToday, $stats['last_order']['entity_id']);
        self::assertSame('Bordeaux', $stats['last_order']['city']);
        self::assertSame(3, $stats['last_order']['items']);
        self::assertSame($lastToday, $stats['last_entity_id']);

        // --- Events
        $cursor = $this->get('/V1/cm-cube/events?since=0');
        self::assertSame([], $cursor['events']);
        self::assertSame($lastToday, $cursor['last_entity_id']);

        $events = $this->get('/V1/cm-cube/events?since=' . $lastYesterday);
        self::assertSame([$firstToday, $firstToday + 1, $lastToday], array_column($events['events'], 'entity_id'));
        self::assertTrue($events['events'][0]['first_of_day']);
        self::assertFalse($events['events'][1]['first_of_day']);
        self::assertSame([false, false, false], array_column($events['events'], 'record_broken'));
        self::assertSame(0, $events['skipped']);
        self::assertSame($lastToday, $events['last_entity_id']);
        self::assertStringStartsWith($todayStart->format('Y-m-d') . 'T', $events['events'][0]['created_at']);

        $limited = $this->get('/V1/cm-cube/events?since=' . $lastYesterday . '&limit=1');
        self::assertSame([$lastToday], array_column($limited['events'], 'entity_id'));
        self::assertSame(2, $limited['skipped']);

        // --- 7-day record (1500), broken by a new order
        $this->magento('config:set cm_cube/advanced/record_window_days 7');
        $big = $this->order($now->modify('-1 minute'), 1400.0, 1, 'Toulouse');
        $this->magento('cache:flush');
        $stats = $this->get('/V1/cm-cube/stats');
        self::assertEqualsWithDelta(1500.0, $stats['record_revenue'], 0.001);
        self::assertTrue($stats['record_broken_today']);
        $events = $this->get('/V1/cm-cube/events?since=' . $lastToday);
        self::assertSame($big, $events['events'][0]['entity_id']);
        self::assertTrue($events['events'][0]['record_broken']);
        self::assertFalse($events['events'][0]['first_of_day']);

        // --- Scope: excluded states and stores
        $this->magento("config:set cm_cube/general/excluded_states ''");
        $this->magento('cache:flush');
        self::assertSame(5, $this->get('/V1/cm-cube/stats')['today']['orders']);
        $this->magento('config:set cm_cube/general/excluded_states canceled');
        $this->magento('config:set cm_cube/general/store_ids 99');
        $this->magento('cache:flush');
        $stats = $this->get('/V1/cm-cube/stats');
        self::assertSame(0, $stats['today']['orders']);
        self::assertNull($stats['last_order'] ?? null, 'Magento < 2.4.7 omits null fields');
        self::assertSame([99], $stats['store_ids']);
        $this->magento("config:set cm_cube/general/store_ids ''");

        // --- Disabled API
        $this->magento('config:set cm_cube/general/enabled 0');
        $this->magento('cache:flush');
        self::assertSame(403, $this->call('/V1/cm-cube/stats')['status']);
        self::assertSame(403, $this->call('/V1/cm-cube/events?since=1')['status']);
        $this->magento('config:set cm_cube/general/enabled 1');
        $this->magento('config:set cm_cube/advanced/record_window_days 365');
        $this->magento('cache:flush');
    }

    private function resetOrders(): void
    {
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach (['sales_order_address', 'sales_order_grid', 'sales_order'] as $table) {
            self::$pdo->exec("DELETE FROM $table");
        }
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    private function order(
        \DateTimeImmutable $local,
        float $amount,
        int $qty,
        string $city,
        string $state = 'processing'
    ): int {
        $utc = $local->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $stmt = self::$pdo->prepare(
            'INSERT INTO sales_order (state, status, store_id, base_grand_total, grand_total, total_qty_ordered,
                base_currency_code, order_currency_code, customer_is_guest, customer_email, customer_firstname,
                customer_lastname, created_at, updated_at)
             VALUES (?, ?, 1, ?, ?, ?, \'EUR\', \'EUR\', 1, \'secret@example.com\', \'Jane\', \'Secretname\', ?, ?)'
        );
        $stmt->execute([$state, $state, $amount, $amount, $qty, $utc, $utc]);
        $id = (int) self::$pdo->lastInsertId();
        self::$pdo->prepare('UPDATE sales_order SET increment_id = ? WHERE entity_id = ?')
            ->execute([sprintf('T%08d', $id), $id]);
        self::$pdo->prepare(
            "INSERT INTO sales_order_address (parent_id, address_type, city, country_id, firstname, lastname, street, postcode)
             VALUES (?, 'shipping', ?, 'FR', 'Jane', 'Secretname', '1 Private Street', '75001')"
        )->execute([$id, $city]);
        return $id;
    }

    private function magento(string $command): void
    {
        // phpcs:ignore Magento2.Security.InsecureFunction -- end-to-end test, fixed command
        exec('cd ' . escapeshellarg(self::$magentoRoot) . ' && php bin/magento ' . $command . ' 2>&1', $out, $code);
        self::assertSame(0, $code, "bin/magento $command\n" . implode("\n", $out));
    }

    private function get(string $path): array
    {
        $response = $this->call($path);
        self::assertSame(200, $response['status'], $path . ' : ' . $response['body']);
        $json = json_decode($response['body'], true);
        self::assertIsArray($json);
        self::assertStringNotContainsString('secret@example.com', $response['body']);
        self::assertStringNotContainsString('Secretname', $response['body']);
        self::assertStringNotContainsString('Private Street', $response['body']);
        return $json;
    }

    /**
     * @return array{status: int, body: string}
     */
    private function call(string $path, ?string $token = 'env'): array
    {
        $token = $token === 'env' ? (string) getenv('CUBE_TOKEN') : $token;
        $ch = curl_init(rtrim((string) getenv('CUBE_BASE_URL'), '/') . '/rest' . $path);
        $headers = ['Accept: application/json'];
        if ($token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => $headers, CURLOPT_TIMEOUT => 30]);
        $body = (string) curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        return ['status' => $status, 'body' => $body];
    }
}

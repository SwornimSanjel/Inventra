<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/stock_status.php';

class AIForecastModel
{
    private PDO $db;
    private const REQUIRED_DAYS = 7;
    private const DETAIL_LOOKBACK_DAYS = 30;
    private const DEFAULT_LEAD_TIME_DAYS = 7;
    private const DEMO_MOVEMENT_REFERENCE_PREFIXES = ['STK-TEST-%'];
    private const DEMO_MOVEMENT_FULL_NAMES = ['sample customer', 'sample supplier'];
    private const DEMO_MOVEMENT_NOTE_PATTERNS = [
        '%initial sample%',
        '%sample stock-%',
    ];

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connect();
        $this->ensureForecastTable();
        $this->ensureReorderMarksTable();
    }

    public function getForecastResponse(int $rangeDays = 7): array
    {
        $rangeDays = in_array($rangeDays, [7, 14, 30], true) ? $rangeDays : 7;
        $availableDays = $this->getAvailableMovementDays();

        if ($availableDays < self::REQUIRED_DAYS) {
            return [
                'status' => 'empty',
                'message' => 'No forecasting data yet',
                'required_days' => self::REQUIRED_DAYS,
                'available_days' => $availableDays,
                'selected_range' => $rangeDays,
            ];
        }

        $forecastDate = date('Y-m-d');
        $generatedForecasts = $this->generateForecasts($forecastDate);

        return array_merge([
            'status' => 'success',
            'forecast_date' => $forecastDate,
            'required_days' => self::REQUIRED_DAYS,
            'available_days' => $availableDays,
            'forecasts' => $generatedForecasts,
            'selected_range' => $rangeDays,
        ], $this->buildDashboardData($rangeDays, $generatedForecasts));
    }

    public function getProductDetailResponse(int $productId): array
    {
        $product = $this->findProductById($productId);
        if ($product === null) {
            throw new RuntimeException('Product not found.');
        }

        $today = date('Y-m-d');
        $usageMetrics = $this->getProductUsageMetrics($productId);
        $forecast = $this->getForecastForProductOnDate($productId, $today);

        if ($forecast === null) {
            $forecast = $this->buildForecastForProduct($product, $today, $usageMetrics);
        }

        $dailyAverageUsage = $usageMetrics['daily_average_usage'];
        $weeklyAverageUsage = $usageMetrics['weekly_average_usage'];
        $currentStock = (int) $product['current_stock'];
        $leadTimeDays = self::DEFAULT_LEAD_TIME_DAYS;
        $runoutTimeDays = $dailyAverageUsage > 0 ? round($currentStock / $dailyAverageUsage, 2) : null;
        $confidencePercentage = $this->calculateConfidencePercentage(
            $usageMetrics['usage_days'],
            $usageMetrics['movement_days']
        );

        $riskLevel = $this->determineRiskLevel($runoutTimeDays, $dailyAverageUsage);
        $demandTrend = $this->determineDemandTrend($productId);
        $analysis = $this->buildAnalysisMessage($riskLevel, $product['product_name'], $runoutTimeDays, $dailyAverageUsage);
        $recommendation = $this->buildRecommendationMessage($riskLevel, (int) $forecast['suggested_reorder_quantity'], $leadTimeDays);
        $estimatedReorderCost = $this->buildEstimatedReorderCost(
            (int) $forecast['suggested_reorder_quantity'],
            (float) $product['unit_price']
        );

        return [
            'status' => 'success',
            'product' => [
                'product_id' => (int) $product['product_id'],
                'product_name' => (string) $product['product_name'],
                'sku' => null,
                'sku_note' => 'SKU is not available in the current products table.',
                'category' => (string) $product['category'],
                'stock_status' => $product['stock_status'],
                'current_stock' => $currentStock,
                'lower_limit' => (int) $product['lower_limit'],
                'upper_limit' => (int) $product['upper_limit'],
                'threshold' => (int) $product['lower_limit'] . '/' . (int) $product['upper_limit'],
            ],
            'stock_signals' => [
                'daily_average_usage' => $dailyAverageUsage,
                'weekly_average_usage' => $weeklyAverageUsage,
                'lead_time_days' => $leadTimeDays,
                'runout_time_days' => $runoutTimeDays,
                'confidence_percentage' => $confidencePercentage,
                'demand_trend' => $demandTrend,
            ],
            'ai_analysis' => [
                'risk_level' => $riskLevel,
                'analysis' => $analysis,
                'recommendation' => $recommendation,
            ],
            'reorder_data' => [
                'suggested_reorder_quantity' => (int) $forecast['suggested_reorder_quantity'],
                'estimated_reorder_cost' => $estimatedReorderCost['value'],
                'estimated_reorder_cost_note' => $estimatedReorderCost['note'],
                'forecast_date' => $forecast['forecast_date'],
                'predicted_demand' => (int) $forecast['predicted_demand'],
            ],
            'trend_data' => $this->buildTrendData($productId, $currentStock),
            'recent_movements' => $this->getRecentMovements($productId),
            'action_references' => [
                'view_product_url' => 'index.php?url=admin/products',
                'update_stock_url' => 'index.php?url=admin/stock-update',
                'mark_for_reorder_url' => 'index.php?url=admin/ai-forecasting/mark-reorder&id=' . (int) $product['product_id'],
            ],
        ];
    }

    public function getInsightPayloadForProduct(int $productId): array
    {
        $detail = $this->getProductDetailResponse($productId);
        if (($detail['status'] ?? 'error') !== 'success') {
            throw new RuntimeException('Forecast detail is unavailable for this product.');
        }

        $runoutDaysValue = $detail['stock_signals']['runout_time_days'] ?? null;

        return [
            'product_id' => (int) ($detail['product']['product_id'] ?? $productId),
            'product_name' => (string) ($detail['product']['product_name'] ?? ''),
            'current_stock' => (string) (int) ($detail['product']['current_stock'] ?? 0),
            'daily_usage' => number_format((float) ($detail['stock_signals']['daily_average_usage'] ?? 0), 2, '.', ''),
            'runout_days' => $runoutDaysValue === null ? 'N/A' : number_format((float) $runoutDaysValue, 2, '.', ''),
            'trend' => (string) ($detail['stock_signals']['demand_trend'] ?? 'Stable'),
            'suggested_reorder' => (string) (int) ($detail['reorder_data']['suggested_reorder_quantity'] ?? 0),
            'status' => ucfirst(str_replace('_', ' ', (string) ($detail['ai_analysis']['risk_level'] ?? 'no_data'))),
            'threshold' => (string) ($detail['product']['threshold'] ?? '0/0'),
        ];
    }

    public function markProductForReorder(int $productId, int $adminId): array
    {
        $product = $this->findProductById($productId);
        if ($product === null) {
            throw new RuntimeException('Product not found.');
        }

        $this->ensureReorderMarksTable();

        $stmt = $this->db->prepare(
            'INSERT INTO ai_reorder_marks (
                product_id,
                marked_by_admin_id,
                status,
                marked_at
            ) VALUES (?, ?, ?, CURRENT_TIMESTAMP)
            ON CONFLICT (product_id) DO UPDATE
            SET marked_by_admin_id = EXCLUDED.marked_by_admin_id,
                status = EXCLUDED.status,
                marked_at = CURRENT_TIMESTAMP
            RETURNING id, product_id, status, marked_at'
        );
        $stmt->execute([$productId, $adminId, 'marked']);
        $row = $stmt->fetch();

        return [
            'status' => 'success',
            'message' => 'Product marked for reorder successfully.',
            'reorder_mark' => [
                'id' => (int) ($row['id'] ?? 0),
                'product_id' => (int) ($row['product_id'] ?? $productId),
                'product_name' => (string) $product['product_name'],
                'status' => (string) ($row['status'] ?? 'marked'),
                'marked_at' => (string) ($row['marked_at'] ?? ''),
                'marked_by_admin_id' => $adminId,
            ],
        ];
    }

    private function ensureForecastTable(): void
    {
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS ai_forecasts (
                id INTEGER GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
                product_id INTEGER NOT NULL,
                current_stock INTEGER NOT NULL,
                predicted_demand INTEGER NOT NULL,
                suggested_reorder_quantity INTEGER NOT NULL,
                forecast_date DATE NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_ai_forecasts_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                CONSTRAINT uq_ai_forecasts_product_forecast_date UNIQUE (product_id, forecast_date)
            )'
        );

        $this->db->exec('CREATE INDEX IF NOT EXISTS idx_ai_forecasts_forecast_date ON ai_forecasts (forecast_date)');
    }

    private function ensureReorderMarksTable(): void
    {
        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS ai_reorder_marks (
                id INTEGER GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
                product_id INTEGER NOT NULL,
                marked_by_admin_id INTEGER NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'marked',
                marked_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_ai_reorder_marks_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                CONSTRAINT fk_ai_reorder_marks_admin FOREIGN KEY (marked_by_admin_id) REFERENCES admin(id) ON DELETE CASCADE,
                CONSTRAINT uq_ai_reorder_marks_product UNIQUE (product_id)
            )"
        );

        $this->db->exec('CREATE INDEX IF NOT EXISTS idx_ai_reorder_marks_status_marked_at ON ai_reorder_marks (status, marked_at)');
    }

    private function getAvailableMovementDays(): int
    {
        if (!Database::tableExists('stock_movements')) {
            return 0;
        }

        $stmt = $this->db->query(
            "SELECT COUNT(DISTINCT DATE(created_at))
             FROM stock_movements
             WHERE movement_type = 'out'
               AND quantity > 0
               AND created_at >= CURRENT_DATE - INTERVAL '29 days'
               AND " . $this->buildRealMovementCondition()
        );
        return (int) $stmt->fetchColumn();
    }

    private function getStoredForecastsForDate(string $forecastDate): array
    {
        $stmt = $this->db->prepare(
            'SELECT
                f.product_id,
                p.name AS product_name,
                f.current_stock,
                f.predicted_demand,
                f.suggested_reorder_quantity,
                f.forecast_date
            FROM ai_forecasts f
            INNER JOIN products p ON p.id = f.product_id
            WHERE f.forecast_date = ?
            ORDER BY p.name ASC'
        );
        $stmt->execute([$forecastDate]);

        return array_map([$this, 'mapForecastRow'], $stmt->fetchAll());
    }

    private function getForecastForProductOnDate(int $productId, string $forecastDate): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT
                product_id,
                current_stock,
                predicted_demand,
                suggested_reorder_quantity,
                forecast_date
            FROM ai_forecasts
            WHERE product_id = ? AND forecast_date = ?
            LIMIT 1'
        );
        $stmt->execute([$productId, $forecastDate]);
        $row = $stmt->fetch();

        return $row ? $this->mapForecastRow($row) : null;
    }

    private function generateForecasts(string $forecastDate): array
    {
        $stmt = $this->db->query(
            "SELECT
                p.id AS product_id,
                p.name AS product_name,
                COALESCE(p.qty, 0) AS current_stock,
                COALESCE(SUM(CASE WHEN sm.movement_type = 'out' THEN sm.quantity ELSE 0 END), 0) AS total_stock_out,
                COUNT(DISTINCT CASE WHEN sm.movement_type = 'out' THEN DATE(sm.created_at) END) AS stock_out_days
            FROM products p
            INNER JOIN stock_movements sm
                ON sm.product_id = p.id
               AND sm.created_at >= CURRENT_DATE - INTERVAL '29 days'
               AND " . $this->buildRealMovementCondition('sm') . "
            GROUP BY p.id, p.name, p.qty
            ORDER BY p.name ASC"
        );

        $rows = $stmt->fetchAll();
        if ($rows === []) {
            return [];
        }

        $insert = $this->db->prepare(
            'INSERT INTO ai_forecasts (
                product_id,
                current_stock,
                predicted_demand,
                suggested_reorder_quantity,
                forecast_date
            ) VALUES (?, ?, ?, ?, ?)
            ON CONFLICT (product_id, forecast_date) DO UPDATE
            SET current_stock = EXCLUDED.current_stock,
                predicted_demand = EXCLUDED.predicted_demand,
                suggested_reorder_quantity = EXCLUDED.suggested_reorder_quantity,
                created_at = CURRENT_TIMESTAMP'
        );

        $forecasts = [];
        $this->db->beginTransaction();

        try {
            foreach ($rows as $row) {
                $stockOutDays = max(0, (int) $row['stock_out_days']);
                $totalStockOut = max(0, (int) $row['total_stock_out']);
                $currentStock = max(0, (int) $row['current_stock']);

                $averageDailyStockOut = $stockOutDays > 0 ? ($totalStockOut / $stockOutDays) : 0;
                $predictedDemand = (int) round($averageDailyStockOut * 7);
                $suggestedReorderQuantity = max(0, $predictedDemand - $currentStock);

                $insert->execute([
                    (int) $row['product_id'],
                    $currentStock,
                    $predictedDemand,
                    $suggestedReorderQuantity,
                    $forecastDate,
                ]);

                $forecasts[] = [
                    'product_id' => (int) $row['product_id'],
                    'product_name' => (string) $row['product_name'],
                    'current_stock' => $currentStock,
                    'predicted_demand' => $predictedDemand,
                    'suggested_reorder_quantity' => $suggestedReorderQuantity,
                    'forecast_date' => $forecastDate,
                ];
            }

            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }

        return $forecasts;
    }

    private function mapForecastRow(array $row): array
    {
        return [
            'product_id' => (int) $row['product_id'],
            'product_name' => (string) ($row['product_name'] ?? ''),
            'current_stock' => (int) $row['current_stock'],
            'predicted_demand' => (int) $row['predicted_demand'],
            'suggested_reorder_quantity' => (int) $row['suggested_reorder_quantity'],
            'forecast_date' => (string) $row['forecast_date'],
        ];
    }

    private function findProductById(int $productId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT
                p.id AS product_id,
                p.name AS product_name,
                COALESCE(c.name, p.category, '') AS category,
                COALESCE(p.qty, 0) AS current_stock,
                COALESCE(p.lower_limit, 0) AS lower_limit,
                COALESCE(p.upper_limit, 0) AS upper_limit,
                COALESCE(p.unit_price, 0) AS unit_price
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE p.id = ?
            LIMIT 1"
        );
        $stmt->execute([$productId]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $row['stock_status'] = strtolower(str_replace(' ', '_', getStockStatus(
            (int) $row['current_stock'],
            (int) $row['lower_limit'],
            (int) $row['upper_limit']
        )));

        return $row;
    }

    private function getProductUsageMetrics(int $productId): array
    {
        if (!Database::tableExists('stock_movements')) {
            return [
                'total_stock_out' => 0,
                'usage_days' => 0,
                'movement_days' => 0,
                'daily_average_usage' => 0.0,
                'weekly_average_usage' => 0.0,
            ];
        }

        $stmt = $this->db->prepare(
            "SELECT
                COALESCE(SUM(CASE WHEN movement_type = 'out' THEN quantity ELSE 0 END), 0) AS total_stock_out,
                COUNT(DISTINCT CASE WHEN movement_type = 'out' THEN DATE(created_at) END) AS usage_days,
                COUNT(DISTINCT DATE(created_at)) AS movement_days
            FROM stock_movements
            WHERE product_id = ?
              AND created_at >= CURRENT_DATE - INTERVAL '29 days'
              AND " . $this->buildRealMovementCondition()
        );
        $stmt->execute([$productId]);
        $row = $stmt->fetch() ?: [];

        $totalStockOut = (int) ($row['total_stock_out'] ?? 0);
        $usageDays = (int) ($row['usage_days'] ?? 0);
        $movementDays = (int) ($row['movement_days'] ?? 0);
        $dailyAverageUsage = round($totalStockOut / self::DETAIL_LOOKBACK_DAYS, 2);
        $weeklyAverageUsage = round($dailyAverageUsage * 7, 2);

        return [
            'total_stock_out' => $totalStockOut,
            'usage_days' => $usageDays,
            'movement_days' => $movementDays,
            'daily_average_usage' => $dailyAverageUsage,
            'weekly_average_usage' => $weeklyAverageUsage,
        ];
    }

    private function buildForecastForProduct(array $product, string $forecastDate, array $usageMetrics): array
    {
        $predictedDemand = (int) round($usageMetrics['daily_average_usage'] * 7);
        $currentStock = (int) $product['current_stock'];
        $suggestedReorderQuantity = max(0, $predictedDemand - $currentStock);

        return [
            'product_id' => (int) $product['product_id'],
            'product_name' => (string) $product['product_name'],
            'current_stock' => $currentStock,
            'predicted_demand' => $predictedDemand,
            'suggested_reorder_quantity' => $suggestedReorderQuantity,
            'forecast_date' => $forecastDate,
        ];
    }

    private function determineDemandTrend(int $productId): string
    {
        if (!Database::tableExists('stock_movements')) {
            return 'No recent demand';
        }

        $stmt = $this->db->prepare(
            "SELECT
                COALESCE(SUM(CASE
                    WHEN movement_type = 'out' AND created_at >= CURRENT_DATE - INTERVAL '6 days' THEN quantity
                    ELSE 0
                END), 0) AS recent_out,
                COALESCE(SUM(CASE
                    WHEN movement_type = 'out'
                     AND created_at >= CURRENT_DATE - INTERVAL '13 days'
                    AND created_at < CURRENT_DATE - INTERVAL '6 days' THEN quantity
                    ELSE 0
                END), 0) AS previous_out
            FROM stock_movements
            WHERE product_id = ?
              AND " . $this->buildRealMovementCondition()
        );
        $stmt->execute([$productId]);
        $row = $stmt->fetch() ?: [];

        $recentOut = max(0, (int) ($row['recent_out'] ?? 0));
        $previousOut = max(0, (int) ($row['previous_out'] ?? 0));

        if ($recentOut === 0 && $previousOut === 0) {
            return 'No recent demand';
        }

        if ($previousOut === 0 && $recentOut > 0) {
            return 'Increasing';
        }

        $deltaRatio = $previousOut > 0 ? (($recentOut - $previousOut) / $previousOut) : 0.0;
        if ($deltaRatio >= 0.2) {
            return 'Increasing';
        }

        if ($deltaRatio <= -0.2) {
            return 'Declining';
        }

        return 'Stable';
    }

    private function calculateConfidencePercentage(int $usageDays, int $movementDays): int
    {
        if ($usageDays <= 0) {
            return 0;
        }

        $usageCoverage = min(1, $usageDays / self::DETAIL_LOOKBACK_DAYS);
        $movementCoverage = min(1, max($usageDays, $movementDays) / self::DETAIL_LOOKBACK_DAYS);

        return (int) round((($usageCoverage * 0.7) + ($movementCoverage * 0.3)) * 100);
    }

    private function determineRiskLevel(?float $runoutTimeDays, float $dailyAverageUsage): string
    {
        if ($dailyAverageUsage <= 0 || $runoutTimeDays === null) {
            return 'no_data';
        }

        if ($runoutTimeDays <= 3) {
            return 'critical';
        }

        if ($runoutTimeDays <= 7) {
            return 'warning';
        }

        return 'stable';
    }

    private function buildAnalysisMessage(string $riskLevel, string $productName, ?float $runoutTimeDays, float $dailyAverageUsage): string
    {
        if ($riskLevel === 'no_data') {
            return 'No stock-out usage trend is available for ' . $productName . ' in the past 30 days.';
        }

        return sprintf(
            '%s is trending at an average daily usage of %.2f units and may run out in %.2f days.',
            $productName,
            $dailyAverageUsage,
            (float) $runoutTimeDays
        );
    }

    private function buildRecommendationMessage(string $riskLevel, int $suggestedReorderQuantity, int $leadTimeDays): string
    {
        if ($riskLevel === 'critical') {
            return 'Reorder immediately. Current runout risk is inside the lead time window of ' . $leadTimeDays . ' days.';
        }

        if ($riskLevel === 'warning') {
            return 'Prepare a replenishment order soon and monitor stock movement daily.';
        }

        if ($riskLevel === 'stable') {
            return $suggestedReorderQuantity > 0
                ? 'Stock is currently stable, but scheduling a reorder will help prevent future shortages.'
                : 'Stock is stable and no immediate reorder is suggested right now.';
        }

        return 'Collect more stock-out movement history before relying on forecasting recommendations.';
    }

    private function buildEstimatedReorderCost(int $suggestedReorderQuantity, float $unitPrice): array
    {
        if ($unitPrice <= 0) {
            return [
                'value' => null,
                'note' => 'Estimated reorder cost is unavailable because the product unit price is missing or zero.',
            ];
        }

        return [
            'value' => round($suggestedReorderQuantity * $unitPrice, 2),
            'note' => null,
        ];
    }

    private function buildTrendData(int $productId, int $currentStock): array
    {
        if (!Database::tableExists('stock_movements')) {
            return [
                'has_history' => false,
                'available_days' => 0,
                'series' => [],
            ];
        }

        $stmt = $this->db->prepare(
            "SELECT
                DATE(created_at) AS movement_date,
                COALESCE(SUM(CASE WHEN movement_type = 'in' THEN quantity ELSE 0 END), 0) AS stock_in,
                COALESCE(SUM(CASE WHEN movement_type = 'out' THEN quantity ELSE 0 END), 0) AS stock_out
            FROM stock_movements
            WHERE product_id = ?
              AND created_at >= CURRENT_DATE - INTERVAL '29 days'
              AND " . $this->buildRealMovementCondition() . "
            GROUP BY DATE(created_at)
            ORDER BY movement_date ASC"
        );
        $stmt->execute([$productId]);

        $movementMap = [];
        foreach ($stmt->fetchAll() as $row) {
            $movementMap[(string) $row['movement_date']] = [
                'stock_in' => (int) $row['stock_in'],
                'stock_out' => (int) $row['stock_out'],
            ];
        }

        if ($movementMap === []) {
            return [
                'has_history' => false,
                'available_days' => 0,
                'series' => [],
            ];
        }

        $dates = [];
        $movementDates = array_keys($movementMap);
        sort($movementDates);
        $startDate = new DateTimeImmutable($movementDates[0]);
        $today = new DateTimeImmutable('today');
        $daysToRender = max(1, min(self::DETAIL_LOOKBACK_DAYS, (int) $startDate->diff($today)->days + 1));

        for ($offset = 0; $offset < $daysToRender; $offset++) {
            if ($offset === 0) {
                $dates[] = $startDate->format('Y-m-d');
                continue;
            }

            $dates[] = $startDate->modify('+' . $offset . ' days')->format('Y-m-d');
        }

        $descending = array_reverse($dates);
        $closingStock = $currentStock;
        $trendDescending = [];

        foreach ($descending as $date) {
            $stockIn = $movementMap[$date]['stock_in'] ?? 0;
            $stockOut = $movementMap[$date]['stock_out'] ?? 0;

            $trendDescending[] = [
                'date' => $date,
                'stock_level' => max(0, $closingStock),
                'stock_in' => $stockIn,
                'stock_out' => $stockOut,
            ];

            $closingStock = $closingStock - $stockIn + $stockOut;
        }

        return [
            'has_history' => true,
            'available_days' => count($movementMap),
            'series' => array_reverse($trendDescending),
        ];
    }

    private function getRecentMovements(int $productId): array
    {
        if (!Database::tableExists('stock_movements')) {
            return [];
        }

        $stmt = $this->db->prepare(
            "SELECT
                reference,
                movement_type,
                quantity,
                COALESCE(full_name, '') AS full_name,
                created_at
            FROM stock_movements
            WHERE product_id = ?
              AND " . $this->buildRealMovementCondition() . "
            ORDER BY created_at DESC, id DESC
            LIMIT 10"
        );
        $stmt->execute([$productId]);

        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = [
                'reference' => (string) $row['reference'],
                'movement_type' => (string) $row['movement_type'],
                'quantity' => (int) $row['quantity'],
                'full_name' => (string) $row['full_name'],
                'created_at' => (string) $row['created_at'],
            ];
        }

        return $rows;
    }

    private function buildEmptyTrendData(int $currentStock): array
    {
        $dates = [];
        $start = new DateTimeImmutable('-29 days');
        for ($offset = 0; $offset < self::DETAIL_LOOKBACK_DAYS; $offset++) {
            $dates[] = $start->modify('+' . $offset . ' days')->format('Y-m-d');
        }

        return [
            'has_history' => false,
            'available_days' => 0,
            'series' => array_map(function (string $date) use ($currentStock): array {
                return [
                    'date' => $date,
                    'stock_level' => $currentStock,
                    'stock_in' => 0,
                    'stock_out' => 0,
                ];
            }, $dates),
        ];
    }

    private function buildDashboardData(int $rangeDays, array $forecasts): array
    {
        $products = $this->getDashboardProducts($rangeDays, $forecasts);
        $categories = $this->extractCategories($products);
        $summary = $this->buildDashboardSummary($products);
        $insights = $this->buildDashboardInsights($products, $summary, $rangeDays);
        $chartItems = $this->buildChartItems($products);

        return [
            'filters' => [
                'selected_range' => $rangeDays,
                'available_ranges' => [7, 14, 30],
            ],
            'summary_cards' => $summary,
            'insights' => $insights,
            'chart' => [
                'title' => 'Demand Forecast - Next ' . $rangeDays . ' Days',
                'subtitle' => 'Days remaining',
                'items' => $chartItems,
            ],
            'recommendations' => $products,
            'recommendation_count' => count($products),
            'categories' => $categories,
        ];
    }

    private function getDashboardProducts(int $rangeDays, array $forecasts): array
    {
        $forecastMap = [];
        foreach ($forecasts as $forecast) {
            $forecastMap[(int) $forecast['product_id']] = $forecast;
        }

        $sinceDate = (new DateTimeImmutable())
            ->setTime(0, 0, 0)
            ->modify('-' . max(0, self::DETAIL_LOOKBACK_DAYS - 1) . ' days')
            ->format('Y-m-d H:i:s');

        $sql = "
            SELECT
                p.id AS product_id,
                p.name AS product_name,
                COALESCE(c.name, p.category, '') AS category,
                COALESCE(p.qty, 0) AS current_stock,
                COALESCE(p.lower_limit, 0) AS lower_limit,
                COALESCE(p.upper_limit, 0) AS upper_limit,
                COALESCE(p.unit_price, 0) AS unit_price,
                COALESCE(SUM(CASE WHEN sm.movement_type = 'out' THEN sm.quantity ELSE 0 END), 0) AS total_stock_out,
                COALESCE(SUM(CASE WHEN sm.movement_type = 'in' THEN sm.quantity ELSE 0 END), 0) AS total_stock_in,
                COUNT(DISTINCT CASE WHEN sm.movement_type = 'out' THEN DATE(sm.created_at) END) AS usage_days
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN stock_movements sm
                ON sm.product_id = p.id
               AND sm.created_at >= ?
               AND " . $this->buildRealMovementCondition('sm') . "
            GROUP BY
                p.id,
                p.name,
                c.name,
                p.category,
                p.qty,
                p.lower_limit,
                p.upper_limit,
                p.unit_price
            ORDER BY p.name ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sinceDate]);

        $reorderMarks = $this->getReorderMarksMap();
        $products = [];

        foreach ($stmt->fetchAll() as $row) {
            $productId = (int) $row['product_id'];
            $currentStock = max(0, (int) $row['current_stock']);
            $lowerLimit = max(0, (int) $row['lower_limit']);
            $upperLimit = max(0, (int) $row['upper_limit']);
            $totalStockOut = max(0, (int) $row['total_stock_out']);
            $dailyUse = round($totalStockOut / self::DETAIL_LOOKBACK_DAYS, 1);
            $runoutDays = $dailyUse > 0 ? round($currentStock / $dailyUse, 1) : null;
            $baseStatus = strtolower(str_replace(' ', '_', getStockStatus($currentStock, $lowerLimit, $upperLimit)));
            $rangeForecast = $this->buildForecastForProduct($row, date('Y-m-d'), [
                'daily_average_usage' => $dailyUse,
            ]);
            $rangeForecast['predicted_demand'] = (int) round($dailyUse * $rangeDays);
            $rangeForecast['suggested_reorder_quantity'] = max(0, $rangeForecast['predicted_demand'] - $currentStock);
            $forecast = $forecastMap[$productId] ?? $rangeForecast;
            $status = $this->resolveDashboardStatus($baseStatus, $runoutDays);
            $recommendation = $this->buildDashboardRecommendation($status, (int) $rangeForecast['suggested_reorder_quantity']);
            $threshold = $lowerLimit . '/' . $upperLimit;
            $projectedRangeDemand = (int) round($dailyUse * $rangeDays);

            $products[] = [
                'product_id' => $productId,
                'product_name' => (string) $row['product_name'],
                'category' => (string) ($row['category'] !== '' ? $row['category'] : 'Uncategorized'),
                'current_stock' => $currentStock,
                'daily_use' => $dailyUse,
                'runout_days' => $runoutDays,
                'runout_label' => $this->formatRunoutLabel($runoutDays),
                'ai_recommendation' => $recommendation,
                'status' => $status,
                'status_label' => ucfirst($status),
                'threshold' => $threshold,
                'lower_limit' => $lowerLimit,
                'upper_limit' => $upperLimit,
                'predicted_demand' => $projectedRangeDemand,
                'suggested_reorder_quantity' => (int) $rangeForecast['suggested_reorder_quantity'],
                'estimated_reorder_cost' => $this->buildEstimatedReorderCost(
                    (int) $rangeForecast['suggested_reorder_quantity'],
                    (float) ($row['unit_price'] ?? 0)
                )['value'],
                'projected_range_demand' => $projectedRangeDemand,
                'usage_days' => (int) ($row['usage_days'] ?? 0),
                'stock_in' => (int) ($row['total_stock_in'] ?? 0),
                'reorder_marked' => isset($reorderMarks[$productId]),
                'reorder_mark' => $reorderMarks[$productId] ?? null,
                'detail_url' => 'index.php?url=admin/ai-forecasting/product-detail&id=' . $productId,
                'mark_reorder_url' => 'index.php?url=admin/ai-forecasting/mark-reorder&id=' . $productId,
            ];
        }

        usort($products, function (array $left, array $right): int {
            $priority = ['critical' => 0, 'warning' => 1, 'stable' => 2, 'overstock' => 3];
            $leftPriority = $priority[$left['status']] ?? 9;
            $rightPriority = $priority[$right['status']] ?? 9;

            if ($leftPriority !== $rightPriority) {
                return $leftPriority <=> $rightPriority;
            }

            return strcmp($left['product_name'], $right['product_name']);
        });

        return $products;
    }

    private function getReorderMarksMap(): array
    {
        if (!Database::tableExists('ai_reorder_marks')) {
            return [];
        }

        $stmt = $this->db->query(
            'SELECT product_id, status, marked_at, marked_by_admin_id
             FROM ai_reorder_marks'
        );

        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[(int) $row['product_id']] = [
                'status' => (string) $row['status'],
                'marked_at' => (string) $row['marked_at'],
                'marked_by_admin_id' => (int) $row['marked_by_admin_id'],
            ];
        }

        return $map;
    }

    private function resolveDashboardStatus(string $baseStatus, ?float $runoutDays): string
    {
        if ($baseStatus === 'overstocked') {
            return 'overstock';
        }

        if ($runoutDays !== null && $runoutDays <= 3) {
            return 'critical';
        }

        if ($runoutDays !== null && $runoutDays <= 7) {
            return 'warning';
        }

        return 'stable';
    }

    private function buildDashboardRecommendation(string $status, int $suggestedReorderQuantity): string
    {
        if ($status === 'critical') {
            return 'Reorder immediately';
        }

        if ($status === 'warning') {
            return $suggestedReorderQuantity > 0 ? 'Reorder soon' : 'Monitor demand';
        }

        if ($status === 'overstock') {
            return 'Overstock risk';
        }

        return $suggestedReorderQuantity > 0 ? 'Monitor demand' : 'Stable inventory';
    }

    private function formatRunoutLabel(?float $runoutDays): string
    {
        if ($runoutDays === null) {
            return 'N/A';
        }

        if ($runoutDays > 180) {
            return '180+ days';
        }

        if ($runoutDays === 0.0) {
            return '0 days';
        }

        $rounded = (abs($runoutDays - round($runoutDays)) < 0.05) ? (string) ((int) round($runoutDays)) : number_format($runoutDays, 1);
        return $rounded . ' days';
    }

    private function extractCategories(array $products): array
    {
        $categories = [];
        foreach ($products as $product) {
            $categories[] = (string) $product['category'];
        }

        $categories = array_values(array_unique($categories));
        sort($categories);

        return $categories;
    }

    private function buildDashboardSummary(array $products): array
    {
        $criticalCount = 0;
        $reorderSoonCount = 0;
        $fastMovingCount = 0;
        $overstockCount = 0;
        $averageDailyUse = 0.0;
        $withUsage = 0;

        foreach ($products as $product) {
            if ($product['status'] === 'critical') {
                $criticalCount++;
            }

            if (in_array($product['status'], ['critical', 'warning'], true) || $product['suggested_reorder_quantity'] > 0) {
                $reorderSoonCount++;
            }

            if ($product['status'] === 'overstock') {
                $overstockCount++;
            }

            if ($product['daily_use'] > 0) {
                $averageDailyUse += $product['daily_use'];
                $withUsage++;
            }
        }

        $averageDailyUse = $withUsage > 0 ? $averageDailyUse / $withUsage : 0.0;

        foreach ($products as $product) {
            if ($product['daily_use'] > 0 && $product['daily_use'] >= $averageDailyUse && $averageDailyUse > 0) {
                $fastMovingCount++;
            }
        }

        return [
            [
                'key' => 'stockout_risk',
                'label' => 'Stockout Risk',
                'count' => $criticalCount,
                'meta' => $criticalCount > 0 ? 'Critical action required' : 'No urgent stockout risk',
                'tone' => 'critical',
                'icon' => 'alert',
            ],
            [
                'key' => 'reorder_soon',
                'label' => 'Reorder Soon',
                'count' => $reorderSoonCount,
                'meta' => $reorderSoonCount > 0 ? 'Items need replenishment review' : 'No reorder action pending',
                'tone' => 'warning',
                'icon' => 'clock',
            ],
            [
                'key' => 'fast_moving',
                'label' => 'Fast-Moving',
                'count' => $fastMovingCount,
                'meta' => $fastMovingCount > 0 ? 'Above recent usage average' : 'No accelerated movers right now',
                'tone' => 'success',
                'icon' => 'trend',
            ],
            [
                'key' => 'overstock_risk',
                'label' => 'Overstock Risk',
                'count' => $overstockCount,
                'meta' => $overstockCount > 0 ? 'Potential capital lock' : 'Overstock exposure is limited',
                'tone' => 'neutral',
                'icon' => 'inventory',
            ],
        ];
    }

    private function buildDashboardInsights(array $products, array $summaryCards, int $rangeDays): array
    {
        $criticalProducts = array_values(array_filter($products, static fn(array $product): bool => $product['status'] === 'critical'));
        $warningProducts = array_values(array_filter($products, static fn(array $product): bool => $product['status'] === 'warning'));
        $overstockProducts = array_values(array_filter($products, static fn(array $product): bool => $product['status'] === 'overstock'));

        usort($products, static fn(array $left, array $right): int => $right['daily_use'] <=> $left['daily_use']);
        $topMover = $products[0] ?? null;

        $insights = [];

        if ($topMover !== null && $topMover['daily_use'] > 0) {
            $insights[] = [
                'tone' => 'neutral',
                'title' => 'Fast mover trend',
                'message' => $topMover['product_name'] . ' is averaging ' . number_format((float) $topMover['daily_use'], 1) . ' units/day over the last ' . self::DETAIL_LOOKBACK_DAYS . ' days.',
            ];
        }

        if ($criticalProducts !== []) {
            $critical = $criticalProducts[0];
            $insights[] = [
                'tone' => 'warning',
                'title' => 'Urgent reorder signal',
                'message' => $critical['product_name'] . ' may run out in ' . strtolower($critical['runout_label']) . ' and should be reviewed immediately.',
            ];
        }

        if ($overstockProducts !== []) {
            $overstock = $overstockProducts[0];
            $insights[] = [
                'tone' => 'success',
                'title' => 'Overstock optimization',
                'message' => $overstock['product_name'] . ' is above its threshold. Slowing replenishment could free up storage capacity.',
            ];
        }

        if ($warningProducts !== [] && count($insights) < 3) {
            $warning = $warningProducts[0];
            $insights[] = [
                'tone' => 'warning',
                'title' => 'Demand watch',
                'message' => $warning['product_name'] . ' is nearing its runout window and should be monitored for reorder timing.',
            ];
        }

        while (count($insights) < 3) {
            $stableCount = 0;
            foreach ($summaryCards as $summaryCard) {
                if ($summaryCard['key'] === 'fast_moving') {
                    $stableCount = (int) $summaryCard['count'];
                    break;
                }
            }

            $insights[] = [
                'tone' => 'neutral',
                'title' => 'Coverage summary',
                'message' => $stableCount > 0
                    ? $stableCount . ' products are moving faster than their recent average and should stay on the demand watchlist.'
                    : 'Forecasting coverage is active. Continue recording stock movements to strengthen trend confidence.',
            ];
        }

        return array_slice($insights, 0, 3);
    }

    private function buildChartItems(array $products): array
    {
        $chartCandidates = array_values(array_filter($products, static fn(array $product): bool => $product['projected_range_demand'] > 0 || $product['runout_days'] !== null));

        usort($chartCandidates, function (array $left, array $right): int {
            $leftRunout = $left['runout_days'] ?? 999999;
            $rightRunout = $right['runout_days'] ?? 999999;

            if ($leftRunout !== $rightRunout) {
                return $leftRunout <=> $rightRunout;
            }

            return $right['projected_range_demand'] <=> $left['projected_range_demand'];
        });

        $chartItems = array_slice($chartCandidates, 0, 5);
        $maxDemand = 1;
        foreach ($chartItems as $item) {
            $maxDemand = max($maxDemand, (int) $item['projected_range_demand']);
        }

        foreach ($chartItems as &$item) {
            $item['bar_percent'] = max(10, (int) round(((int) $item['projected_range_demand'] / $maxDemand) * 100));
        }
        unset($item);

        return $chartItems;
    }

    private function buildRealMovementCondition(string $alias = ''): string
    {
        $prefix = $alias !== '' ? $alias . '.' : '';
        $referenceColumn = $prefix . 'reference';
        $notesColumn = 'LOWER(COALESCE(' . $prefix . 'notes, \'\'))';
        $fullNameColumn = 'LOWER(COALESCE(' . $prefix . 'full_name, \'\'))';

        $conditions = [];

        foreach (self::DEMO_MOVEMENT_REFERENCE_PREFIXES as $pattern) {
            $conditions[] = $referenceColumn . " NOT LIKE '" . $pattern . "'";
        }

        foreach (self::DEMO_MOVEMENT_NOTE_PATTERNS as $pattern) {
            $conditions[] = $notesColumn . " NOT LIKE '" . strtolower($pattern) . "'";
        }

        if (self::DEMO_MOVEMENT_FULL_NAMES !== []) {
            $quotedNames = array_map(
                static fn (string $name): string => "'" . str_replace("'", "''", $name) . "'",
                self::DEMO_MOVEMENT_FULL_NAMES
            );
            $conditions[] = $fullNameColumn . ' NOT IN (' . implode(', ', $quotedNames) . ')';
        }

        return implode(' AND ', $conditions);
    }
}

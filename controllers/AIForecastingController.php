<?php

require_once __DIR__ . '/../models/AdminSession.php';
require_once __DIR__ . '/../models/AIForecastModel.php';
require_once __DIR__ . '/../services/GeminiInsightService.php';
require_once __DIR__ . '/../helpers/session.php';

class AIForecastingController
{
    private AdminSession $adminSession;
    private AIForecastModel $aiForecastModel;
    private GeminiInsightService $geminiInsightService;

    public function __construct()
    {
        $this->adminSession = new AdminSession();
        $this->aiForecastModel = new AIForecastModel();
        $this->geminiInsightService = new GeminiInsightService();
    }

    public function show(): void
    {
        $admin = $this->adminSession->requireAuthenticatedAdmin();
        $mockEmpty = isset($_GET['mock_empty']) && $_GET['mock_empty'] === '1';
        $forecastApiUrl = 'index.php?url=admin/ai-forecasting/data';
        if ($mockEmpty) {
            $forecastApiUrl .= '&mock_empty=1';
        }

        $forecastingPageState = [
            'current_admin' => $admin,
            'forecast_api_url' => $forecastApiUrl,
            'product_detail_api_url' => 'index.php?url=admin/ai-forecasting/product-detail',
            'generate_insight_api_url' => 'index.php?url=admin/ai-forecasting/generate-insight',
        ];

        $url = 'admin/ai-forecasting';
        require __DIR__ . '/../views/layout/shell.php';
    }

    public function getForecastData(): void
    {
        $this->adminSession->requireAuthenticatedAdmin();
        $rangeDays = isset($_GET['range']) && is_numeric($_GET['range']) ? (int) $_GET['range'] : 14;
        $forceEmpty = isset($_GET['mock_empty']) && $_GET['mock_empty'] === '1';

        header('Content-Type: application/json');
        echo json_encode($this->aiForecastModel->getForecastResponse($rangeDays, $forceEmpty));
        exit;
    }

    public function getProductDetail(): void
    {
        $this->adminSession->requireAuthenticatedAdmin();

        $productId = $this->resolveProductId();
        if ($productId === null) {
            return;
        }

        try {
            $response = $this->aiForecastModel->getProductDetailResponse($productId);
            $this->jsonResponse($response);
        } catch (Throwable $exception) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ], 404);
        }
    }

    public function markReorder(): void
    {
        $admin = $this->adminSession->requireAuthenticatedAdmin();

        $productId = $this->resolveProductId();
        if ($productId === null) {
            return;
        }

        try {
            $response = $this->aiForecastModel->markProductForReorder($productId, (int) $admin['id']);
            $this->jsonResponse($response);
        } catch (Throwable $exception) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ], 404);
        }
    }

    public function generateInsight(): void
    {
        $this->adminSession->requireAuthenticatedAdmin();

        $productId = $this->resolveProductIdFromRequest();
        if ($productId === null) {
            return;
        }

        try {
            $forecastResult = $this->aiForecastModel->getInsightPayloadForProduct($productId);
            $insightResult = $this->geminiInsightService->generateInsight($forecastResult);

            $this->jsonResponse([
                'success' => true,
                'source' => $insightResult['source'],
                'insight' => $insightResult['insight'],
            ]);
        } catch (Throwable $exception) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ], 404);
        }
    }

    private function resolveProductId(): ?int
    {
        if (!isset($_GET['id']) || trim((string) $_GET['id']) === '') {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Product ID is required.',
            ], 400);
            return null;
        }

        if (!is_numeric($_GET['id'])) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Product ID must be numeric.',
            ], 400);
            return null;
        }

        $productId = (int) $_GET['id'];
        if ($productId <= 0) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Invalid product ID.',
            ], 400);
            return null;
        }

        return $productId;
    }

    private function resolveProductIdFromRequest(): ?int
    {
        $requestData = [];
        $rawInput = file_get_contents('php://input');
        if (is_string($rawInput) && trim($rawInput) !== '') {
            $decoded = json_decode($rawInput, true);
            if (is_array($decoded)) {
                $requestData = $decoded;
            }
        }

        $candidate = $requestData['product_id']
            ?? $requestData['id']
            ?? ($requestData['selected_row']['product_id'] ?? null)
            ?? $_POST['product_id']
            ?? $_POST['id']
            ?? $_GET['product_id']
            ?? $_GET['id']
            ?? null;

        if ($candidate === null || trim((string) $candidate) === '') {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Product ID is required.',
            ], 400);
            return null;
        }

        if (!is_numeric($candidate)) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Product ID must be numeric.',
            ], 400);
            return null;
        }

        $productId = (int) $candidate;
        if ($productId <= 0) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Invalid product ID.',
            ], 400);
            return null;
        }

        return $productId;
    }

    private function jsonResponse(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit;
    }
}

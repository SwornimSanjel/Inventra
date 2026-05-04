<?php

require_once __DIR__ . '/../models/AdminSession.php';
require_once __DIR__ . '/../models/AIForecastModel.php';
require_once __DIR__ . '/../helpers/session.php';

class AIForecastingController
{
    private AdminSession $adminSession;
    private AIForecastModel $aiForecastModel;

    public function __construct()
    {
        $this->adminSession = new AdminSession();
        $this->aiForecastModel = new AIForecastModel();
    }

    public function show(): void
    {
        $admin = $this->adminSession->requireAuthenticatedAdmin();

        $forecastingPageState = [
            'current_admin' => $admin,
            'forecast_api_url' => 'index.php?url=admin/ai-forecasting/data',
        ];

        $url = 'admin/ai-forecasting';
        require __DIR__ . '/../views/layout/shell.php';
    }

    public function getForecastData(): void
    {
        $this->adminSession->requireAuthenticatedAdmin();
        $rangeDays = isset($_GET['range']) && is_numeric($_GET['range']) ? (int) $_GET['range'] : 14;

        header('Content-Type: application/json');
        echo json_encode($this->aiForecastModel->getForecastResponse($rangeDays));
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
        } catch (RuntimeException $exception) {
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
        } catch (RuntimeException $exception) {
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

    private function jsonResponse(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit;
    }
}

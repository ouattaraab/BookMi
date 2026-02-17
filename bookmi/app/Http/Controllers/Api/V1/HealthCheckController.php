<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;

class HealthCheckController extends BaseController
{
    public function __invoke(): JsonResponse
    {
        return $this->successResponse([
            'status' => 'ok',
            'version' => '1.0.0',
        ]);
    }
}

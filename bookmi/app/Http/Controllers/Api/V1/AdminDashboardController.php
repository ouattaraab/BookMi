<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\AdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDashboardController extends BaseController
{
    public function __construct(private readonly AdminService $admin)
    {
    }

    /**
     * GET /api/v1/admin/dashboard
     */
    public function index(Request $request): JsonResponse
    {
        return $this->successResponse($this->admin->dashboardStats());
    }
}

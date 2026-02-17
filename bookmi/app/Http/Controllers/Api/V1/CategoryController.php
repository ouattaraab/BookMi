<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends BaseController
{
    public function index(): JsonResponse
    {
        $categories = Category::roots()->with('children')->get();

        return $this->successResponse(
            CategoryResource::collection($categories),
        );
    }
}

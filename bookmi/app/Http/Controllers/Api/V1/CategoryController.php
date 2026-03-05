<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class CategoryController extends BaseController
{
    public function index(): JsonResponse
    {
        $categories = Cache::rememberForever('categories.tree', function () {
            return Category::roots()->with('children')->get();
        });

        return $this->successResponse(
            CategoryResource::collection($categories),
        );
    }
}

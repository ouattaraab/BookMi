<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponseTrait
{
    protected function successResponse(mixed $data, int $statusCode = 200, array $meta = []): JsonResponse
    {
        $response = ['data' => $data];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    protected function errorResponse(
        string $code,
        string $message,
        int $statusCode = 422,
        array $details = [],
    ): JsonResponse {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
                'status' => $statusCode,
                'details' => $details ?: new \stdClass(),
            ],
        ], $statusCode);
    }

    protected function paginatedResponse(CursorPaginator|LengthAwarePaginator $paginator): JsonResponse
    {
        $meta = [];

        if ($paginator instanceof LengthAwarePaginator) {
            $meta = [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'has_more' => $paginator->hasMorePages(),
            ];
        }

        if ($paginator instanceof CursorPaginator) {
            $meta = [
                'next_cursor' => $paginator->nextCursor()?->encode(),
                'prev_cursor' => $paginator->previousCursor()?->encode(),
                'per_page' => $paginator->perPage(),
                'has_more' => $paginator->hasMorePages(),
            ];
        }

        return response()->json([
            'data' => $paginator->items(),
            'meta' => $meta,
        ]);
    }
}

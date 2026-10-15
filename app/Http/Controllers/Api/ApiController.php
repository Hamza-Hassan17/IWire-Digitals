<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Every API response uses the envelope { "success": bool, "message": string, "data": ... }.
 */
abstract class ApiController extends Controller
{
    protected function ok(mixed $data = null, string $message = 'OK', int $status = 200, array $extra = []): JsonResponse
    {
        return response()->json(array_merge(['success' => true, 'message' => $message, 'data' => $data], $extra), $status);
    }

    protected function fail(string $message, int $status, array $extra = []): JsonResponse
    {
        return response()->json(array_merge(['success' => false, 'message' => $message], $extra), $status);
    }

    protected function paginated(LengthAwarePaginator $page, callable $map, string $message = 'OK'): JsonResponse
    {
        return $this->ok($page->getCollection()->map($map)->values(), $message, 200, ['meta' => [
            'current_page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
            'per_page' => $page->perPage(),
            'total' => $page->total(),
        ]]);
    }
}

<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponse
{
    /**
     * Return a standardized success JSON response.
     *
     * @param  mixed  $data
     * @param  string  $message
     * @param  int  $statusCode
     * @param  array<string, mixed>  $meta
     */
    protected function success(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = 200,
        array $meta = []
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if (! empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a paginated success JSON response.
     *
     * @param  LengthAwarePaginator  $paginator
     * @param  string  $message
     * @param  int  $statusCode
     */
    protected function paginated(
        LengthAwarePaginator $paginator,
        string $message = 'Data retrieved successfully',
        int $statusCode = 200
    ): JsonResponse {
        return $this->success(
            $paginator->items(),
            $message,
            $statusCode,
            [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ]
        );
    }

    /**
     * Return a 201 Created response.
     *
     * @param  mixed  $data
     * @param  string  $message
     */
    protected function created(mixed $data = null, string $message = 'Resource created successfully'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    /**
     * Return a 204 No Content response.
     *
     * @param  string  $message
     */
    protected function noContent(string $message = 'No content'): JsonResponse
    {
        return $this->success(null, $message, 204);
    }

    /**
     * Return a standardized error JSON response.
     *
     * @param  string  $message
     * @param  int  $statusCode
     * @param  array<string, mixed>|null  $errors
     */
    protected function error(
        string $message = 'An error occurred',
        int $statusCode = 400,
        ?array $errors = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a 404 Not Found response.
     *
     * @param  string  $message
     */
    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error($message, 404);
    }

    /**
     * Return a 403 Forbidden response.
     *
     * @param  string  $message
     */
    protected function forbidden(string $message = 'This action is unauthorized'): JsonResponse
    {
        return $this->error($message, 403);
    }

    /**
     * Return a 401 Unauthorized response.
     *
     * @param  string  $message
     */
    protected function unauthorized(string $message = 'Unauthenticated'): JsonResponse
    {
        return $this->error($message, 401);
    }
}

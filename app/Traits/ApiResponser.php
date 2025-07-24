<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

trait ApiResponser
{
    /**
     * Success response method.
     *
     * @param mixed $data
     * @param string $message
     * @param int $code
     * @return JsonResponse
     */
    protected function successResponse(
        $data = null, 
        string $message = '', 
        int $code = HttpResponse::HTTP_OK
    ): JsonResponse {
        $response = [
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ];

        return response()->json($response, $code);
    }

    /**
     * Error response method.
     *
     * @param string $message
     * @param int $code
     * @param array|null $errors
     * @return JsonResponse
     */
    protected function errorResponse(
        string $message = '', 
        int $code = HttpResponse::HTTP_BAD_REQUEST, 
        ?array $errors = null
    ): JsonResponse {
        $response = [
            'status' => 'error',
            'message' => $message,
            'errors' => $errors,
        ];

        return response()->json($response, $code);
    }

    /**
     * Resource response method.
     *
     * @param JsonResource $resource
     * @param string $message
     * @param int $code
     * @return JsonResponse
     */
    protected function resourceResponse(
        JsonResource $resource, 
        string $message = '', 
        int $code = HttpResponse::HTTP_OK
    ): JsonResponse {
        return $this->successResponse($resource, $message, $code);
    }

    /**
     * Collection response method.
     *
     * @param ResourceCollection $collection
     * @param string $message
     * @param int $code
     * @return JsonResponse
     */
    protected function collectionResponse(
        ResourceCollection $collection, 
        string $message = '', 
        int $code = HttpResponse::HTTP_OK
    ): JsonResponse {
        return $this->successResponse($collection, $message, $code);
    }

    /**
     * Paginated response method.
     *
     * @param LengthAwarePaginator $paginator
     * @param string $message
     * @param int $code
     * @return JsonResponse
     */
    protected function paginatedResponse(
        LengthAwarePaginator $paginator, 
        string $message = '', 
        int $code = HttpResponse::HTTP_OK
    ): JsonResponse {
        $response = [
            'status' => 'success',
            'message' => $message,
            'data' => $paginator->items(),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ];

        return response()->json($response, $code);
    }

    /**
     * No content response method.
     *
     * @return JsonResponse
     */
    protected function noContentResponse(): JsonResponse
    {
        return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
    }

    /**
     * Not found response method.
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return $this->errorResponse($message, HttpResponse::HTTP_NOT_FOUND);
    }

    /**
     * Forbidden response method.
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function forbiddenResponse(string $message = 'Forbidden'): JsonResponse
    {
        return $this->errorResponse($message, HttpResponse::HTTP_FORBIDDEN);
    }

    /**
     * Unauthorized response method.
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function unauthorizedResponse(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->errorResponse($message, HttpResponse::HTTP_UNAUTHORIZED);
    }

    /**
     * Validation error response method.
     *
     * @param array $errors
     * @param string $message
     * @return JsonResponse
     */
    protected function validationErrorResponse(
        array $errors, 
        string $message = 'Validation failed'
    ): JsonResponse {
        return $this->errorResponse($message, HttpResponse::HTTP_UNPROCESSABLE_ENTITY, $errors);
    }

    /**
     * Server error response method.
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function serverErrorResponse(string $message = 'Internal server error'): JsonResponse
    {
        return $this->errorResponse($message, HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Custom response method.
     *
     * @param string $status
     * @param mixed $data
     * @param string $message
     * @param int $code
     * @return JsonResponse
     */
    protected function customResponse(
        string $status, 
        $data = null, 
        string $message = '', 
        int $code = HttpResponse::HTTP_OK
    ): JsonResponse {
        $response = [
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ];

        return response()->json($response, $code);
    }
}
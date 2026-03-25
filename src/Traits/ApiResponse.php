<?php
namespace RahatulRabbi\SocialAuth\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Return a successful JSON response.
     *
     * @param  mixed       $data
     * @param  string      $message
     * @param  int         $statusCode
     */
    protected function success(mixed $data = null, string $message = 'Success', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $statusCode);
    }

    /**
     * Return an error JSON response.
     *
     * @param  mixed       $data
     * @param  string      $message
     * @param  int         $statusCode
     */
    protected function error(mixed $data = null, string $message = 'Error', int $statusCode = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data'    => $data,
        ], $statusCode);
    }
}

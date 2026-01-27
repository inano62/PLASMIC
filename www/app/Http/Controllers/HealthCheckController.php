<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthCheckController extends Controller
{
    public function dbCheck(): JsonResponse
    {
        try {
            $path = config('database.connections.sqlite.database');

            return response()->json([
                'driver' => config('database.default'),
                'sqlite_path' => $path,
                'realpath' => $path ? (file_exists($path) ? realpath($path) : null) : null,
                'exists' => $path ? file_exists($path) : null,
                'users_count' => DB::table('users')->count(),
                'admin' => DB::table('users')->where('email', 'admin@example.com')->first(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'class' => get_class($e),
                'code' => $e->getCode(),
            ], 500);
        }
    }

    public function ping(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'time' => now()->toIso8601String(),
        ]);
    }
}

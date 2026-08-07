<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class HealthCheckController extends Controller
{
    public function __invoke()
    {
        $dbOk = false;
        try {
            DB::connection()->getPdo();
            $dbOk = true;
        } catch (\Throwable $e) {}

        return response()->json([
            'status' => $dbOk ? 'ok' : 'degraded',
            'database' => $dbOk ? 'ok' : 'failed',
            'queue' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ], $dbOk ? 200 : 500);
    }
}

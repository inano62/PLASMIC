<?php

// app/Http/Controllers/PublicApiController.php
namespace App\Http\Controllers;
use App\Models\Setting;

class PublicApiController extends Controller {
    public function settings() {
        try {
            $map = Setting::query()->pluck('value','key');
            return response()->json($map);
        } catch (\Throwable $e) {
            // 空っぽでも画面が落ちないデフォルト
            return response()->json([
                'brand'  => ['name' => 'PLASMIC'],
                'prices' => ['plan' => 19800],
            ]);
        }
    }
}

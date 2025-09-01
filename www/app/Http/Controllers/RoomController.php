<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RoomController extends Controller
{
    public function issue(Request $request): JsonResponse
    {
        // 仮実装：受け取ったJSONをそのまま返す（動作確認用）
        $data = $request->validate([
            'tenant_id' => 'required|string',
            'limit'     => 'nullable|integer',
            'sub'       => 'required|string',
        ]);

        return response()->json([
            'ok'   => true,
            'echo' => $data,
        ]);
    }
}

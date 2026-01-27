<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\CallLog;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LivekitController extends Controller
{
    public function issueToken(Request $request): JsonResponse
    {
        $room = $request->input('room') ?: $request->query('room');
        $ticket = $request->input('ticket') ?: $request->query('ticket');
        $aid = $request->input('aid') ?: $request->query('aid');

        $appointment = $aid ? Appointment::find($aid) : null;
        $hostId = $appointment?->lawyer_user_id ?? $request->user()?->id;
        $guestId = $appointment?->client_user_id;

        if (!$room && $ticket) {
            try {
                $decoded = JWT::decode($ticket, new Key(env('TICKET_SECRET', 'changeme'), 'HS256'));
                $room = $decoded->sub ?? null;
            } catch (\Throwable $e) {
                // ignore decode errors
            }
        }

        if (!$room && $aid) {
            $room = $appointment?->room_name;
        }

        abort_unless($room, 404, 'room not found');

        $apiKey = env('LIVEKIT_API_KEY', 'devkey');
        $apiSecret = env('LIVEKIT_API_SECRET', 'devsecret');
        $wsUrl = env('LIVEKIT_WS_URL', env('LIVEKIT_URL', 'ws://localhost:7880'));

        $identity = (string)($request->input('identity') ?: Str::uuid());
        $claims = [
            'jti' => (string)Str::uuid(),
            'iss' => $apiKey,
            'sub' => $identity,
            'nbf' => time() - 10,
            'exp' => time() + 3600,
            'video' => [
                'roomJoin' => true,
                'room' => $room,
                'canPublish' => true,
                'canSubscribe' => true,
                'canPublishData' => true,
            ],
        ];

        $jwt = JWT::encode($claims, $apiSecret, 'HS256');

        $log = CallLog::firstOrCreate(
            ['room_name' => $room, 'started_at' => now()->subMinutes(10)],
            [
                'appointment_id' => $aid,
                'host_user_id' => $hostId,
                'guest_user_id' => $guestId,
                'started_at' => now(),
            ]
        );

        $log->update([
            'ended_at' => now(),
            'duration_sec' => $log->started_at ? $log->started_at->diffInSeconds(now(), true) : null,
        ]);

        return response()->json([
            'url' => $wsUrl,
            'wsUrl' => $wsUrl,
            'token' => $jwt,
            'accessToken' => $jwt,
            'room' => $room,
            'identity' => $identity,
        ]);
    }

    public function devToken(Request $request): JsonResponse
    {
        $room = $request->input('room') ?? 'room_' . Str::lower(Str::random(6));
        $identity = $request->input('identity') ?? 'guest_' . Str::lower(Str::random(6));
        $name = $request->input('name', $identity);

        $apiKey = env('LIVEKIT_API_KEY');
        $apiSecret = env('LIVEKIT_API_SECRET');
        if (!$apiKey || !$apiSecret) {
            return response()->json(['message' => 'LIVEKIT_API_KEY / LIVEKIT_API_SECRET が未設定'], 500);
        }

        $videoGrant = [
            'room' => $room,
            'roomJoin' => true,
            'canPublish' => true,
            'canSubscribe' => true,
            'canPublishData' => true,
            'canUpdateOwnMetadata' => true,
        ];

        $now = time();
        $payload = [
            'iss' => $apiKey,
            'sub' => $identity,
            'nbf' => $now - 1,
            'iat' => $now,
            'exp' => $now + 3600,
            'name' => $name,
            'video' => $videoGrant,
            'metadata' => json_encode(['name' => $name]),
        ];

        return response()->json([
            'token' => JWT::encode($payload, $apiSecret, 'HS256'),
            'room' => $room,
            'identity' => $identity,
        ]);
    }
}

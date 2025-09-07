<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\TenantUser; // あれば（tenantとuserを結ぶpivot）
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;


class ClientController extends Controller
{
    public function upsert(Request $r)
    {
        $data = $r->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email',
        ]);

        $user = User::updateOrCreate(
            ['email' => $data['email']],
            [
                'name'     => $data['name'],
                // 初回だけ適当なパスワードを入れる（運用でメールログイン等に変える）
                'password' => Hash::make(Str::random(24)),
            ]
        );

        return response()->json(['user_id' => $user->id]);
    }
}

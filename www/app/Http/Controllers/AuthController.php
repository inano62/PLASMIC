<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
class AuthController extends Controller
{
    public function me(Request $request)
    {
        $user = $request->user()->load([
            'tenants:id,slug,display_name',     // 必要最小だけ
        ]);

        // pivot.role を含めて整形
        $tenants = $user->tenants->map(fn($t) => [
            'id'    => $t->id,
            'slug'  => $t->slug,
            'name'  => $t->display_name,
            'role'  => $t->pivot->role,         // ← ここ大事
        ])->values();

        // 代表テナント（例：owner の最初）を決めておくと便利
        $primaryTenantId = $tenants->firstWhere('role', 'owner')['id']
            ?? ($tenants[0]['id'] ?? null);

        return response()->json([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role,
            'tenants' => $tenants,
            'primary_tenant_id' => $primaryTenantId,
        ]);
    }
    public function apiLogin(Request $r)
    {
        $r->validate(['email'=>'required|email','password'=>'required']);
        $user = User::where('email', $r->email)->first();
        if (!$user || !Hash::check($r->password, $user->password)) {
            return response()->json(['message'=>'Unauthorized'], 401);
        }
        // Sanctum Personal Access Token を発行
        $token = $user->createToken('admin')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'=>$user->id, 'name'=>$user->name, 'email'=>$user->email, 'role'=>$user->role,
                // 必要なら tenants もここで同梱
                // 'tenants' => $user->tenants()->get(['id','slug'])->map(fn($t)=>['id'=>$t->id,'slug'=>$t->slug]),
            ],
        ]);
    }

    public function apiLogout(Request $r)
    {
        // 現在のトークンだけ失効
        $r->user()->currentAccessToken()?->delete();
        return response()->json(['ok'=>true]);
    }

}

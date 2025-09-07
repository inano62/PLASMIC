<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicController extends Controller
{
    // /api/public/tenants -> [{ id, name }]
    public function tenants(Request $r)
    {
        $rows = DB::table('tenants')
            ->select('id', DB::raw('display_name AS name'))
            ->orderBy('id')
            ->get();

        return response()->json($rows);
    }

    // 先生一覧：tenant_users → users を join
    public function pros(int $id)
    {
        $rows = DB::table('tenant_users')
            ->join('users', 'users.id', '=', 'tenant_users.user_id')
            ->where('tenant_users.tenant_id', $id)
            ->whereIn('tenant_users.role', ['owner','pro'])
            ->orderBy('users.id')
            ->get(['users.id as user_id', 'users.name']);

        return response()->json($rows);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Tenant;
use App\Models\Site;
class PublicController extends Controller
{
    // /api/public/tenants -> [{ id, name }]
    public function tenants() {
        return response()->json(Tenant::select('id','display_name')->orderBy('id')->get());
    }

    // 先生一覧：tenant_users → users を join
//    public function pros(int $id)
//    {
//        $rows = DB::table('tenant_users')
//            ->join('users', 'users.id', '=', 'tenant_users.user_id')
//            ->where('tenant_users.tenant_id', $id)
//            ->whereIn('tenant_users.role', ['owner','pro'])
//            ->orderBy('users.id')
//            ->get(['users.id as user_id', 'users.name']);
//
//        return response()->json($rows);
//    }
    public function pros($tenant) {
        $tenantId = resolveTenantId($tenant);

        // DBが未整備でも落とさない
        if (Schema::hasTable('users') && Schema::hasColumn('users','role')) {
            $rows = \App\Models\User::query()
                ->where('role', 'lawyer')
                ->get(['id','name']);
            if ($rows->count()) {
                return response()->json($rows->map(fn($u)=>[
                    'id'   => (int)$u->id,
                    'name' => $u->name,
                ]));
            }
        }

        // ★ フォールバック（最低1名）
        return response()->json([
            ['id'=>1,'name'=>'担当者A'],
        ]);
    }
    public function resolve(Request $req)
    {
        $key = $req->query('key');
        $slug = $req->query('slug');

        abort_if(!$slug, 400, 'key or slug is required');
        // abort_if(!$key && !$slug, 400, 'key or slug is required');

        $tenantQuery = Tenant::query();
        if ($slug && Schema::hasColumn('tenants', 'slug')) {
            $tenantQuery->where('slug', $slug);
        }
        if ($key) {
            $tenantQuery->orWhere('key', $key);
        }

        $tenant = $tenantQuery->first();

        if (!$tenant) {
            return response()->json(['message' => 'tenant not found'], 404);
        }

        return response()->json([
            'id' => (int) $tenant->id,
            'display_name' => $tenant->display_name ?? $tenant->name,
        ]);
    }
}

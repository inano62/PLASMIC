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
        $id   = $req->query('id');
        $slug = $req->query('slug');
        $key  = $req->query('key');

        if (!$id && !$slug && !$key) {
            return response()->json(['message' => 'id|slug|key is required'], 400);
        }

        $tenant = null;

        if ($id) {
            $tenant = Tenant::find($id);
        } else {
            $v = $slug ?? $key;

            // tenants から検索（slug/key 両方）
            $tenant = Tenant::where('slug', $v)->orWhere('key', $v)->first();

            // 見つからなければ sites から tenant_id を辿る
            if (!$tenant) {
                $site = Site::where('slug', $v)->orWhere('key', $v)->first();
                if ($site && $site->tenant_id) {
                    $tenant = Tenant::find($site->tenant_id);
                }
            }
        }

        if (!$tenant) {
            return response()->json(['message' => 'tenant not found'], 404);
        }

        return response()->json([
            'id'           => (int) $tenant->id,
            'display_name' => $tenant->display_name,
        ]);
    }
}

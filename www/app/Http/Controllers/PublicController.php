<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant;
class PublicController extends Controller
{
    // /api/public/tenants -> [{ id, name }]
    public function tenants() {
        return response()->json(Tenant::select('id','display_name')->orderBy('id')->get());
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
    public function resolveTenant(Request $r) {
        $slug = $r->query('slug') ?? $r->query('tenant');
        $t = Tenant::where('slug',$slug)->firstOrFail();
        return response()->json(['id'=>$t->id,'name'=>$t->name]);
    }
}

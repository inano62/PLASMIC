<?php
use App\Models\Tenant;
use App\Models\Site;

if (!function_exists('foo')) {
    function foo() {
        return 'bar';
    }
}
if (!function_exists('resolveTenantId')) {
    function resolveTenantId(string|int $tenantOrId): int {
        // そのまま ID が来たら終了
        if (is_numeric($tenantOrId)) {
            return (int) $tenantOrId;
        }

        // tenants.slug / tenants.key を両方見る
        $t = Tenant::where('slug', $tenantOrId)
            ->orWhere('key', $tenantOrId)
            ->first();

        // 見つからなければ sites.slug / sites.key から tenant_id を辿る
        if (!$t) {
            $s = Site::where('slug', $tenantOrId)
                ->orWhere('key', $tenantOrId)
                ->first();
            if ($s) {
                $t = Tenant::find($s->tenant_id ?? null);
            }
        }

        abort_if(!$t, 404, 'Tenant not found');
        return (int) $t->id;
    }
}

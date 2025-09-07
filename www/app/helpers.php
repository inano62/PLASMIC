<?php
use App\Models\Tenant;

if (!function_exists('resolveTenantId')) {
    function resolveTenantId(string|int $tenantOrId): int {
        $id = Tenant::query()
            ->where('slug', $tenantOrId)
            ->orWhere('id', $tenantOrId)
            ->value('id');
        abort_if(!$id, 404, 'tenant not found');
        return (int)$id;
    }
}

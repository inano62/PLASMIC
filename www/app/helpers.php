<?php
use App\Models\Tenant;

if (!function_exists('resolveTenantId')) {
    function resolveTenantId(string|int $tenantOrId): int {
        if (is_numeric($tenant)) {
            return (int) $tenant;
        }
        abort(404, 'Tenant not found');
    }
}

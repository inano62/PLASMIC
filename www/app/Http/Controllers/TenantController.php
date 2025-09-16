<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\Tenant;
class TenantController extends Controller
{
    public function index(Request $request)
    {
        $query = Tenant::query();

        // 🔎 フィルタリング
        if ($region = $request->query('region')) {
            $query->where('region', $region);
        }
        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }
        if ($q = $request->query('q')) {
            $query->where('display_name', 'like', "%{$q}%");
        }

        // 📄 ページネーション（15件/ページ）
        $perPage = (int) $request->query('per_page', 15);
        $tenants = $query->paginate($perPage);

        return response()->json($tenants);
    }
}

<?php
// app/Services/SiteProvisioner.php
namespace App\Services;

use App\Models\{Tenant, Site, Page};
use Illuminate\Support\Facades\DB;

class SiteProvisioner
{
    public function provisionForTenant(Tenant $tenant): Site
    {
        return DB::transaction(function () use ($tenant) {
            $site = Site::firstOrCreate(
                ['slug' => $tenant->slug],
                ['tenant_id' => $tenant->id, 'title' => $tenant->display_name, 'meta' => []]
            );

            $page = Page::firstOrCreate(
                ['site_id' => $site->id, 'path' => '/'],
                ['title' => $site->title, 'sort' => 0]
            );

            if ($page->blocks()->count() === 0) {
                $page->blocks()->createMany([
                    ['type'=>'hero','sort'=>1,'data'=>[
                        'title'=>$site->title,
                        'subtitle'=>trim(($tenant->region ?? '').'・'.($tenant->type ?? '')),
                        'actions'=>[['label'=>'予約する','href'=>"/book?tenant={$tenant->id}"]],
                    ]],
                    ['type'=>'info','sort'=>2,'data'=>[
                        'address'=>$tenant->address ?? '',
                        'phone'=>$tenant->phone ?? '',
                        'hours'=>'平日 9:00-18:00',
                    ]],
                ]);
            }

            return $site->fresh();
        });
    }
}

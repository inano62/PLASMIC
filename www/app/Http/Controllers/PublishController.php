<?php
// app/Http/Controllers/PublishController.php
namespace App\Http\Controllers;
use App\Models\{Site,Page,Block};
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;

class PublishController extends Controller
{
    public function publishSite(Site $site)
    {
        foreach ($site->pages()->with('blocks')->get() as $page) {
            $payload = [
                'title' => $page->title,
                'path' => $page->path,
                'blocks' => $page->blocks->sortBy('sort')->values()->map(fn($b)=>[
                    'type'=>$b->type,
                    'data'=>$b->data,
                    'sort'=>$b->sort,
                ])->all(),
            ];
            $page->update([
                'published_json' => json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
                'published_at' => Carbon::now(),
            ]);
        }
        return ['ok'=>true, 'slug'=>$site->slug];
    }
}

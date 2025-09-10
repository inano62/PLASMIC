<?php
// app/Http/Controllers/SiteBuilderController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Stripe\Stripe;
use App\Models\{Site,Page,Block};

class SiteBuilderController extends Controller
{
    public function status(Request $r)
    {
        return ['entitled' => Gate::allows('site.build', $r->user())];
    }

    public function checkout(Request $r)
    {
        $u = $r->user();
        Stripe::setApiKey(config('services.stripe.secret'));

        $session = \Stripe\Checkout\Session::create([
            'mode' => 'payment',
            'line_items' => [[
                // .env に価格IDを入れてください
                'price' => env('STRIPE_PRICE_SITE_PRO'), // 例: price_XXXXXXXX
                'quantity' => 1,
            ]],
            'customer' => $u->stripe_customer_id ?: null,
            'customer_creation' => $u->stripe_customer_id ? 'always' : 'if_required',
            'success_url' => url('/admin/site/thanks?session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url'  => url('/admin/site/pay?canceled=1'),
            'metadata'    => ['user_id' => $u->id],
        ]);

        return ['url' => $session->url];
    }

    public function thanks(Request $r)
    {
        $sid = $r->query('session_id');
        if (!$sid) {
            return redirect()->route('site.paywall')->with('error','session_id がありません');
        }

        Stripe::setApiKey(config('services.stripe.secret'));
        $sess = \Stripe\Checkout\Session::retrieve($sid);

        if ($sess->payment_status === 'paid') {
            $u = $r->user();
            $u->account_type = 'pro';
            if ($sess->customer && !$u->stripe_customer_id) {
                $u->stripe_customer_id = $sess->customer;
            }
            $u->save();

            return redirect()->route('site.builder');
        }

        return redirect()->route('site.paywall')->with('error','未決済です');
    }
    public function showSite(Site $site)
    {
        $pages = $site->pages()
            ->orderBy('sort')
            ->with(['blocks' => function ($q) {
                $q->orderBy('sort')
                    ->select('id','page_id','type','sort','data');
            }])
            ->get(['id','site_id','title','path','sort']);

        return response()->json([
            'site'  => $site->only(['id','title','slug','meta']),
            'pages' => $pages,
        ]);
    }

    public function updateSite(Request $r, Site $site)
    {
        $site->fill($r->only('title','slug','meta'));
        $site->save();
        return ['ok'=>true, 'site'=>$site->only('id','title','slug','meta')];
    }

    public function createPage(Request $r, Site $site)
    {
        $next = (int)$site->pages()->max('sort') + 1;
        $page = $site->pages()->create([
            'title' => $r->input('title','Untitled'),
            'path'  => $r->input('path','/'),
            'sort'  => $next,
        ]);
        return ['ok'=>true, 'page'=>$page->only('id','title','path','sort')];
    }

    public function updatePage(Request $r, Page $page)
    {
        $page->fill($r->only('title','path','sort'));
        $page->save();
        return ['ok'=>true];
    }

    public function createBlock(Request $r, Page $page)
    {
        $next = (int)$page->blocks()->max('sort') + 1;
        $b = $page->blocks()->create([
            'type' => $r->string('type'),
            'data' => $r->input('data', []),
            'sort' => $next,
        ]);
        return ['ok'=>true, 'block'=>$b->only('id','type','sort','data')];
    }

    public function updateBlock(Request $r, Block $block)
    {
        if ($r->has('type')) $block->type = (string)$r->input('type');
        if ($r->has('data')) $block->data = $r->input('data');
        if ($r->has('sort')) $block->sort = (int)$r->input('sort');
        $block->save();
        return ['ok'=>true];
    }

    public function reorderBlocks(Request $r, Page $page)
    {
        $ids = $r->input('ids', []);
        foreach ($ids as $i => $id) {
            Block::where('id', $id)->where('page_id', $page->id)->update(['sort'=>$i+1]);
        }
        return ['ok'=>true];
    }

    public function destroyBlock(Block $block)
    {
        $block->delete();
        return ['ok'=>true];
    }
}

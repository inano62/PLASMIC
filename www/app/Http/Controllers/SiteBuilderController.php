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
    public function showSite(Site $site){
        return $site->load('pages.blocks');
    }

    public function updateSite(Request $r, Site $site){
        $site->update($r->only('title','slug','meta'));
        return $site->fresh('pages.blocks');
    }

    public function createPage(Request $r, Site $site){
        $p = $site->pages()->create([
            'title'=>$r->input('title','新しいページ'),
            'path' =>$r->input('path','/'),
            'sort' => ($site->pages()->max('sort') ?? 0) + 1,
        ]);
        return $p->load('blocks');
    }

    public function updatePage(Request $r, Page $page){
        $page->update($r->only('title','path','sort'));
        return $page->fresh('blocks');
    }

    public function createBlock(Request $r, Page $page){
        $type = $r->input('type');
        $defaults = [
            'hero'     => ['kicker'=>'','title'=>'タイトル','subtitle'=>'サブタイトル','btnText'=>'はじめる','btnHref'=>'#'],
            'features' => ['items'=>[['title'=>'特徴1','text'=>'説明'],['title'=>'特徴2','text'=>'説明']]],
            'cta'      => ['text'=>'今すぐお問い合わせ','btnText'=>'問い合わせる','btnHref'=>'/contact'],
        ];
        $b = $page->blocks()->create([
            'type'=>$type,
            'data'=>$defaults[$type] ?? (object)[],
            'sort'=>($page->blocks()->max('sort') ?? 0) + 1,
        ]);
        return $b;
    }

    public function updateBlock(Request $r, Block $block){
        $block->update([
            'type'=>$r->input('type',$block->type),
            'data'=>$r->input('data',$block->data),
            'sort'=>$r->input('sort',$block->sort),
        ]);
        return $block->fresh();
    }

    public function reorderBlocks(Request $r, Page $page){
        // { ids: [blockIdの配列] }
        foreach($r->input('ids',[]) as $i=>$id){
            Block::where('id',$id)->where('page_id',$page->id)->update(['sort'=>$i+1]);
        }
        return $page->fresh('blocks');
    }

    public function destroyBlock(Block $block){
        $block->delete();
        return response()->noContent();
    }
}

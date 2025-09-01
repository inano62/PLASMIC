<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    private array $keys = [
        'site.name', 'hero.title', 'hero.subtitle',
        'cta.primary.label', 'cta.primary.href',
        'cta.secondary.label', 'cta.secondary.href',
        'pricing.basic.price', 'pricing.pro.price', 'pricing.site.price',
        'contact.email',
        // 画像URLなど
        'hero.image', 'logo.image',
    ];

    public function index()
    {
        $rows = DB::table('settings')->whereIn('key', $this->keys)->pluck('value', 'key');
        return response()->json($rows);
    }

    public function update(Request $r)
    {
        $data = $r->all(); // { key: value, ... }
        foreach ($data as $k => $v) {
            if (!in_array($k, $this->keys, true)) continue;
            DB::table('settings')->updateOrInsert(['key'=>$k], ['value'=>$v]);
        }
        return $this->index();
    }

    public function upload(Request $r)
    {
        $r->validate(['file'=>'required|image|max:2048']);
        $path = $r->file('file')->store('uploads', 'public');
        return response()->json(['url' => asset('storage/'.$path)]);
    }
}

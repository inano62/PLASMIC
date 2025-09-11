<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Site, Page, Block};

class SiteSeeder extends Seeder
{
    public function run(): void
    {
        $site = Site::firstOrCreate(['id'=>1], [
            'title' => 'Demo Site',
            'slug'  => 'demo',
            'meta'  => ['theme' => 'default'],
        ]);

        $page = Page::firstOrCreate(
            ['site_id' => $site->id, 'path' => '/'],
            ['title' => 'Home', 'sort' => 1]
        );

        Block::firstOrCreate(
            ['page_id' => $page->id, 'type' => 'hero', 'sort' => 1],
            ['data' => ['headline' => 'ようこそ']]
        );
    }
}

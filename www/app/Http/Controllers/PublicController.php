<?php

use App\Http\Controllers\Controller;

class PublicController extends Controller {
    public function tenants() {
        return \App\Models\Tenant::select('id','display_name')->orderBy('id')->get();
    }
}

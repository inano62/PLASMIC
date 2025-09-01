<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoomsController extends Controller
{
    public function issue(Request $req)
    {
        return response()->json(['ok' => true]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function guest()
    {
        return ['guest' => 'v1'];
    }

    public function user(Request $request)
    {
        return $request->user();
    }
}

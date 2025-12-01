<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

class BlogController extends Controller
{
    //
    public function guide()
    {
        return view('guide');
    }
    public function blog()
    {
        return view('blog');
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MaterialController extends Controller
{
    public function index(Request $request)
    {
        return view('materials.index');
    }
}

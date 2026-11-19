<?php

namespace App\Http\Controllers;

use App\Models\Cinema;
use App\Models\Format;

class ServiceController extends Controller
{
    public function index()
    {
        $formats = Format::where('is_active', true)
            ->withCount('rooms')
            ->orderBy('name')
            ->get();
        $cinemas = Cinema::where('is_active', true)
            ->withCount('rooms')
            ->orderBy('name')
            ->get();

        return view('services.index', compact('formats', 'cinemas'));
    }

    public function vouchers()
    {
        $formats = Format::where('is_active', true)
            ->withCount('rooms')
            ->orderBy('name')
            ->get();

        return view('services.vouchers', compact('formats'));
    }

    public function giftCards()
    {
        return view('services.gift-cards');
    }
}

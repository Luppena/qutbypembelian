<?php

namespace App\Http\Controllers;

use App\Models\Pembelian;
use Illuminate\Http\Request;

class PembelianController extends Controller
{
    public function show(Pembelian $pembelian)
    {
        return view('pembelian.show', compact('pembelian'));
    }
}

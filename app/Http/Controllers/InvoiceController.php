<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function show(Sale $sale): View
    {
        $sale->load([
            'items.product',
            'user',
            'paymentTransactions.channel',
            'payments',
            'paymentProofs',
            'shipments',
        ]);

        return view('invoice.show', compact('sale'));
    }
}

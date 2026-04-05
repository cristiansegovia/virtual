<?php

use App\Models\Factura;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/facturas/{factura}/pdf', function (Factura $factura) {
    $pdf = Pdf::loadView('facturas.pdf', compact('factura'));

    return $pdf->download(sprintf('factura-%s.pdf', $factura->id));
})->name('facturas.pdf');

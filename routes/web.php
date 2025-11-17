<?php

use App\Http\Controllers\AdjustementInventory;
use App\Http\Controllers\ajustarController;
use App\Http\Controllers\ContingencyController;
use App\Http\Controllers\DTEController;
use App\Http\Controllers\EmployeesController;
use App\Http\Controllers\InventoryReport;
use App\Http\Controllers\OrdenController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SenEmailDTEController;
use App\Http\Controllers\TransferController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
})->name('home');


Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::get('/generarDTE/{idVenta}', [DTEController::class, 'generarDTE'])->middleware(['auth'])->name('generarDTE')->where('idVenta', '[0-9]+');
Route::get('/sendAnularDTE/{idVenta}', [DTEController::class, 'anularDTE'])->middleware(['auth'])->name('sendAnularDTE')->where('idVenta', '[0-9]+');
Route::get('/printDTETicket/{idVenta}', [DTEController::class, 'printDTETicket'])->middleware(['auth'])->name('printDTETicket')->where('idVenta', '[a-fA-F0-9\-]+');
Route::get('/printDTEPdf/{idVenta}', [DTEController::class, 'printDTEPdf'])->middleware(['auth'])->name('printDTEPdf')->where('idVenta', '[a-fA-F0-9\-]+');
Route::get('/sendDTE/{idVenta}', [SenEmailDTEController::class, 'SenEmailDTEController'])->middleware(['auth'])->name('sendDTE')->where('idVenta', '[0-9]+');
Route::get('/ordenPrint/{idVenta}', [OrdenController::class, 'generarPdf'])->middleware(['auth'])->name('ordenGenerarPdf')->where('idVenta', '[0-9]+');
Route::get('/ordenPrintTicket/{idVenta}', [OrdenController::class, 'ordenGenerarTicket'])->middleware(['auth'])->name('ordenGenerarTicket')->where('idVenta', '[0-9]+');
Route::get('/closeCashboxPrint/{idCasboxClose}', [OrdenController::class, 'closeClashBoxPrint'])->middleware(['auth'])->name('closeClashBoxPrint')->where('idCasboxClose', '[0-9]+');
Route::get('/admin/sales/{idVenta}/edit', [OrdenController::class, 'billingOrder'])->middleware(['auth'])->name('billingOrder')->where('idVenta', '[0-9]+');
Route::get('/printQuote/{idVenta}', [QuoteController::class, 'printQuote'])->name('printQuote')->where('idVenta', '[0-9]+');
//Traslados
Route::get('/printTransfer/{idTransfer}', [TransferController::class, 'printTransfer'])->middleware(['auth'])->name('printTransfer')->where('idTransfer', '[0-9]+');
Route::get('/employee/sales/{id_employee}/{star_date}/{end_date}', [EmployeesController::class, 'sales'])->middleware(['auth'])->name('employee.sales')->where(['id_employee' => '[0-9]+', 'star_date' => '[0-9]{4}-[0-9]{2}-[0-9]{2}', 'end_date' => '[0-9]{4}-[0-9]{2}-[0-9]{2}']);
Route::get('/employee/sales-work/{id_employee}/{star_date}/{end_date}', [EmployeesController::class, 'salesWork'])->middleware(['auth'])->name('employee.sales-work')->where(['id_employee' => '[0-9]+', 'star_date' => '[0-9]{4}-[0-9]{2}-[0-9]{2}', 'end_date' => '[0-9]{4}-[0-9]{2}-[0-9]{2}']);
Route::get('/employee/test/{id_employee}/{star_date}/{end_date}', [EmployeesController::class, 'dataEmployee'])->middleware(['auth'])->name('employee.sales-test')->where(['id_employee' => '[0-9]+', 'star_date' => '[0-9]{4}-[0-9]{2}-[0-9]{2}', 'end_date' => '[0-9]{4}-[0-9]{2}-[0-9]{2}']);

//Libros de excel
//Route::get('/sale/iva/{doctype}/{starDate}/{endDate}',[ReportsController::class,'saleReportFact']);
Route::get('/sale/iva/libro/fact/{starDate}/{endDate}',[ReportsController::class,'saleReportFact'])->where(['starDate' => '[0-9]{4}-[0-9]{2}-[0-9]{2}', 'endDate' => '[0-9]{4}-[0-9]{2}-[0-9]{2}']);
Route::get('/sale/iva/libro/ccf/{starDate}/{endDate}',[ReportsController::class,'saleReportCCF'])->where(['starDate' => '[0-9]{4}-[0-9]{2}-[0-9]{2}', 'endDate' => '[0-9]{4}-[0-9]{2}-[0-9]{2}']);
Route::get('/sale/iva/libro/ccf/{startDate}/{endDate}', [ReportsController::class, 'saleReportCCF'])->name('sale.iva.libro.ccf')->where(['startDate' => '[0-9]{4}-[0-9]{2}-[0-9]{2}', 'endDate' => '[0-9]{4}-[0-9]{2}-[0-9]{2}']);
Route::get('/contingency/{description}',[ContingencyController::class,'contingencyDTE'])->middleware(['auth'])->name('contingency')->where('description', '[a-zA-Z0-9\s]+');
Route::get('/contingency_close/{uuid_contingence}',[ContingencyController::class,'contingencyCloseDTE'])->middleware(['auth'])->name('contingencyClose')->where('uuid_contingence', '[a-fA-F0-9\-]{36}');
//ZIP
Route::get('/sale/json/{starDate}/{endDate}',[ReportsController::class,'downloadJson'])->where(['starDate' => '[0-9]{4}-[0-9]{2}-[0-9]{2}', 'endDate' => '[0-9]{4}-[0-9]{2}-[0-9]{2}']);
Route::get('/sale/pdf/{starDate}/{endDate}',[ReportsController::class,'downloadPdf'])->where(['starDate' => '[0-9]{4}-[0-9]{2}-[0-9]{2}', 'endDate' => '[0-9]{4}-[0-9]{2}-[0-9]{2}']);
Route::get('/sale/pdf/progress/{downloadId}',[ReportsController::class,'checkProgress'])->name('sale.pdf.progress');
Route::post('/sale/pdf/cancel/{downloadId}',[ReportsController::class,'cancelPdfGeneration'])->middleware(['auth'])->name('sale.pdf.cancel');

// Vista de progreso de descarga
Route::get('/reports/download-progress', function () {
    $startDate = request('start') ?? request('startDate');
    $endDate = request('end') ?? request('endDate');

    if (!$startDate || !$endDate) {
        abort(400, 'Fechas requeridas');
    }

    return view('reports.download-progress', compact('startDate', 'endDate'));
})->name('reports.download.progress')->middleware('auth');
//Entrada Salia
//Route::get('/printSalida/{idsalida}', [DTEController::class, 'printDTETicket'])->middleware(['auth'])->name('printSalida');
Route::get('/salidaPrintTicket/{id}', [AdjustementInventory::class, 'salidaPrintTicket'])->middleware(['auth'])->name('salidaPrintTicket')->where('id', '[0-9]+');


//Inventory
Route::get('/inventor/report/{upadte}/{starDate}/{endDate}', [InventoryReport::class, 'inventoryReportExport'])->name('inventor.report')->where(['upadte' => '[0-9]+', 'starDate' => '[0-9]{4}-[0-9]{2}-[0-9]{2}', 'endDate' => '[0-9]{4}-[0-9]{2}-[0-9]{2}']);
Route::get('/inventor/report-mov/{code}/{starDate}/{endDate}', [InventoryReport::class, 'inventoryMovimentReportExport'])->name('inventor.moviment.report')->where(['code' => '[a-zA-Z0-9\-]+', 'starDate' => '[0-9]{4}-[0-9]{2}-[0-9]{2}', 'endDate' => '[0-9]{4}-[0-9]{2}-[0-9]{2}']);

Route::get('/ajustarInventario', [ajustarController::class, 'index'])->middleware(['auth'])->name('ajustarInventario');

//Purchase Routes
Route::get('/purchase/iva/{document_type}/{starDate}/{endDate}',[ReportsController::class,'purchaseReport'])->where(['document_type' => '[0-9]+', 'starDate' => '[0-9]{4}-[0-9]{2}-[0-9]{2}', 'endDate' => '[0-9]{4}-[0-9]{2}-[0-9]{2}']);
Route::get('/purchasePrint/{idCompra}', [PurchaseController::class, 'generarPdf'])->middleware(['auth'])->name('purchase.pdf')->where('idCompra', '[0-9]+');


require __DIR__ . '/auth.php';

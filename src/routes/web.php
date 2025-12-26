<?php

use App\Http\Controllers\GoogleCalendarController;
use App\Http\Controllers\GoogleDriveController;
use App\Http\Controllers\SignatureController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Public pages (About / Contact)
Route::view('/about', 'about')->name('about');
Route::view('/contact', 'contact')->name('contact');
Route::post('/contact/send', function(\Illuminate\Http\Request $request){
    $data = $request->validate(["name"=>"required","email"=>"required|email","message"=>"required"]);

    // Send contact email to office
    try {
        \Mail::to(config('juris.emails.contact'))->send(new \App\Mail\ContactSubmitted($data));
    } catch (\Exception $e) {
        \Log::error('Contact email failed', ['error' => $e->getMessage(), 'data' => $data]);
        return back()->with('status','Ocorreu um erro ao enviar. Por favor, tente novamente mais tarde.');
    }

    \Log::info('Contact form submitted', $data);
    return back()->with('status','Mensagem enviada. Entraremos em contato em breve.');
})->name('contact.send');

// Google Calendar OAuth Routes
Route::middleware(['web', 'auth'])->prefix('funil')->group(function () {
    Route::get('/google-calendar/callback', [GoogleCalendarController::class, 'callback'])
        ->name('google-calendar.callback');
    Route::post('/google-calendar/disconnect', [GoogleCalendarController::class, 'disconnect'])
        ->name('google-calendar.disconnect');
    Route::post('/google-calendar/sync', [GoogleCalendarController::class, 'sync'])
        ->name('google-calendar.sync');
});

// Google Drive OAuth Routes
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/google/redirect', [GoogleDriveController::class, 'redirect'])
        ->name('google.redirect');
    Route::get('/google/callback', [GoogleDriveController::class, 'callback'])
        ->name('google.callback');
    Route::post('/google/disconnect', [GoogleDriveController::class, 'disconnect'])
        ->name('google.disconnect');
});

// =============================================
// ROTAS PÚBLICAS DE ASSINATURA DIGITAL
// =============================================
Route::prefix('assinar')->name('signatures.')->group(function () {
    // Página de assinatura (pública)
    Route::get('/{token}', [SignatureController::class, 'sign'])
        ->name('sign');
    
    // Solicitar código de verificação
    Route::post('/{token}/code', [SignatureController::class, 'requestCode'])
        ->name('request-code');
    
    // Processar assinatura
    Route::post('/{token}/process', [SignatureController::class, 'processSignature'])
        ->name('process');
    
    // Rejeitar assinatura
    Route::post('/{token}/reject', [SignatureController::class, 'reject'])
        ->name('reject');
    
    // Visualizar/baixar documento
    Route::get('/{token}/documento', [SignatureController::class, 'viewDocument'])
        ->name('document');
    
    // Página de sucesso
    Route::get('/{token}/sucesso', [SignatureController::class, 'success'])
        ->name('success');
    
    // Página de rejeição
    Route::get('/{token}/recusado', [SignatureController::class, 'rejected'])
        ->name('rejected');
});

// Verificar status de assinatura (pública)
Route::get('/assinatura/status/{uid}', [SignatureController::class, 'status'])
    ->name('signatures.status');

// Download de documento (autenticado)
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/assinaturas/{request}/download', [SignatureController::class, 'download'])
        ->name('signatures.download');
});

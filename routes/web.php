<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DevController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Livewire\Caldue1;
use App\Livewire\NewOrder;
use Illuminate\Support\Facades\Route;

Route::post('/language/{locale}', function (string $locale) {
    if (in_array($locale, ['ar', 'en'])) {
        session(['locale' => $locale]);
    }

    return redirect()->back();
})->name('language.switch');

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return view('welcome');
});

Route::get('/offline', function () {
    return view('offline');
})->name('offline');

// Public blog
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
Route::post('/blog/{post}/comments', [BlogController::class, 'storeComment'])
    ->middleware('throttle:10,1')
    ->name('blog.comments.store');

// Public static pages
Route::get('/pages/{slug}', [PageController::class, 'show'])->name('pages.show');

Route::get('/new-order', NewOrder::class)
    ->name('new-order');

Route::get('/caldue1', Caldue1::class)
    ->name('caldue1');

Route::middleware('auth')->group(function () {
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::post('/orders/bulk', [OrderController::class, 'bulkUpdate'])->name('orders.bulk-update');

    Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{id}/comments', [OrderController::class, 'storeComment'])->name('orders.comments.store');
    Route::patch('/orders/{orderId}/comments/{commentId}', [OrderController::class, 'updateComment'])->name('orders.comments.update');
    Route::delete('/orders/{orderId}/comments/{commentId}', [OrderController::class, 'destroyComment'])->name('orders.comments.destroy');
    Route::post('/orders/{id}/status', [OrderController::class, 'updateStatus'])->name('orders.status.update');
    Route::post('/orders/{id}/mark-paid', [OrderController::class, 'markPaid'])->name('orders.mark-paid');
    Route::post('/orders/{id}/files', [OrderController::class, 'storeFile'])->name('orders.files.store');
    Route::post('/orders/{id}/prices', [OrderController::class, 'updatePrices'])->name('orders.prices.update');
    Route::post('/orders/{id}/invoice', [OrderController::class, 'generateInvoice'])->name('orders.invoice.generate');
    Route::post('/orders/{id}/merge', [OrderController::class, 'merge'])->name('orders.merge');
    Route::post('/orders/{orderId}/comments/{commentId}/notify', [OrderController::class, 'sendNotification'])->name('orders.comments.notify');
    Route::patch('/orders/{id}/shipping-address', [OrderController::class, 'updateShippingAddress'])->name('orders.shipping-address.update');
    Route::post('/orders/{id}/send-email', [OrderController::class, 'sendEmail'])->name('orders.send-email');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/account', [AccountController::class, 'index'])->name('account.index');
    Route::patch('/account/profile', [AccountController::class, 'updateProfile'])->name('account.profile.update');
    Route::patch('/account/password', [AccountController::class, 'updatePassword'])->name('account.password.update');
    Route::post('/account/addresses', [AccountController::class, 'storeAddress'])->name('account.addresses.store');
    Route::patch('/account/addresses/{address}', [AccountController::class, 'updateAddress'])->name('account.addresses.update');
    Route::delete('/account/addresses/{address}', [AccountController::class, 'destroyAddress'])->name('account.addresses.destroy');
    Route::post('/account/addresses/{address}/default', [AccountController::class, 'setDefaultAddress'])->name('account.addresses.default');
    Route::patch('/account/notifications', [AccountController::class, 'updateNotifications'])->name('account.notifications.update');
    Route::post('/account/request-deletion', [AccountController::class, 'requestDeletion'])->name('account.request-deletion');
    Route::delete('/account/request-deletion', [AccountController::class, 'cancelDeletion'])->name('account.cancel-deletion');
});

Route::middleware(['auth', 'can:view-all-orders'])->group(function () {
    Route::get('/inbox', [InboxController::class, 'index'])->name('inbox.index');
    Route::post('/inbox/mark-all-read', [InboxController::class, 'markAllRead'])->name('inbox.mark-all-read');
    Route::post('/inbox/{activity}/mark-read', [InboxController::class, 'markRead'])->name('inbox.mark-read');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Dev-only quick login — never registered in production
if (app()->environment('local')) {
    Route::post('/_dev/login-as', [DevController::class, 'loginAs'])->name('dev.login-as');
}

require __DIR__.'/auth.php';

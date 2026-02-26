<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\DevController;
use App\Http\Controllers\GoController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\OrderCommentController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderFileController;
use App\Http\Controllers\OrderMergeController;
use App\Http\Controllers\OrderStatusController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Livewire\NewOrder;
use Illuminate\Support\Facades\Route;

Route::match(['get', 'post'], '/language/{locale}', function (string $locale) {
    $locales = config('app.available_locales', ['ar', 'en']);
    if (! in_array($locale, $locales)) {
        return redirect()->back();
    }

    session(['locale' => $locale]);
    if (auth()->check()) {
        auth()->user()->update(['locale' => $locale]);
    }

    $url = url()->previous();
    $parsed = parse_url($url);
    $path = $parsed['path'] ?? '/';
    $query = [];
    if (! empty($parsed['query'])) {
        parse_str($parsed['query'], $query);
    }
    $query['lang'] = $locale;

    return redirect()->to($path.'?'.http_build_query($query));
})->name('language.switch');

Route::get('/', function () {
    return view('welcome');
});

Route::get('/offline', function () {
    return view('offline');
})->name('offline');

Route::get('/go/{slug}', GoController::class)->name('go');

// Public blog
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
Route::post('/blog/{post}/comments', [BlogController::class, 'storeComment'])
    ->middleware('throttle:5,15')
    ->name('blog.comments.store');

// Public static pages
Route::get('/pages/{slug}', [PageController::class, 'show'])->name('pages.show');

Route::get('/new-order', NewOrder::class)->name('new-order');

Route::middleware('auth')->group(function () {
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/list-{variant}', [OrderController::class, 'indexVariant'])
        ->where('variant', 'simple|table|minimal')
        ->name('orders.list-variant');
    Route::post('/orders/bulk', [OrderController::class, 'bulkUpdate'])->name('orders.bulk-update');

    Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{id}/comments', [OrderCommentController::class, 'store'])->name('orders.comments.store');
    Route::patch('/orders/{orderId}/comments/{commentId}', [OrderCommentController::class, 'update'])->name('orders.comments.update');
    Route::delete('/orders/{orderId}/comments/{commentId}', [OrderCommentController::class, 'destroy'])->name('orders.comments.destroy');
    Route::post('/orders/{id}/status', [OrderStatusController::class, 'update'])->name('orders.status.update');
    Route::post('/orders/{id}/mark-paid', [OrderStatusController::class, 'markPaid'])->name('orders.mark-paid');
    Route::post('/orders/{id}/files', [OrderFileController::class, 'store'])->name('orders.files.store');
    Route::post('/orders/{id}/prices', [OrderController::class, 'updatePrices'])->name('orders.prices.update');
    Route::post('/orders/{id}/invoice', [OrderController::class, 'generateInvoice'])->name('orders.invoice.generate');
    Route::post('/orders/{id}/merge', OrderMergeController::class)->name('orders.merge');
    Route::post('/orders/{orderId}/comments/{commentId}/notify', [OrderCommentController::class, 'sendNotification'])->name('orders.comments.notify');
    Route::post('/orders/{orderId}/comments/{commentId}/log-whatsapp', [OrderCommentController::class, 'logWhatsAppSend'])->name('orders.comments.log-whatsapp');
    Route::post('/orders/{orderId}/comments/mark-read', [OrderCommentController::class, 'markRead'])->name('orders.comments.mark-read');
    Route::post('/orders/{id}/timeline/{timelineId}/add-as-comment', [OrderCommentController::class, 'addTimelineAsComment'])->name('orders.timeline.add-as-comment');
    Route::patch('/orders/{id}/shipping-address', [OrderController::class, 'updateShippingAddress'])->name('orders.shipping-address.update');
    Route::post('/orders/{id}/send-email', [OrderController::class, 'sendEmail'])->name('orders.send-email');

    // Customer quick actions
    Route::post('/orders/{id}/payment-notify', [OrderController::class, 'paymentNotify'])->name('orders.payment-notify');
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancelOrder'])->name('orders.cancel');
    Route::post('/orders/{id}/customer-merge', [OrderController::class, 'customerMerge'])->name('orders.customer-merge');

    // Staff quick actions
    Route::post('/orders/{id}/transfer', [OrderController::class, 'transferOrder'])->name('orders.transfer');
    Route::post('/orders/{id}/shipping-tracking', [OrderController::class, 'updateShippingTracking'])->name('orders.shipping-tracking');
    Route::post('/orders/{id}/update-payment', [OrderController::class, 'updatePayment'])->name('orders.update-payment');
    Route::patch('/orders/{id}/staff-notes', [OrderController::class, 'updateStaffNotes'])->name('orders.staff-notes.update');
    Route::delete('/orders/{orderId}/product-image', [OrderController::class, 'deleteProductImage'])->name('orders.product-image.delete');
    Route::get('/orders/{id}/export-excel', [OrderController::class, 'exportExcel'])->name('orders.export-excel');
});

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
    Route::post('/account/email-change/request', [AccountController::class, 'requestEmailChange'])->name('account.email-change.request');
    Route::post('/account/email-change/verify', [AccountController::class, 'verifyEmailChange'])->name('account.email-change.verify');
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
    Route::post('/_dev/login-as', [DevController::class, 'loginAs'])
        ->middleware('local')
        ->name('dev.login-as');
}

// Homepage test variants
Route::get('/homepagetest555', fn () => view('homepage-tests.555'));
Route::get('/homepagetest666', fn () => view('homepage-tests.666'));
Route::get('/homepagetest777', fn () => view('homepage-tests.777'));
Route::get('/homepagetest888', fn () => view('homepage-tests.888'));

require __DIR__.'/auth.php';

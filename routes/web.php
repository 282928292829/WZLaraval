<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CommentsController;
use App\Http\Controllers\CommentTemplateExportController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DevController;
use App\Http\Controllers\GoController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\OrderCommentController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderMergeController;
use App\Http\Controllers\OrderStatusController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Livewire\NewOrder;
use App\Livewire\OldNewOrder;
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
    session()->save();

    $url = url()->previous();
    $parsed = parse_url($url);
    $path = $parsed['path'] ?? '/';
    $query = [];
    if (! empty($parsed['query'])) {
        parse_str($parsed['query'], $query);
    }
    $query['lang'] = $locale;

    return redirect()
        ->to($path.'?'.http_build_query($query))
        ->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
})->name('language.switch');

Route::get('/', function () {
    return view('welcome');
});

Route::get('/offline', function () {
    return view('offline');
})->name('offline');

Route::get('/go/{slug}', GoController::class)->name('go');

// Legacy: redirect /order/{slug} → /orders/{slug} (WordPress used /order/; nginx should handle this too)
Route::get('/order/{order}', fn (string $order) => redirect()->route('orders.show', $order, 301))
    ->name('orders.show.legacy');

// Public blog
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
Route::post('/blog/{post}/comments', [BlogController::class, 'storeComment'])
    ->middleware('throttle:5,15')
    ->name('blog.comments.store');

// Public static pages — flat URLs (matches WordPress: /payment-methods, /faq, etc.)
Route::get('/pages/{slug}', fn (string $slug) => redirect('/'.$slug, 301))->name('pages.redirect');
Route::post('/{page:slug}/comments', [PageController::class, 'storeComment'])
    ->middleware('throttle:5,15')
    ->name('pages.comments.store');

// Contact form (public, throttled)
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:5,15')
    ->name('contact.store');

Route::get('/new-order', NewOrder::class)
    ->middleware('role.throttle:new-order')
    ->name('new-order');

// New order layouts — each URL always renders its own layout regardless of admin setting
Route::get('/new-order-cards', NewOrder::class)
    ->middleware('role.throttle:new-order')
    ->name('new-order-cards');

Route::get('/new-order-table', NewOrder::class)
    ->middleware('role.throttle:new-order')
    ->name('new-order-table');

Route::get('/new-order-hybrid', NewOrder::class)
    ->middleware('role.throttle:new-order')
    ->name('new-order-hybrid');

Route::get('/new-order-wizard', NewOrder::class)
    ->middleware('role.throttle:new-order')
    ->name('new-order-wizard');

Route::get('/new-order-cart', NewOrder::class)
    ->middleware('role.throttle:new-order')
    ->name('new-order-cart');

Route::get('/new-order-cart-inline', NewOrder::class)
    ->middleware('role.throttle:new-order')
    ->name('new-order-cart-inline');

Route::get('/new-order-cart-next', NewOrder::class)
    ->middleware('role.throttle:new-order')
    ->name('new-order-cart-next');

// Standalone reference: old responsive layout (Option 1). For AI reference when building the 5 new layouts.
Route::get('/old-new-order', OldNewOrder::class)
    ->middleware('role.throttle:new-order')
    ->name('old-new-order');

Route::middleware('auth')->group(function () {
    Route::post('/orders/dismiss-comments-discovery', [OrderController::class, 'dismissCommentsDiscovery'])->name('orders.comments-discovery.dismiss');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/all', [OrderController::class, 'allOrders'])->name('orders.all');
    Route::get('/orders/list-{variant}', [OrderController::class, 'indexVariant'])
        ->where('variant', 'simple|table|minimal')
        ->name('orders.list-variant');
    Route::post('/orders/bulk', [OrderController::class, 'bulkUpdate'])->name('orders.bulk-update');

    Route::get('/orders/{order}/success', [OrderController::class, 'success'])->name('orders.success');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/comments', [OrderCommentController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('orders.comments.store');
    Route::patch('/orders/{order}/comments/{commentId}', [OrderCommentController::class, 'update'])->name('orders.comments.update');
    Route::post('/orders/{order}/comments/{commentId}/attach-files', [OrderCommentController::class, 'attachFiles'])->name('orders.comments.attach-files');
    Route::delete('/orders/{order}/comments/{commentId}', [OrderCommentController::class, 'destroy'])->name('orders.comments.destroy');
    Route::post('/orders/{order}/status', [OrderStatusController::class, 'update'])->name('orders.status.update');
    Route::post('/orders/{order}/mark-paid', [OrderStatusController::class, 'markPaid'])->name('orders.mark-paid');
    Route::post('/orders/{order}/prices', [OrderController::class, 'updatePrices'])->name('orders.prices.update');
    Route::post('/orders/{order}/invoice', [OrderController::class, 'generateInvoice'])->name('orders.invoice.generate');
    Route::post('/orders/{order}/merge', OrderMergeController::class)->name('orders.merge');
    Route::post('/orders/{order}/comments/{commentId}/notify', [OrderCommentController::class, 'sendNotification'])->name('orders.comments.notify');
    Route::post('/orders/{order}/comments/{commentId}/log-whatsapp', [OrderCommentController::class, 'logWhatsAppSend'])->name('orders.comments.log-whatsapp');
    Route::post('/orders/{order}/comments/mark-read', [OrderCommentController::class, 'markRead'])->name('orders.comments.mark-read');
    Route::post('/orders/{order}/timeline/{timelineId}/add-as-comment', [OrderCommentController::class, 'addTimelineAsComment'])->name('orders.timeline.add-as-comment');
    Route::patch('/orders/{order}/shipping-address', [OrderController::class, 'updateShippingAddress'])->name('orders.shipping-address.update');
    Route::post('/orders/{order}/send-email', [OrderController::class, 'sendEmail'])->name('orders.send-email');

    // Customer quick actions
    Route::post('/orders/{order}/payment-notify', [OrderController::class, 'paymentNotify'])->name('orders.payment-notify');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancelOrder'])->name('orders.cancel');
    Route::post('/orders/{order}/customer-merge', [OrderController::class, 'customerMerge'])->name('orders.customer-merge');

    // Staff quick actions
    Route::post('/orders/{order}/transfer', [OrderController::class, 'transferOrder'])->name('orders.transfer');
    Route::post('/orders/{order}/shipping-tracking', [OrderController::class, 'updateShippingTracking'])->name('orders.shipping-tracking');
    Route::post('/orders/{order}/update-payment', [OrderController::class, 'updatePayment'])->name('orders.update-payment');
    Route::patch('/orders/{order}/staff-notes', [OrderController::class, 'updateStaffNotes'])->name('orders.staff-notes.update');
    Route::delete('/orders/{order}/product-image', [OrderController::class, 'deleteProductImage'])->name('orders.product-image.delete');
    Route::post('/orders/{order}/items/{itemId}/files', [OrderController::class, 'storeItemFiles'])->name('orders.items.files.store');
    Route::get('/orders/{order}/export-excel', [OrderController::class, 'exportExcel'])->name('orders.export-excel');
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
    Route::get('/comments', [CommentsController::class, 'index'])->name('comments.index');
    Route::get('/contact-submissions/{contactSubmission}', [ContactController::class, 'show'])->name('contact-submissions.show');
    Route::get('/inbox', [InboxController::class, 'index'])->name('inbox.index');
    Route::post('/inbox/mark-all-read', [InboxController::class, 'markAllRead'])->name('inbox.mark-all-read');
    Route::post('/inbox/{activity}/mark-read', [InboxController::class, 'markRead'])->name('inbox.mark-read');
    Route::get('/activity-files/{activityFile}/download', [\App\Http\Controllers\ActivityFileController::class, 'download'])->name('activity-files.download');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin: comment templates CSV export (requires manage-comment-templates permission)
Route::middleware(['auth'])->get('/admin/export-comment-templates-csv', CommentTemplateExportController::class)
    ->name('admin.comment-templates.export-csv');

// Dev-only quick login — never registered in production
if (app()->environment('local')) {
    Route::post('/_dev/login-as', [DevController::class, 'loginAs'])
        ->middleware('local')
        ->name('dev.login-as');
}

// Layout demo pages — one page per layout, titled by name
Route::get('/layout-demo/app', fn () => view('layouts-demo.app'));
Route::get('/layout-demo/guest', fn () => view('layouts-demo.guest'));
Route::get('/layout-demo/order', fn () => view('layouts.order', [
    'slot' => view('layouts-demo.order-content'),
    'title' => __('layouts_demo.order_layout'),
]));
Route::get('/layout-demo/order-focused', fn () => view('layouts.order-focused', [
    'slot' => view('layouts-demo.order-focused-content'),
    'title' => __('layouts_demo.order_focused_layout'),
]));
Route::get('/layout-demo/bare', fn () => view('layouts.bare', [
    'slot' => view('layouts-demo.bare-content'),
    'title' => __('layouts_demo.bare_layout'),
]));
// Homepage design demos — temporary test pages, may be removed
Route::get('/test-homepage-demo1', fn () => view('homepage-tests.demo1'));
Route::get('/test-homepage-demo2', fn () => view('homepage-tests.demo2'));
Route::get('/test-homepage-demo3', fn () => view('homepage-tests.demo3'));
Route::get('/test-homepage-demo4', fn () => view('homepage-tests.demo4'));

require __DIR__.'/auth.php';

// Testimonials — dedicated route (no Page record required)
Route::get('/testimonials', [\App\Http\Controllers\TestimonialsController::class, '__invoke'])->name('testimonials');

// Page fallback — flat URLs, must be last so auth routes (login, register, etc.) match first
Route::get('/{slug}', [PageController::class, 'show'])->name('pages.show');

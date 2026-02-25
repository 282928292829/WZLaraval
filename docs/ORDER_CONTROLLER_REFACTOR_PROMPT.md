# OrderController Refactor — AI Prompt

Use this prompt when asking an AI to refactor OrderController. Copy everything below the line.

---

## Context

- **Project:** Wasetzon Laravel rebuild (Laravel 12, Breeze, Livewire 3, Filament 4, Spatie Permission)
- **Rules:** Read `wasetzon.mdc` and `LARAVEL_PLAN.md`. Bilingual (ar/en), all strings via `__()`, Form Requests for validation, no `env()` outside config.
- **Already extracted:** OrderCommentController, OrderStatusController, OrderFileController, OrderMergeController
- **OrderController:** ~1,041 lines. Too large. Needs splitting.

## Current OrderController Methods (with line counts)

| Method | Lines | Purpose |
|--------|-------|---------|
| show | 83 | Load order with relations, authorize, compute edit flags, recent orders for merge, creation log, comments banner |
| updatePrices | 31 | Staff: set subtotal/total on an order |
| generateInvoice | 205 | Staff: create PDF invoice, attach to order, add comment |
| invoiceSettings | 26 | Load invoice settings (logo, labels, etc.) |
| buildInvoicePdf | 54 | Build PDF with mPDF, RTL, fonts |
| resolveInvoiceCommentMessage | 17 | Build invoice comment text with placeholders |
| updateShippingAddress | 47 | Customer/staff: update shipping address |
| index | 15 | Route to customer or staff list |
| customerIndex | 44 | Customer orders list with filters, stats, last order |
| staffIndex | 41 | Staff orders list with filters, search |
| bulkUpdate | 20 | Staff: bulk status change, AdCampaign updates |
| exportCsv | 57 | Staff: CSV export with filters (up to 10k rows) |
| sendEmail | 67 | Staff: queue order confirmation email |
| paymentNotify | 41 | Customer: report payment, add comment |
| cancelOrder | 31 | Customer: cancel order |
| customerMerge | 36 | Customer: request merge with another order |
| transferOrder | 68 | Staff: move order to another customer |
| updateShippingTracking | 26 | Staff: set carrier + tracking number |
| updatePayment | 33 | Staff: mark paid/unpaid |
| updateStaffNotes | 18 | Staff: update internal notes |
| deleteProductImage | 33 | Staff: delete product image (file or item) |
| exportExcel | 15 | Staff: export single order to Excel |

**Invoice block total:** ~302 lines (generateInvoice + invoiceSettings + buildInvoicePdf + resolveInvoiceCommentMessage)

**Export block total:** ~72 lines (exportCsv + exportExcel)

## Routes (web.php)

```
Route::get('/orders', [OrderController::class, 'index'])
Route::post('/orders/bulk', [OrderController::class, 'bulkUpdate'])
Route::get('/orders/{id}', [OrderController::class, 'show'])
Route::post('/orders/{id}/prices', [OrderController::class, 'updatePrices'])
Route::post('/orders/{id}/invoice', [OrderController::class, 'generateInvoice'])
Route::patch('/orders/{id}/shipping-address', [OrderController::class, 'updateShippingAddress'])
Route::post('/orders/{id}/send-email', [OrderController::class, 'sendEmail'])
Route::post('/orders/{id}/payment-notify', [OrderController::class, 'paymentNotify'])
Route::post('/orders/{id}/cancel', [OrderController::class, 'cancelOrder'])
Route::post('/orders/{id}/customer-merge', [OrderController::class, 'customerMerge'])
Route::post('/orders/{id}/transfer', [OrderController::class, 'transferOrder'])
Route::post('/orders/{id}/shipping-tracking', [OrderController::class, 'updateShippingTracking'])
Route::post('/orders/{id}/update-payment', [OrderController::class, 'updatePayment'])
Route::patch('/orders/{id}/staff-notes', [OrderController::class, 'updateStaffNotes'])
Route::delete('/orders/{orderId}/product-image', [OrderController::class, 'deleteProductImage'])
Route::get('/orders/{id}/export-excel', [OrderController::class, 'exportExcel'])
```

Export CSV is triggered via `index` with `?export=csv` query param (staff only).

## Key Files

- Controller: `app/Http/Controllers/OrderController.php`
- Invoice view: `resources/views/orders/invoice-pdf-mpdf.blade.php`
- Form Requests: `app/Http/Requests/Order/` (GenerateInvoiceRequest, UpdatePricesRequest, etc.)
- OrderExport (Excel): `app/Exports/OrderExport.php`

## Task

1. **Decide** which methods to extract into new controllers. Options:
   - OrderInvoiceController (generateInvoice + invoiceSettings + buildInvoicePdf + resolveInvoiceCommentMessage)
   - OrderExportController (exportCsv, exportExcel — or keep exportExcel in OrderController since it uses OrderExport)
   - Other splits if justified

2. **Implement** the extraction:
   - Create new controller(s)
   - Move methods and private helpers
   - Update routes in `routes/web.php`
   - Update any internal references (e.g. `$this->exportCsv` → call new controller or extract to service)
   - Ensure Form Requests, `__()`, and authorization stay correct
   - Run `vendor/bin/pint --dirty --format agent`
   - Run relevant tests: `php artisan test --compact --filter=Order`

3. **Do not** change behavior, only structure. Keep route names and URLs the same unless you have a good reason.

## Constraints

- Stick to existing directory structure
- No new base folders without approval
- All user-facing strings must use `__()` with keys in lang/ar.json and lang/en.json
- Use Form Request classes for validation
- Follow Laravel 12 conventions (bootstrap/app.php for middleware, etc.)

# Authorization Patterns

This document describes how order-related and comment-related actions are authorized across the application. All order actions use either **OrderPolicy** (model-based) or **Spatie abilities** via `authorize('permission-name')`. Consistency is enforced so that authorization logic is centralized and testable.

## Pattern Summary

| Pattern | Use When | Example |
|---------|----------|---------|
| **OrderPolicy** | Actions on an Order that depend on ownership and/or staff permissions | `$this->authorize('view', $order)` |
| **Spatie ability** | Staff-only actions that don't need order context (or need only the permission) | `$this->authorize('edit-prices')` |
| **OrderCommentPolicy** | Actions on an OrderComment (edit, delete, attach files) | `$this->authorize('update', $comment)` |

## OrderPolicy (app/Policies/OrderPolicy.php)

- **view** — User can view order: owner with `view-own-orders`, or staff with `view-all-orders`.
- **viewAny** — User can list orders: has `view-own-orders` or `view-all-orders`.
- **create** — User can create orders: has `create-orders`.
- **update** — User can update order: owner always; staff with `update-order-status`, `edit-prices`, `merge-orders`, or `reply-to-comments`.
- **performCustomerAction** — Owner-only actions: payment notify, cancel, customer merge request.
- **addItemFiles** — Add files to order items after submit: staff always; customers when `customer_can_add_files_after_submit` is enabled.

**Used by:** `OrderController::show`, `OrderController::success`, `OrderController::updateShippingAddress`, `OrderMergeController`, `OrderCommentController::store` (via update), `OrderController::storeItemFiles` (addItemFiles), etc.

## Spatie Abilities (authorize with permission name)

| Permission | Where Used |
|------------|------------|
| `edit-prices` | `OrderController::updatePrices` |
| `generate-pdf-invoice` | `OrderController::generateInvoice` |
| `bulk-update-orders` | `OrderController::bulkUpdate` |
| `update-order-status` | `OrderStatusController::update`, `OrderStatusController::markPaid` |
| `merge-orders` | `OrderMergeController` |
| `send-comment-notification` | `OrderCommentController::sendNotification`, `OrderCommentController::logWhatsAppSend` |
| `view-all-orders` | `OrderController::index` (staff path), `OrderController::sendEmail`, `OrderCommentController::addTimelineAsComment`, `OrderCommentController::markRead` |
| `manage-comment-templates` | `CommentTemplateExportController::export` |
| `export-csv` | `OrderController::allOrders` (when export=csv) |

## OrderCommentPolicy (app/Policies/OrderCommentPolicy.php)

- **update** — User can edit comment: comment owner, or staff with `reply-to-comments`. Never for system comments.
- **delete** — User can delete comment: comment owner (with `delete-own-comment`), or staff with `delete-any-comment`. Never for system comments.

**Used by:** `OrderCommentController::update`, `OrderCommentController::attachFiles`, `OrderCommentController::destroy`.

## Owner-only actions (no separate permission)

For actions that only the order owner may perform (e.g. `paymentNotify`, `cancelOrder`, `customerMerge`), we use `$this->authorize('performCustomerAction', $order)` which checks `$order->user_id === $user->id`.

## Route middleware

- `can:view-all-orders` — Staff routes: `/comments`, `/inbox`, `/contact-submissions`, etc.
- `auth` — All order and account routes require authentication.

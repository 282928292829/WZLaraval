<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\Setting;
use App\Models\User;

class OrderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view-own-orders') || $user->can('view-all-orders');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Order $order): bool
    {
        if ($order->user_id === $user->id) {
            return $user->can('view-own-orders');
        }

        return $user->can('view-all-orders');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create-orders');
    }

    /**
     * Determine whether the user can update the model.
     * Staff with update-order-status, edit-prices, or merge-orders can update.
     */
    public function update(User $user, Order $order): bool
    {
        if ($order->user_id === $user->id) {
            return true;
        }

        return $user->can('update-order-status')
            || $user->can('edit-prices')
            || $user->can('merge-orders')
            || $user->can('reply-to-comments');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Order $order): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Order $order): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Order $order): bool
    {
        return false;
    }

    /**
     * Determine whether the user can perform customer-only actions (payment notify, cancel, customer merge request).
     * Only the order owner may perform these.
     */
    public function performCustomerAction(User $user, Order $order): bool
    {
        return $order->user_id === $user->id;
    }

    /**
     * Determine whether the user can add files to order items after submission.
     * Staff can always add; customers only when customer_can_add_files_after_submit is enabled.
     */
    public function addItemFiles(User $user, Order $order): bool
    {
        if ($user->isStaffOrAbove()) {
            return true;
        }

        if ($order->user_id !== $user->id) {
            return false;
        }

        return (bool) Setting::get('customer_can_add_files_after_submit', false);
    }
}

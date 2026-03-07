<?php

namespace App\Policies;

use App\Models\OrderComment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrderCommentPolicy
{
    /**
     * Determine whether the user can update the comment (edit body, attach files).
     * Comment owner or staff with reply-to-comments may edit. System comments cannot be edited.
     */
    public function update(User $user, OrderComment $orderComment): bool|Response
    {
        if ($orderComment->is_system) {
            return Response::deny(__('orders.cannot_edit_system_comment'));
        }

        if ($orderComment->user_id === $user->id) {
            return true;
        }

        return $user->isStaffOrAbove() && $user->can('reply-to-comments');
    }

    /**
     * Determine whether the user can delete the comment.
     * Comment owner needs delete-own-comment; staff needs delete-any-comment.
     */
    public function delete(User $user, OrderComment $orderComment): bool
    {
        if ($orderComment->user_id === $user->id) {
            return $user->can('delete-own-comment');
        }

        return $user->isStaffOrAbove() && $user->can('delete-any-comment');
    }
}

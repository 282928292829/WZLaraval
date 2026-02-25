<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Permission Labels
    |--------------------------------------------------------------------------
    | Human-readable labels for permissions shown in the user edit form.
    | Keys must match permission names in RoleAndPermissionSeeder.
    */
    'labels' => [
        // Access & dashboard
        'access-filament' => 'Access admin panel',
        'view-staff-dashboard' => 'View staff dashboard',

        // Orders
        'create-orders' => 'Create orders',
        'view-own-orders' => 'View own orders',
        'view-all-orders' => 'View all orders',
        'update-order-status' => 'Update order status',
        'edit-prices' => 'Edit order prices',
        'bulk-update-orders' => 'Bulk update orders',
        'merge-orders' => 'Merge orders',
        'export-csv' => 'Export orders to CSV',
        'generate-pdf-invoice' => 'Generate PDF invoice',
        'upload-receipt' => 'Upload payment receipt',

        // Comments
        'comment-on-own-orders' => 'Comment on own orders',
        'reply-to-comments' => 'Reply to comments',
        'edit-own-comment' => 'Edit own comment',
        'delete-own-comment' => 'Delete own comment',
        'delete-any-comment' => 'Delete any comment',
        'add-internal-note' => 'Add internal notes',
        'view-internal-note' => 'View internal notes',
        'view-comment-reads' => 'View comment read status',
        'send-comment-notification' => 'Send comment notification',

        // Profile & users
        'manage-own-profile' => 'Manage own profile',
        'manage-users' => 'Manage users',
        'ban-users' => 'Ban / unban users',
        'assign-user-roles' => 'Assign user roles',
        'assign-user-permissions' => 'Assign user permissions',
        'manage-admins' => 'Manage admin accounts',
        'demote-admins' => 'Demote admins',

        // Content & settings
        'manage-posts' => 'Manage blog posts',
        'manage-pages' => 'Manage static pages',
        'manage-settings' => 'Manage site settings',
        'manage-shipping-companies' => 'Manage shipping companies',
        'manage-currencies' => 'Manage currencies',
        'manage-exchange-rates' => 'Manage exchange rates',
        'edit-commission-rules' => 'Edit commission rules',
        'manage-ad-campaigns' => 'Manage ad campaigns',
        'manage-comment-templates' => 'Manage comment templates',
        'manage-roles' => 'Manage roles and permissions',
    ],
];

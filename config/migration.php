<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Legacy database table prefix
    |--------------------------------------------------------------------------
    |
    | WordPress table prefix (e.g. wp_, wp_2_ for multisite).
    | Used by the legacy DB connection when querying tables.
    |
    */
    'legacy_db_prefix' => env('LEGACY_DB_PREFIX', 'wp_'),

    /*
    |--------------------------------------------------------------------------
    | Legacy uploads path
    |--------------------------------------------------------------------------
    |
    | Absolute path to the legacy WordPress wp-content/uploads directory.
    | Used by migrate:order-files to locate product images and comment
    | attachments from the old site.
    |
    | Sole source: Wordpress/pwa3/old-wordpress/old-wp-content/uploads
    |
    */
    'legacy_uploads_path' => env(
        'LEGACY_UPLOADS_PATH',
        base_path('../Wordpress/pwa3/old-wordpress/old-wp-content/uploads')
    ),

    /*
    |--------------------------------------------------------------------------
    | Superadmin emails
    |--------------------------------------------------------------------------
    |
    | Comma-separated emails to assign superadmin role after migration.
    | Set SUPERADMIN_EMAILS in .env to override.
    |
    */
    'superadmin_emails' => array_filter(array_map('trim', explode(',', env('SUPERADMIN_EMAILS', 'abdulsgz@hotmail.com,ulgasan491@yahoo.com,aminoos@live.com')))),

];

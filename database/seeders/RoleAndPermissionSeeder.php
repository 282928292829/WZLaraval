<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Permissions grouped by the role level they are first granted at.
     * Higher roles inherit all lower-level permissions.
     */
    private array $permissions = [

        // ── Customer-level ───────────────────────────────────────────────
        'customer' => [
            'create-orders',           // submit a new order
            'view-own-orders',         // view own order list & detail
            'upload-receipt',          // upload payment proof on own orders
            'comment-on-own-orders',   // post comments on own orders
            'edit-own-comment',        // edit own comment (within time window)
            'delete-own-comment',      // delete own comment (within time window)
            'manage-own-profile',      // update name / email / password / address
        ],

        // ── Editor-level (editors + admins + superadmins) ───────────────
        'editor' => [
            'view-staff-dashboard',    // see staff dashboard section
            'view-all-orders',         // see every order in the system
            'update-order-status',     // change order status dropdown
            'reply-to-comments',       // comment on any order
            'delete-any-comment',      // remove any user comment
            'add-internal-note',       // post internal notes (hidden from customers)
            'view-internal-note',      // read internal notes on orders
            'merge-orders',            // merge two orders into one
            'edit-prices',             // set/edit subtotal & total on orders
            'export-csv',              // export filtered orders to CSV/Excel
            'generate-pdf-invoice',    // generate and attach PDF invoice
            'bulk-update-orders',      // bulk-select & apply status/action changes
            'send-comment-notification', // manually trigger email on a comment
            'view-comment-reads',      // see "read by" list per comment
        ],

        // ── Admin-level (admins + superadmins) ───────────────────────────
        'admin' => [
            'access-filament',         // enter the /admin Filament panel
            'manage-posts',            // create / edit / delete blog posts
            'manage-pages',            // create / edit / delete static pages
            'manage-settings',         // change site settings in Filament
            'manage-users',            // view / edit / search users
            'ban-users',               // ban / unban user accounts
            'assign-user-roles',       // assign roles to users from Filament
            'assign-user-permissions', // grant/revoke individual permissions per user
            'manage-currencies',       // enable/disable currencies in settings
            'manage-exchange-rates',   // set exchange rates per currency
            'edit-commission-rules',   // configure commission percentages
        ],

        // ── Superadmin-level (superadmins only) ──────────────────────────
        'superadmin' => [
            'manage-admins',           // view / manage admin accounts
            'demote-admins',           // demote an admin to editor or below
        ],
    ];

    public function run(): void
    {
        // Reset cached roles and permissions so we start fresh
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Create all permissions ────────────────────────────────────────
        $allPermissions = collect($this->permissions)->flatten()->unique();

        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // ── Create roles ──────────────────────────────────────────────────
        $guest = Role::firstOrCreate(['name' => 'guest',      'guard_name' => 'web']);
        $customer = Role::firstOrCreate(['name' => 'customer',   'guard_name' => 'web']);
        $editor = Role::firstOrCreate(['name' => 'editor',     'guard_name' => 'web']);
        $admin = Role::firstOrCreate(['name' => 'admin',      'guard_name' => 'web']);
        $superadmin = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);

        // ── Assign permissions to roles (cumulative / hierarchical) ───────

        // Guest: can browse the site; no permissions required
        $guest->syncPermissions([]);

        // Customer: customer-level permissions
        $customer->syncPermissions(
            $this->permissions['customer']
        );

        // Editor: customer-level + editor-level
        $editor->syncPermissions(array_merge(
            $this->permissions['customer'],
            $this->permissions['editor'],
        ));

        // Admin: customer-level + editor-level + admin-level
        $admin->syncPermissions(array_merge(
            $this->permissions['customer'],
            $this->permissions['editor'],
            $this->permissions['admin'],
        ));

        // Superadmin: all permissions
        $superadmin->syncPermissions($allPermissions);

        // ── Seed one test user per role ───────────────────────────────────
        $this->seedTestUsers($guest, $customer, $editor, $admin, $superadmin);

        $this->command->info('✓ Roles and permissions seeded.');
        $this->command->table(
            ['Role', 'Permissions'],
            [
                ['guest',      '0 (browse only)'],
                ['customer',   count($this->permissions['customer'])],
                ['editor',     count($this->permissions['customer']) + count($this->permissions['editor'])],
                ['admin',      count($this->permissions['customer']) + count($this->permissions['editor']) + count($this->permissions['admin'])],
                ['superadmin', $allPermissions->count()],
            ]
        );
    }

    private function seedTestUsers(
        Role $guest,
        Role $customer,
        Role $editor,
        Role $admin,
        Role $superadmin,
    ): void {
        $users = [
            [
                'name' => 'Guest User',
                'email' => 'guest@wasetzon.test',
                'password' => 'password',
                'role' => $guest,
            ],
            [
                'name' => 'Customer User',
                'email' => 'customer@wasetzon.test',
                'password' => 'password',
                'role' => $customer,
            ],
            [
                'name' => 'Editor User',
                'email' => 'editor@wasetzon.test',
                'password' => 'password',
                'role' => $editor,
            ],
            [
                'name' => 'Admin User',
                'email' => 'admin@wasetzon.test',
                'password' => 'password',
                'role' => $admin,
            ],
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@wasetzon.test',
                'password' => 'password',
                'role' => $superadmin,
            ],
        ];

        foreach ($users as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make($data['password']),
                    'email_verified_at' => now(),
                ]
            );

            // Remove all roles first, then assign the correct one
            $user->syncRoles([$data['role']]);
        }
    }
}

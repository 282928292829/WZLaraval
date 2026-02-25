<?php

namespace App\Console\Commands;

use App\Models\AdCampaign;
use App\Models\CommentTemplate;
use App\Models\Order;
use App\Models\OrderComment;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\ImportCommentTemplatesFromWordPress;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ImportFromWordPress extends Command
{
    protected $signature = 'wp:import
        {--users : Import users only}
        {--addresses : Import user addresses only}
        {--orders : Import orders only}
        {--items : Import order items only}
        {--comments : Import order comments only}
        {--timeline : Import order timeline only}
        {--ad-campaigns : Import ad campaigns (myads) only}
        {--comment-templates : Import comment templates only}
        {--all : Import everything (default)}
    ';

    protected $description = 'Import data from legacy WordPress DB into Laravel';

    private const STATUS_MAP = [
        0 => 'pending',
        1 => 'needs_payment',
        2 => 'processing',
        3 => 'purchasing',
        4 => 'shipped',
        5 => 'delivered',
        6 => 'cancelled',
        7 => 'on_hold',
    ];

    private array $postIdToOrderId = [];

    private array $emailCount = [];

    private array $usedOrderNumbers = [];

    public function handle(): int
    {
        $legacy = DB::connection('legacy');
        if (! $legacy->getSchemaBuilder()->hasTable('wp_users')) {
            $this->error('Legacy DB not configured or wp_users missing.');

            return 1;
        }

        $all = $this->option('all') || ! ($this->option('users') || $this->option('addresses') || $this->option('orders')
            || $this->option('items') || $this->option('comments') || $this->option('timeline') || $this->option('ad-campaigns')
            || $this->option('comment-templates'));

        if ($all || $this->option('ad-campaigns')) {
            $this->importAdCampaigns();
        }
        if ($all || $this->option('comment-templates')) {
            $this->importCommentTemplates();
        }
        if ($all || $this->option('users')) {
            $this->importUsers();
        }
        if ($all || $this->option('addresses')) {
            $this->importAddresses();
        }
        if ($all || $this->option('orders')) {
            $this->importOrders();
        }
        if ($all || $this->option('items')) {
            $this->importOrderItems();
        }
        if ($all || $this->option('comments')) {
            $this->importComments();
        }
        if ($all || $this->option('timeline')) {
            $this->importTimeline();
        }

        if ($all) {
            $this->fixMerges();
            $this->assignSuperadmin();
        }

        $this->info('Import complete.');

        return 0;
    }

    private function importUsers(): void
    {
        $this->info('Importing users...');
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('model_has_roles')->truncate();
        DB::table('users')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $legacy = DB::connection('legacy');
        $bar = $this->output->createProgressBar($legacy->table('wp_users')->count());
        $bar->start();

        $defaultPassword = Hash::make('MigrateReset123');
        $now = now();

        $legacy->table('wp_users')
            ->orderBy('ID')
            ->chunk(500, function ($users) use ($defaultPassword, $now, $bar) {
                $rows = [];
                foreach ($users as $u) {
                    $email = $this->uniqueEmail($u->user_email, (int) $u->ID);
                    $rows[] = [
                        'id' => (int) $u->ID,
                        'name' => $u->display_name ?: $u->user_login,
                        'email' => $email,
                        'password' => $defaultPassword,
                        'phone' => null,
                        'email_verified_at' => null,
                        'locale' => 'ar',
                        'avatar' => null,
                        'is_banned' => false,
                        'created_at' => $u->user_registered && $u->user_registered !== '0000-00-00 00:00:00'
                            ? $u->user_registered
                            : $now,
                        'updated_at' => $now,
                    ];
                }
                DB::table('users')->insert($rows);
                $bar->advance($users->count());
            });

        $bar->finish();
        $this->newLine();
        $this->info('Users: '.User::count());
    }

    private function uniqueEmail(string $email, int $userId): string
    {
        $key = strtolower(trim($email));
        $this->emailCount[$key] = ($this->emailCount[$key] ?? 0) + 1;
        $n = $this->emailCount[$key];
        if ($n === 1) {
            return $email;
        }
        $parts = explode('@', $email, 2);
        $local = preg_replace('/[^a-zA-Z0-9._+-]/', '', $parts[0] ?? 'user');
        $domain = $parts[1] ?? 'local';

        return $local.'+dup'.$userId.'@'.$domain;
    }

    private function importAddresses(): void
    {
        $this->info('Importing user addresses...');
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('user_addresses')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $legacy = DB::connection('legacy');
        $meta = $legacy->table('wp_usermeta')->where('meta_key', 'saved_addresses')->get();
        if ($meta->isEmpty()) {
            $this->warn('No saved_addresses in legacy DB. Skipping.');

            return;
        }

        $rows = [];
        $now = now();
        foreach ($meta as $m) {
            $addrs = @unserialize($m->meta_value);
            if (! is_array($addrs)) {
                $addrs = json_decode($m->meta_value, true) ?: [];
            }
            foreach ($addrs as $i => $addr) {
                if (! is_array($addr)) {
                    continue;
                }
                $rows[] = [
                    'user_id' => (int) $m->user_id,
                    'label' => $addr['label'] ?? null,
                    'recipient_name' => $addr['recipient_name'] ?? $addr['name'] ?? null,
                    'phone' => $addr['phone'] ?? null,
                    'country' => $addr['country'] ?? 'SA',
                    'city' => $addr['city'] ?? '',
                    'address' => $addr['address'] ?? $addr['street'] ?? '',
                    'is_default' => ! empty($addr['is_default']),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        if ($rows) {
            foreach (array_chunk($rows, 200) as $chunk) {
                DB::table('user_addresses')->insert($chunk);
            }
        }
        $this->info('User addresses: '.UserAddress::count());
    }

    private function importOrders(): void
    {
        $this->info('Importing orders...');
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('order_items')->truncate();
        DB::table('order_comments')->truncate();
        DB::table('order_timeline')->truncate();
        DB::table('order_files')->truncate();
        DB::table('orders')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $legacy = DB::connection('legacy');
        $total = $legacy->table('wp_posts')
            ->where('post_type', 'orders')
            ->where('post_status', 'publish')
            ->count();

        $this->info('Orders to import: '.$total);
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $now = now();
        $this->postIdToOrderId = [];
        $this->usedOrderNumbers = [];

        $legacy->table('wp_posts')
            ->where('post_type', 'orders')
            ->where('post_status', 'publish')
            ->orderBy('ID')
            ->chunk(1000, function ($posts) use ($legacy, $now, $bar) {
                $postIds = $posts->pluck('ID')->toArray();
                $allMeta = $legacy->table('wp_postmeta')
                    ->whereIn('post_id', $postIds)
                    ->get()
                    ->groupBy('post_id');

                foreach ($posts as $post) {
                    $meta = collect($allMeta->get($post->ID, []))->keyBy('meta_key');
                    $orderIdMeta = $meta->get('order_id');
                    $orderNumber = $orderIdMeta ? trim($orderIdMeta->meta_value) : (string) $post->ID;
                    if (strlen($orderNumber) > 20) {
                        $orderNumber = substr($orderNumber, 0, 20);
                    }
                    if (isset($this->usedOrderNumbers[$orderNumber])) {
                        $orderNumber = substr($orderNumber, 0, 15).'_'.$post->ID;
                    }
                    $this->usedOrderNumbers[$orderNumber] = true;
                    $statusMeta = $meta->get('order_status');
                    $wpStatus = $statusMeta ? (int) $statusMeta->meta_value : 0;
                    $status = self::STATUS_MAP[$wpStatus] ?? 'pending';

                    $paymentAmount = $meta->get('payment_amount')?->meta_value;
                    $isPaid = $paymentAmount && (float) $paymentAmount > 0;

                    $userId = (int) $post->post_author;
                    if ($userId <= 0 || ! DB::table('users')->where('id', $userId)->exists()) {
                        $userId = 1;
                    }

                    $row = [
                        'order_number' => $orderNumber,
                        'user_id' => $userId,
                        'status' => $status,
                        'layout_option' => 2,
                        'notes' => $post->post_content ?: null,
                        'is_paid' => $isPaid,
                        'paid_at' => $isPaid ? $now : null,
                        'payment_proof' => $meta->get('payment_receipt')?->meta_value,
                        'total_amount' => $paymentAmount ? (float) $paymentAmount : null,
                        'payment_amount' => $paymentAmount ? (float) $paymentAmount : null,
                        'payment_date' => $this->parseDate($meta->get('payment_date')?->meta_value),
                        'payment_method' => $meta->get('payment_method')?->meta_value,
                        'payment_receipt' => $meta->get('payment_receipt')?->meta_value,
                        'tracking_number' => $meta->get('tracking_number')?->meta_value,
                        'tracking_company' => $meta->get('tracking_company')?->meta_value,
                        'shipping_address_snapshot' => $this->parseJson($meta->get('shipping_address_snapshot')?->meta_value),
                        'currency' => 'SAR',
                        'created_at' => $post->post_date !== '0000-00-00 00:00:00' ? $post->post_date : $now,
                        'updated_at' => $post->post_modified !== '0000-00-00 00:00:00' ? $post->post_modified : $now,
                    ];

                    $id = DB::table('orders')->insertGetId($row);
                    $this->postIdToOrderId[(int) $post->ID] = $id;
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();
        $this->info('Orders: '.Order::count());
    }

    private function importOrderItems(): void
    {
        $this->info('Importing order items...');
        if (empty($this->postIdToOrderId)) {
            $this->loadPostIdMapping();
        }
        if (empty($this->postIdToOrderId)) {
            $this->error('No orders. Run --orders first.');

            return;
        }
        $this->info('Post-to-order mapping: '.count($this->postIdToOrderId).' entries');

        $legacy = DB::connection('legacy');
        $postIds = array_keys($this->postIdToOrderId);
        $total = 0;
        foreach (array_chunk($postIds, 1000) as $chunk) {
            $total += $legacy->table('wp_postmeta')
                ->whereIn('post_id', $chunk)
                ->where(function ($q) {
                    $q->where('meta_key', 'order_products_json')
                        ->orWhere('meta_key', 'like', 'p_%');
                })
                ->count();
        }
        $total = max($total, count($postIds)) ?: count($postIds);

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $now = now();
        $totalItems = 0;

        foreach (array_chunk($postIds, 1000) as $chunk) {
            $posts = $legacy->table('wp_postmeta')
                ->whereIn('post_id', $chunk)
                ->where(function ($q) {
                    $q->where('meta_key', 'order_products_json')
                        ->orWhere('meta_key', 'like', 'p_%');
                })
                ->get()
                ->groupBy('post_id');

            foreach ($posts as $postId => $metaRows) {
                $orderId = $this->postIdToOrderId[(int) $postId] ?? null;
                if (! $orderId) {
                    continue;
                }
                $metaByKey = $metaRows->keyBy('meta_key');
                $jsonRow = $metaByKey->get('order_products_json');
                $products = $jsonRow ? $this->parseJson($jsonRow->meta_value) : null;
                if (! is_array($products)) {
                    $products = $this->getLegacyProducts($metaByKey);
                }
                if (empty($products)) {
                    $bar->advance();

                    continue;
                }
                $rows = [];
                foreach ($products as $i => $p) {
                    $url = is_string($p) ? $p : ($p['url'] ?? $p['link'] ?? null);
                    $rows[] = [
                        'order_id' => $orderId,
                        'url' => $url,
                        'is_url' => $url && (str_starts_with($url, 'http') || str_contains($url, '.')),
                        'qty' => min(65535, max(1, (int) ($p['qty'] ?? $p['quantity'] ?? 1))),
                        'color' => isset($p['color']) ? mb_substr((string) $p['color'], 0, 100) : null,
                        'size' => isset($p['size']) ? mb_substr((string) $p['size'], 0, 100) : null,
                        'notes' => isset($p['notes']) || isset($p['info']) ? Str::limit($p['notes'] ?? $p['info'] ?? '', 65535) : null,
                        'unit_price' => isset($p['price']) ? (float) $p['price'] : null,
                        'final_price' => isset($p['final_price']) ? (float) $p['final_price'] : null,
                        'sort_order' => $i,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                if ($rows) {
                    DB::table('order_items')->insert($rows);
                    $totalItems += count($rows);
                }
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info('Order items: '.$totalItems);
    }

    private function importComments(): void
    {
        $this->info('Importing order comments...');
        if (empty($this->postIdToOrderId)) {
            $this->loadPostIdMapping();
        }
        if (empty($this->postIdToOrderId)) {
            $this->error('No orders. Run --orders first.');

            return;
        }

        $legacy = DB::connection('legacy');
        $postIds = array_keys($this->postIdToOrderId);
        $orderUserIds = DB::table('orders')->pluck('user_id', 'id')->toArray();
        $now = now();

        $total = 0;
        foreach (array_chunk($postIds, 1000) as $chunk) {
            $total += $legacy->table('wp_comments')
                ->whereIn('comment_post_ID', $chunk)
                ->where('comment_approved', '1')
                ->count();
        }
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach (array_chunk($postIds, 1000) as $chunk) {
            $comments = $legacy->table('wp_comments')
                ->whereIn('comment_post_ID', $chunk)
                ->where('comment_approved', '1')
                ->orderBy('comment_ID')
                ->get();

            foreach ($comments as $c) {
                $orderId = $this->postIdToOrderId[(int) $c->comment_post_ID] ?? null;
                if (! $orderId) {
                    $bar->advance();

                    continue;
                }
                $userId = (int) $c->user_id;
                if ($userId <= 0 || ! User::find($userId)) {
                    $userId = $orderUserIds[$orderId] ?? 1;
                }

                DB::table('order_comments')->insert([
                    'order_id' => $orderId,
                    'user_id' => $userId,
                    'body' => $c->comment_content,
                    'is_internal' => false,
                    'is_edited' => false,
                    'created_at' => $c->comment_date !== '0000-00-00 00:00:00' ? $c->comment_date : $now,
                    'updated_at' => $c->comment_date !== '0000-00-00 00:00:00' ? $c->comment_date : $now,
                ]);
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info('Comments: '.OrderComment::count());
    }

    private function importTimeline(): void
    {
        $this->info('Importing order timeline...');
        if (empty($this->postIdToOrderId)) {
            $this->loadPostIdMapping();
        }
        if (empty($this->postIdToOrderId)) {
            $this->error('No orders. Run --orders first.');

            return;
        }

        $legacy = DB::connection('legacy');
        $postIds = array_keys($this->postIdToOrderId);
        $metaCount = 0;
        foreach (array_chunk($postIds, 1000) as $chunk) {
            $metaCount += $legacy->table('wp_postmeta')
                ->where('meta_key', 'activity_log')
                ->whereIn('post_id', $chunk)
                ->count();
        }
        $bar = $this->output->createProgressBar($metaCount ?: 1);
        $bar->start();

        $total = 0;
        foreach (array_chunk($postIds, 1000) as $chunk) {
            $meta = $legacy->table('wp_postmeta')
                ->where('meta_key', 'activity_log')
                ->whereIn('post_id', $chunk)
                ->get();

            foreach ($meta as $m) {
                $orderId = $this->postIdToOrderId[(int) $m->post_id] ?? null;
                if (! $orderId) {
                    $bar->advance();

                    continue;
                }
                $log = $this->parseJson($m->meta_value);
                if (! is_array($log)) {
                    $log = @unserialize($m->meta_value) ?: [];
                }
                foreach ($log as $entry) {
                    if (! is_array($entry)) {
                        continue;
                    }
                    $type = $this->mapActivityType($entry['action'] ?? '');
                    $body = $entry['details'] ?? $entry['action'] ?? null;
                    if (is_array($body)) {
                        $body = json_encode($body);
                    }

                    DB::table('order_timeline')->insert([
                        'order_id' => $orderId,
                        'user_id' => null,
                        'type' => $type,
                        'status_from' => null,
                        'status_to' => null,
                        'body' => $body,
                        'created_at' => $entry['timestamp'] ?? now(),
                    ]);
                    $total++;
                }
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info('Timeline entries: '.$total);
    }

    /**
     * Import ad campaigns from WordPress myads post type (edit.php?post_type=myads).
     * Maps: post_title→title, unique_url→slug, website→destination_url, clicks→click_count,
     * register→(users), orders→order_count, purchase→orders_delivered.
     */
    private function importAdCampaigns(): void
    {
        $this->info('Importing ad campaigns (myads)...');

        $legacy = DB::connection('legacy');
        if (! $legacy->getSchemaBuilder()->hasTable('wp_posts')) {
            $this->error('Legacy wp_posts table not found.');

            return;
        }

        $posts = $legacy->table('wp_posts')
            ->where('post_type', 'myads')
            ->where('post_status', 'publish')
            ->orderBy('ID')
            ->get();

        if ($posts->isEmpty()) {
            $this->warn('No myads posts found in legacy DB.');

            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('users')->update(['ad_campaign_id' => null]);
        DB::table('ad_campaigns')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $postIds = $posts->pluck('ID')->all();
        $metaRows = $legacy->table('wp_postmeta')
            ->whereIn('post_id', $postIds)
            ->whereIn('meta_key', ['unique_url', 'website', 'clicks', 'register', 'orders', 'purchase'])
            ->get();

        $metaByPost = $metaRows->groupBy('post_id')->map(fn ($rows) => $rows->keyBy('meta_key'));

        $bar = $this->output->createProgressBar($posts->count());
        $bar->start();

        foreach ($posts as $post) {
            $meta = $metaByPost->get($post->ID, collect());
            $getMeta = fn ($key, $default = null) => $meta->get($key)?->meta_value ?? $default;

            $uniqueUrl = $getMeta('unique_url', (string) $post->ID);
            $slug = 'myad-'.$post->ID;
            $website = trim((string) $getMeta('website', ''));
            $clicks = (int) $getMeta('clicks', 0);
            $orders = (int) $getMeta('orders', 0);
            $purchase = (int) $getMeta('purchase', 0);

            $destinationUrl = $website !== '' && (str_starts_with($website, 'http') || str_starts_with($website, '/')) ? $website : null;
            if ($destinationUrl !== null && strlen($destinationUrl) > 255) {
                $destinationUrl = substr($destinationUrl, 0, 255);
            }

            AdCampaign::create([
                'title' => $post->post_title ?: 'Campaign '.$post->ID,
                'slug' => $slug,
                'destination_url' => $destinationUrl,
                'tracking_code' => $uniqueUrl !== (string) $post->ID ? $uniqueUrl : null,
                'platform' => null,
                'notes' => $post->post_excerpt ?: null,
                'is_active' => true,
                'click_count' => $clicks,
                'order_count' => $orders,
                'orders_cancelled' => 0,
                'orders_shipped' => 0,
                'orders_delivered' => $purchase,
            ]);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Ad campaigns: '.AdCampaign::count());
    }

    /**
     * Import comment templates from WordPress comments_template post type.
     */
    private function importCommentTemplates(): void
    {
        $this->info('Importing comment templates...');

        $result = app(ImportCommentTemplatesFromWordPress::class)->import(replaceExisting: true);

        if (! $result['success']) {
            $this->error($result['message']);

            return;
        }

        $this->info('Comment templates: '.CommentTemplate::count());
    }

    private function mapActivityType(string $action): string
    {
        return match (true) {
            str_contains($action, 'status') => 'status_change',
            str_contains($action, 'comment') => 'comment',
            str_contains($action, 'file') => 'file_upload',
            str_contains($action, 'payment') => 'payment',
            str_contains($action, 'merge') => 'merge',
            default => 'note',
        };
    }

    private function fixMerges(): void
    {
        $this->info('Fixing merge references...');
        if (empty($this->postIdToOrderId)) {
            $this->loadPostIdMapping();
        }

        $legacy = DB::connection('legacy');
        $postIds = array_keys($this->postIdToOrderId);
        $updated = 0;

        foreach (array_chunk($postIds, 1000) as $chunk) {
            $mergedInto = $legacy->table('wp_postmeta')
                ->where('meta_key', 'merged_into')
                ->whereIn('post_id', $chunk)
                ->get();

            foreach ($mergedInto as $m) {
                $orderId = $this->postIdToOrderId[(int) $m->post_id] ?? null;
                $targetPostId = (int) $m->meta_value;
                $targetOrderId = $this->postIdToOrderId[$targetPostId] ?? null;
                if ($orderId && $targetOrderId) {
                    DB::table('orders')->where('id', $orderId)->update([
                        'merged_into' => $targetOrderId,
                        'merged_at' => now(),
                    ]);
                    $updated++;
                }
            }
        }
        $this->info("Updated $updated merge references.");
    }

    private function assignSuperadmin(): void
    {
        $user = User::find(1);
        if (! $user) {
            $this->warn('User ID 1 not found. Create admin manually.');

            return;
        }
        $user->assignRole('superadmin');
        $user->update(['password' => Hash::make('password')]);
        $this->info('Assigned superadmin to user 1. Password set to: password');
    }

    private function loadPostIdMapping(): void
    {
        $legacy = DB::connection('legacy');
        $posts = $legacy->table('wp_posts')
            ->where('post_type', 'orders')
            ->where('post_status', 'publish')
            ->get();

        $orderNumberToPostId = [];
        foreach ($posts as $p) {
            $meta = $legacy->table('wp_postmeta')
                ->where('post_id', $p->ID)
                ->where('meta_key', 'order_id')
                ->first();
            $num = $meta ? trim($meta->meta_value) : (string) $p->ID;
            $postId = (int) $p->ID;
            $orderNumberToPostId[$num] = $postId;
            $orderNumberToPostId[$num.'_'.$postId] = $postId;
        }

        $this->postIdToOrderId = [];
        foreach (DB::table('orders')->get(['id', 'order_number']) as $o) {
            $postId = $orderNumberToPostId[$o->order_number] ?? null;
            if ($postId) {
                $this->postIdToOrderId[$postId] = $o->id;
            }
        }
    }

    private function getLegacyProducts($metaByKey): array
    {
        $products = [];
        $i = 1;
        while (true) {
            $url = $metaByKey->get('p_url_'.$i)?->meta_value ?? $metaByKey->get('p_'.$i)?->meta_value;
            if ($url === null && $metaByKey->get('p_'.$i) === null) {
                break;
            }
            $products[] = [
                'url' => $url,
                'qty' => (int) ($metaByKey->get('p_qty_'.$i)?->meta_value ?? 1),
                'color' => $metaByKey->get('p_color_'.$i)?->meta_value,
                'size' => $metaByKey->get('p_size_'.$i)?->meta_value,
                'info' => $metaByKey->get('p_info_'.$i)?->meta_value,
                'price' => $metaByKey->get('p_price_'.$i)?->meta_value,
            ];
            $i++;
        }

        return $products;
    }

    private function parseJson(?string $v): mixed
    {
        if ($v === null || $v === '') {
            return null;
        }
        $d = json_decode($v, true);

        return json_last_error() === JSON_ERROR_NONE ? $d : null;
    }

    private function parseDate(?string $v): ?string
    {
        if (! $v || $v === '0000-00-00') {
            return null;
        }

        return $v;
    }
}

<?php

namespace App\Console\Commands\Migration;

use App\Models\AdCampaign;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Migrate ad campaigns from legacy WordPress (post_type=myads).
 *
 * Source:  wp_posts + wp_postmeta  (legacy connection)
 * Target:  ad_campaigns            (default connection)
 *
 * Run before migrate:users (ad_campaign_id is set on users during registration attribution).
 */
class MigrateAdCampaigns extends Command
{
    protected $signature = 'migrate:ad-campaigns
                            {--fresh : Truncate ad_campaigns before migrating}';

    protected $description = 'Migrate ad campaigns (myads) from legacy WordPress into ad_campaigns';

    public function handle(): int
    {
        $this->info('=== MigrateAdCampaigns ===');

        $legacy = DB::connection('legacy');
        if (! $legacy->getSchemaBuilder()->hasTable('wp_posts')) {
            $this->error('Legacy wp_posts table not found.');

            return self::FAILURE;
        }

        $posts = $legacy->table('wp_posts')
            ->where('post_type', 'myads')
            ->where('post_status', 'publish')
            ->orderBy('ID')
            ->get();

        if ($posts->isEmpty()) {
            $this->warn('No myads posts found in legacy DB.');

            return self::SUCCESS;
        }

        if ($this->option('fresh')) {
            $this->warn('Truncating ad_campaigns …');
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('users')->update(['ad_campaign_id' => null]);
            DB::table('ad_campaigns')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $postIds = $posts->pluck('ID')->all();
        $metaRows = $legacy->table('wp_postmeta')
            ->whereIn('post_id', $postIds)
            ->whereIn('meta_key', ['unique_url', 'website', 'clicks', 'register', 'orders', 'purchase'])
            ->get();

        $metaByPost = $metaRows->groupBy('post_id')->map(fn ($rows) => $rows->keyBy('meta_key'));

        $bar = $this->output->createProgressBar($posts->count());
        $bar->start();

        $inserted = 0;
        foreach ($posts as $post) {
            $meta = $metaByPost->get($post->ID, collect());
            $getMeta = fn ($key, $default = null) => $meta->get($key)?->meta_value ?? $default;

            $uniqueUrl = $getMeta('unique_url', (string) $post->ID);
            $slug = 'myad-'.$post->ID;
            $website = trim((string) $getMeta('website', ''));
            $clicks = (int) $getMeta('clicks', 0);
            $orders = (int) $getMeta('orders', 0);
            $purchase = (int) $getMeta('purchase', 0);

            $destinationUrl = $website !== '' && (str_starts_with($website, 'http') || str_starts_with($website, '/'))
                ? $website
                : null;
            if ($destinationUrl !== null && strlen($destinationUrl) > 255) {
                $destinationUrl = substr($destinationUrl, 0, 255);
            }

            AdCampaign::updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $post->post_title ?: 'Campaign '.$post->ID,
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
                ]
            );
            $inserted++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Ad campaigns: {$inserted}");

        return self::SUCCESS;
    }
}

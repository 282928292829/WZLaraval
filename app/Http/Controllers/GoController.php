<?php

namespace App\Http\Controllers;

use App\Models\AdCampaign;
use Illuminate\Http\RedirectResponse;

class GoController extends Controller
{
    /**
     * Record a click and redirect. Used for banner/ads tracking.
     * /go/{slug} → increment click_count → redirect to destination (or /register?utm_campaign=slug).
     */
    public function __invoke(string $slug): RedirectResponse
    {
        $campaign = AdCampaign::where('slug', $slug)->where('is_active', true)->first();

        if ($campaign) {
            $campaign->increment('click_count');
            $destination = $campaign->destination_url ?? url('/');
        } else {
            $destination = url('/');
        }

        return redirect()->to($destination);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\View\View;

class PageController extends Controller
{
    public function show(string $slug): View
    {
        $page = Page::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        // Use a dedicated template if one exists for this slug (e.g. pages/faq.blade.php)
        $dedicatedView = 'pages.' . str_replace('-', '_', $slug);

        if (view()->exists($dedicatedView)) {
            return view($dedicatedView, compact('page'));
        }

        return view('pages.show', compact('page'));
    }
}

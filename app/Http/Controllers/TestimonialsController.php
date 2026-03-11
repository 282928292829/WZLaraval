<?php

namespace App\Http\Controllers;

use App\Models\Testimonial;
use Illuminate\View\View;

class TestimonialsController extends Controller
{
    public function __invoke(): View
    {
        $testimonials = Testimonial::query()
            ->published()
            ->ordered()
            ->get();

        return view('pages.testimonials', [
            'testimonials' => $testimonials,
            'title' => __('testimonials.title'),
            'description' => __('testimonials.subtitle'),
            'canonicalUrl' => url('/testimonials'),
        ]);
    }
}

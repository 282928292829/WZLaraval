<?php

namespace App\Livewire;

use App\Models\Testimonial;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Testimonials extends Component
{
    public function render()
    {
        $testimonials = Testimonial::query()
            ->published()
            ->ordered()
            ->get();

        return view('livewire.testimonials', [
            'testimonials' => $testimonials,
        ]);
    }
}

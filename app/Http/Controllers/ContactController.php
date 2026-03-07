<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactStoreRequest;
use App\Models\Activity;
use App\Models\ContactSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function store(ContactStoreRequest $request): RedirectResponse
    {
        $user = auth()->user();

        $submission = ContactSubmission::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
            'subject' => $request->validated('subject'),
            'message' => $request->validated('message'),
            'user_id' => $user?->id,
            'page_slug' => 'contact-us',
        ]);

        Activity::create([
            'type' => 'contact_form',
            'subject_type' => ContactSubmission::class,
            'subject_id' => $submission->id,
            'causer_id' => $user?->id,
            'data' => [
                'name' => $submission->name,
                'email' => $submission->email,
                'subject' => $submission->subject,
            ],
            'created_at' => now(),
        ]);

        return redirect()->back()->with('status', 'contact-sent');
    }
}

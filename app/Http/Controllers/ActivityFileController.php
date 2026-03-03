<?php

namespace App\Http\Controllers;

use App\Models\ActivityFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class ActivityFileController extends Controller
{
    public function download(ActivityFile $activityFile): Response|RedirectResponse
    {

        if (! Storage::disk('public')->exists($activityFile->path)) {
            abort(404);
        }

        return response()->download(
            Storage::disk('public')->path($activityFile->path),
            $activityFile->original_name,
            ['Content-Type' => $activityFile->mime_type ?? 'application/octet-stream'],
            'attachment'
        );
    }
}

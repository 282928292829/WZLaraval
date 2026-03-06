<?php

use App\Models\CommentTemplate;
use App\Services\ImportCommentTemplatesFromCsv;
use Illuminate\Http\UploadedFile;

beforeEach(function (): void {
    CommentTemplate::query()->delete();
});

test('imports templates from valid csv', function (): void {
    $csv = "title,content,sort_order,usage_count\n".
        "Template A,Content A,1,5\n".
        "Template B,Content B,2,10\n";

    $file = UploadedFile::fake()->createWithContent('templates.csv', $csv);

    $result = app(ImportCommentTemplatesFromCsv::class)->import($file, replaceExisting: false);

    expect($result['success'])->toBeTrue()
        ->and($result['count'])->toBe(2);

    $templates = CommentTemplate::all();
    expect($templates)->toHaveCount(2)
        ->and($templates->first()->title)->toBe('Template A')
        ->and($templates->first()->content)->toBe('Content A')
        ->and($templates->first()->sort_order)->toBe(1)
        ->and($templates->first()->usage_count)->toBe(5);
});

test('replace existing deletes before import', function (): void {
    CommentTemplate::create([
        'title' => 'Old',
        'content' => 'Old content',
        'sort_order' => 0,
        'usage_count' => 0,
        'is_active' => true,
    ]);

    $csv = "title,content\nNew,New content\n";
    $file = UploadedFile::fake()->createWithContent('templates.csv', $csv);

    $result = app(ImportCommentTemplatesFromCsv::class)->import($file, replaceExisting: true);

    expect($result['success'])->toBeTrue()
        ->and($result['count'])->toBe(1)
        ->and(CommentTemplate::count())->toBe(1)
        ->and(CommentTemplate::first()->title)->toBe('New');
});

test('handles utf8 bom', function (): void {
    $bom = chr(0xEF).chr(0xBB).chr(0xBF);
    $csv = $bom."title,content\nArabic قالب,محتوى\n";
    $file = UploadedFile::fake()->createWithContent('templates.csv', $csv);

    $result = app(ImportCommentTemplatesFromCsv::class)->import($file, replaceExisting: false);

    expect($result['success'])->toBeTrue()
        ->and($result['count'])->toBe(1)
        ->and(CommentTemplate::first()->title)->toBe('Arabic قالب');
});

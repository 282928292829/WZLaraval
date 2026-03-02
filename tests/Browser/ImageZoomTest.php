<?php

use App\Models\User;
use Laravel\Dusk\Browser;

test('image zoom opens when clicking attached image thumbnail', function () {
    $user = User::factory()->create();
    $testImage = __DIR__.'/fixtures/test-image.jpg';
    if (! file_exists($testImage)) {
        $this->markTestSkipped('Test image fixture missing');
    }

    $this->browse(function (Browser $browser) use ($user, $testImage) {
        $browser->loginAs($user)
            ->visit(route('new-order'))
            ->waitFor('#order-form', 10);

        $browser->attach('#order-file-desktop-0', $testImage)
            ->pause(2500)
            ->waitFor('img[src^="data:image"]', 5)
            ->click('div.relative.w-11.h-11.cursor-pointer:has(img[src^="data:image"])')
            ->pause(500)
            ->assertPresent('div.fixed.inset-0 img.object-contain');
    });
});

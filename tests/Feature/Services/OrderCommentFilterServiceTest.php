<?php

use App\Services\OrderCommentFilterService;

test('orderIdsAwaitingResponse returns collection', function () {
    $ids = OrderCommentFilterService::orderIdsAwaitingResponse('customer', null, null, null);

    expect($ids)->toBeInstanceOf(\Illuminate\Support\Collection::class);
});

test('orderIdsAwaitingResponse with preset 24h does not throw', function () {
    $ids = OrderCommentFilterService::orderIdsAwaitingResponse('customer', '24h', null, null);

    expect($ids)->toBeInstanceOf(\Illuminate\Support\Collection::class);
});

test('orderIdsAwaitingResponse with custom value does not throw', function () {
    $ids = OrderCommentFilterService::orderIdsAwaitingResponse('customer', 'custom', 3, 'days');

    expect($ids)->toBeInstanceOf(\Illuminate\Support\Collection::class);
});

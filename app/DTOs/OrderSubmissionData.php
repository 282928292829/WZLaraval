<?php

namespace App\DTOs;

use Illuminate\Http\Request;

/**
 * Data passed to OrderSubmissionService::submit() for new order or edit flow.
 *
 * @property int $userId
 * @property bool $isStaff
 * @property array<int, array{data: array, orig: int}> $items itemsWithOriginalIndex format
 * @property string $orderNotes
 * @property array<int, array<int, mixed>> $normalizedFiles indexed by original item index
 * @property array<string, float> $exchangeRates
 * @property int $maxImagesPerItem
 * @property int $maxImagesPerOrder
 * @property int|null $editingOrderId
 * @property int|null $duplicateFrom
 * @property string $productUrl
 * @property string $activeLayout
 * @property Request|null $request for UserActivityLog::fromRequest
 */
readonly class OrderSubmissionData
{
    public function __construct(
        public int $userId,
        public bool $isStaff,
        public array $items,
        public string $orderNotes,
        public array $normalizedFiles,
        public array $exchangeRates,
        public int $maxImagesPerItem,
        public int $maxImagesPerOrder,
        public ?int $editingOrderId = null,
        public ?int $duplicateFrom = null,
        public string $productUrl = '',
        public string $activeLayout = '',
        public ?Request $request = null,
    ) {}
}

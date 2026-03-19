<?php

namespace App\DTOs;

use App\Models\Order;

/**
 * Result returned by OrderSubmissionService::submit().
 *
 * @property bool $success
 * @property int|null $orderId
 * @property Order|null $order the created/updated order when success
 * @property string|null $redirectUrl URL to redirect to when success
 * @property bool $redirectToSuccessPage when true, go to orders.success; when false, orders.show
 * @property array<string, mixed> $sessionFlashes key-value pairs to flash before redirect
 * @property string|null $errorMessage when success=false
 * @property string|null $errorType 'notify'|'redirect' when success=false
 */
readonly class OrderSubmissionResult
{
    public function __construct(
        public bool $success,
        public ?int $orderId = null,
        public ?Order $order = null,
        public ?string $redirectUrl = null,
        public bool $redirectToSuccessPage = false,
        public array $sessionFlashes = [],
        public ?string $errorMessage = null,
        public ?string $errorType = null,
    ) {}
}

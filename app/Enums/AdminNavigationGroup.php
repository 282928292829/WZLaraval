<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AdminNavigationGroup: string implements HasLabel
{
    case Orders = 'orders';
    case OrderSetup = 'order_setup';
    case Content = 'content';
    case Settings = 'settings';
    case Users = 'users';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Orders => __('Orders'),
            self::OrderSetup => __('Order Setup'),
            self::Content => __('Content'),
            self::Settings => __('Settings'),
            self::Users => __('Users'),
        };
    }
}

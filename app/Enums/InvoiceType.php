<?php

namespace App\Enums;

enum InvoiceType: string
{
    case FirstPayment = 'first_payment';
    case SecondFinal = 'second_final';
    case ItemsCost = 'items_cost';
    case General = 'general';

    public function label(): string
    {
        return match ($this) {
            self::FirstPayment => __('orders.invoice_type_first_payment'),
            self::SecondFinal => __('orders.invoice_type_second_final'),
            self::ItemsCost => __('orders.invoice_type_items_cost'),
            self::General => __('orders.invoice_type_general'),
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }
}

<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class InboxPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('Orders');
    }

    public static function getNavigationLabel(): string
    {
        return __('inbox.inbox');
    }

    public function getTitle(): string|Htmlable
    {
        return __('inbox.inbox');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view-all-orders') ?? false;
    }

    public function mount(): void
    {
        $this->redirect(route('inbox.index'), navigate: false);
    }
}

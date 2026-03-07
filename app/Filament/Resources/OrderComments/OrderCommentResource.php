<?php

namespace App\Filament\Resources\OrderComments;

use App\Enums\AdminNavigationGroup;
use App\Filament\Resources\OrderComments\Pages\ListOrderComments;
use App\Filament\Resources\OrderComments\Schemas\OrderCommentForm;
use App\Filament\Resources\OrderComments\Tables\OrderCommentsTable;
use App\Models\OrderComment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderCommentResource extends Resource
{
    protected static ?string $model = OrderComment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('Order Comments');
    }

    public static function getModelLabel(): string
    {
        return __('Order Comment');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Order Comments');
    }

    public static function getNavigationGroup(): ?AdminNavigationGroup
    {
        return AdminNavigationGroup::Orders;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view-all-orders') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return OrderCommentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrderCommentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrderComments::route('/'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}

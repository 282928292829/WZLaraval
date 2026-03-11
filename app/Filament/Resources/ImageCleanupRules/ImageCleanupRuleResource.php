<?php

namespace App\Filament\Resources\ImageCleanupRules;

use App\Enums\AdminNavigationGroup;
use App\Filament\Resources\ImageCleanupRules\Pages\CreateImageCleanupRule;
use App\Filament\Resources\ImageCleanupRules\Pages\EditImageCleanupRule;
use App\Filament\Resources\ImageCleanupRules\Pages\ListImageCleanupRules;
use App\Filament\Resources\ImageCleanupRules\Schemas\ImageCleanupRuleForm;
use App\Filament\Resources\ImageCleanupRules\Tables\ImageCleanupRulesTable;
use App\Models\ImageCleanupRule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ImageCleanupRuleResource extends Resource
{
    protected static ?string $model = ImageCleanupRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrash;

    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return __('image_cleanup.nav_label');
    }

    public static function getModelLabel(): string
    {
        return __('image_cleanup.rule_model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('image_cleanup.rule_plural_label');
    }

    public static function getNavigationGroup(): ?AdminNavigationGroup
    {
        return AdminNavigationGroup::Settings;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermissionTo('manage-image-cleanup') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return ImageCleanupRuleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ImageCleanupRulesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListImageCleanupRules::route('/'),
            'create' => CreateImageCleanupRule::route('/create'),
            'edit' => EditImageCleanupRule::route('/{record}/edit'),
        ];
    }
}

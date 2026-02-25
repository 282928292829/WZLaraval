<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Models\UserFile;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class UserFilesRelationManager extends RelationManager
{
    protected static string $relationship = 'staffFiles';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('Staff Notes & Files');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('path')
                    ->label(__('File'))
                    ->required()
                    ->disk('public')
                    ->acceptedFileTypes(['image/*', 'application/pdf'])
                    ->storeFileNamesIn('original_name')
                    ->directory(fn () => 'user-files/'.$this->getOwnerRecord()->id)
                    ->maxSize(10240)
                    ->helperText(__('Images or PDF. Max 10 MB.')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('original_name')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('original_name')
                    ->label(__('File'))
                    ->url(fn (UserFile $record) => $record->url())
                    ->openUrlInNewTab()
                    ->icon(fn (UserFile $record) => $record->isImage() ? 'heroicon-o-photo' : 'heroicon-o-document')
                    ->sortable(),

                TextColumn::make('mime_type')
                    ->label(__('Type'))
                    ->formatStateUsing(fn (?string $state) => $state ? explode('/', $state)[1] ?? $state : '—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('size')
                    ->label(__('Size'))
                    ->formatStateUsing(fn (UserFile $record) => $record->humanSize())
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('Uploaded'))
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('uploader.name')
                    ->label(__('By'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('Add File'))
                    ->mutateFormDataUsing(function (array $data): array {
                        $path = $data['path'] ?? null;
                        if ($path) {
                            try {
                                $disk = Storage::disk('public');
                                $data['mime_type'] = $disk->mimeType($path);
                                $data['size'] = $disk->size($path);
                            } catch (\Throwable) {
                                // Metadata optional if file not yet synced; record still saves
                            }
                        }
                        $data['uploaded_by'] = auth()->id();

                        return $data;
                    }),
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

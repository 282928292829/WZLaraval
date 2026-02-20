<?php

namespace App\Filament\Resources\Blog\PostCommentResource\Pages;

use App\Filament\Resources\Blog\PostCommentResource;
use Filament\Resources\Pages\ListRecords;

class ListPostComments extends ListRecords
{
    protected static string $resource = PostCommentResource::class;
}

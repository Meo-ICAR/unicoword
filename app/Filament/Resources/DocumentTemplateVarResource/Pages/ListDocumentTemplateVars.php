<?php

namespace App\Filament\Resources\DocumentTemplateVarResource\Pages;

use App\Filament\Resources\DocumentTemplateVarResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDocumentTemplateVars extends ListRecords
{
    protected static string $resource = DocumentTemplateVarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

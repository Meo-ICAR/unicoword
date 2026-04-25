<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentTemplateVarResource\Pages;
use App\Filament\Resources\DocumentTemplateVarResource\RelationManagers;
use App\Models\DocumentTemplateVar;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DocumentTemplateVarResource extends Resource
{
    protected static ?string $model = DocumentTemplateVar::class;

    protected static ?string $navigationIcon = 'fas-code';

    protected static ?string $navigationLabel = 'Variabili Template';

    protected static ?string $modelLabel = 'Variabile Template';

    protected static ?string $pluralModelLabel = 'Variabili Template';

    protected static ?string $recordTitleAttribute = 'var';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('document_template_id')
                    ->relationship('documentTemplate', 'name')
                    ->label('Template Documento')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('var')
                    ->label('Variabile')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('es: $POST_TITLE'),
                Forms\Components\TextInput::make('model')
                    ->label('Model')
                    ->maxLength(255)
                    ->placeholder('es: App\Models\Post'),
                Forms\Components\Textarea::make('value')
                    ->label('Valore')
                    ->placeholder('es: "title" o JSON complesso')
                    ->rows(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('documentTemplate.name')
                    ->label('Template')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('var')
                    ->label('Variabile')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('model')
                    ->label('Model')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('value')
                    ->label('Valore')
                    ->searchable()
                    ->limit(50)
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('document_template_id')
                    ->relationship('documentTemplate', 'name')
                    ->label('Template Documento'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocumentTemplateVars::route('/'),
            'create' => Pages\CreateDocumentTemplateVar::route('/create'),
            'edit' => Pages\EditDocumentTemplateVar::route('/{record}/edit'),
        ];
    }
}

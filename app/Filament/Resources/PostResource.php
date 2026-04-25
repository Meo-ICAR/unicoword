<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Filament\Resources\PostResource\RelationManagers;
use App\Models\Post;
use App\Services\DocumentTemplateService;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
use FilamentTiptapEditor\TiptapEditor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use TomatoPHP\FilamentDocs\Facades\FilamentDocs;
use TomatoPHP\FilamentDocs\Filament\Actions\DocumentAction;
use TomatoPHP\FilamentDocs\Models\Document;
use TomatoPHP\FilamentDocs\Services\Contracts\DocsVar;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon = 'fas-newspaper';

    protected static ?string $navigationLabel = 'Articoli';

    protected static ?string $modelLabel = 'Articolo';

    protected static ?string $pluralModelLabel = 'Articoli';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Titolo')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('type')
                    ->label('Tipo')
                    ->required()
                    ->maxLength(255),
                TiptapEditor::make('content')
                    ->label('Contenuto')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->withoutGlobalScope(SoftDeletingScope::class))
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Titolo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                DocumentAction::make()
                    ->vars(fn($record) => DocumentTemplateService::getTemplateVarsWithRecord(1, $record)),
                Action::make('generate-document')
                    ->label('Genera Documento')
                    ->icon('fas-file-alt')
                    ->action(function ($record) {
                        $body = FilamentDocs::body(1, DocumentTemplateService::getTemplateVarsWithRecord(1, $record));

                        Document::query()->create([
                            'model_id' => $record->id,
                            'model_type' => get_class($record),
                            'document_template_id' => 1,
                            'body' => $body,
                        ]);
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
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
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}

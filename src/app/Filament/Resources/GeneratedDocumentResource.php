<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GeneratedDocumentResource\Pages;
use App\Filament\Resources\GeneratedDocumentResource\RelationManagers;
use App\Models\GeneratedDocument;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Storage;

class GeneratedDocumentResource extends Resource
{
    protected static ?string $model = GeneratedDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    
    protected static ?string $navigationGroup = 'Documentos';
    
    protected static ?string $modelLabel = 'Documento Gerado';
    
    protected static ?string $pluralModelLabel = 'Documentos Gerados';
    
    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false; // Documentos são criados pela página GenerateDocument
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações do Documento')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Título')
                            ->disabled(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(GeneratedDocument::getStatusOptions())
                            ->required(),

                        Forms\Components\Placeholder::make('template_name')
                            ->label('Template')
                            ->content(fn ($record) => $record->template?->name ?? '-'),

                        Forms\Components\Placeholder::make('file_info')
                            ->label('Arquivo')
                            ->content(fn ($record) => $record->file_name . ' (' . $record->formatted_file_size . ')'),
                    ]),

                Forms\Components\Section::make('Vínculos')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Placeholder::make('client_name')
                            ->label('Cliente')
                            ->content(fn ($record) => $record->client?->name ?? 'Não vinculado'),

                        Forms\Components\Placeholder::make('service_code')
                            ->label('Serviço')
                            ->content(fn ($record) => $record->service?->code ?? 'Não vinculado'),

                        Forms\Components\Placeholder::make('user_name')
                            ->label('Gerado por')
                            ->content(fn ($record) => $record->user?->name),

                        Forms\Components\Placeholder::make('created_at')
                            ->label('Gerado em')
                            ->content(fn ($record) => $record->created_at?->format('d/m/Y H:i')),
                    ]),

                Forms\Components\Section::make('Conteúdo')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Placeholder::make('content_preview')
                            ->label('')
                            ->content(fn ($record) => new \Illuminate\Support\HtmlString(
                                '<div class="prose dark:prose-invert max-w-none">' . $record->content . '</div>'
                            )),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(40),

                Tables\Columns\TextColumn::make('template.name')
                    ->label('Template')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('client.name')
                    ->label('Cliente')
                    ->searchable()
                    ->toggleable()
                    ->limit(25),

                Tables\Columns\TextColumn::make('service.code')
                    ->label('Serviço')
                    ->toggleable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? (GeneratedDocument::getStatusOptions()[$state] ?? $state) : '-')
                    ->color(fn (?string $state): string => GeneratedDocument::getStatusColors()[$state] ?? 'gray'),

                Tables\Columns\TextColumn::make('formatted_file_size')
                    ->label('Tamanho')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Gerado por')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(GeneratedDocument::getStatusOptions()),

                Tables\Filters\SelectFilter::make('document_template_id')
                    ->label('Template')
                    ->relationship('template', 'name'),

                Tables\Filters\SelectFilter::make('client_id')
                    ->label('Cliente')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TrashedFilter::make()
                    ->label('Excluídos'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('download')
                        ->label('Download PDF')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->visible(fn ($record) => $record->hasPdf())
                        ->action(function ($record) {
                            return response()->download(
                                storage_path('app/' . $record->file_path),
                                $record->file_name
                            );
                        }),

                    Tables\Actions\Action::make('view_content')
                        ->label('Ver Conteúdo')
                        ->icon('heroicon-o-eye')
                        ->color('gray')
                        ->modalHeading(fn ($record) => $record->title)
                        ->modalContent(fn ($record) => new \Illuminate\Support\HtmlString(
                            '<div class="prose dark:prose-invert max-w-none p-4">' . $record->content . '</div>'
                        )),

                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('mark_sent')
                        ->label('Marcar como Enviado')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('info')
                        ->visible(fn ($record) => $record->status === 'generated')
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->update(['status' => 'sent'])),

                    Tables\Actions\Action::make('mark_signed')
                        ->label('Marcar como Assinado')
                        ->icon('heroicon-o-pencil')
                        ->color('primary')
                        ->visible(fn ($record) => in_array($record->status, ['generated', 'sent']))
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->update(['status' => 'signed'])),

                    Tables\Actions\Action::make('archive')
                        ->label('Arquivar')
                        ->icon('heroicon-o-archive-box')
                        ->color('warning')
                        ->visible(fn ($record) => $record->status !== 'archived')
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->update(['status' => 'archived'])),

                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Nenhum documento gerado')
            ->emptyStateDescription('Acesse "Gerar Documento" no menu para criar seu primeiro documento.')
            ->emptyStateIcon('heroicon-o-document-check');
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
            'index' => Pages\ListGeneratedDocuments::route('/'),
            'edit' => Pages\EditGeneratedDocument::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::recent(7)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }
}

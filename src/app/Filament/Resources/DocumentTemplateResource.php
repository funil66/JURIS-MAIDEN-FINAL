<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentTemplateResource\Pages;
use App\Filament\Resources\DocumentTemplateResource\RelationManagers;
use App\Models\DocumentTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class DocumentTemplateResource extends Resource
{
    protected static ?string $model = DocumentTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';
    
    protected static ?string $navigationGroup = 'Documentos';
    
    protected static ?string $modelLabel = 'Template de Documento';
    
    protected static ?string $pluralModelLabel = 'Templates de Documentos';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identificação')
                    ->description('Informações básicas do template')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome do Template')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, Forms\Set $set) => 
                                $set('slug', Str::slug($state))
                            )
                            ->placeholder('Ex: Procuração Ad Judicia'),

                        Forms\Components\TextInput::make('slug')
                            ->label('Identificador (Slug)')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Gerado automaticamente'),

                        Forms\Components\Select::make('category')
                            ->label('Categoria')
                            ->options(DocumentTemplate::getCategoryOptions())
                            ->required()
                            ->searchable(),

                        Forms\Components\Textarea::make('description')
                            ->label('Descrição')
                            ->rows(2)
                            ->maxLength(500)
                            ->placeholder('Descreva quando e como usar este template...')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Conteúdo do Template')
                    ->description('Use {{variavel}} para inserir variáveis dinâmicas')
                    ->schema([
                        Forms\Components\RichEditor::make('content')
                            ->label('Conteúdo')
                            ->required()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'strike',
                                'h2',
                                'h3',
                                'bulletList',
                                'orderedList',
                                'blockquote',
                                'redo',
                                'undo',
                            ])
                            ->columnSpanFull(),

                        Forms\Components\Placeholder::make('variables_help')
                            ->label('📋 Variáveis Disponíveis')
                            ->content(function () {
                                $vars = DocumentTemplate::getSystemVariables();
                                $html = '<div class="grid grid-cols-2 gap-2 text-sm">';
                                foreach ($vars as $key => $description) {
                                    $html .= "<div><code class='bg-gray-100 dark:bg-gray-800 px-1 rounded'>{{" . $key . "}}</code> - {$description}</div>";
                                }
                                $html .= '</div>';
                                return new \Illuminate\Support\HtmlString($html);
                            })
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Configurações de Impressão')
                    ->columns(3)
                    ->collapsed()
                    ->schema([
                        Forms\Components\Select::make('format')
                            ->label('Formato do Papel')
                            ->options(DocumentTemplate::getFormatOptions())
                            ->default('A4')
                            ->required(),

                        Forms\Components\Select::make('orientation')
                            ->label('Orientação')
                            ->options(DocumentTemplate::getOrientationOptions())
                            ->default('portrait')
                            ->required(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Ativo')
                            ->default(true)
                            ->inline(false),
                    ]),

                Forms\Components\Section::make('Informações do Sistema')
                    ->columns(3)
                    ->collapsed()
                    ->visible(fn ($record) => $record !== null)
                    ->schema([
                        Forms\Components\Placeholder::make('usage_display')
                            ->label('Vezes Utilizado')
                            ->content(fn ($record) => $record?->usage_count ?? 0),

                        Forms\Components\Placeholder::make('creator_display')
                            ->label('Criado por')
                            ->content(fn ($record) => $record?->creator?->name ?? 'Sistema'),

                        Forms\Components\Placeholder::make('created_display')
                            ->label('Criado em')
                            ->content(fn ($record) => $record?->created_at?->format('d/m/Y H:i') ?? '-'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->description ? Str::limit($record->description, 50) : null),

                Tables\Columns\TextColumn::make('category')
                    ->label('Categoria')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => DocumentTemplate::getCategoryOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => DocumentTemplate::getCategoryColors()[$state] ?? 'gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('format')
                    ->label('Formato')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_system')
                    ->label('Sistema')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('usage_count')
                    ->label('Uso')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Atualizado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Categoria')
                    ->options(DocumentTemplate::getCategoryOptions()),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Ativo'),

                Tables\Filters\TernaryFilter::make('is_system')
                    ->label('Template do Sistema'),

                Tables\Filters\TrashedFilter::make()
                    ->label('Excluídos'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()
                        ->visible(fn ($record) => !$record->is_system),
                    Tables\Actions\Action::make('duplicate')
                        ->label('Duplicar')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $new = $record->replicate();
                            $new->name = $record->name . ' (Cópia)';
                            $new->slug = Str::slug($new->name);
                            $new->is_system = false;
                            $new->usage_count = 0;
                            $new->created_by = auth()->id();
                            $new->save();
                        }),
                    Tables\Actions\Action::make('preview')
                        ->label('Visualizar')
                        ->icon('heroicon-o-eye')
                        ->color('gray')
                        ->modalHeading(fn ($record) => $record->name)
                        ->modalContent(fn ($record) => new \Illuminate\Support\HtmlString(
                            '<div class="prose dark:prose-invert max-w-none">' . $record->content . '</div>'
                        )),
                    Tables\Actions\DeleteAction::make()
                        ->visible(fn ($record) => !$record->is_system),
                    Tables\Actions\RestoreAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            // Não deletar templates do sistema
                            return $records->filter(fn ($record) => !$record->is_system);
                        }),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Nenhum template cadastrado')
            ->emptyStateDescription('Crie templates de documentos para agilizar seu trabalho.')
            ->emptyStateIcon('heroicon-o-document-duplicate');
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
            'index' => Pages\ListDocumentTemplates::route('/'),
            'create' => Pages\CreateDocumentTemplate::route('/create'),
            'edit' => Pages\EditDocumentTemplate::route('/{record}/edit'),
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
        return static::getModel()::active()->count() ?: null;
    }
}

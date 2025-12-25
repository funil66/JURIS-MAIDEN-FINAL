<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportTemplateResource\Pages;
use App\Models\Client;
use App\Models\Process;
use App\Models\ReportTemplate;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ReportTemplateResource extends Resource
{
    protected static ?string $model = ReportTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationLabel = 'Templates de Relatório';
    protected static ?string $modelLabel = 'Template de Relatório';
    protected static ?string $pluralModelLabel = 'Templates de Relatório';
    protected static ?string $navigationGroup = 'Relatórios';
    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('user_id', Auth::id())
            ->where('is_favorite', true)
            ->count();
        
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações do Template')
                    ->description('Configure o template de relatório')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        Forms\Components\Textarea::make('description')
                            ->label('Descrição')
                            ->rows(2)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('type')
                            ->label('Tipo de Relatório')
                            ->options(ReportTemplate::getTypeOptions())
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn ($set) => $set('columns', null)),

                        Forms\Components\Select::make('default_format')
                            ->label('Formato Padrão')
                            ->options(ReportTemplate::getFormatOptions())
                            ->default('pdf')
                            ->required(),

                        Forms\Components\Select::make('orientation')
                            ->label('Orientação')
                            ->options([
                                'portrait' => 'Retrato',
                                'landscape' => 'Paisagem',
                            ])
                            ->default('portrait'),

                        Forms\Components\Toggle::make('is_favorite')
                            ->label('Favorito')
                            ->helperText('Marcar como favorito para acesso rápido'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Colunas do Relatório')
                    ->description('Selecione as colunas que deseja incluir')
                    ->schema([
                        Forms\Components\CheckboxList::make('columns')
                            ->label('Colunas')
                            ->options(fn ($get) => ReportTemplate::getAvailableColumns($get('type') ?? 'processes'))
                            ->columns(3)
                            ->searchable()
                            ->bulkToggleable()
                            ->visible(fn ($get) => !empty($get('type'))),
                    ])
                    ->visible(fn ($get) => !empty($get('type'))),

                Forms\Components\Section::make('Ordenação')
                    ->schema([
                        Forms\Components\Select::make('order_by')
                            ->label('Ordenar por')
                            ->options(fn ($get) => ReportTemplate::getAvailableColumns($get('type') ?? 'processes'))
                            ->searchable()
                            ->visible(fn ($get) => !empty($get('type'))),

                        Forms\Components\Select::make('order_direction')
                            ->label('Direção')
                            ->options([
                                'asc' => 'Crescente (A-Z, 0-9)',
                                'desc' => 'Decrescente (Z-A, 9-0)',
                            ])
                            ->default('desc'),

                        Forms\Components\Select::make('group_by')
                            ->label('Agrupar por')
                            ->options(fn ($get) => ReportTemplate::getAvailableColumns($get('type') ?? 'processes'))
                            ->searchable()
                            ->placeholder('Sem agrupamento')
                            ->visible(fn ($get) => !empty($get('type'))),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Filtros Padrão')
                    ->description('Defina filtros que serão aplicados automaticamente')
                    ->schema([
                        Forms\Components\KeyValue::make('filters')
                            ->label('Filtros')
                            ->keyLabel('Campo')
                            ->valueLabel('Valor')
                            ->addActionLabel('Adicionar Filtro')
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),

                Forms\Components\Section::make('Opções de Exibição')
                    ->schema([
                        Forms\Components\Toggle::make('include_summary')
                            ->label('Incluir Resumo')
                            ->helperText('Adiciona estatísticas resumidas ao relatório')
                            ->default(true),

                        Forms\Components\Toggle::make('include_charts')
                            ->label('Incluir Gráficos')
                            ->helperText('Adiciona gráficos visuais (apenas PDF)')
                            ->default(true),

                        Forms\Components\Toggle::make('include_details')
                            ->label('Incluir Detalhes')
                            ->helperText('Lista todos os registros individualmente')
                            ->default(true),

                        Forms\Components\Toggle::make('is_public')
                            ->label('Público')
                            ->helperText('Disponível para todos os usuários')
                            ->default(false),
                    ])
                    ->columns(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uid')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->color('primary')
                    ->weight('bold')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_favorite')
                    ->label('')
                    ->icon(fn ($state) => $state ? 'heroicon-s-star' : 'heroicon-o-star')
                    ->color(fn ($state) => $state ? 'warning' : 'gray')
                    ->action(fn ($record) => $record->toggleFavorite())
                    ->width(40),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->description)
                    ->limit(40),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ReportTemplate::getTypeOptions()[$state] ?? $state)
                    ->color(fn ($state) => match($state) {
                        'processes' => 'info',
                        'deadlines' => 'warning',
                        'invoices' => 'success',
                        'financial' => 'success',
                        'time_entries' => 'primary',
                        'productivity' => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('default_format')
                    ->label('Formato')
                    ->badge()
                    ->formatStateUsing(fn ($state) => strtoupper($state))
                    ->color(fn ($state) => match($state) {
                        'pdf' => 'danger',
                        'excel' => 'success',
                        'csv' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('usage_count')
                    ->label('Usos')
                    ->sortable()
                    ->alignCenter()
                    ->description(fn ($record) => $record->last_used_at?->diffForHumans() ?? 'Nunca usado'),

                Tables\Columns\IconColumn::make('is_public')
                    ->label('Público')
                    ->boolean()
                    ->trueIcon('heroicon-o-globe-alt')
                    ->falseIcon('heroicon-o-lock-closed')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Criado por')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('is_favorite', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(ReportTemplate::getTypeOptions()),

                Tables\Filters\SelectFilter::make('default_format')
                    ->label('Formato')
                    ->options(ReportTemplate::getFormatOptions()),

                Tables\Filters\TernaryFilter::make('is_favorite')
                    ->label('Favoritos'),

                Tables\Filters\TernaryFilter::make('is_public')
                    ->label('Públicos'),

                Tables\Filters\Filter::make('mine')
                    ->label('Meus templates')
                    ->query(fn (Builder $query) => $query->where('user_id', Auth::id()))
                    ->default(),
            ])
            ->actions([
                Tables\Actions\Action::make('generate')
                    ->label('Gerar')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->url(fn ($record) => route('filament.funil.pages.advanced-reports', ['template' => $record->id])),

                Tables\Actions\Action::make('duplicate')
                    ->label('Duplicar')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->action(fn ($record) => $record->duplicate())
                    ->successNotificationTitle('Template duplicado com sucesso'),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Informações do Template')
                    ->schema([
                        Components\TextEntry::make('uid')
                            ->label('Código')
                            ->badge()
                            ->color('primary'),

                        Components\TextEntry::make('name')
                            ->label('Nome'),

                        Components\TextEntry::make('description')
                            ->label('Descrição')
                            ->columnSpanFull(),

                        Components\TextEntry::make('type')
                            ->label('Tipo')
                            ->badge()
                            ->formatStateUsing(fn ($state) => ReportTemplate::getTypeOptions()[$state] ?? $state),

                        Components\TextEntry::make('default_format')
                            ->label('Formato')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => $state ? strtoupper($state) : '-'),

                        Components\TextEntry::make('orientation')
                            ->label('Orientação')
                            ->formatStateUsing(fn ($state) => $state === 'portrait' ? 'Retrato' : 'Paisagem'),

                        Components\IconEntry::make('is_favorite')
                            ->label('Favorito')
                            ->boolean()
                            ->trueIcon('heroicon-s-star')
                            ->falseIcon('heroicon-o-star')
                            ->trueColor('warning'),
                    ])
                    ->columns(4),

                Components\Section::make('Estatísticas')
                    ->schema([
                        Components\TextEntry::make('usage_count')
                            ->label('Vezes utilizado'),

                        Components\TextEntry::make('last_used_at')
                            ->label('Último uso')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Nunca usado'),

                        Components\TextEntry::make('user.name')
                            ->label('Criado por'),

                        Components\TextEntry::make('created_at')
                            ->label('Criado em')
                            ->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(4),

                Components\Section::make('Configurações')
                    ->schema([
                        Components\IconEntry::make('include_summary')
                            ->label('Incluir Resumo')
                            ->boolean(),

                        Components\IconEntry::make('include_charts')
                            ->label('Incluir Gráficos')
                            ->boolean(),

                        Components\IconEntry::make('include_details')
                            ->label('Incluir Detalhes')
                            ->boolean(),

                        Components\IconEntry::make('is_public')
                            ->label('Público')
                            ->boolean(),
                    ])
                    ->columns(4),
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
            'index' => Pages\ListReportTemplates::route('/'),
            'create' => Pages\CreateReportTemplate::route('/create'),
            'view' => Pages\ViewReportTemplate::route('/{record}'),
            'edit' => Pages\EditReportTemplate::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where(function ($query) {
                $query->where('user_id', Auth::id())
                    ->orWhere('is_public', true);
            });
    }
}

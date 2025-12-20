<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceTypeResource\Pages;
use App\Filament\Resources\ServiceTypeResource\RelationManagers;
use App\Models\ServiceType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServiceTypeResource extends Resource
{
    protected static ?string $model = ServiceType::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    
    protected static ?string $navigationGroup = 'Configurações';
    
    protected static ?string $modelLabel = 'Tipo de Serviço';
    
    protected static ?string $pluralModelLabel = 'Tipos de Serviço';
    
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações Básicas')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ex: Audiência, Protocolo, Cópias'),

                        Forms\Components\TextInput::make('code')
                            ->label('Código')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20)
                            ->placeholder('Ex: AUD, PROT, COP')
                            ->helperText('Código curto para identificação'),

                        Forms\Components\Textarea::make('description')
                            ->label('Descrição')
                            ->rows(3)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Valores Padrão')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('default_price')
                            ->label('Preço Padrão')
                            ->numeric()
                            ->prefix('R$')
                            ->default(0)
                            ->step(0.01),

                        Forms\Components\TextInput::make('default_deadline_days')
                            ->label('Prazo Padrão (dias)')
                            ->numeric()
                            ->default(1)
                            ->suffix('dias'),
                    ]),

                Forms\Components\Section::make('Aparência e Configurações')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('icon')
                            ->label('Ícone')
                            ->options([
                                'heroicon-o-scale' => '⚖️ Balança (Audiência)',
                                'heroicon-o-document-text' => '📄 Documento',
                                'heroicon-o-document-duplicate' => '📋 Cópias',
                                'heroicon-o-paper-airplane' => '✈️ Protocolo',
                                'heroicon-o-truck' => '🚚 Diligência',
                                'heroicon-o-magnifying-glass' => '🔍 Pesquisa',
                                'heroicon-o-camera' => '📷 Fotografia',
                                'heroicon-o-user' => '👤 Pessoa',
                                'heroicon-o-building-office' => '🏢 Empresa',
                                'heroicon-o-map-pin' => '📍 Local',
                            ])
                            ->searchable(),

                        Forms\Components\Select::make('color')
                            ->label('Cor')
                            ->options([
                                'primary' => '🔵 Azul',
                                'success' => '🟢 Verde',
                                'warning' => '🟡 Amarelo',
                                'danger' => '🔴 Vermelho',
                                'info' => '🩵 Ciano',
                                'gray' => '⚫ Cinza',
                            ])
                            ->default('primary'),

                        Forms\Components\TextInput::make('sort_order')
                            ->label('Ordem')
                            ->numeric()
                            ->default(0),

                        Forms\Components\Toggle::make('requires_deadline')
                            ->label('Exige Prazo')
                            ->default(true)
                            ->helperText('Serviço requer data limite'),

                        Forms\Components\Toggle::make('requires_location')
                            ->label('Exige Local')
                            ->default(true)
                            ->helperText('Serviço requer endereço'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Ativo')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->width(50),

                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('default_price')
                    ->label('Preço Padrão')
                    ->money('BRL')
                    ->sortable(),

                Tables\Columns\TextColumn::make('default_deadline_days')
                    ->label('Prazo')
                    ->suffix(' dias')
                    ->sortable(),

                Tables\Columns\IconColumn::make('requires_deadline')
                    ->label('Prazo')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('requires_location')
                    ->label('Local')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),

                Tables\Columns\TextColumn::make('services_count')
                    ->label('Serviços')
                    ->counts('services')
                    ->badge()
                    ->color('info'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Todos')
                    ->trueLabel('Apenas Ativos')
                    ->falseLabel('Apenas Inativos'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Nenhum tipo de serviço cadastrado')
            ->emptyStateDescription('Cadastre tipos como: Audiência, Protocolo, Cópias, etc.')
            ->emptyStateIcon('heroicon-o-tag');
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
            'index' => Pages\ListServiceTypes::route('/'),
            'create' => Pages\CreateServiceType::route('/create'),
            'edit' => Pages\EditServiceType::route('/{record}/edit'),
        ];
    }
}

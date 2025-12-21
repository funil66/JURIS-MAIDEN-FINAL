<?php

namespace App\Filament\ClientPanel\Pages;

use App\Models\GeneratedDocument;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MyDocuments extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';
    protected static ?string $navigationLabel = 'Meus Documentos';
    protected static ?string $title = 'Meus Documentos';
    protected static string $view = 'filament.client-panel.pages.my-documents';
    protected static ?int $navigationSort = 4;

    public function table(Table $table): Table
    {
        $clientId = Auth::guard('client')->id();

        return $table
            ->query(GeneratedDocument::query()->where('client_id', $clientId))
            ->columns([
                TextColumn::make('template.name')
                    ->label('Tipo de Documento')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('service.code')
                    ->label('Serviço')
                    ->searchable(),

                TextColumn::make('generated_by_user.name')
                    ->label('Gerado por')
                    ->default('Sistema'),

                TextColumn::make('created_at')
                    ->label('Data de Geração')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('file_path')
                    ->label('Arquivo')
                    ->formatStateUsing(fn ($state) => $state ? 'PDF disponível' : 'Não gerado')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray'),
            ])
            ->actions([
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->visible(fn (GeneratedDocument $record): bool => !empty($record->file_path))
                    ->action(function (GeneratedDocument $record) {
                        if (Storage::disk('public')->exists($record->file_path)) {
                            return response()->download(
                                Storage::disk('public')->path($record->file_path),
                                $record->template?->name . '_' . $record->created_at->format('Y-m-d') . '.pdf'
                            );
                        }
                    }),

                Action::make('view')
                    ->label('Visualizar')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn (GeneratedDocument $record) => $record->template?->name ?? 'Documento')
                    ->modalContent(fn (GeneratedDocument $record) => view('filament.client-panel.partials.document-preview', ['document' => $record]))
                    ->modalWidth('4xl'),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50]);
    }
}

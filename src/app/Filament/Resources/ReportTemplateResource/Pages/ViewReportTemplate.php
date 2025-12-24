<?php

namespace App\Filament\Resources\ReportTemplateResource\Pages;

use App\Filament\Resources\ReportTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewReportTemplate extends ViewRecord
{
    protected static string $resource = ReportTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generate')
                ->label('Gerar Relatório')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->url(fn () => route('filament.funil.pages.advanced-reports', ['template' => $this->record->id])),

            Actions\Action::make('duplicate')
                ->label('Duplicar')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->action(function () {
                    $copy = $this->record->duplicate();
                    return redirect()->to(ReportTemplateResource::getUrl('edit', ['record' => $copy]));
                }),

            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}

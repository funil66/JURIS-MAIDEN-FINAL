<?php

namespace App\Filament\Pages;

use App\Services\GlobalSearchService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Livewire\Attributes\Url;

class GlobalSearchPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';
    protected static ?string $navigationLabel = 'Busca Global';
    protected static ?string $title = 'Busca Global';
    protected static ?string $slug = 'global-search';
    protected static ?int $navigationSort = -2;

    protected static string $view = 'filament.pages.global-search';

    #[Url]
    public string $query = '';

    public array $selectedEntities = [];
    public array $results = [];
    public array $entityCounts = [];
    public int $totalResults = 0;
    public bool $hasSearched = false;
    public array $recentSearches = [];
    public array $entityStats = [];

    protected GlobalSearchService $searchService;

    public function boot(GlobalSearchService $searchService): void
    {
        $this->searchService = $searchService;
    }

    public function mount(): void
    {
        $this->selectedEntities = array_keys($this->searchService->getSearchableEntities());
        $this->recentSearches = $this->searchService->getRecentSearches();
        $this->entityStats = $this->searchService->getEntityStats();

        // Se tem query na URL, buscar automaticamente
        if (!empty($this->query)) {
            $this->search();
        }
    }

    public function search(): void
    {
        if (strlen($this->query) < 2) {
            $this->results = [];
            $this->totalResults = 0;
            $this->entityCounts = [];
            return;
        }

        $searchResults = $this->searchService->search(
            $this->query,
            ['entities' => $this->selectedEntities],
            100
        );

        $this->results = $searchResults['results'];
        $this->totalResults = $searchResults['total'];
        $this->entityCounts = $searchResults['entities'];
        $this->hasSearched = true;

        // Salvar busca recente
        $this->searchService->saveRecentSearch($this->query);
        $this->recentSearches = $this->searchService->getRecentSearches();
    }

    public function searchFromRecent(string $term): void
    {
        $this->query = $term;
        $this->search();
    }

    public function clearSearch(): void
    {
        $this->query = '';
        $this->results = [];
        $this->totalResults = 0;
        $this->entityCounts = [];
        $this->hasSearched = false;
    }

    public function toggleEntity(string $entity): void
    {
        if (in_array($entity, $this->selectedEntities)) {
            $this->selectedEntities = array_filter(
                $this->selectedEntities,
                fn($e) => $e !== $entity
            );
        } else {
            $this->selectedEntities[] = $entity;
        }

        // Refazer busca se já pesquisou
        if ($this->hasSearched) {
            $this->search();
        }
    }

    public function selectAllEntities(): void
    {
        $this->selectedEntities = array_keys($this->searchService->getSearchableEntities());
        
        if ($this->hasSearched) {
            $this->search();
        }
    }

    public function deselectAllEntities(): void
    {
        $this->selectedEntities = [];
        
        if ($this->hasSearched) {
            $this->search();
        }
    }

    public function getSearchableEntitiesProperty(): array
    {
        return $this->searchService->getSearchableEntities();
    }

    public function getMaxWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }
}

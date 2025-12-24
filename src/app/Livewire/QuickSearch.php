<?php

namespace App\Livewire;

use App\Services\GlobalSearchService;
use Livewire\Component;

class QuickSearch extends Component
{
    public string $query = '';
    public array $results = [];
    public bool $isOpen = false;
    public int $selectedIndex = -1;

    protected GlobalSearchService $searchService;

    public function boot(GlobalSearchService $searchService): void
    {
        $this->searchService = $searchService;
    }

    public function updatedQuery(): void
    {
        if (strlen($this->query) >= 2) {
            $searchResults = $this->searchService->search($this->query, [], 8);
            $this->results = $searchResults['results'];
            $this->isOpen = true;
            $this->selectedIndex = -1;
        } else {
            $this->results = [];
            $this->isOpen = false;
        }
    }

    public function selectNext(): void
    {
        if (count($this->results) > 0) {
            $this->selectedIndex = min($this->selectedIndex + 1, count($this->results) - 1);
        }
    }

    public function selectPrevious(): void
    {
        if (count($this->results) > 0) {
            $this->selectedIndex = max($this->selectedIndex - 1, 0);
        }
    }

    public function goToSelected(): void
    {
        if ($this->selectedIndex >= 0 && isset($this->results[$this->selectedIndex])) {
            $url = $this->results[$this->selectedIndex]['url'];
            $this->redirect($url);
        }
    }

    public function goToResult(int $index): void
    {
        if (isset($this->results[$index])) {
            $url = $this->results[$index]['url'];
            $this->redirect($url);
        }
    }

    public function goToFullSearch(): void
    {
        $this->redirect(route('filament.admin.pages.global-search', ['query' => $this->query]));
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->query = '';
        $this->results = [];
    }

    public function render()
    {
        return view('livewire.quick-search');
    }
}

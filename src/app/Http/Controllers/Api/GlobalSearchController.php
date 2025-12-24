<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GlobalSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function __construct(
        protected GlobalSearchService $searchService
    ) {}

    /**
     * Busca global via API
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:255',
            'entities' => 'nullable|array',
            'entities.*' => 'string',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $query = $request->input('q');
        $entities = $request->input('entities', []);
        $limit = $request->input('limit', 20);

        $results = $this->searchService->search($query, [
            'entities' => empty($entities) ? [] : $entities,
        ], $limit);

        return response()->json($results);
    }

    /**
     * Busca por UID
     */
    public function searchByUid(Request $request): JsonResponse
    {
        $request->validate([
            'uid' => 'required|string|min:5|max:50',
        ]);

        $uid = strtoupper($request->input('uid'));
        $result = $this->searchService->searchByUid($uid);

        if (!$result) {
            return response()->json([
                'found' => false,
                'message' => 'UID não encontrado',
            ], 404);
        }

        return response()->json([
            'found' => true,
            'result' => $result,
        ]);
    }

    /**
     * Obtém sugestões de busca
     */
    public function suggestions(Request $request): JsonResponse
    {
        $recentSearches = $this->searchService->getRecentSearches(5);
        $entityStats = $this->searchService->getEntityStats();

        return response()->json([
            'recent_searches' => $recentSearches,
            'entity_stats' => $entityStats,
        ]);
    }

    /**
     * Obtém entidades pesquisáveis
     */
    public function entities(): JsonResponse
    {
        $entities = $this->searchService->getSearchableEntities();

        return response()->json([
            'entities' => array_map(function ($key, $entity) {
                return [
                    'key' => $key,
                    'label' => $entity['label'],
                    'icon' => $entity['icon'],
                    'color' => $entity['color'],
                ];
            }, array_keys($entities), $entities),
        ]);
    }
}

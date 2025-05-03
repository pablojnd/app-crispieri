<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SaldosApiController extends Controller
{
    public function getSuggestions(Request $request)
    {
        $search = $request->input('search');

        if (strlen($search) < 3) {
            return response()->json([
                'suggestions' => []
            ]);
        }

        try {
            $response = Http::get(env('API_SALDOS_URL') . '&search=' . urlencode($search));

            if ($response->successful()) {
                $data = $response->json();
                $items = $data['data'] ?? [];

                // Extraer sugerencias únicas de códigos de artículo
                $suggestions = collect($items)
                    ->pluck('Código_Artículo')
                    ->unique()
                    ->values()
                    ->take(10)
                    ->toArray();

                return response()->json([
                    'suggestions' => $suggestions
                ]);
            }

            return response()->json([
                'suggestions' => [],
                'error' => 'Error al obtener sugerencias: ' . $response->status()
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'suggestions' => [],
                'error' => 'Error de conexión: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getDetails(Request $request)
    {
        $search = $request->input('search');
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 10);

        if (empty($search)) {
            return response()->json([
                'data' => [],
                'total' => 0,
                'current_page' => 1
            ]);
        }

        try {
            $response = Http::get(env('API_SALDOS_URL') . '&search=' . urlencode($search) . '&page=' . $page);

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'data' => [],
                'error' => 'Error al obtener datos: ' . $response->status()
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'data' => [],
                'error' => 'Error de conexión: ' . $e->getMessage()
            ], 500);
        }
    }
}

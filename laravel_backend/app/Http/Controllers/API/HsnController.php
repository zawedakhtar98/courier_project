<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\HSNCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HsnController extends Controller
{
    public function search(Request $request)
    {
        $query = trim($request->get('q', ''));

        // 1. Guard clause: Minimum 2 characters
        if (strlen($query) < 2) {
            return response()->json([
                'status' => 'success',
                'data' => []
            ]);
        }

        // 2. Cache query results for 1 hour
        $cacheKey = 'hsn_search_' . md5(strtolower($query));

        $results = Cache::remember($cacheKey, 3600, function () use ($query) {
            $builder = HSNCode::query()
                ->select(['id', 'hsn_code', 'description'])
                ->where('status', 'active');

            // Scenario A: User types numeric HSN Code (e.g. "8471", "6109")
            if (ctype_digit($query)) {
                // Prefix match uses standard B-tree index (takes < 2ms)
                $builder->where('hsn_code', 'LIKE', "{$query}%");
            } 
            // Scenario B: User types text description (e.g. "cotton shirt", "laptop")
            else {
                // Clean punctuation for Boolean Mode
                $cleanQuery = preg_replace('/[+\-><()~*\"@]+/', ' ', $query);
                $words = array_filter(explode(' ', trim($cleanQuery)));
                
                // Words with 3+ characters can use MySQL Full-Text index
                $validWords = array_filter($words, fn($w) => strlen($w) >= 3);

                if (!empty($validWords)) {
                    // Match words with wildcard prefix (e.g., +cotton* +shirt*)
                    $booleanSearch = implode(' ', array_map(fn($w) => '+' . $w . '*', $validWords));

                    $builder->whereRaw(
                        "MATCH(description) AGAINST(? IN BOOLEAN MODE)",
                        [$booleanSearch]
                    );
                } else {
                    // Fallback for 2-letter search terms
                    $builder->where('description', 'LIKE', '%' . addcslashes($query, '%_') . '%');
                }
            }

            // Return top 20 matches
            return $builder->limit(20)->get();
        });

        return response()->json([
            'status' => 'success',
            'data' => $results
        ]);
    }
}

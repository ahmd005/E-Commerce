<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function index() {
        return Product::all();
    }

    private function benchmark(callable $callback): array
    {
        $start = hrtime(true);

        $result = $callback();

        return [
            'result' => $result,
            'duration_ms' => round((hrtime(true) - $start) / 1_000_000, 2),
            'count' => $result->count(),
        ];
    }

    public function before()
    {
        Cache::forget('products.all');

        $before = $this->benchmark(fn () => Product::all());

        Log::info('Products before benchmark', [
            'duration_ms' => $before['duration_ms'],
            'count' => $before['count'],
        ]);

        return response()->json([
            'stage' => 'before',
            'message' => 'Without cache',
            'duration_ms' => $before['duration_ms'],
            'count' => $before['count'],
        ]);
    }

    public function after()
    {
        $after = $this->benchmark(fn () =>
            Cache::remember('products.all', now()->addMinutes(10), fn () => Product::all())
        );

        Log::info('Products after benchmark', [
            'duration_ms' => $after['duration_ms'],
            'count' => $after['count'],
        ]);

        return response()->json([
            'stage' => 'after',
            'message' => 'With cache',
            'duration_ms' => $after['duration_ms'],
            'count' => $after['count'],
        ]);
    }

    public function compare()
    {
        Cache::forget('products.all');

        $before = $this->benchmark(fn () => Product::all());
        $after = $this->benchmark(fn () =>
            Cache::remember('products.all', now()->addMinutes(10), fn () => Product::all())
        );

        return response()->json([
            'before_ms' => $before['duration_ms'],
            'after_ms' => $after['duration_ms'],
            'difference_ms' => round($before['duration_ms'] - $after['duration_ms'], 2),
        ]);
    }
}
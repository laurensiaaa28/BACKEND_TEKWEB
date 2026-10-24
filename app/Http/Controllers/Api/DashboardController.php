<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Http\Resources\ProductResource;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    // Dashboard customer
    public function customer(Request $request)
    {
        $userId = $request->user()->id;
        $activeStatuses = ['pending', 'processing', 'delivering'];

        return response()->json([
            'data' => [
                'total_orders' => Order::where('user_id', $userId)->count(),
                'active_orders' => Order::where('user_id', $userId)
                    ->whereIn('status', $activeStatuses)
                    ->count(),
                'completed_orders' => Order::where('user_id', $userId)
                    ->where('status', 'completed')
                    ->count(),
                'total_spent' => (float) Order::where('user_id', $userId)
                    ->where('status', 'completed')
                    ->sum('total_price'),
                'cart_items' => (int) CartItem::where('user_id', $userId)->sum('quantity'),
                'recent_orders' => OrderResource::collection(
                    Order::with('items')
                        ->where('user_id', $userId)
                        ->latest()
                        ->take(5)
                        ->get()
                ),
            ],
        ]);
    }

    // Dashboard admin
    public function admin()
    {
        $rawStatus = Order::select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $ordersByStatus = collect(Order::STATUSES)->mapWithKeys(
            fn ($status) => [$status => (int) ($rawStatus[$status] ?? 0)]
        );

        $topProducts = OrderItem::select('product_name', DB::raw('SUM(quantity) as total_sold'))
            ->whereHas('order', fn ($q) => $q->where('status', '!=', 'cancelled'))
            ->groupBy('product_name')
            ->orderByDesc('total_sold')
            ->take(5)
            ->get();

        $lowStock = Product::with('category')
            ->where('is_active', true)
            ->where('stock', '<=', 5)
            ->orderBy('stock')
            ->take(5)
            ->get();

        return response()->json([
            'data' => [
                'total_customers' => User::where('role', 'customer')->count(),
                'total_products' => Product::count(),
                'total_orders' => Order::count(),
                'orders_today' => Order::whereDate('created_at', today())->count(),
                'total_revenue' => (float) Order::where('status', 'completed')->sum('total_price'),
                'orders_by_status' => $ordersByStatus,
                'top_products' => $topProducts,
                'low_stock_products' => ProductResource::collection($lowStock),
                'recent_orders' => OrderResource::collection(
                    Order::with('user')->latest()->take(5)->get()
                ),
            ],
        ]);
    }
}
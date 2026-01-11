<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    // ✅ Lister toutes les commandes de l'utilisateur
    public function index()
    {
        $user = Auth::user();

        $orders = Order::with('items.product')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' =>OrderResource::collection($orders)
        ]);
    }

    // ✅ Détail d'une commande
    public function show($id)
    {
        $user = Auth::user();

        $order = Order::with('items.product.images')
            ->where('user_id', $user->id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order)
        ]);
    }

    // ✅ Créer une commande
    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $total = 0;
        foreach ($request->items as $item) {
            $product = Product::findOrFail($item['product_id']);
            $total += ($product->promotion_price ?? $product->price) * $item['quantity'];
        }

        $order = Order::create([
            'user_id' => $user->id,
            'amount' => $total,
            'amount_rest' => $total,
            'status' => 'pending',
            'operator'=>$request->operator_id,
            'meta'=>[
                'operator_id'=>$request->operator_id,
                'email'=>$request->customer_address,
                'phone'=>$user->phone,
                'name'=>$request->customer_name
            ]
        ]);

        foreach ($request->items as $item) {
            $product = Product::findOrFail($item['product_id']);
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $item['quantity'],
                'amount' => ($product->promotion_price ?? $product->price) * $item['quantity'],

            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Commande créée avec succès',
            'data' => $order->load('items.product')
        ]);
    }
}

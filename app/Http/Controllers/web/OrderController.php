<?php


namespace App\Http\Controllers\web;


use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * 📄 Liste des commandes
     */
    public function index()
    {
        $orders = Order::with('items.product')
            ->latest()
            ->paginate(15);

        return view('orders.index', compact('orders'));
    }

    /**
     * 🔍 Détail d'une commande
     */
    public function show(Order $order)
    {
        $order->load('items.product', 'user');

        return view('orders.show', compact('order'));
    }

    /**
     * ✏️ Modifier le statut (admin)
     */
    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,paid,processing,delivered,canceled'
        ]);

        $order->update([
            'status' => $request->status
        ]);

        return redirect()
            ->back()
            ->with('success', 'Statut de la commande mis à jour');
    }
}

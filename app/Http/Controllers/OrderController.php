<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Models\Operator;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\FedaPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use function Illuminate\Database\Query\orderBy;

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

    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'operator_id' => 'required|exists:operators,id',
            'customer_name' => 'required|string|max:255',
            'customer_address' => 'required|email|max:255',
        ]);

        DB::beginTransaction();

        try {
            /** 🧮 Calcul total */
            $total = 0;
            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $total += ($product->promotion_price ?? $product->price) * $item['quantity'];
            }

            /** 📦 Commande */
            $order = Order::create([
                'user_id' => $user->id,
                'amount' => $total,
                'amount_rest' => $total,
                'status' => 'pending',
                'operator' => $request->operator_id,
                'meta' => [
                    'operator_id' => $request->operator_id,
                    'email' => $request->customer_address,
                    'phone' => $user->phone,
                    'name' => $request->customer_name
                ]
            ]);

            /** 🧾 Items */
            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'amount' => ($product->promotion_price ?? $product->price) * $item['quantity'],
                ]);
            }

            /** 💳 FedaPay */
            $operator = Operator::with('country')->findOrFail($request->operator_id);
            $fedaPay = new FedaPayService();

            /** 👤 Client */
            if (!$user->code) {
                $customer = $fedaPay->createCustomer([
                    'firstname' => $request->customer_name,
                    'lastname' => $request->customer_name,
                    'email' => $request->customer_address,
                    'phone' => $user->phone,
                    'country' => $operator->country->iso,
                ]);

                $customerId = $customer->data->{'v1/customer'}->id ?? null;

                if (!$customerId) {
                    throw new \Exception('Création client FedaPay échouée');
                }

                $user->update(['code' => $customerId]);
            }

            /** 💰 Paiement */
            $payment = $fedaPay->collect([
                'amount' => $total,
                'phone' => $user->phone,
                'method' => $operator->code,
                'customer_id' => $user->code,
                'callback_url' => route('fedapay.callback'),
            ]);

            $transaction = $payment->data->{'v1/transaction'} ?? null;
            if (!$transaction || !isset($transaction->id)) {
                throw new \Exception('Création paiement FedaPay échouée');
            }

            /** 🔗 Sauvegarde référence */
            $order->update([
                'reference_id' => $transaction->id,
                'meta' => array_merge($order->meta ?? [], [
                    'paymentId' => $transaction->id,
                    'reference' => $transaction->reference,
                    'payment_url' => $transaction->payment_url,
                ])
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Commande créée avec succès',
                'payment_url' => $transaction->payment_url,
                'referenceId' => $transaction->reference,
                'data' => $order->load('items.product')
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            logger()->error('ORDER ERROR: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la commande',
            ], 500);
        }
    }

}

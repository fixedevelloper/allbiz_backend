@extends('layouts.app')

@section('content')
    <div class="container">
        <h3>Commande #{{ $order->id }}</h3>

        <p>
            Client : <strong>{{ $order->user->name }}</strong><br>
            Date : {{ $order->created_at->format('d/m/Y H:i') }}<br>
            Total : <strong>{{ number_format($order->amount) }} FCFA</strong>
        </p>

        <hr>

        <table class="table">
            <thead>
            <tr>
                <th>Produit</th>
                <th>Qté</th>
                <th>Prix</th>
                <th>Total</th>
            </tr>
            </thead>
            <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->product->name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->promotion_price ?? $item->price) }}</td>
                    <td>
                        {{ number_format(($item->promotion_price ?? $item->price) * $item->quantity) }} FCFA
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection

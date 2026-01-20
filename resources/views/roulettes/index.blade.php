@extends('layouts.app')

@section('content')
    <h4>Roulettes disponibles</h4>

    @foreach($roulettes as $roulette)
        <div class="card mb-2">
            <div class="card-body">
                <p>Commission: {{ number_format($roulette->commission->amount) }} FCFA</p>

            </div>
        </div>
    @endforeach
@endsection

@extends('layouts.app')

@section('content')
    <div class="container mt-5">
        <h1 class="mb-4">Pays et Opérateurs</h1>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="mb-3">
            <a href="{{ route('countries.create') }}" class="btn btn-primary">Ajouter un pays</a>
            <a href="{{ route('operators.create') }}" class="btn btn-success">Ajouter un opérateur</a>
        </div>

        <div class="accordion" id="countriesAccordion">
            @foreach($countries as $country)
                <div class="card mb-2">
                    <div class="card-header" id="heading{{ $country->id }}">
                        <h2 class="mb-0 d-flex align-items-center">
                            <button class="btn btn-link flex-grow-1 text-left" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $country->id }}" aria-expanded="true" aria-controls="collapse{{ $country->id }}">
                                {{ $country->name }} ({{ $country->iso }})
                            </button>
                            @if($country->image_url)
                                <img src="{{ $country->image_url }}" alt="{{ $country->name }}" class="rounded-circle ms-2" width="50" height="50">
                            @endif
                        </h2>
                    </div>

                    <div id="collapse{{ $country->id }}" class="collapse" aria-labelledby="heading{{ $country->id }}" data-bs-parent="#countriesAccordion">
                        <div class="card-body">
                            @if($country->operators->count())
                                <ul class="list-group">
                                    @foreach($country->operators as $operator)
                                        <li class="list-group-item d-flex align-items-center">
                                            {{ $operator->name }}
                                            @if($operator->image_url)
                                                <img src="{{ $operator->image_url }}" alt="{{ $operator->name }}" class="rounded ms-3" width="40" height="40">
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p>Aucun opérateur pour ce pays.</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection

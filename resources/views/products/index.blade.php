@extends('layouts.app')

@section('content')

    <div class="content-header">
        <div>
            <h2 class="content-title card-title">Products List</h2>
            <p></p>
        </div>
        <div>
{{--            <a href="#" class="btn btn-light rounded font-md">Export</a>
            <a href="#" class="btn btn-light rounded font-md">Import</a>--}}
            <a href="{{ route('products.create') }}" class="btn btn-primary btn-sm rounded">Ajouter un product</a>
        </div>
    </div>
    <div class="card mb-4">
        <header class="card-header">
            <div class="row align-items-center">
                <div class="col col-check flex-grow-0">
                    <div class="form-check ms-2">
                        <input class="form-check-input" type="checkbox" value="">
                    </div>
                </div>
                <div class="col-md-3 col-12 me-auto mb-md-0 mb-3">
                    <select class="form-select">
                        <option selected="">All category</option>

                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <input type="date" value="02.05.2021" class="form-control">
                </div>
                <div class="col-md-2 col-6">
                    <select class="form-select">
                        <option selected="">Status</option>
                        <option>Active</option>
                        <option>Disabled</option>
                        <option>Show all</option>
                    </select>
                </div>
            </div>
        </header>
        <!-- card-header end// -->
        <div class="card-body">
            @foreach($products as $product)
                @php
                    $image = $product->images->first();
                @endphp

                <article class="itemlist">
                    <div class="row align-items-center">

                        {{-- Checkbox --}}
                        <div class="col-auto">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="{{ $product->id }}">
                            </div>
                        </div>

                        {{-- Produit --}}
                        <div class="col-lg-4 col-sm-4 col-8">
                            <a class="itemside" href="{{ route('products.show', $product) }}">
                                <div class="left">
                                    <img
                                        src="{{ $image ? asset($image->src) : asset('assets/imgs/placeholder.png') }}"
                                        class="img-sm img-thumbnail"
                                        alt="{{ $product->name }}"
                                    >
                                </div>
                                <div class="info">
                                    <h6 class="mb-0">{{ $product->name }}</h6>

                                    @if($product->is_promotion)
                                        <small class="text-danger">Promo</small>
                                    @endif
                                </div>
                            </a>
                        </div>

                        {{-- Prix --}}
                        <div class="col-lg-2 col-sm-2 col-4">
            <span class="fw-bold">
                {{ number_format($product->price) }} FCFA
            </span>

                            @if($product->is_promotion)
                                <div class="text-muted small">
                                    <del>{{ number_format($product->promotion_price) }} FCFA</del>
                                </div>
                            @endif
                        </div>

                        {{-- Statut --}}
                        <div class="col-lg-2 col-sm-2 col-4">
            <span class="badge rounded-pill
                {{ $product->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                {{ ucfirst($product->status) }}
            </span>
                        </div>

                        {{-- Date --}}
                        <div class="col-lg-1 col-sm-2 col-4">
            <span class="text-muted small">
                {{ $product->created_at->format('d/m/Y') }}
            </span>
                        </div>

                        {{-- Actions --}}
                        <div class="col-lg-1 col-sm-2 col-4 text-end">
                            <a href="{{ route('products.edit', $product) }}"
                               class="btn btn-sm btn-brand rounded">
                                <i class="material-icons md-edit"></i>
                            </a>

                            <form method="POST"
                                  action="{{ route('products.destroy', $product) }}"
                                  class="d-inline"
                                  onsubmit="return confirm('Supprimer ce produit ?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-light rounded">
                                    <i class="material-icons md-delete_forever text-danger"></i>
                                </button>
                            </form>
                        </div>

                    </div>
                </article>
            @endforeach


        </div>
        <!-- card-body end// -->
    </div>
    <!-- card end// -->
    <div class="pagination-area mt-30 mb-50">
        {{ $products->links() }}
    </div>
@endsection

@extends('layouts.app')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">

                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            {{ isset($category) ? 'Modifier la catégorie' : 'Ajouter une catégorie' }}
                        </h4>
                    </div>
                    <div class="card-body">

                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form
                            action="{{ isset($category) ? route('categories.update', $category) : route('categories.store') }}"
                            method="POST"
                        >
                            @csrf
                            @if(isset($category))
                                @method('PUT')
                            @endif

                            <div class="mb-3">
                                <label for="name" class="form-label">Nom de la catégorie</label>
                                <input
                                    type="text"
                                    name="name"
                                    id="name"
                                    class="form-control"
                                    placeholder="Entrez le nom de la catégorie"
                                    value="{{ old('name', $category->name ?? '') }}"
                                    required
                                >
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <a href="{{ route('categories.index') }}" class="btn btn-secondary">
                                    Retour
                                </a>
                                <button type="submit" class="btn btn-success">
                                    {{ isset($category) ? 'Mettre à jour' : 'Ajouter' }}
                                </button>
                            </div>
                        </form>

                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

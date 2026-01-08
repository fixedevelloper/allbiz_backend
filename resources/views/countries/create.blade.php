@extends('layouts.app')

@section('content')
    <div class="container mt-5">
        <h1>Ajouter un pays</h1>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('countries.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label class="form-label">Nom du pays</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Code ISO</label>
                <input type="text" name="iso" class="form-control" maxlength="3" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Indicatif</label>
                <input type="text" name="code" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Statut</label>
                <select name="status" class="form-select">
                    <option value="1">Actif</option>
                    <option value="0">Inactif</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Logo</label>
                <input type="file" name="image" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Créer le pays</button>
        </form>
    </div>
@endsection

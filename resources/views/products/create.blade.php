@extends('layouts.app')

@section('content')
    <div class="container py-5">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h3 class="mb-0">Ajouter un produit</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    {{-- Nom --}}
                    <div class="mb-3">
                        <label class="form-label">Nom du produit</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}">
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>


                    {{-- Prix --}}
                    <div class="mb-3">
                        <label class="form-label">Prix (FCFA)</label>
                        <input type="number" name="price" step="0.01" class="form-control @error('price') is-invalid @enderror" value="{{ old('price') }}">
                        @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Promotion --}}
                    <div class="form-check mb-3">
                        <input type="hidden" name="is_promotion" value="0">
                        <input type="checkbox" name="is_promotion" value="1" class="form-check-input" id="promotion" {{ old('is_promotion') ? 'checked' : '' }}>
                        <label class="form-check-label" for="promotion">En promotion</label>
                    </div>

                    {{-- Prix promotion --}}
                    <div class="mb-3">
                        <label class="form-label">Prix Promotion (FCFA)</label>
                        <input type="number" name="promotion_price" step="0.01" class="form-control @error('promotion_price') is-invalid @enderror" value="{{ old('promotion_price') }}">
                        @error('promotion_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Téléchargeable --}}
                    <div class="form-check mb-3">
                        <input type="hidden" name="downloable" value="0">
                        <input type="checkbox" name="downloable" value="1" class="form-check-input" id="downloable" {{ old('downloable') ? 'checked' : '' }}>
                        <label class="form-check-label" for="downloable">Téléchargeable</label>
                    </div>

                    {{-- Catégorie --}}
                    <div class="mb-3">
                        <label class="form-label">Catégorie</label>
                        <select name="category_id" class="form-select">
                            <option value="">-- Sélectionner une catégorie --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Description</label>
                        <textarea name="description" placeholder="Type here" class="form-control" rows="4"></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Comment ca marche</label>
                        <textarea name="how_it_works" placeholder="Type here" class="form-control" rows="4"></textarea>
                    </div>
                    {{-- Images --}}
                    <div class="mb-3">
                        <label class="form-label">Images du produit</label>
                        <input type="file" name="images[]" class="form-control" multiple accept="image/*" id="productImages">
                        <div class="row mt-2" id="previewImages"></div>
                    </div>

                    <button type="submit" class="btn btn-success">Ajouter le produit</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Preview JS --}}
    <script>
        const input = document.getElementById('productImages');
        const preview = document.getElementById('previewImages');

        input.addEventListener('change', function() {
            preview.innerHTML = '';
            Array.from(this.files).forEach(file => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.classList.add('col-3', 'mb-2');
                    div.innerHTML = `<img src="${e.target.result}" class="img-fluid rounded shadow-sm">`;
                    preview.appendChild(div);
                }
                reader.readAsDataURL(file);
            });
        });
    </script>
@endsection

@extends('layouts.app')

@section('content')
    <div class="container py-5">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h3 class="mb-0">Modifier le produit</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    {{-- Nom --}}
                    <div class="mb-3">
                        <label class="form-label">Nom du produit</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $product->name) }}">
                        @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Slug --}}
                    <div class="mb-3">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $product->slug) }}">
                        @error('slug')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Prix --}}
                    <div class="mb-3">
                        <label class="form-label">Prix (FCFA)</label>
                        <input type="number" name="price" step="0.01" class="form-control @error('price') is-invalid @enderror" value="{{ old('price', $product->price) }}">
                        @error('price')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Promotion --}}
                    <div class="form-check mb-3">
                        <input type="hidden" name="is_promotion" value="{{$product->is_promotion}}">
                        <input type="checkbox" name="is_promotion" class="form-check-input" id="promotion" {{ old('is_promotion', $product->is_promotion) ? 'checked' : '' }}>

                        <label class="form-check-label" for="promotion">En promotion</label>
                    </div>


                    {{-- Prix promotion --}}
                    <div class="mb-3">
                        <label class="form-label">Prix Promotion (FCFA)</label>
                        <input type="number" name="promotion_price" step="0.01" class="form-control @error('promotion_price') is-invalid @enderror" value="{{ old('promotion_price', $product->promotion_price) }}">
                        @error('promotion_price')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>


                    <div class="form-check mb-3">
                        <input type="hidden" name="downloable" value="{{$product->downloable}}">
                        <input type="checkbox" name="downloable" value="1" class="form-check-input" id="downloable" {{ old('downloable') ? 'checked' : '' }}>
                        <label class="form-check-label" for="downloable">Téléchargeable</label>
                    </div>
                    {{-- Catégorie --}}
                    <div class="mb-3">
                        <label class="form-label">Catégorie</label>
                        <select name="category_id" class="form-select">
                            <option value="">-- Sélectionner une catégorie --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Description</label>
                        <textarea  name="description" placeholder="Type here" class="form-control" rows="4">
                  {{ old('description', $product->description) }}
                        </textarea>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Comment ca marche</label>
                        <textarea name="how_it_works" placeholder="Type here" class="form-control" rows="4">
                              {{ old('how_it_works', $product->how_it_works) }}
                        </textarea>
                    </div>
                    {{-- Images existantes --}}
                    <div class="mb-3">
                        <label class="form-label">Images existantes</label>
                        <div class="row mb-2" id="existingImages">
                            @foreach($product->images as $img)
                                <div class="col-4 col-md-3 mb-2 position-relative">
                                    <img src="{{ $img->src }}" class="img-fluid rounded shadow-sm">
                                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 remove-existing" data-id="{{ $img->id }}">×</button>
                                    <input type="hidden" name="existing_images[]" value="{{ $img->id }}">
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Ajouter de nouvelles images --}}
                    <div class="mb-3">
                        <label class="form-label">Ajouter de nouvelles images</label>
                        <input type="file" name="images[]" class="form-control" multiple accept="image/*" id="newImages">
                        <div class="row mt-2" id="previewNewImages"></div>
                    </div>

                    <button type="submit" class="btn btn-success">Mettre à jour le produit</button>
                </form>
            </div>
        </div>
    </div>

    {{-- JS pour gérer les images --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const existingImages = document.getElementById('existingImages');
            const newImagesInput = document.getElementById('newImages');
            const previewNewImages = document.getElementById('previewNewImages');

            // Supprimer une image existante
            existingImages.addEventListener('click', function(e) {
                if(e.target.classList.contains('remove-existing')){
                    const parent = e.target.parentElement;
                    parent.remove();
                }
            });

            // Prévisualiser nouvelles images
            newImagesInput.addEventListener('change', function() {
                previewNewImages.innerHTML = '';
                Array.from(this.files).forEach(file => {
                    const reader = new FileReader();
                    reader.onload = function(e){
                        const div = document.createElement('div');
                        div.classList.add('col-4','col-md-3','mb-2','position-relative');
                        div.innerHTML = `
                    <img src="${e.target.result}" class="img-fluid rounded shadow-sm">
                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1" onclick="this.parentElement.remove()">×</button>
                `;
                        previewNewImages.appendChild(div);
                    }
                    reader.readAsDataURL(file);
                });
            });
        });
    </script>
@endsection

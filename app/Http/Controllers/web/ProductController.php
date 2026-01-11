<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Image;
use Illuminate\Container\Container;
use Illuminate\Container\TClass;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    // ✅ Liste des produits
    public function index()
    {
        $products = Product::with(['category', 'images'])->latest()->paginate(10);

        return view('products.index', compact('products'));
    }

    // ✅ Détail d’un produit
    public function show(Product $product)
    {
        $product->load('category', 'images');

        return view('products.show', compact('product'));
    }

    // ✅ Formulaire ajout produit
    public function create()
    {
        $categories = Category::all();
        $images = Image::all();

        return view('products.create', compact('categories', 'images'));
    }

    // ✅ Sauvegarde d’un produit
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                //'slug' => 'required|string|unique:products,slug',
                'downloable' => 'sometimes|boolean',
                'is_promotion' => 'sometimes|boolean',
                'price' => 'required|numeric|min:0',
                'promotion_price' => 'nullable|numeric|min:0',
                'category_id' => 'nullable|exists:categories,id',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            ]);

            $slug = $request->slug ?? Str::slug($request->name);

            // ⚠️ Vérifie unicité
            $originalSlug = $slug;
            $count = 1;
            while (Product::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $count;
                $count++;
            }
            $product = Product::create([
                'name' => $request->name,
                'slug' => $slug,
                'downloable' => $request->has('downloable'), // true si coché, false sinon
                'how_it_works'=>$request->how_it_works,
                'description'=>$request->description,
                'is_promotion' => $request->has('is_promotion'), // true si coché
                'price' => $request->price,
                'promotion_price' => $request->promotion_price ?? 0,
                'category_id' => $request->category_id,
            ]);


            // 3️⃣ Upload des images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $path = $file->store('products', 'public');

                    // Créer l'image
                    $image = Image::create([
                        'name' => $file->getClientOriginalName(),
                        'src' => '/storage/' . $path,
                    ]);

                    // Lier au produit via le pivot
                    $product->images()->attach($image->id);
                }
            }
        }catch (\Exception $exception){
            logger($exception);
        }


        return redirect()->route('products.index')
            ->with('success', 'Produit ajouté avec succès');
    }

    /**
     * Afficher le formulaire d'édition
     * @param Product $product
     * @return Container|TClass|Factory|View|object
     */
    public function edit(Product $product)
    {
     $categories=Category::all();
        return view('products.edit', compact('product','categories'));
    }

    public function update(Request $request, Product $product)
    {
        try {
            // 1️⃣ Validation
            $request->validate([
                'name' => 'required|string|max:255',
                //'downloable' => 'sometimes|boolean',
               // 'is_promotion' => 'sometimes|boolean',
                'price' => 'required|numeric|min:0',
                'promotion_price' => 'nullable|numeric|min:0',
                'category_id' => 'nullable|exists:categories,id',
                'existing_images' => 'nullable|array',
                'existing_images.*' => 'exists:images,id',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048', // nouvelles images
            ]);

            // 2️⃣ Mise à jour du produit
            $product->update([
                'name' => $request->name,
                'slug' => $request->slug,
                'downloable' => $request->has('downloable'), // true si coché, false sinon
                'is_promotion' => $request->has('is_promotion'), // true si coché
                'how_it_works'=>$request->how_it_works,
                'description'=>$request->description,
                'price' => $request->price,
                'promotion_price' => $request->promotion_price ?? 0,
                'category_id' => $request->category_id,
            ]);

            // 3️⃣ Gestion des images existantes
            $existingImages = $request->existing_images ?? [];
            $product->images()->sync($existingImages);

            // 4️⃣ Upload des nouvelles images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $path = $file->store('products', 'public');

                    // Créer l'image
                    $image = Image::create([
                        'name' => $file->getClientOriginalName(),
                        'src' => '/storage/' . $path,
                    ]);

                    // Lier au produit
                    $product->images()->attach($image->id);
                }
            }
        }catch (\Exception $exception){
            logger($exception);
        }


        return redirect()->route('products.index')
            ->with('success', 'Produit mis à jour avec succès');
    }

}

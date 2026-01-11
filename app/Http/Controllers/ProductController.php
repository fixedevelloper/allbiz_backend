<?php


namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Category;
use App\Models\Image;
use Illuminate\Http\Request;

class ProductController extends Controller
{


    // ✅ Lister tous les produits avec catégorie et images
    public function index()
    {
        $products = Product::with(['category', 'images'])->get();

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products)
        ]);
    }

    // ✅ Détails d'un produit
    public function show($slug)
    {
        $product = Product::with(['category', 'images'])->where(['slug'=>$slug])->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $product
        ]);
    }
}


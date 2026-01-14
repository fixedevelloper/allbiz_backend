<?php


namespace App\Http\Controllers\web;


use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Operator;
use Illuminate\Http\Request;

class HookController extends Controller
{

    /**
     * Afficher tous les pays avec leurs opérateurs
     */
    public function dashboard()
    {
        return view('dashboard');
    }
    /**
     * Afficher tous les pays avec leurs opérateurs
     */
    public function index()
    {
        $countries = Country::with('operators')->get();
        return view('countries.index', compact('countries'));
    }

    /**
     * Formulaire pour créer un nouveau pays
     */
    public function createCountry()
    {
        return view('countries.create');
    }

    /**
     * Ajouter un nouveau pays avec upload d'image
     */
    public function storeCountry(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:countries,name',
            'iso' => 'required|string|unique:countries,iso|max:3',
            'code' => 'required|string|unique:countries,code',
            'status' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->only(['name','iso','code','status']);

        if($request->hasFile('image')) {
            $path = $request->file('image')->store('countries', 'public');
            $data['image_url'] = '/storage/' . $path;
        }

        Country::create($data);

        return redirect()->route('countries.index')->with('success', 'Pays créé avec succès.');
    }

    /**
     * Formulaire pour créer un nouvel opérateur
     */
    public function createOperator()
    {
        $countries = Country::all();
        return view('operators.create', compact('countries'));
    }

    /**
     * Ajouter un nouvel opérateur avec upload d'image
     */
    public function storeOperator(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'code' => 'required|string',
            'country_id' => 'required|exists:countries,id',
            'status' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->only(['name','country_id','status']);

        if($request->hasFile('image')) {
            $path = $request->file('image')->store('operators', 'public');
            $data['image_url'] = '/storage/' . $path;
        }

        Operator::create($data);

        return redirect()->route('countries.index')->with('success', 'Opérateur créé avec succès.');
    }
}

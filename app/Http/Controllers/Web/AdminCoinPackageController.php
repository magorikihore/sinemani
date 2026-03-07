<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CoinPackage;
use Illuminate\Http\Request;

class AdminCoinPackageController extends Controller
{
    public function index()
    {
        $packages = CoinPackage::orderBy('sort_order')->get();

        return view('admin.coin-packages.index', compact('packages'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'coins' => 'required|integer|min:1',
            'bonus_coins' => 'nullable|integer|min:0',
            'price' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|max:3',
            'store_product_id' => 'nullable|string|max:255',
            'is_popular' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $data['is_popular'] = $request->boolean('is_popular');
        $data['is_active'] = $request->boolean('is_active', true);
        $data['bonus_coins'] = $data['bonus_coins'] ?? 0;

        CoinPackage::create($data);

        return back()->with('success', 'Coin package created.');
    }

    public function update(Request $request, CoinPackage $coinPackage)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'coins' => 'required|integer|min:1',
            'bonus_coins' => 'nullable|integer|min:0',
            'price' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|max:3',
            'store_product_id' => 'nullable|string|max:255',
            'is_popular' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $data['is_popular'] = $request->boolean('is_popular');
        $data['is_active'] = $request->boolean('is_active');

        $coinPackage->update($data);

        return back()->with('success', 'Coin package updated.');
    }

    public function destroy(CoinPackage $coinPackage)
    {
        $coinPackage->delete();

        return back()->with('success', 'Coin package deleted.');
    }
}

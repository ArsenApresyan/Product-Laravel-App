<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendPriceChangeNotification;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Services\PriceChangeService;
use App\Repositories\ProductRepositoryInterface;

class AdminController extends Controller
{
    protected $productRepository;
    protected $priceChangeService;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        PriceChangeService $priceChangeService
    ) {
        $this->productRepository = $productRepository;
        $this->priceChangeService = $priceChangeService;
    }

    public function loginPage()
    {
        return view('login');
    }

    public function login(Request $request)
    {
        if (Auth::attempt($request->except('_token'))) {
            return redirect()->route('admin.products');
        }

        return redirect()->back()->with('error', 'Invalid login credentials');
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('login');
    }

    public function products()
    {
        $products = $this->productRepository->paginate(15);
        return view('admin.products', compact('products'));
    }

    public function editProduct($id)
    {
        $product = $this->productRepository->findOrFail($id);
        return view('admin.edit_product', compact('product'));
    }

    public function updateProduct(UpdateProductRequest $request, $id)
    {
        $product = $this->productRepository->findOrFail($id);

        // Store the old price before updating
        $oldPrice = $product->price;

        $this->productRepository->update($product, $request->validated());

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads'), $filename);
            $product->image = 'uploads/' . $filename;
            $product->save();
        }

        // Check if price has changed and notify
        $this->priceChangeService->notifyPriceChange($product, $oldPrice, $product->price);

        return redirect()->route('admin.products')->with('success', 'Product updated successfully');
    }

    public function deleteProduct($id)
    {
        try {
            $this->productRepository->delete($id);
            return redirect()->route('admin.products')->with('success', 'Product deleted successfully');
        } catch (\Exception $e) {
            Log::error('Failed to delete product: ' . $e->getMessage());
            return redirect()->route('admin.products')->with('error', 'Failed to delete product');
        }
    }

    public function addProductForm()
    {
        return view('admin.add_product');
    }

    public function addProduct(StoreProductRequest $request)
    {
        $product = $this->productRepository->create($request->validated());

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads'), $filename);
            $product->image = 'uploads/' . $filename;
        } else {
            $product->image = 'product-placeholder.jpg';
        }

        $product->save();

        return redirect()->route('admin.products')->with('success', 'Product added successfully');
    }
}

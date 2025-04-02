<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendPriceChangeNotification;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Services\PriceChangeService;
use App\Repositories\ProductRepositoryInterface;
use App\Services\ImageService;

class AdminController extends Controller
{
    protected $productRepository;
    protected $priceChangeService;
    protected $imageService;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        PriceChangeService $priceChangeService,
        ImageService $imageService
    ) {
        $this->productRepository = $productRepository;
        $this->priceChangeService = $priceChangeService;
        $this->imageService = $imageService;
    }

    // Login methods removed as they're now in LoginController

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
            $product->image = $this->imageService->uploadImage($request->file('image'));
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
            $product->image = $this->imageService->uploadImage($request->file('image'));
        } else {
            $product->image = 'product-placeholder.jpg';
        }

        $product->save();

        return redirect()->route('admin.products')->with('success', 'Product added successfully');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Repositories\ProductRepositoryInterface;
use App\Services\ImageService;
use App\Services\PriceChangeService;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
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

    /**
     * Display a listing of the products.
     */
    public function index()
    {
        $products = $this->productRepository->paginate(15);
        return view('admin.products.index', compact('products'));
    }

    /**
     * Show the form for creating a new product.
     */
    public function create()
    {
        return view('admin.products.create');
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(StoreProductRequest $request)
    {
        $product = $this->productRepository->create($request->validated());

        if ($request->hasFile('image')) {
            $product->image = $this->imageService->uploadImage($request->file('image'));
        } else {
            $product->image = 'product-placeholder.jpg';
        }

        $product->save();

        return redirect()->route('admin.products.index')->with('success', 'Product added successfully');
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit($id)
    {
        $product = $this->productRepository->findOrFail($id);
        return view('admin.products.edit', compact('product'));
    }

    /**
     * Update the specified product in storage.
     */
    public function update(UpdateProductRequest $request, $id)
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

        return redirect()->route('admin.products.index')->with('success', 'Product updated successfully');
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy($id)
    {
        try {
            $this->productRepository->delete($id);
            return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully');
        } catch (\Exception $e) {
            Log::error('Failed to delete product: ' . $e->getMessage());
            return redirect()->route('admin.products.index')->with('error', 'Failed to delete product');
        }
    }
}
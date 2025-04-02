<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\ProductRepositoryInterface;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    protected $productRepository;

    public function __construct(ProductRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function index()
    {
        $products = $this->productRepository->paginate(12);
        $exchangeRate = $this->getExchangeRate();

        return view('products.list', compact('products', 'exchangeRate'));
    }

    public function show(Request $request)
    {
        $id = $request->route('product_id');
        $product = $this->productRepository->findOrFail($id);
        $exchangeRate = $this->getExchangeRate();

        return view('products.show', compact('product', 'exchangeRate'));
    }

    private function getExchangeRate()
    {
        try {
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => "https://open.er-api.com/v6/latest/USD",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if (!$err) {
                $data = json_decode($response, true);
                if (isset($data['rates']['EUR'])) {
                    return $data['rates']['EUR'];
                }
            }
        } catch (\Exception $e) {
            Log::error('Exchange rate API error: ' . $e->getMessage());
        }

        return config('app.default_exchange_rate', 0.85); // Use config instead of env
    }
}

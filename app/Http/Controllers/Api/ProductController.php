<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ProductController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Product::class);

        return ProductResource::collection(
            Product::query()->latest('id')->paginate(25)
        );
    }

    public function show(Product $product): ProductResource
    {
        Gate::authorize('view', $product);

        return new ProductResource($product);
    }

    public function store(ProductStoreRequest $request): JsonResponse
    {
        Gate::authorize('create', Product::class);

        $product = Product::query()->create($request->validated());

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201);
    }

    public function update(ProductUpdateRequest $request, Product $product): ProductResource
    {
        Gate::authorize('update', $product);

        $product->update($request->validated());

        return new ProductResource($product->refresh());
    }
}

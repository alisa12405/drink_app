<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Drink\StoreDrinkRequest;
use App\Http\Requests\Drink\UpdateDrinkRequest;
use App\Http\Resources\DrinkResource;
use App\Jobs\UpdateDrinkEmbeddingJob;
use App\Models\Drink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class DrinkController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $drinks = Drink::query()
            ->available()
            ->when(
                $request->filled('category'),
                fn ($query) => $query->where('category', $request->string('category')->toString()),
            )
            ->orderBy('name')
            ->get();

        return DrinkResource::collection($drinks);
    }

    public function show(Drink $drink): DrinkResource
    {
        abort_unless($drink->is_available, 404);

        return new DrinkResource($drink);
    }

    public function adminIndex(Request $request): AnonymousResourceCollection
    {
        $drinks = Drink::query()
            ->when(
                $request->filled('category'),
                fn ($query) => $query->where('category', $request->string('category')->toString()),
            )
            ->orderBy('name')
            ->get();

        return DrinkResource::collection($drinks);
    }

    public function adminShow(Drink $drink): DrinkResource
    {
        return new DrinkResource($drink);
    }

    public function store(StoreDrinkRequest $request): JsonResponse
    {
        $drink = Drink::query()->create($request->validated());

        UpdateDrinkEmbeddingJob::dispatch($drink);

        return (new DrinkResource($drink))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateDrinkRequest $request, Drink $drink): DrinkResource
    {
        $drink->update($request->validated());

        if ($drink->wasChanged(['name', 'ingredients', 'tags'])) {
            UpdateDrinkEmbeddingJob::dispatch($drink);
        }

        return new DrinkResource($drink->refresh());
    }

    public function destroy(Drink $drink): Response
    {
        $drink->delete();

        return response()->noContent();
    }
}

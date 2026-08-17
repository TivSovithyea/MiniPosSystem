<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(Customer::when($request->filled('search'), fn ($q) => $q->where(fn ($inner) => $inner->where('name', 'like', '%'.$request->string('search').'%')->orWhere('phone', 'like', '%'.$request->string('search').'%')->orWhere('email', 'like', '%'.$request->string('search').'%')))->latest()->paginate(min(max($request->integer('per_page', 20), 1), 100)));
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(Customer::create($this->validated($request)), 201);
    }

    public function show(Customer $customer): JsonResponse
    {
        return response()->json($customer->load(['orders' => fn ($q) => $q->latest()->limit(20)]));
    }

    public function update(Request $request, Customer $customer): JsonResponse
    {
        $customer->update($this->validated($request, $customer));

        return response()->json($customer);
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $customer->delete();

        return response()->json(null, 204);
    }

    private function validated(Request $request, ?Customer $customer = null): array
    {
        return $request->validate(['name' => [$customer ? 'sometimes' : 'required', 'string', 'max:150'], 'email' => ['nullable', 'email', 'max:255', Rule::unique('customers')->ignore($customer)], 'phone' => ['nullable', 'string', 'max:30', Rule::unique('customers')->ignore($customer)], 'address' => ['nullable', 'string', 'max:1000']]);
    }
}

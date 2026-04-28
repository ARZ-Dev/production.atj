<?php

namespace App\Http\Controllers;

use App\Services\ApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupplierController extends Controller
{
    public function __construct(
        private ApiService $api
    ) {
    }

    public function index()
    {
        $data = [];
        $data['suppliers'] = $this->api->get('/v1/suppliers', ['module' => 'production'])['data'] ?? [];

        return view('suppliers.index', $data);
    }

    public function create()
    {
        $data = [];
        $data['route'] = route('suppliers.store');
        $data['editing'] = false;
        $data['departments'] = $this->api->get('/v1/departments', ['module' => 'production'])['data'] ?? [];
        $data['departments'] = collect($data['departments'])
            ->where('related_to_production', true)
            ->values()
            ->toArray();
        $data['countries'] = $this->api->get('/v1/locations/countries')['data'] ?? [];

        return view('suppliers.create', $data);
    }

    public function getProvinces($countryId)
    {
        $response = $this->api->get("/v1/locations/provinces/{$countryId}");

        if (!($response['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $response['message'] ?? 'Failed to fetch provinces.'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'provinces' => $response['data'] ?? []
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'department_id' => 'nullable|integer',
            'company_name' => 'required|string|max:255',
            'company_registration_number' => 'required|string|max:255',
            'company_website' => 'nullable|url|max:255',
            'company_phone' => 'required|string|max:50',
            'country_id' => 'required|integer',
            'province_id' => 'nullable|integer',
            'municipality_id' => 'nullable|integer',
            'neighborhood_id' => 'nullable|integer',
            'company_address' => 'required|string|max:500',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'poc_name' => 'required|string|max:255',
            'poc_email' => 'nullable|email|max:255',
            'poc_phone' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $response = $this->api->post('/v1/suppliers', [
            'department_id' => $request->input('department_id'),
            'company_name' => $request->input('company_name'),
            'company_registration_number' => $request->input('company_registration_number'),
            'company_website' => $request->input('company_website'),
            'company_phone' => $request->input('company_phone'),
            'country_id' => $request->input('country_id'),
            'province_id' => $request->input('province_id'),
            'municipality_id' => $request->input('municipality_id'),
            'neighborhood_id' => $request->input('neighborhood_id'),
            'company_address' => $request->input('company_address'),
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'poc_name' => $request->input('poc_name'),
            'poc_email' => $request->input('poc_email'),
            'poc_phone' => $request->input('poc_phone'),
        ]);

        if (!($response['success'] ?? false)) {
            return redirect()->back()
                ->with('error', $response['message'] ?? 'Failed to create supplier.')
                ->withInput();
        }

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier created successfully.');
    }

    public function edit(int $id)
    {
        $supplier = $this->api->get("/v1/suppliers/{$id}")['data'] ?? null;

        if (!$supplier) {
            return redirect()->route('suppliers.index')
                ->with('error', 'Supplier not found.');
        }

        $data = [];
        $data['route'] = route('suppliers.update', $id);
        $data['editing'] = true;
        $data['supplier'] = $supplier;
        $data['departments'] = $this->api->get('/v1/departments', ['module' => 'production'])['data'] ?? [];
        $data['departments'] = collect($data['departments'])
            ->where('related_to_production', true)
            ->values()
            ->toArray();
        $data['countries'] = $this->api->get('/v1/countries')['data'] ?? [];
        $data['provinces'] = $supplier['country_id'] ? ($this->api->get("/v1/locations/provinces/{$supplier['country_id']}")['data'] ?? []) : [];

        return view('suppliers.create', $data);
    }

    public function update(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'department_id' => 'nullable|integer',
            'company_name' => 'required|string|max:255',
            'company_registration_number' => 'required|string|max:255',
            'company_website' => 'nullable|url|max:255',
            'company_phone' => 'required|string|max:50',
            'country_id' => 'required|integer',
            'province_id' => 'nullable|integer',
            'municipality_id' => 'nullable|integer',
            'neighborhood_id' => 'nullable|integer',
            'company_address' => 'required|string|max:500',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'poc_name' => 'required|string|max:255',
            'poc_email' => 'nullable|email|max:255',
            'poc_phone' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $response = $this->api->put("/v1/suppliers/{$id}", [
            'department_id' => $request->input('department_id'),
            'company_name' => $request->input('company_name'),
            'company_registration_number' => $request->input('company_registration_number'),
            'company_website' => $request->input('company_website'),
            'company_phone' => $request->input('company_phone'),
            'country_id' => $request->input('country_id'),
            'province_id' => $request->input('province_id'),
            'municipality_id' => $request->input('municipality_id'),
            'neighborhood_id' => $request->input('neighborhood_id'),
            'company_address' => $request->input('company_address'),
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'poc_name' => $request->input('poc_name'),
            'poc_email' => $request->input('poc_email'),
            'poc_phone' => $request->input('poc_phone'),
        ]);

        if (!($response['success'] ?? false)) {
            return redirect()->back()
                ->with('error', $response['message'] ?? 'Failed to update supplier.')
                ->withInput();
        }

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier updated successfully.');
    }

    public function destroy(int $id)
    {
        $response = $this->api->delete("/v1/suppliers/delete/{$id}");

        if (!($response['success'] ?? false)) {
            return redirect()->back()
                ->with('error', $response['message'] ?? 'Failed to delete supplier.');
        }

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier deleted successfully.');
    }
}

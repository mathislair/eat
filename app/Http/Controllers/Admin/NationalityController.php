<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreNationalityRequest;
use App\Http\Requests\Admin\UpdateNationalityRequest;
use App\Models\Nationality;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Admin-only JSON management of nationalities. No front-end yet — these
 * endpoints exist so the catalogue can be changed outside of the seeder.
 */
class NationalityController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Nationality::query()
                ->with('groups:id,name')
                ->orderBy('name')
                ->get(['id', 'name'])
        );
    }

    public function store(StoreNationalityRequest $request): JsonResponse
    {
        $data = $request->validated();

        $nationality = Nationality::create(['name' => $data['name']]);

        if (array_key_exists('groups', $data)) {
            $nationality->groups()->sync($data['groups']);
        }

        return response()->json($nationality->load('groups:id,name'), Response::HTTP_CREATED);
    }

    public function update(UpdateNationalityRequest $request, Nationality $nationality): JsonResponse
    {
        $data = $request->validated();

        if (array_key_exists('name', $data)) {
            $nationality->update(['name' => $data['name']]);
        }

        if (array_key_exists('groups', $data)) {
            $nationality->groups()->sync($data['groups']);
        }

        return response()->json($nationality->load('groups:id,name'));
    }

    public function destroy(Nationality $nationality): Response
    {
        $nationality->delete();

        return response()->noContent();
    }
}

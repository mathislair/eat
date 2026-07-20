<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreNationalityGroupRequest;
use App\Http\Requests\Admin\UpdateNationalityGroupRequest;
use App\Models\NationalityGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Admin-only JSON management of nationality groups (Asian, Spicy, …) and the
 * nationalities they contain. No front-end yet.
 */
class NationalityGroupController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            NationalityGroup::query()
                ->with('nationalities:id,name')
                ->orderBy('name')
                ->get(['id', 'name'])
        );
    }

    public function store(StoreNationalityGroupRequest $request): JsonResponse
    {
        $data = $request->validated();

        $group = NationalityGroup::create(['name' => $data['name']]);

        if (array_key_exists('nationalities', $data)) {
            $group->nationalities()->sync($data['nationalities']);
        }

        return response()->json($group->load('nationalities:id,name'), Response::HTTP_CREATED);
    }

    public function update(UpdateNationalityGroupRequest $request, NationalityGroup $nationalityGroup): JsonResponse
    {
        $data = $request->validated();

        if (array_key_exists('name', $data)) {
            $nationalityGroup->update(['name' => $data['name']]);
        }

        if (array_key_exists('nationalities', $data)) {
            $nationalityGroup->nationalities()->sync($data['nationalities']);
        }

        return response()->json($nationalityGroup->load('nationalities:id,name'));
    }

    public function destroy(NationalityGroup $nationalityGroup): Response
    {
        $nationalityGroup->delete();

        return response()->noContent();
    }
}

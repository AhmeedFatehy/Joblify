<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Skill;
use App\Http\Resources\SkillResource;
use App\Http\Requests\StoreSkillRequest;
use App\Http\Requests\UpdateSkillRequest;

class SkillController extends BaseApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            return $this->success(SkillResource::collection(Skill::all()), 'Skills retrieved successfully');
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSkillRequest $request)
    {
        try {
            $this->authorize('create', Skill::class);

            $validatedData = $request->validated();
            $skill = Skill::create($validatedData);

            return $this->success(new SkillResource($skill), 'Skill saved successfully');
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Skill $skill)
    {
        return $this->success(
            new SkillResource($skill),
            'Skill retrieved successfully'
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSkillRequest $request, Skill $skill)
    {
        try {
            $this->authorize('update', $skill);

            $validatedData = $request->validated();
            $skill->update($validatedData);

            return $this->success(new SkillResource($skill), 'Skill updated successfully');
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Skill $skill)
    {
        try {
            $this->authorize('delete', $skill);

            $skill->delete();

            return $this->success(null, 'Skill deleted successfully');
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Suggest skills based on search query.
     */
    public function suggest(Request $request)
    {
        $search = $request->query('search');
        $skills = Skill::where('name', 'like', "%{$search}%")
            ->limit(10) // Limit results for performance
            ->get();

        return $this->success(SkillResource::collection($skills), 'Skills suggested successfully');
    }
}

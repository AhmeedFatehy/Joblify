<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'requirements' => $this->requirements,
            'benefits' => $this->benefits,
            'salary_min' => $this->salary_min,
            'salary_max' => $this->salary_max,
            'location' => $this->location,
            'work_type' => $this->work_type,
            'experience_level' => $this->experience_level,
            'deadline' => $this->deadline,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'categories' => CategoryResource::collection($this->categories),
            'skills' => SkillResource::collection($this->skills),
            'company_id' => $this->company_id,
            'company' => CompanyResource::make($this->company),
        ];
    }
}

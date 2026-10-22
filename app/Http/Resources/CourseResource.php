<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'sks' => $this->sks,
            'status' => $this->status,
            'lecturer' => $this->whenLoaded('lecturer', fn () => new UserResource($this->lecturer)),
            'counts' => [
                'materials' => $this->whenCounted('materials'),
                'assignments' => $this->whenCounted('assignments'),
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

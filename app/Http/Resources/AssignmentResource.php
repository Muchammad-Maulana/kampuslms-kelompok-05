<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'title' => $this->title,
            'instructions' => $this->instructions,
            'due_at' => $this->due_at?->toIso8601String(),
            'max_score' => $this->max_score,
            'allow_late' => $this->allow_late,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', fn () => new UserResource($this->creator)),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

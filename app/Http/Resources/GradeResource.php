<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GradeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'submission_id' => $this->submission_id,
            'score' => $this->score,
            'feedback' => $this->feedback,
            'graded_by' => $this->graded_by,
            'graded_at' => $this->graded_at?->toIso8601String(),
        ];
    }
}

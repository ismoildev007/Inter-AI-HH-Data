<?php

namespace Modules\Resumes\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ResumeResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'description' => $this->description,
            'parsed_text' => $this->parsed_text,
            'is_primary'  => $this->is_primary,
            'file_path'   => $this->file_path,
            'file_url'    => $this->file_path 
                                ? Storage::disk('spaces')->url($this->file_path) 
                                : null,
            'skills'      => optional($this->analysis)->skills,
            'strengths'   => optional($this->analysis)->strengths,
            'weaknesses'  => optional($this->analysis)->weaknesses,
            'keywords'    => optional($this->analysis)->keywords,
            'created_at'  => $this->created_at,
        ];
    }
}

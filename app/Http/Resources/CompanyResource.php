<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name_of_company' => $this->name_of_company,
            'years_of_experience' => $this->founding_date
                ? $this->founding_date->diffInYears(now())
                : null,
            'image' =>  $this->image ? asset('storage/' . $this->image) : null,
            'description' => $this->description,
            'number_of_trips' => $this->number_of_trips,
            'rating' => $this->rating,
        ];
    }
}

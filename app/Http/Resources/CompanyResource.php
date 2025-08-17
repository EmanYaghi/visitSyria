<?php

namespace App\Http\Resources;

use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        $companyId=$this->id;
        $profile = $this->adminProfile;
        return [
            'id' => $companyId,
            'name_of_company' => $profile->name_of_company,
            'years_of_experience' => $profile->founding_date
                ? (int)\Carbon\Carbon::parse($profile->founding_date)->diffInYears(now())
                : null,
            'image' =>  $profile->image ? asset('storage/' . $profile->image) : null,
            'description' => $profile->description,
            'number_of_trips' => $profile->number_of_trips,
            'rating' =>Rating::whereHas('trip', function($query) use ($companyId) {
                        $query->where('user_id', $companyId);
                    })
                    ->whereNotNull('trip_id')
                    ->avg('rating_value'),
        ];
    }
}

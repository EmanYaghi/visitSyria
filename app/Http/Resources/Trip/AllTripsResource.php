<?php

namespace App\Http\Resources\Trip;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class AllTripsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $companyId=$this->user->id;
        $user=Auth::user();
        if(!$user||$user->hasRole('client'))
        {
            $tripImage=Media::where('trip_id',$this->id)->first();
            return [
                'id' => $this->id,
                'name' => $this->name,
                'description' => $this->description,
                'season' => $this->season,
                'start_date' => $this->start_date,
                'duration' => $this->duration,
                'remaining_tickets'=>($this->tickets)-($this->reserved_tickets),
                'discount' => $this->discount,
                'tags' => $this->tags->map(function ($tag) {
                    return optional($tag->tagName)->body;
                })->filter()->values()->all(),
                'image' =>$tripImage? asset('storage/' . $tripImage->url) : null,
                'company' => [
                    'id'=>$companyId,
                    'name' => $this->user->adminProfile->name_of_company,
                    'image' => $this->user->adminProfile->image,
                ],
                'is_saved' => $user
                    ? $this->saves()->where('user_id', $user->id)->exists()
                    : false,
            ];
        }
        else if($user->hasRole('super_admin'))
        {
            return [
                'id' => $this->id,
                'name' => $this->name,
                'start_date' => $this->start_date,
                'duration' => $this->duration,
                'company' => [
                    'id'=>$companyId,
                    'name' => $this->user->adminProfile->name_of_company,
                    'image' => $this->user->adminProfile->image,
                ],
                'status'=>$this->status,
                'tickets'=>$this->tickets,
                'price'=>$this->new_price?$this->new_price:$this->price
            ];
        }
        else if($user->hasRole('admin'))
        {
            return [
                'id' => $this->id,
                'name' => $this->name,
                'start_date' => $this->start_date,
                'duration' => $this->duration,
                'status'=>$this->status,
                'tickets'=>$this->tickets,
                'price'=>$this->new_price?$this->new_price:$this->price
            ];
        }
        else
           return[];
    }
}

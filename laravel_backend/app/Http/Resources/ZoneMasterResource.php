<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class ZoneMasterResource extends JsonResource
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
            'name' => $this->zone_name,
            'status' => ucwords($this->status),
            'countries' => $this->mapCountries->pluck('name')->toArray(),
            'created_at' => Carbon::parse($this->created_at)->format('d-m-Y'),
        ];
    }
}

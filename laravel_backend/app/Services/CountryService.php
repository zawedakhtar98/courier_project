<?php

namespace App\Services;

use App\Models\Country;

class CountryService
{

    public function create($data)
    {
        return Country::create($data);
    }

    public function update($data, $id)
    {
        $country = Country::find($id);
        $country->update($data);
        return $country;
    }

    public function delete($id)
    {
        $country = Country::find($id);
        $country->delete();
        return $country;
    }

    public function findAll()
    {
        return Country::all();
    }

    public function findById($id)
    {
        return Country::find($id);
    }
}

<?php

namespace App\Repositories;

use App\Models\Country;
use App\Repositories\Interface\CountryRepositoryInterface;

class CountryRepository implements CountryRepositoryInterface
{
    public function create(array $data)
    {
        return Country::create($data);
    }

    public function update(array $data, int $id)
    {
        $country = Country::find($id);
        if ($country) {
            $country->update($data);
        }
        return $country;
    }

    public function delete(int $id)
    {
        $country = Country::find($id);
        if ($country) {
            $country->delete();
        }
        return $country;
    }

    public function findAll()
    {
        return Country::all();
    }

    public function findById(int $id)
    {
        return Country::find($id);
    }
}

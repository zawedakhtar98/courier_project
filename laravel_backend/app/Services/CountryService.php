<?php

namespace App\Services;

use App\Repositories\Interface\CountryRepositoryInterface;

class CountryService
{
    public function __construct(protected CountryRepositoryInterface $countryRepository) {}

    public function create(array $data)
    {
        return $this->countryRepository->create($data);
    }

    public function update(array $data, int $id)
    {
        return $this->countryRepository->update($data, $id);
    }

    public function delete(int $id)
    {
        return $this->countryRepository->delete($id);
    }

    public function findAll()
    {
        return $this->countryRepository->findAll();
    }

    public function findById(int $id)
    {
        return $this->countryRepository->findById($id);
    }
}

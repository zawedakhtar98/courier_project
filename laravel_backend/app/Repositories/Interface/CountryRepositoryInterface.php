<?php

namespace App\Repositories\Interface;

interface CountryRepositoryInterface
{
    public function create(array $data);
    public function update(array $data, int $id);
    public function delete(int $id);
    public function findAll();
    public function findById(int $id);
}

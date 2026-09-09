<?php

namespace App\Repositories;

use App\Models\ServicePartner;
use App\Repositories\Interface\ServicePartnerRepositoryInterface;
use GuzzleHttp\Promise\Create;

class ServicePartnerRepository implements ServicePartnerRepositoryInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function create(array $data)
    {
        return ServicePartner::create($data);
    }
    public function update(array $data, int $id)
    {
        return ServicePartner::where('id', $id)->update($data);
    }
    public function delete(int $id)
    {
        return ServicePartner::where('id', $id)->delete();
    }
    public function findByName(string $name)
    {
        return ServicePartner::where('name', 'like', $name)->get();
    }
    public function findByStatus(string $status)
    {
        return ServicePartner::where('status', $status)->get();
    }

    public function getAllServicePartner(int $perPage)
    {
        return ServicePartner::orderBy('id', 'desc')->paginate($perPage);
    }
}

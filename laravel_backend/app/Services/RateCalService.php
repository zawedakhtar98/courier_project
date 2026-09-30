<?php

namespace App\Services;

use App\Repositories\Interface\RateCalRepositoryInterface;

class RateCalService
{
    /**
     * Create a new class instance.
     */
    public function __construct(protected RateCalRepositoryInterface $rateCalRepository) {}

    public function calculateRate(array $condition)
    {
        return $this->rateCalRepository->calculateRate($condition);
    }
}

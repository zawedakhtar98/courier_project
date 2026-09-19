<?php

namespace App\Services;

use App\Repositories\RateCalRepository;

class RateCalService
{
    /**
     * Create a new class instance.
     */
    public function __construct(protected RateCalRepository $rateCalRepository) {}

    public function calculateRate(array $condition)
    {
        return $this->rateCalRepository->calculateRate($condition);
    }
}

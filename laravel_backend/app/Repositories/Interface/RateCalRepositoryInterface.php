<?php

namespace App\Repositories\Interface;

interface RateCalRepositoryInterface
{
    public function calculateRate(array $condition);
}

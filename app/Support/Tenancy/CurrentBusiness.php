<?php

namespace App\Support\Tenancy;

use App\Models\Business;

class CurrentBusiness
{
    protected ?Business $business = null;

    public function get(): ?Business
    {
        return $this->business;
    }

    public function id(): ?int
    {
        return $this->business?->id;
    }

    public function set(Business $business): void
    {
        $this->business = $business;
        session(['business_id' => $business->id]);
    }

    public function setWithoutSession(Business $business): void
    {
        $this->business = $business;
    }

    public function clear(): void
    {
        $this->business = null;
        session()->forget('business_id');
    }

    public function isSet(): bool
    {
        return $this->business !== null;
    }
}

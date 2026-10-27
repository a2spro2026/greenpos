<?php

namespace App\Services;

use App\Models\Customer;

class LoyaltyService
{
    /** 1 point per currency unit of completed sale (floor). */
    public function earnFromSale(Customer $customer, float $totalTtc): Customer
    {
        $points = (int) floor(max(0, $totalTtc));
        if ($points <= 0) {
            return $customer;
        }

        $customer->loyalty_points = (int) $customer->loyalty_points + $points;
        $customer->loyalty_tier = $this->tierFor((int) $customer->loyalty_points);
        $customer->save();

        return $customer;
    }

    public function redeem(Customer $customer, int $points): Customer
    {
        $points = max(0, $points);
        if ($points > (int) $customer->loyalty_points) {
            $points = (int) $customer->loyalty_points;
        }

        $customer->loyalty_points = (int) $customer->loyalty_points - $points;
        $customer->loyalty_tier = $this->tierFor((int) $customer->loyalty_points);
        $customer->save();

        return $customer;
    }

    public function adjust(Customer $customer, int $delta): Customer
    {
        $customer->loyalty_points = max(0, (int) $customer->loyalty_points + $delta);
        $customer->loyalty_tier = $this->tierFor((int) $customer->loyalty_points);
        $customer->save();

        return $customer;
    }

    public function tierFor(int $points): string
    {
        return match (true) {
            $points >= 5000 => 'platinum',
            $points >= 2000 => 'gold',
            $points >= 500 => 'silver',
            default => 'standard',
        };
    }
}

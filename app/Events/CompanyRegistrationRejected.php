<?php

namespace App\Events;

use App\Models\CompanyRegistrationRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompanyRegistrationRejected
{
    use Dispatchable, SerializesModels;

    public function __construct(public CompanyRegistrationRequest $request)
    {
    }
}

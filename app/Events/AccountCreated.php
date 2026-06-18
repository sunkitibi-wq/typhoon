<?php

namespace App\Events;

use App\Models\Banking\Account;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AccountCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Account $account,
    ) {}
}

<?php

namespace App\Actions\Guest;

use App\Actions\BaseAction;
use App\Models\Guest;

class SearchGuestByPhoneAction extends BaseAction
{
    /**
     * Search for a guest by phone number.
     *
     * @param string $phone
     * @return Guest|null
     */
    public function execute(string $phone): ?Guest
    {
        return Guest::byPhone($phone)->notBlacklisted()->first();
    }
}
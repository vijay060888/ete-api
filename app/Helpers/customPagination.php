<?php

namespace App\Helpers;

use Illuminate\Pagination\LengthAwarePaginator;

class CustomPagination extends LengthAwarePaginator
{
    public function toArray()
    {
        return [
            'current_page' => $this->currentPage(),
            'partyYouHaveAdminAccess' => $this->items->toArray(),
            'per_page' => $this->perPage(),
            'total' => $this->total(),
        ];
    }
}

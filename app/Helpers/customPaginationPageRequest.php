<?php

namespace App\Helpers;

use Illuminate\Pagination\LengthAwarePaginator;

class CustomPaginationPageRequest extends LengthAwarePaginator
{
    public function toArray()
    {
        return [
            'current_page' => $this->currentPage(),
            'yourPageRequest' => $this->items->toArray(),
            'per_page' => $this->perPage(),
            'total' => $this->total(),
        ];
    }
}

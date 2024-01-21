<?php

namespace App\Http\Controllers\Services\AePS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FlowController extends Controller
{
    /**
     * -----------------------------------------
     * All AePS requests will be processed here
     * -----------------------------------------
     * Step 1: Middleware checks
     * Step 2: Optimistic/Pessimistic Locking for
     *         all monetary requests
     * Step 3: Validate all requests
     * Step 4: API calls
     * Step 5: Commit or rollback transactions (depends on response)
     */

    public function authentication(): bool
    {
        
    }
}

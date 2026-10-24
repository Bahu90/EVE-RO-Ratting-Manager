<?php

namespace RattingManager\Http\Controllers;

use Illuminate\Http\Request;
use RattingManager\Services\Character\AffiliationResolutionService;
use Seat\Web\Http\Controllers\Controller;

/**
 * Lets a page that showed characters as in progress find out when the
 * background lookup has them. Reads the plugin's table only.
 */
class CharacterLookupController extends Controller
{
    public function pending(Request $request, AffiliationResolutionService $resolver)
    {
        $ids = array_slice(explode(',', (string) $request->query('ids', '')), 0, 500);

        return response()->json(['pending' => $resolver->stillPending($ids)]);
    }
}

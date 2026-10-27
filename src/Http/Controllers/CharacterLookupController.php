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
        // Only characters a page showed this user as in progress, so the
        // endpoint can't be used to ask about anyone else.
        $shown = array_map('intval', (array) $request->session()->get(\RattingManager\Services\Character\Players::SHOWN_PENDING, []));
        $asked = array_map('intval', array_slice(explode(',', (string) $request->query('ids', '')), 0, 500));
        $ids = array_values(array_intersect($asked, $shown));

        return response()->json(['pending' => $ids === [] ? [] : $resolver->stillPending($ids)]);
    }
}

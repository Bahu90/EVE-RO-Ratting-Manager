<?php

namespace RattingManager\Http\Controllers;

use RattingManager\Services\Configuration\SettingsRegistry;
use RattingManager\Support\Access;
use Seat\Web\Http\Controllers\Controller;

class HelpController extends Controller
{
    public function index()
    {
        return view('ratting-manager::help.index', [
            'isDirector' => Access::isDirector(),
            'isAdmin' => Access::isAdmin(),
            'sessionGap' => SettingsRegistry::default('data.session_gap_minutes'),
        ]);
    }
}

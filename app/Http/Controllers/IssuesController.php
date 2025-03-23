<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Software bug reporting.
 */
class IssuesController extends Controller
{
    /**
     * Show the bug reporting page.
     */
    public function create(): View
    {
        return view('issues');
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ForexJournalController extends Controller
{
    /**
     * Forex Journal dashboard.
     */
    public function dashboard(): Response
    {
        return Inertia::render('Forex/Dashboard');
    }

    /**
     * Placeholder for the journal listing page (future).
     */
    public function journal(): Response
    {
        return Inertia::render('Forex/Dashboard');
    }

    /**
     * Placeholder for the analytics page (future).
     */
    public function analytics(): Response
    {
        return Inertia::render('Forex/Dashboard');
    }

    /**
     * Placeholder for the settings page (future).
     */
    public function settings(): Response
    {
        return Inertia::render('Forex/Dashboard');
    }
}

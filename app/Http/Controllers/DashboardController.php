<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the teacher dashboard.
     */
    public function index(): View
    {
        return view('dashboard', [
            'user' => Auth::user(),
        ]);
    }
}

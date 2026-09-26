<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the teacher dashboard with gradebooks overview.
     */
    public function index(): View
    {
        $user = Auth::user();

        $recentGradebooks = $user->gradebooks()
            ->with(['classroom', 'subject', 'academicYear'])
            ->latest()
            ->take(6)
            ->get();

        $totalGradebooks = $user->gradebooks()->count();

        return view('dashboard', compact('user', 'recentGradebooks', 'totalGradebooks'));
    }
}

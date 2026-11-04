<?php

namespace App\Http\Controllers;

use App\Models\ArGuide;
use Illuminate\View\View;

class ArGuidePublicController extends Controller
{
    public function index(): View
    {
        $guides = ArGuide::query()
            ->where('is_active', true)
            ->latest()
            ->get();

        return view('guides.index', compact('guides'));
    }

    public function show(string $slug): View
    {
        $guide = ArGuide::query()
            ->where('is_active', true)
            ->where('slug', $slug)
            ->with('steps')
            ->firstOrFail();

        return view('guides.show', compact('guide'));
    }
}
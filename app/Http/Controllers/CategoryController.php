<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Return all available categories (default + custom approved ones).
     */
    public function index()
    {
        $categories = Category::orderBy('is_default', 'desc')
            ->orderBy('label', 'asc')
            ->get(['slug', 'label', 'is_default']);

        return response()->json($categories);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class LegalController extends Controller
{
    /**
     * Display the About Us page.
     */
    public function aboutUs()
    {
        return Inertia::render('Legal/AboutUs');
    }

    /**
     * Display the Privacy Policy page.
     */
    public function privacyPolicy()
    {
        return Inertia::render('Legal/PrivacyPolicy');
    }

    /**
     * Display the Terms of Service page.
     */
    public function termsOfService()
    {
        return Inertia::render('Legal/TermsOfService');
    }
}

<?php

namespace App\Http\Controllers;

class LegalPageController extends Controller
{
    public function terms()
    {
        return view('legal.terms');
    }

    public function privacy()
    {
        return view('legal.privacy');
    }

    public function legalNotice()
    {
        return view('legal.legal-notice');
    }
}

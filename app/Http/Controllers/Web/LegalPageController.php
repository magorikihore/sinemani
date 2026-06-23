<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\LegalContent;

class LegalPageController extends Controller
{
    public function privacy()
    {
        return view('legal.page', [
            'title' => 'Privacy Policy',
            'content' => LegalContent::privacyPolicy(),
            'otherLink' => ['url' => route('terms'), 'label' => 'Terms of Service'],
        ]);
    }

    public function terms()
    {
        return view('legal.page', [
            'title' => 'Terms of Service',
            'content' => LegalContent::termsOfService(),
            'otherLink' => ['url' => route('privacy'), 'label' => 'Privacy Policy'],
        ]);
    }
}

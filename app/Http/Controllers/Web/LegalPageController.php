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
            'footerLinks' => [
                ['url' => route('terms'), 'label' => 'Terms of Service'],
                ['url' => route('account-deletion'), 'label' => 'Account Deletion'],
                ['url' => route('data-deletion'), 'label' => 'Data Deletion'],
            ],
        ]);
    }

    public function terms()
    {
        return view('legal.page', [
            'title' => 'Terms of Service',
            'content' => LegalContent::termsOfService(),
            'footerLinks' => [
                ['url' => route('privacy'), 'label' => 'Privacy Policy'],
                ['url' => route('account-deletion'), 'label' => 'Account Deletion'],
                ['url' => route('data-deletion'), 'label' => 'Data Deletion'],
            ],
        ]);
    }

    public function accountDeletion()
    {
        return view('legal.page', [
            'title' => 'Account & Data Deletion',
            'content' => LegalContent::accountDeletion(),
            'footerLinks' => [
                ['url' => route('privacy'), 'label' => 'Privacy Policy'],
                ['url' => route('terms'), 'label' => 'Terms of Service'],
                ['url' => route('data-deletion'), 'label' => 'Data Deletion'],
            ],
        ]);
    }

    public function dataDeletion()
    {
        return view('legal.page', [
            'title' => 'Data Deletion Request',
            'content' => LegalContent::dataDeletion(),
            'footerLinks' => [
                ['url' => route('privacy'), 'label' => 'Privacy Policy'],
                ['url' => route('terms'), 'label' => 'Terms of Service'],
                ['url' => route('account-deletion'), 'label' => 'Account Deletion'],
            ],
        ]);
    }
}

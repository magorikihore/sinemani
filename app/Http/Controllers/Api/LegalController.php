<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\JsonResponse;

class LegalController extends Controller
{
    public function urls(): JsonResponse
    {
        return $this->success([
            'privacy_url' => AppSetting::getValue('privacy_url', url('/privacy')),
            'terms_url' => AppSetting::getValue('terms_url', url('/terms')),
            'account_deletion_url' => AppSetting::getValue('account_deletion_url', url('/delete-account')),
            'data_deletion_url' => AppSetting::getValue('data_deletion_url', url('/delete-data')),
        ]);
    }
}

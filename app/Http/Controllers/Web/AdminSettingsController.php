<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;

class AdminSettingsController extends Controller
{
    public function index()
    {
        $settings = AppSetting::orderBy('group')->orderBy('key')->get()->groupBy('group');

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
        ]);

        foreach ($request->settings as $key => $value) {
            AppSetting::where('key', $key)->update(['value' => $value ?? '']);
        }

        return back()->with('success', 'Settings updated successfully.');
    }

    public function store(Request $request)
    {
        $request->validate([
            'key' => 'required|string|max:255|unique:app_settings,key',
            'value' => 'nullable|string',
            'group' => 'nullable|string|max:100',
        ]);

        AppSetting::create([
            'key' => $request->key,
            'value' => $request->value ?? '',
            'group' => $request->group ?? 'general',
        ]);

        return back()->with('success', "Setting '{$request->key}' created.");
    }

    public function paymentGateway()
    {
        $keys = [
            'payment_gateway_url',
            'payment_gateway_api_key',
            'payment_gateway_api_secret',
            'payment_callback_url',
            'payment_gateway_timeout',
        ];

        $settings = AppSetting::whereIn('key', $keys)->get()->keyBy('key');

        // If settings don't exist yet, create defaults from .env
        $defaults = [
            'payment_gateway_url' => config('services.payin.base_url', env('PAYMENT_GATEWAY_URL', '')),
            'payment_gateway_api_key' => env('PAYMENT_GATEWAY_API_KEY', ''),
            'payment_gateway_api_secret' => env('PAYMENT_GATEWAY_API_SECRET', ''),
            'payment_callback_url' => env('PAYMENT_CALLBACK_URL', ''),
            'payment_gateway_timeout' => env('PAYMENT_GATEWAY_TIMEOUT', '30'),
        ];

        $config = [];
        foreach ($keys as $key) {
            $config[$key] = $settings->get($key)?->value ?? $defaults[$key] ?? '';
        }

        return view('admin.settings.payment', compact('config'));
    }

    public function updatePaymentGateway(Request $request)
    {
        $data = $request->validate([
            'payment_gateway_url' => 'nullable|url|max:500',
            'payment_gateway_api_key' => 'nullable|string|max:500',
            'payment_gateway_api_secret' => 'nullable|string|max:500',
            'payment_callback_url' => 'nullable|url|max:500',
            'payment_gateway_timeout' => 'nullable|integer|min:5|max:120',
        ]);

        foreach ($data as $key => $value) {
            if ($value !== null) {
                AppSetting::setValue($key, (string) $value, 'payment');
            }
        }

        return back()->with('success', 'Payment gateway settings updated.');
    }
}

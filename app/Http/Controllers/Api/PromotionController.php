<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use App\Services\PushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PromotionController extends Controller
{
    /**
     * Get active popup promotions for the app.
     */
    public function activePopup(Request $request): JsonResponse
    {
        $promotion = Promotion::active()
            ->popups()
            ->orderByDesc('priority')
            ->first();

        if (!$promotion) {
            return $this->success(null);
        }

        if ($promotion->image) {
            $promotion->image = asset('storage/' . $promotion->image);
        }

        return $this->success([
            'id' => $promotion->id,
            'title' => $promotion->title,
            'description' => $promotion->description,
            'image' => $promotion->image,
            'action_type' => $promotion->action_type,
            'action_value' => $promotion->action_value,
            'button_text' => $promotion->button_text,
            'show_once_per_day' => $promotion->show_once_per_day,
        ]);
    }

    // ── Admin Methods ──────────────────────────────────────────────

    public function index(): JsonResponse
    {
        $promotions = Promotion::orderByDesc('priority')->get();
        return $this->success($promotions);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'max:5120'],
            'action_type' => ['required', 'string', 'in:subscription,coin_store,drama,url,daily_reward'],
            'action_value' => ['nullable', 'string'],
            'button_text' => ['sometimes', 'string', 'max:100'],
            'position' => ['sometimes', 'string', 'in:popup,banner'],
            'is_active' => ['sometimes', 'boolean'],
            'show_once_per_day' => ['sometimes', 'boolean'],
            'priority' => ['sometimes', 'integer'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('promotions', 'public');
        }

        $promotion = Promotion::create($data);

        return $this->created($promotion);
    }

    public function update(Request $request, Promotion $promotion): JsonResponse
    {
        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'image' => ['sometimes', 'image', 'max:5120'],
            'action_type' => ['sometimes', 'string', 'in:subscription,coin_store,drama,url,daily_reward'],
            'action_value' => ['sometimes', 'nullable', 'string'],
            'button_text' => ['sometimes', 'string', 'max:100'],
            'position' => ['sometimes', 'string', 'in:popup,banner'],
            'is_active' => ['sometimes', 'boolean'],
            'show_once_per_day' => ['sometimes', 'boolean'],
            'priority' => ['sometimes', 'integer'],
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date'],
        ]);

        if ($request->hasFile('image')) {
            if ($promotion->image) {
                Storage::disk('public')->delete($promotion->image);
            }
            $data['image'] = $request->file('image')->store('promotions', 'public');
        }

        $promotion->update($data);

        return $this->success($promotion->fresh());
    }

    public function destroy(Promotion $promotion): JsonResponse
    {
        if ($promotion->image) {
            Storage::disk('public')->delete($promotion->image);
        }
        $promotion->delete();

        return $this->noContent('Promotion deleted');
    }

    /**
     * Send a push notification to all users.
     */
    public function sendPush(Request $request): JsonResponse
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:500'],
            'action_type' => ['nullable', 'string'],
            'action_value' => ['nullable', 'string'],
        ]);

        $pushService = app(PushNotificationService::class);

        $sent = $pushService->sendPromotion(
            $request->title,
            $request->body,
            array_filter([
                'type' => $request->action_type ?? 'promo',
                'action_value' => $request->action_value,
            ])
        );

        return $this->success(['sent_count' => $sent], "Push notification sent to {$sent} users");
    }
}

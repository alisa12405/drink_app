<?php

namespace App\Services;

use App\Enums\IceLevel;
use App\Jobs\UpdateUserProfileEmbeddingJob;
use App\Models\OrderItem;
use App\Models\Rating;
use App\Models\User;
use App\Models\UserPreference;

/**
 * Ghép `profile_text` từ sở thích khai báo (explicit — UC-02) + lịch sử mua/đánh giá
 * gần nhất (implicit — FR1) và chỉ dispatch job tính lại embedding khi nội dung
 * thực sự đổi (FR6, database/tables/user_preferences.md).
 */
class UserProfileTextService
{
    public function sync(UserPreference $preference, User $user): UserPreference
    {
        $profileText = $this->compose($preference, $user);

        if ($preference->profile_text !== $profileText) {
            $preference->update(['profile_text' => $profileText]);
            UpdateUserProfileEmbeddingJob::dispatch($preference);
        }

        return $preference;
    }

    private function compose(UserPreference $preference, User $user): string
    {
        $parts = [];

        if (! empty($preference->taste_tags)) {
            $parts[] = 'Sở thích: '.implode(', ', $preference->taste_tags);
        }

        $sugarLabel = $preference->sugar_level_default->value.'% đường';
        $iceLabel = match ($preference->ice_level_default) {
            IceLevel::NoIce => 'không đá',
            IceLevel::LessIce => 'ít đá',
            IceLevel::NormalIce => 'đá bình thường',
            IceLevel::ExtraIce => 'nhiều đá',
        };
        $parts[] = "Mặc định: {$sugarLabel}, {$iceLabel}";

        if (! empty($preference->allergy_notes)) {
            $parts[] = "Dị ứng: {$preference->allergy_notes}";
        }

        $recentDrinks = OrderItem::query()
            ->whereHas('order', fn ($query) => $query->where('user_id', $user->id))
            ->with('drink:id,name')
            ->latest()
            ->limit(5)
            ->get()
            ->pluck('drink.name')
            ->filter()
            ->unique();

        if ($recentDrinks->isNotEmpty()) {
            $parts[] = 'Từng đặt: '.$recentDrinks->implode(', ');
        }

        $topRatings = Rating::query()
            ->where('user_id', $user->id)
            ->where('rating', '>=', 4)
            ->with('drink:id,name')
            ->latest()
            ->limit(3)
            ->get()
            ->filter(fn (Rating $rating) => $rating->drink !== null);

        if ($topRatings->isNotEmpty()) {
            $summary = $topRatings
                ->map(fn (Rating $rating) => "{$rating->drink->name} ({$rating->rating} sao)")
                ->implode(', ');
            $parts[] = "Đánh giá cao: {$summary}";
        }

        return implode('. ', $parts);
    }
}

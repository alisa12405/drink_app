<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Preference\UpdatePreferenceRequest;
use App\Http\Resources\PreferenceResource;
use App\Models\UserPreference;
use App\Services\UserProfileTextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PreferenceController extends Controller
{
    public function __construct(private readonly UserProfileTextService $profileTextService) {}

    public function show(Request $request): PreferenceResource
    {
        $preference = $request->user()->preference ?? new UserPreference;

        return new PreferenceResource($preference);
    }

    public function update(UpdatePreferenceRequest $request): JsonResponse
    {
        $user = $request->user();

        // updateOrCreate() chỉ trả về các thuộc tính đã truyền vào; refresh lại
        // để lấy giá trị mặc định DB áp dụng khi tạo mới (sugar_level_default/ice_level_default).
        $preference = $user->preference()->updateOrCreate([], $request->validated())->fresh();

        $preference = $this->profileTextService->sync($preference, $user);

        return (new PreferenceResource($preference))
            ->response()
            ->setStatusCode(200);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexUsersRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminUserController extends Controller
{
    public function index(IndexUsersRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();

        $users = User::query()
            ->withCount('orders')
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where(
                fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"),
            ))
            ->when($filters['role'] ?? null, fn ($query, string $role) => $query->where('role', $role))
            ->when(
                $filters['status'] ?? null,
                fn ($query, string $status) => $query->where('is_active', $status === 'active'),
            )
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15);

        return UserResource::collection($users);
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $data = $request->validated();
        $nextRole = UserRole::from($data['role']);
        $nextIsActive = (bool) $data['is_active'];
        $actor = $request->user();

        if ($actor->is($user) && ($nextRole !== UserRole::Admin || ! $nextIsActive)) {
            throw ValidationException::withMessages([
                'role' => ['Không thể tự hạ quyền hoặc vô hiệu hóa tài khoản quản trị hiện tại.'],
            ]);
        }

        $updatedUser = DB::transaction(function () use ($data, $nextRole, $nextIsActive, $user): User {
            $activeAdminIds = User::query()
                ->where('role', UserRole::Admin->value)
                ->where('is_active', true)
                ->orderBy('id')
                ->lockForUpdate()
                ->pluck('id');

            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $isRemovingActiveAdmin = $lockedUser->isAdmin()
                && $lockedUser->is_active
                && ($nextRole !== UserRole::Admin || ! $nextIsActive);

            if ($isRemovingActiveAdmin && $activeAdminIds->count() <= 1) {
                throw ValidationException::withMessages([
                    'role' => ['Cần duy trì ít nhất một tài khoản quản trị đang hoạt động.'],
                ]);
            }

            $lockedUser->fill([
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => $nextRole,
                'is_active' => $nextIsActive,
            ]);
            $lockedUser->save();

            if (! $nextIsActive) {
                $lockedUser->tokens()->delete();
            }

            return $lockedUser->loadCount('orders');
        });

        return new UserResource($updatedUser);
    }
}

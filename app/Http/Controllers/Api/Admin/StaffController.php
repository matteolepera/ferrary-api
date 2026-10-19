<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class StaffController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', User::class);

        $users = User::query()
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        return UserResource::collection($users);
    }

    public function show(User $user): UserResource
    {
        Gate::authorize('view', $user);

        return new UserResource($user);
    }
}

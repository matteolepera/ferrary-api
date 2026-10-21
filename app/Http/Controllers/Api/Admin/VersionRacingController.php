<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RacingRecordRequest;
use App\Http\Resources\Admin\VersionRacingResource;
use App\Models\Version;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class VersionRacingController extends Controller
{
    public function index(Version $version): AnonymousResourceCollection
    {
        Gate::authorize('view', $version);

        return VersionRacingResource::collection(
            $version->racingRecords()->orderBy('season_start')->get(),
        );
    }

    public function store(RacingRecordRequest $request, Version $version): JsonResponse
    {
        $data = $request->validated();
        $data['notes'] = array_filter($data['notes'] ?? [], 'filled');

        $racing = $version->racingRecords()->create($data);

        return (new VersionRacingResource($racing))
            ->response()
            ->setStatusCode(201);
    }
}

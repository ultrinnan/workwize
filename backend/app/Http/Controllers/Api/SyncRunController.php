<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SyncRunResource;
use App\Models\SyncRun;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SyncRunController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return SyncRunResource::collection(
            SyncRun::query()->latest('id')->limit(20)->get()
        );
    }

    public function show(SyncRun $syncRun): SyncRunResource
    {
        return new SyncRunResource($syncRun);
    }
}

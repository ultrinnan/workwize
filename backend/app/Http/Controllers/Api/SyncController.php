<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\MdmProvider;
use App\Exceptions\MdmProviderException;
use App\Http\Controllers\Controller;
use App\Http\Resources\SyncRunResource;
use App\Services\Mdm\DeviceSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Throwable;

class SyncController extends Controller
{
    public function store(MdmProvider $provider, DeviceSyncService $service): SyncRunResource|JsonResponse
    {
        try {
            $run = $service->sync($provider);
        } catch (MdmProviderException $exception) {
            return response()->json([
                'message' => 'The MDM sync failed.',
                'error' => $exception->getMessage(),
            ], Response::HTTP_BAD_GATEWAY);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'An unexpected error occurred while syncing.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return (new SyncRunResource($run))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}

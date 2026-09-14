<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AssetResource;
use App\Models\Asset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AssetController extends Controller
{
    private const SORTABLE = [
        'device_name',
        'serial_code',
        'provider',
        'last_seen_at',
        'created_at',
        'id',
    ];

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Asset::query()->with('employee');

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('device_name', 'like', "%{$search}%")
                    ->orWhere('serial_code', 'like', "%{$search}%")
                    ->orWhere('attributes->model', 'like', "%{$search}%")
                    ->orWhereHas('employee', function ($employeeQuery) use ($search): void {
                        $employeeQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $sort = (string) $request->query('sort', 'device_name');
        $direction = strtolower((string) $request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        if (! in_array($sort, self::SORTABLE, true)) {
            $sort = 'device_name';
        }

        $query->orderBy($sort, $direction);

        return AssetResource::collection(
            $query->paginate($this->perPage($request))
        );
    }

    public function show(Asset $asset): AssetResource
    {
        return new AssetResource($asset->load('employee'));
    }

    public function destroy(Asset $asset): JsonResponse
    {
        $asset->delete();

        return response()->json(null, 204);
    }

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', '15');

        return max(1, min($perPage, 100));
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmployeeController extends Controller
{
    private const SORTABLE = [
        'name',
        'email',
        'position',
        'assets_count',
        'created_at',
        'id',
    ];

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Employee::query()->withCount('assets');

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('position', 'like', "%{$search}%");
            });
        }

        $sort = (string) $request->query('sort', 'name');
        $direction = strtolower((string) $request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        if (! in_array($sort, self::SORTABLE, true)) {
            $sort = 'name';
        }

        $query->orderBy($sort, $direction);

        $perPage = max(1, min((int) $request->query('per_page', '15'), 100));

        return EmployeeResource::collection($query->paginate($perPage));
    }

    public function show(Employee $employee): EmployeeResource
    {
        return new EmployeeResource($employee->load('assets'));
    }

    public function destroy(Employee $employee): JsonResponse
    {
        if ($employee->assets()->exists()) {
            return response()->json([
                'message' => 'This employee still has assigned assets. '
                    .'Delete those assets first before removing the employee.',
            ], 409);
        }

        $employee->delete();

        return response()->json(null, 204);
    }
}

<?php

namespace Modules\Core\Services;

use Illuminate\Support\Collection;
use Modules\Core\Models\CompanyUserRole;
use Modules\Core\Models\Role;

class SegregationOfDutiesService
{
    /**
     * The configured conflicting pairs fully present in the given permission slugs.
     *
     * @param  iterable<int, string>  $permissionSlugs
     * @return Collection<int, array{0: string, 1: string}>
     */
    public function conflictsIn(iterable $permissionSlugs): Collection
    {
        $held = collect($permissionSlugs)->flip();

        return collect(config('authorization.conflicting_permissions', []))
            ->filter(fn (array $pair) => $held->has($pair[0]) && $held->has($pair[1]))
            ->values();
    }

    /**
     * Conflicts produced by holding all the given roles at once.
     *
     * @param  iterable<int, int>  $roleIds
     * @return Collection<int, array{0: string, 1: string}>
     */
    public function conflictsForRoles(iterable $roleIds): Collection
    {
        return $this->conflictsIn(
            Role::whereIn('id', collect($roleIds))->with('permissions')->get()->flatMap->permissions->pluck('slug')
        );
    }

    /**
     * Existing violations: users whose roles in force in a company combine conflicting permissions.
     *
     * @return Collection<int, array{user_id: int, company_id: int, conflicts: Collection}>
     */
    public function existingViolations(): Collection
    {
        return CompanyUserRole::active()->get()
            ->groupBy(fn (CompanyUserRole $assignment) => $assignment->user_id.':'.$assignment->company_id)
            ->map(fn (Collection $assignments) => [
                'user_id' => $assignments->first()->user_id,
                'company_id' => $assignments->first()->company_id,
                'conflicts' => $this->conflictsForRoles($assignments->pluck('role_id')),
            ])
            ->filter(fn (array $violation) => $violation['conflicts']->isNotEmpty())
            ->values();
    }

    /**
     * @param  Collection<int, array{0: string, 1: string}>  $conflicts
     */
    public function describe(Collection $conflicts): string
    {
        return $conflicts->map(fn (array $pair) => "{$pair[0]} + {$pair[1]}")->implode('; ');
    }
}

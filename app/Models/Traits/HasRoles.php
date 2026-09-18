<?php

namespace App\Models\Traits;

use Exception;
use Spatie\Permission\Traits\HasRoles as SpatieHasRoles;

trait HasRoles
{
    use SpatieHasRoles {
        assignRole as protected traitAssignRole;
        givePermissionTo as protected traitGivePermissionTo;
    }

    /**
     * @param \Spatie\Permission\Contracts\Permission|\Spatie\Permission\Contracts\Role $roleOrPermission
     *
     * @throws \Exception
     */
    private function ensureInScope($roleOrPermission)
    {
        if (isset($this->permissionScope) && $roleOrPermission->scope != $this->permissionScope) {
            throw new Exception('Invalid role or permission given to '.__CLASS__, 422);
        }
    }

    /**
     * Assign the given role to the model.
     *
     * Scope is validated here, then the actual assignment is delegated to
     * Spatie's own implementation so this stays compatible as its internals
     * (team pivots, wildcard index, events, ...) evolve across versions.
     *
     * @param array|string|\Spatie\Permission\Contracts\Role ...$roles
     *
     * @return $this
     */
    public function assignRole(...$roles)
    {
        collect($roles)
            ->flatten()
            ->filter()
            ->each(function ($role) {
                $this->ensureInScope($this->getStoredRole($role));
            });

        return $this->traitAssignRole(...$roles);
    }

    /**
     * Grant the given permission(s) to a role.
     *
     * Scope is validated here, then the actual assignment is delegated to
     * Spatie's own implementation so this stays compatible as its internals
     * evolve across versions.
     *
     * @param string|array|\Spatie\Permission\Contracts\Permission|\Illuminate\Support\Collection $permissions
     *
     * @return $this
     */
    public function givePermissionTo(...$permissions)
    {
        collect($permissions)
            ->flatten()
            ->filter()
            ->each(function ($permission) {
                $this->ensureInScope($this->getStoredPermission($permission));
            });

        return $this->traitGivePermissionTo(...$permissions);
    }
}

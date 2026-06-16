<?php

namespace App\Services;

use App\Models\AppSubmission;
use App\Models\User;
use App\Models\UserFactoryRole;

class PermissionService
{
    /**
     * Get all permission names for a user within a specific factory context.
     */
    public function getUserPermissions(User $user, ?int $factoryId = null): array
    {
        // super_admin or parent factory IT has all permissions globally
        if ($user->hasRole('super_admin') || $user->is_parent_factory) {
            return $user->getAllPermissions()->pluck('name')->toArray();
        }

        // Factory-specific roles
        if ($factoryId) {
            $factoryRoles = UserFactoryRole::where('user_id', $user->id)
                ->where('factory_id', $factoryId)
                ->with('role.permissions')
                ->get();

            if ($factoryRoles->isNotEmpty()) {
                return $factoryRoles->flatMap(fn($fr) => $fr->role->permissions->pluck('name'))->unique()->toArray();
            }
        }

        // Fall back to global Spatie permissions
        return $user->getAllPermissions()->pluck('name')->toArray();
    }

    /**
     * Check if a user can approve a specific submission,
     * considering factory scope of the approval step.
     */
    public function canApproveSubmission(User $user, AppSubmission $submission): bool
    {
        if (! $user->hasPermissionTo('submission.approve')) {
            return false;
        }

        $currentStep = $submission->app->approvalSteps()
            ->where('step_order', $submission->current_step)
            ->first();

        if (! $currentStep) {
            return false;
        }

        // Check role match
        if (! $user->hasRole($currentStep->approverRole->name)) {
            return false;
        }

        // Check factory scope
        return match ($currentStep->scope) {
            'own_factory'    => $user->factory_id === $submission->factory_id || $user->hasRole('super_admin'),
            'parent_factory' => $user->is_parent_factory || $user->hasRole('super_admin'),
            'any_factory'    => true,
            default          => false,
        };
    }

    /**
     * Assign a role to a user within a specific factory.
     */
    public function assignRoleInFactory(User $user, int $factoryId, int $roleId): void
    {
        UserFactoryRole::firstOrCreate([
            'user_id'    => $user->id,
            'factory_id' => $factoryId,
            'role_id'    => $roleId,
        ]);
    }

    /**
     * Remove a factory-specific role assignment.
     */
    public function revokeRoleInFactory(User $user, int $factoryId, int $roleId): void
    {
        UserFactoryRole::where([
            'user_id'    => $user->id,
            'factory_id' => $factoryId,
            'role_id'    => $roleId,
        ])->delete();
    }
}

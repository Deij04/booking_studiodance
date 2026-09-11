<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return in_array($actor->role, ['admin', 'super_admin'], true);
    }

    public function create(User $actor): bool
    {
        return $this->viewAny($actor);
    }

    public function update(User $actor, User $user): bool
    {
        return $actor->role === 'super_admin'
            || ($actor->role === 'admin' && $user->role === 'customer');
    }

    public function delete(User $actor, User $user): bool
    {
        return ! $actor->is($user) && $this->update($actor, $user);
    }
}

<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Suspends or re-activates an account.
 *
 * Suspension flips is_active, records who did it and why, deletes the
 * user's stored sessions (database driver) and rotates the remember token
 * so "remember me" cookies stop working. Any session the database cannot
 * see is closed by the EnsureUserIsActive middleware on the next request.
 */
class SuspendUser
{
    public function suspend(User $user, User $by, ?string $reason = null): User
    {
        if ($user->is($by)) {
            throw ValidationException::withMessages([
                'user' => 'You cannot suspend your own account.',
            ]);
        }

        DB::transaction(function () use ($user, $by, $reason) {
            $user->forceFill([
                'is_active' => false,
                'suspended_at' => now(),
                'suspended_by' => $by->getKey(),
                'suspension_reason' => $reason,
            ]);
            $user->setRememberToken(null);
            $user->save();

            $this->endSessions($user);
        });

        return $user->refresh();
    }

    public function activate(User $user): User
    {
        $user->forceFill([
            'is_active' => true,
            'suspended_at' => null,
            'suspended_by' => null,
            'suspension_reason' => null,
        ])->save();

        return $user->refresh();
    }

    private function endSessions(User $user): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::connection(config('session.connection'))
            ->table(config('session.table', 'sessions'))
            ->where('user_id', $user->getKey())
            ->delete();
    }
}

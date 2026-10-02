<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Passwords\DatabaseTokenRepository;
use Illuminate\Auth\Passwords\TokenRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ApplicationPasswordResetService
{
    public function createToken(User $user): string
    {
        return $this->repository()->create($user);
    }

    public function isValid(User $user, string $token): bool
    {
        return $this->repository()->exists($user, $token);
    }

    public function deleteToken(User $user): void
    {
        $this->repository()->delete($user);
    }

    private function repository(): TokenRepositoryInterface
    {
        $config = config('auth.passwords.users');

        return new DatabaseTokenRepository(
            DB::connection(),
            Hash::getFacadeRoot(),
            $config['table'],
            (string) config('app.key'),
            $config['expire'] * 60,
            $config['throttle'],
        );
    }
}
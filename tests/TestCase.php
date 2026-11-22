<?php

namespace Tests;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    protected function createTenant(string $name = 'Sunny Nursery'): Tenant
    {
        return Tenant::create([
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(6),
            'timezone' => 'Africa/Cairo',
            'currency' => 'EGP',
        ]);
    }

    protected function createUser(Tenant $tenant, string $role = 'owner', ?string $email = null): User
    {
        return User::create([
            'tenant_id' => $tenant->id,
            'name' => ucfirst($role).' User',
            'email' => $email ?? $role.'-'.Str::random(6).'@example.test',
            'password' => 'password',
            'role' => $role,
        ]);
    }
}

<?php

namespace Tests;

use App\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function actingAsAdmin()
    {
        $user = new User();
        $user->id = 1;
        $user->name = 'QA Admin';
        $user->email = 'admin@seajet.test';
        $user->role = 'admin';

        $this->actingAs($user);

        return $user;
    }
}

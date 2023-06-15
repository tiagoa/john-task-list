<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected $email = 'john@galt.com';
    protected $password = 'password';

    protected function registerUser()
    {
        return $this->postJson('/api/register', ['name' => 'John', 'email' => $this->email, 'password' => $this->password]);
    }

    protected function login()
    {
        return $this->postJson('/api/login', ['email' => $this->email, 'password' => $this->password]);
    }

    protected function registerAndLogin()
    {
        $this->registerUser();
        $this->login();
    }
}

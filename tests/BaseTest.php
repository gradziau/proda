<?php

namespace GradziAu\Proda\Tests;

use GradziAu\Proda\ProdaServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench;

class BaseTest extends Testbench\TestCase
{

    use RefreshDatabase;

    protected function getPackageProviders($app)
    {
        return [
            ProdaServiceProvider::class,
        ];
    }

}



<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ExampleTest extends TestCase
{

    /**
     * A basic test example.
     *
     * @return void
     */

   

    public function testBasicTest()
    {

        $response = $this->get('/');           // the link to visit
        $response->assertStatus(200);                             // status we expect

    }

  
}

<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Session;

class LoginTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */

  
    public function testExample()
    {
        $this->assertTrue(true);
    }
    public function testExample1()
    {
      $dataArray = array();
      $dataArray = [
            "con_person_email" => "imensongo@mailinator.com",
            "password" => 123456,
            ];
      $response = $this->json('POST', '/user/login',$dataArray);
      $status = "login successfully!";
      if(isset(Session::all()["error"]))
      {
        $status = "Error in Login unit test reason validation error!";
        //print_r(Session::all()["error"]);
      }
      else if(isset(Session::all()["success"]))
      {
        $status = "login successfully!";
      }
      else if(isset(Session::all()["message"]))
      {
        $status = "Error in Login unit test reason username or email not password!";
      }
      else if($response->status()!=200)
      {
        $status = "Error in Login unit test reason error in code!";
      }

      if($status!="login successfully!")
      {
      	echo $status;
      }
    }
}
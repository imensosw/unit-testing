<?php

namespace Tests\Unit;

use App\User;
use App\UnitTest;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

class TeamTest extends TestCase
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
      
      $user = User::find(2);
      $response = $this->actingAs($user);

      $infoArray = [
        "userRegistrationNo" => $user->registration_no,
      ];

      $teamUserId = $this->addTeam($infoArray);
      
      $infoArray["teamUserId"] = $teamUserId;
      
      echo $this->updateTeam($infoArray);

      $tableRecordDeleteArray = array("users");
      $whereArray = [
            "columnValue" => $teamUserId,
      ];
      UnitTest::tableRelatedDelete($whereArray,$tableRecordDeleteArray);

    }
    public function addTeam($infoArray)
    { 
      $dataArray = array();
      $dataArray = [
            "con_person_name" => "unit test team".date("d/m/y"),
            "type" => "project_manager",
            "con_person_email" => "unittestteam@mailinator.com",
            "con_person_mobile" => 1234567890,
            "details" => "hello",
            ];
       $response = $this->json('POST','/'.$infoArray["userRegistrationNo"].'/team/add',$dataArray);

       if(isset(json_decode($response->content())->status) && json_decode($response->content())->status=="Y")
       {
          return json_decode($response->content())->user_id;
       }
       else if(isset(json_decode($response->content())->status) && json_decode($response->content())->status=="email")
       {
          die("Error in team unit test reason email already exist!");
       }
       else if(isset(json_decode($response->content())->status) && json_decode($response->content())->status=="emptye")
       {
          die("Error in team unit test reason email is required!");
       }
       else if(isset(json_decode($response->content())->status) && json_decode($response->content())->status=="type")
       {
          die("Error in team unit test reason type is required!");
       }
       else if(isset(json_decode($response->content())->status) && json_decode($response->content())->status=="N")
       {
          die("Error in team unit test reason error in insertion!");
       }
       else if($response->status()!=200)
       {
          die("Error in team unit test reason error in code!");
       }
    }
    public function updateTeam($infoArray)
    {
      $dataArray = [
            "con_person_name" => "unit test team1".date("d/m/y"),
            "type" => "project_member",
            "con_person_email" => "unittestteam@mailinator.com",
            "con_person_mobile" => 9907629428,
            "details" => "hello1",
            "user_id" => $infoArray["teamUserId"],
            ];
      $response = $this->json('POST','/'.$infoArray["userRegistrationNo"].'/team/edit',$dataArray);
      $status = "";
      if(isset(json_decode($response->content())->status) && json_decode($response->content())->status=="Y")
      {
        $status="update team successfully!";//return true;
      }
      else if(isset(json_decode($response->content())->status) && json_decode($response->content())->status=="email")
      {
        $status="Error in team unit test update reason email already exist!";
      }
      else if(isset(json_decode($response->content())->status) && json_decode($response->content())->status=="type")
      {
        $status="Error in team unit test update reason type is required!";
      }
      else if(isset(json_decode($response->content())->status) && json_decode($response->content())->status=="N")
      {
        $status="Error in team unit test update reason error in insertion!";
      }
      else if($response->status()!=200)
      {
        $status="Error in team unit test update reason error in code!";
      }
      return $status;
    }
}
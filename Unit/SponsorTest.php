<?php

namespace Tests\Unit;
use App\User;
use App\UnitTest;
use App\Organization_sectors;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Session;

class SponsorTest extends TestCase
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

       $sponsorInfo = $this->sponsorCreate($infoArray);
       
       $infoArray = [
        "userRegistrationNo" => $user->registration_no,
        "userId" => $sponsorInfo["userId"],
        "verifyCode" => $sponsorInfo["verifyCode"],
       ];

       $whereArray = [
       "columnValue" => $sponsorInfo["userId"],
       ];

       $verifyUser = $this->verifyUser($infoArray);
       if($verifyUser!="verify")
       {
         echo "Error in sponsor unit test reason user not verify!";
         $tableRecordDeleteArray = array("sponsor_invite_by_multiple_ngo","users");
         UnitTest::tableRelatedDelete($whereArray,$tableRecordDeleteArray);
       }

       echo $userRegistration = $this->userRegistration($infoArray);
       $tableRecordDeleteArray = array("sponsor_invite_by_multiple_ngo","ngo","user_sector_work","users");
       UnitTest::tableRelatedDelete($whereArray,$tableRecordDeleteArray);
    }
    public function sponsorCreate($infoArray)
    {
      $dataArray = array();
      $dataArray = [
            "con_person_name" => "unit test sponsor",
            "con_person_email" => "unittestsponsor1@mailinator.com",
            ];

       $response = $this->json('POST','/'.$infoArray["userRegistrationNo"].'/ngo/invite',$dataArray);
       if(isset(json_decode($response->content())->status) && json_decode($response->content())->status=="Y")
       {
          if(isset(json_decode($response->content())->user_id) && json_decode($response->content())->user_id>0)
          {
            return array("userId" => json_decode($response->content())->user_id,"verifyCode" => json_decode($response->content())->verify_code);
          }
          else if(isset(json_decode($response->content())->allready) && json_decode($response->content())->allready=="allready")
          {
            if(isset(json_decode($response->content())->Sponsor_invite_by_multiple_ngo_id) && json_decode($response->content())->Sponsor_invite_by_multiple_ngo_id>0)
            {
              $whereArray = [
                "columnValue" => json_decode($response->content())->Sponsor_invite_by_multiple_ngo_id,
                "columnTableName"   => "sponsor_invite_by_multiple_ngo",
              ];
              $tableRecordDeleteArray = array("sponsor_invite_by_multiple_ngo");
              UnitTest::tableRelatedDelete($whereArray,$tableRecordDeleteArray);
            }
            die("Error in sponsor unit test reason testing email already exist!");
          }
       }
       else if(isset(json_decode($response->content())->status) && json_decode($response->content())->status=="send_error")
       {
          die("Error in sponsor unit test reason send mail error!");
       }
       else if(isset(json_decode($response->content())->status) && json_decode($response->content())->status=="invited")
       {
          die("Error in sponsor unit test reason already invited!");
       }
       else if(isset(json_decode($response->content())->status) && json_decode($response->content())->status=="email")
       {
          die("Error in sponsor unit test reason email exist as a team!");
       }
       else if(isset(json_decode($response->content())->status) && json_decode($response->content())->status=="N")
       {
          die("Error in sponsor unit test reason error in insertion!");
       }
       else if($response->status()!=200)
       {
          die("Error in sponsor unit test reason error in insertion!");
       }
    }
    public function verifyUser($infoArray)
    {
      $response = $this->json('GET', '/user/verify/'.$infoArray["verifyCode"]);
      $status = "not verify";
      if($response->status()==200)
      {
        $status = "verify";
      }
      return $status;
    }
    public function userRegistration($infoArray)
    {
      $dataArray = [
            "registration_no" => "unit test sponsor1",
            "user_org" => "unit test sponsor1",
            "sec_of_work" => UnitTest::getAllOrganizationSectors(),
            "con_person_name" => "unit test sponsor",
            "con_person_mobile" => 9907629428,
            "password" => 123456,
            "confirm" => 123456,
      ];
      $response = $this->json('POST','/user/updates/'.$infoArray["verifyCode"],$dataArray);
      $status = "register";
      if(isset(Session::all()["error"]))
      {
        $status = "Error in sponsor unit test reason register name or user registration no already taken!";
        //print_r(Session::all()["error"]);
      }
      else if(isset(Session::all()["success"]) && Session::all()["success"]=="register successfully!")
      {
        $status = "sponsor register";
      }
      else if($response->status()!=200)
      {
        $status = "register error or mail send error!";
      }
      return $status;
    }
}
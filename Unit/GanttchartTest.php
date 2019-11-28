<?php
namespace Tests\Unit;
use App\Ngo_office;
use App\Project;
use App\User;
use App\Ngo;
use App\Task;
use App\Organization_sectors;
use App\Category;
use App\Project_budget;
use Tests\TestCase;
use Session;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

class GanttchartTest extends TestCase
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
        //request for project create start
        $user = User::find(2);
        $response = $this->actingAs($user);
        $dataArray['ngo_id'] = $user->id;
        $dataArray['project_name'] = "unit test project 1153".time();
        $response = $this->json('POST', '/'.$user->registration_no.'/project/project-add/',$dataArray);
        if($response->status()!=200 || json_decode($response->content())->id=="" || 
        	json_decode($response->content())->id==0)
        {
           die("Error in project creation!");
        }
        //request for project create end
        
        $projectId = json_decode($response->content())->id;
        
        $whereArray = [
        		    "columnName" => "project_id",
        		    "columnValue" => $projectId,
        		];
        
        $infoArray = [
        		    "userRegistrationNo" => $user->registration_no,
        		    "projectId" => $projectId,
        		    "userId" => $user->id,
        		];
        
        $status = 1;

        // request for ganttchart view start
        $this->ganttchartView($infoArray,$whereArray);
    	// request for ganttchart view end

        // request for project update for request->project_name start
        $this->projectUpdateAccordingToProjectName($infoArray,$whereArray);
        // request for project update for request->project_name end
        
        // request for project update for request->cund_category (for Category wise allocation) start
        $this->projectUpdateAccordingCundCategory($infoArray,$whereArray);
        // request for project update for request->cund_category (for Category wise allocation) end
       
        // request for project update for request->cund (for Payment schedule) start
        $this->projectUpdateAccordingCund($infoArray,$whereArray);
        // request for project update for request->cund (for Payment schedule) end
        
        // request for taskadd start
        $taskArray = $this->projectTaskAdd($infoArray,$whereArray);
        // request for taskadd end  
        
        $infoArray["taskParentId"] = $taskArray["taskParentId"];
        $infoArray["taskId"] = $taskArray["taskId"];

        // request for taskweekadd start
        $this->projectTaskWeekAdd($infoArray,$whereArray);
        // request for taskweekadd end

        // request for taskupdate for budget and owner start
        $this->projectTaskUpdateForBudgetAndOwner($infoArray,$whereArray);
        // request for taskupdate for budget and owner end
        
        //request for project publish start
        $this->projectPublish($infoArray,$whereArray);
        //request for project publish end
        
        //request for project start start
        $this->projectStart($infoArray,$whereArray);
        //request for project start end

        //request for taskupdate for Utilized start
        $this->projectTaskUpdateForUtilized($infoArray,$whereArray);
        //request for taskupdate for Utilized end 

        //request for budgetupdate for Recieved start
        $this->projectBudgetUpdateForRecieved($infoArray,$whereArray);
        //request for budgetupdate for Recieved end 

        // delete record after successfully test
        if($status)
        {
            $tableRecordDeleteArray = array("taskweek");
            $whereArray["columnValue"] = $infoArray["taskId"];
            $this->projectRelatedDelete($whereArray,$tableRecordDeleteArray);
            $this->tearDown();
            
            $tableRecordDeleteArray = array("projects","project_sector_work","project_kpi","project_location","project_budget","task","project_sponsors");
            $whereArray["columnValue"] = $infoArray["projectId"];
	        $this->projectRelatedDelete($whereArray,$tableRecordDeleteArray);
            $this->tearDown();
        }
    }
    public function ganttchartView($infoArray,$whereArray)
    {
       $response = $this->json('GET', '/'.$infoArray['userRegistrationNo'].'/ganttchart/view/'.$infoArray['projectId']);
	   if($response->status()!=200)
	   {
			$tableRecordDeleteArray = array("task","projects");
			$this->projectRelatedDelete($whereArray,$tableRecordDeleteArray);
			$this->tearDown();
			die("error in view ganttchart!");
	   }
    }  
    public function projectUpdateAccordingToProjectName($infoArray,$whereArray)
    {
    	$dataArray = array();
        $dataArray = [
            'project_name' => "unit test by demo ".$infoArray["projectId"],
        	"start_date" => date("d/m/Y"), 
        	"project_description" => "hello hi1",
        	"sub_sector" => "hello hi1",
        	"project_budget" => 21,
        	"kpi" => array("hello hi1"), 
        	"baseline" => array(123),
        	"target" => array(21),
        	];

        $Organization_sectors = Organization_sectors::get()
                                ->sortByDesc('id');
        $sec_of_work = array();
        if(count($Organization_sectors)>0)
        {
        	$i = 0;
        	foreach($Organization_sectors as $value) 
	        {
	        	if($i==2)
	        	{
	        		break;
	        	}
	        	$sec_of_work[] = $value->id;
	        	$i++;
	        	//die(); 
	        }
	        $dataArray["sec_of_work"] = $sec_of_work;
        }
        
         

        $project_manager = User::whereIn('type',array('project_manager','project_member'))->where(array('parent_id'=>$infoArray["userId"]))->get();
        if(count($project_manager)>0)
        {
	        $dataArray["manager_id"] = $project_manager[0]->id;
        }

        $project_office = Ngo_office::where(array('ngo_id'=>$infoArray["userId"]))->orderBy('id','ASC')->get();
        if(count($project_office)>0)
        {
	        $dataArray["office"] = $project_office[0]->id;
        }
        
        $loaction=DB::select("SELECT d.id,d.name as city_name ,s.name as city_state FROM `district` d INNER JOIN states s on d.state_id=s.id  order by s.name");
        if(count($loaction)>0)
        {
	        $dataArray["location_district"] = array($loaction[0]->id);
	        $loactionCity=Ngo::getCityBYdistrict($loaction[0]->id);
	        if(count($loactionCity)>0)
            {
               $dataArray["location_city"] = array($loactionCity[0]->id);
            }
        }

        $response = $this->json('POST', '/'.$infoArray["userRegistrationNo"].'/ganttchart/update/'.$infoArray["projectId"], $dataArray);
        
        if($response->status()!=200 || (isset(json_decode($response->content())->status) && json_decode($response->content())->status=="error"))
        {
        	$tableRecordDeleteArray = array("project_sector_work","project_kpi","project_location","task","projects");
    		$this->projectRelatedDelete($whereArray,$tableRecordDeleteArray);
    		$this->tearDown();
    		die("error in project updation reason 1 ) ProjectUpdatepermission return false 2 ) save condition request->project_name status error!");
        }
    }  
    public function projectUpdateAccordingCundCategory($infoArray,$whereArray)
    {
    	$dataArray = array();
        $Category = Category::get()->sortBy('name',SORT_NATURAL,"asc");
        $cund_category = array();
        $category = array();
        $amount = array();
        if(count($Category)>0)
        {
        	$i = 0;
        	foreach($Category as $value) 
	        {
	        	if($i==0)
	        	{
	        	  $amount[] = 21;
	        	}
	        	else
	        	{
	        	  $amount[] = 0;
	        	}
	        	$category[] = $value->name;
	        	$cund_category[] = $i;
	        	$i++;
	        	//die(); 
	        }
	        $dataArray["category"] = $category;
	        $dataArray["cund_category"] = $cund_category;
	        $dataArray["amount"] = $amount;
        }

        $response = $this->json('POST', '/'.$infoArray["userRegistrationNo"].'/ganttchart/update/'.$infoArray["projectId"], $dataArray);
        //print_r($response);
        if( $response->status()!=200 || (isset(json_decode($response->content())->status) && json_decode($response->content())->status=="error"))
        {
        	$tableRecordDeleteArray = array("project_sector_work","project_kpi","project_location","project_budget","task","projects");
		    $this->projectRelatedDelete($whereArray,$tableRecordDeleteArray);
    		$this->tearDown();
    		die("error in Category wise allocation reason request->cund_category ( for Category wise allocation )!");
        }
    }
    public function projectUpdateAccordingCund($infoArray,$whereArray)
    {   
        $dataArray = array();
        $dataArray["cund"] =array(0);
        $dataArray["date"] =array(date("d/m/Y"));
        $dataArray["amount"] =array(21);
        $response = $this->json('POST', '/'.$infoArray["userRegistrationNo"].'/ganttchart/update/'.$infoArray["projectId"], $dataArray);
        if($response->status()!=200 || (isset(json_decode($response->content())->status) && json_decode($response->content())->status=="error"))
        {
    		$tableRecordDeleteArray = array("project_sector_work","project_kpi","project_location","project_budget","task","projects");
		    $this->projectRelatedDelete($whereArray,$tableRecordDeleteArray);
    		$this->tearDown();
    		die("error in Payment schedule for request->cund (for Payment schedule)!");
        }
	} 
	public function projectTaskAdd($infoArray,$whereArray)
	{
       $dataArray = array();
       $task=Task::where(array('task.project_id'=>$infoArray["projectId"],'task.parent_id'=>0))->get();
       if(count($task)>0)
       {
       	 $dataArray["parent_id"] =  $task[0]->id;
       }
       $response = $this->json('POST', '/'.$infoArray["userRegistrationNo"].'/ganttchart/taskadd/'.$infoArray["projectId"], $dataArray);
       if($response->status()!=200 || (isset(json_decode($response->content())->status) && json_decode($response->content())->status=="error"))
       {	    		
    		$tableRecordDeleteArray = array("project_sector_work","project_kpi","project_location","project_budget","task","projects");
		    $this->projectRelatedDelete($whereArray,$tableRecordDeleteArray);
		    $this->tearDown();
    		die("error in add new activity below or task!");
       }
       return array("taskParentId" => $dataArray["parent_id"] ,"taskId" => json_decode($response->content())->id);
	}   
	public function projectTaskWeekAdd($infoArray,$whereArray)
	{
   	   $dataArray = array();
   	   $dataArray['week_value'] = date('W')+1;
   	   $dataArray['week_year'] = date('Y');

   	   $response = $this->json('POST', '/'.$infoArray["userRegistrationNo"].'/ganttchart/taskweekadd/'.$infoArray["taskId"], $dataArray);
   	   if($response->status()!=200 || (isset(json_decode($response->content())->status) && json_decode($response->content())->status=="error"))
       {
       	    $tableRecordDeleteArray = array("project_sector_work","project_kpi","project_location","project_budget","task","projects");
	        $this->projectRelatedDelete($whereArray,$tableRecordDeleteArray);
    	    $this->tearDown();
    		die("error in add task week!");
       }
    }
    public function projectTaskUpdateForBudgetAndOwner($infoArray,$whereArray)
    {

    	$team = User::whereIn('type',array('project_manager','project_member'))->where(array('parent_id'=>$infoArray["userId"]))->get();

    	$teamId = "";
    	if(count($team)>0)
    	{
          $teamId = $team[0]->id;
    	}

        $fieldNameArray = array("budget","owner");
        $fieldValArray = array(21,$teamId);
        $taskUpdateMessageArray = array("error in task budget update!","error in task owner update!");

        $this->taskUpdateOwnerBudgetUtilized($fieldNameArray,$fieldValArray,$taskUpdateMessageArray,$infoArray["userRegistrationNo"],$infoArray["taskId"]);
    } 
    public function taskUpdateOwnerBudgetUtilized($fieldNameArray,$fieldValArray,$taskUpdateMessageArray,$userRegistrationNo,$taskId)
    {
       for ($i=0; $i <count($fieldNameArray) ; $i++) 
       { 
           $dataArray = array();
           $dataArray['field_name'] = $fieldNameArray[$i];
       	   $dataArray['filed_val'] = $fieldValArray[$i];
           $response = $this->json('POST', '/'.$userRegistrationNo.'/ganttchart/taskupdate/'.$taskId, $dataArray);
       	   if($response->status()!=200 || (isset(json_decode($response->content())->status) && json_decode($response->content())->status=="error"))
	       { 
                $tableRecordDeleteArray = array("taskweek");
                $whereArray["columnValue"] = $taskId;
                $this->projectRelatedDelete($whereArray,$tableRecordDeleteArray);
                $this->tearDown();

	       	    $tableRecordDeleteArray = array("project_sector_work","project_kpi","project_location","project_budget","task","projects");
                $whereArray["columnValue"] = $infoArray["projectId"];
		        $this->projectRelatedDelete($whereArray,$tableRecordDeleteArray);
                $this->tearDown();

	    		die($taskUpdateMessageArray[$i]);
	       }
       }
    }
    public function projectPublish($infoArray,$whereArray)
    {
        $dataArray = array(); 
        $dataArray["publish_sponsor_id"] = array(121);  	
       	$response = $this->json('POST', '/'.$infoArray["userRegistrationNo"].'/ganttchart/publish/'.$infoArray["projectId"], $dataArray);
       	//echo $response->status();
       	if(isset(Session::all()["success"]) && Session::all()["success"]=="Successfully published")
		{
          //echo "Published";
		}
       	else if($response->status()!=200 || (isset(Session::all()["error"]) && Session::all()["error"]=="Project  and category and payment Bugget total need to same"))
       	{
            $tableRecordDeleteArray = array("taskweek");
            $whereArray["columnValue"] = $infoArray["taskId"];
            $this->projectRelatedDelete($whereArray,$tableRecordDeleteArray);
            $this->tearDown();

       		$tableRecordDeleteArray = array("project_sector_work","project_kpi","project_location","project_budget","task","project_sponsors","projects");
            $whereArray["columnValue"] = $infoArray["projectId"];
	        $this->projectRelatedDelete($whereArray,$tableRecordDeleteArray);
            $this->tearDown();
            
       		die("Error in project Published! Project  and category and payment Bugget total need to same!");
       	}
    }
    public function projectStart($infoArray,$whereArray)
    {
    	$dataArray = array(); 
	    $dataArray["approval_pschedule"] = "on"; 
		$dataArray["how_mutchDisburse"] = 1; 
		$dataArray["lstart_date"] = date("d/m/Y"); 
		$dataArray["project_dasboard"] = "on"; 
		$dataArray["sponsor_organization"] = array(121); 
		$dataArray["sponsorship_approval"] = "on";
		$dataArray["startProject_submit"] = "sb"; 
	    $response = $this->json('POST', '/'.$infoArray["userRegistrationNo"].'/ganttchart/update/'.$infoArray["projectId"], $dataArray);
	    if(isset(Session::all()["success"]) && Session::all()["success"]=="Start Successfully")
		{
		  //echo Session::all()["success"];
	      //echo "Start";
		}
	   	else if($response->status()!=200 || (isset(Session::all()["error"]) && Session::all()["error"]=="Project  and category and payment Bugget total need to same"))
	   	{
            $tableRecordDeleteArray = array("taskweek");
            $whereArray["columnValue"] = $infoArray["taskId"];
            $this->projectRelatedDelete($whereArray,$tableRecordDeleteArray);
            $this->tearDown();

	   		$tableRecordDeleteArray = array("project_sector_work","project_kpi","project_location","project_budget","task","project_sponsors","projects");
            $whereArray["columnValue"] = $infoArray["projectId"];
	        $this->projectRelatedDelete($whereArray,$tableRecordDeleteArray);
            $this->tearDown();

           
	   		die("Error in project Start! Project and category and payment Bugget total need to same!");
	   	}
    }
    public function projectTaskUpdateForUtilized($infoArray,$whereArray)
    {
    	$utilizedArray = array($infoArray["taskParentId"],$infoArray["taskId"]);
    	for ($i=0; $i < count($utilizedArray) ; $i++) 
    	{ 
    		$fieldNameArray = array("received");
	        $fieldValArray = array(10);
	        $taskUpdateMessageArray = array("Error in taskupdate for Utilized  after start!");
	        $this->taskUpdateOwnerBudgetUtilized($fieldNameArray,$fieldValArray,$taskUpdateMessageArray,$infoArray["userRegistrationNo"],$utilizedArray[$i]);
    	} 
    }
    public function projectBudgetUpdateForRecieved($infoArray,$whereArray)
    {
    	$budget = Project_budget::where(array('project_id'=>$infoArray["projectId"],"category"=>NULL))->get();
        $budgetId = "";
        if(count($budget)>0)
        {
          $budgetId = $budget[0]->id;	
        }

    	$dataArray = array(); 
	    $dataArray["vals"] = 11;
	    $dataArray["id"] = $budgetId; 
	    $response = $this->json('POST', '/'.$infoArray["userRegistrationNo"].'/ganttchart/paymentResive/'.$infoArray["projectId"], $dataArray);
	    if($response->status()!=200 || (isset(json_decode($response->content())->status) && json_decode($response->content())->status=="error"))
	   	{

            $tableRecordDeleteArray = array("taskweek");
            $whereArray["columnValue"] = $infoArray["taskId"];
            $this->projectRelatedDelete($whereArray,$tableRecordDeleteArray);
            $this->tearDown();


	   		$tableRecordDeleteArray = array("project_sector_work","project_kpi","project_location","project_budget","task","project_sponsors","projects");
            $whereArray["columnValue"] = $infoArray["projectId"];
	        $this->projectRelatedDelete($whereArray,$tableRecordDeleteArray);
            $this->tearDown();

	   		die("Error in project payment recieved!");

	   	}
    }
    public function tearDown()
    {

        DB::rollback();
        $max = DB::table('projects')->max('id') + 1;
        DB::statement("ALTER TABLE projects AUTO_INCREMENT =  $max");

        $max = DB::table('project_sector_work')->max('id') + 1;
        DB::statement("ALTER TABLE project_sector_work AUTO_INCREMENT =  $max");

        $max = DB::table('project_budget')->max('id') + 1;
        DB::statement("ALTER TABLE project_budget AUTO_INCREMENT =  $max");

        $max = DB::table('project_kpi')->max('id') + 1;
        DB::statement("ALTER TABLE project_kpi AUTO_INCREMENT =  $max");

        $max = DB::table('project_location')->max('id') + 1;
        DB::statement("ALTER TABLE project_location AUTO_INCREMENT =  $max");

        $max = DB::table('task')->max('id') + 1;
        DB::statement("ALTER TABLE task AUTO_INCREMENT =  $max");

        $max = DB::table('taskweek')->max('id') + 1;
        DB::statement("ALTER TABLE taskweek AUTO_INCREMENT =  $max");

        $max = DB::table('project_sponsors')->max('id') + 1;
        DB::statement("ALTER TABLE project_sponsors AUTO_INCREMENT =  $max");

        //parent::tearDown();
    }
    public function projectRelatedDelete($whereArray,$tableRecordDeleteArray)
    {
    	foreach ($tableRecordDeleteArray as $table) 
        {
        	$whereArray["columnName"] = "project_id";
        	if($table=="projects")
        	{
        	   $whereArray["columnName"] = "id";
        	}
        	if($table=="taskweek")
        	{
        	   $whereArray["columnName"] = "task_id";
        	}
        	$whereArray["tableName"] = $table;
        	DB::delete("DELETE FROM ".$whereArray['tableName']." WHERE ".$whereArray['columnName']."=".$whereArray['columnValue']);
            //print_r($whereArray);
        }
    }
}
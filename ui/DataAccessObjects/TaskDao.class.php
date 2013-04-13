<?php

require_once __DIR__."/../../Common/lib/APIHelper.class.php";

class TaskDao
{
    private $client;
    private $siteApi;

    public function __construct()
    {
        $this->client = new APIHelper(Settings::get("ui.api_format"));
        $this->siteApi = Settings::get("site.api");
    }

    public function getTask($id)
    {
        $request = "{$this->siteApi}v0/tasks/$id";
        $response =$this->client->call("Task", $request);
        return $response;
    }
    
    public function getTasks()
    {
        $request = "{$this->siteApi}v0/tasks";
        $response =$this->client->call(array("Task"), $request);
        return $response;
    }
    

    public function getTaskPreReqs($taskId)
    {
        $request = "{$this->siteApi}v0/tasks/$taskId/prerequisites";
        $response =$this->client->call(array("Task"), $request);
        return $response;
    }

    public function getTopTasks($limit = null)
    {
        $request = "{$this->siteApi}v0/tasks/top_tasks";
        $args=$limit ? array("limit" => $limit) : null;
        $response =$this->client->call(array("Task"), $request,HttpMethodEnum::GET, null, $args);
        return $response;
    }

    public function getTaskTags($taskId)
    {
        $request = "{$this->siteApi}v0/tasks/$taskId/tags";
        $args=$limit ? array("limit" => $limit) : null;
        $response =$this->client->call(array("Tag"), $request,HttpMethodEnum::GET, null, $args);
        return $response;
    }

    // this is wrong fix
    public function getTaskFile($taskId, $version = 0, $convertToXliff = false)
    {
        $request = "{$this->siteApi}v0/tasks/$taskId/file";
        $args=array("version" => $version,"convertToXliff"=>$convertToXliff);
        $response =$this->client->call(null, $request,HttpMethodEnum::GET, null, $args);
        return $response;
    }

    public function getTaskVersion($taskId)
    {
        $request = "{$this->siteApi}v0/tasks/$taskId/version";
        $response =$this->client->call(null, $request);
        return $response;
    }

    public function getTaskInfo($taskId, $version = 0)
    {
        $request = "{$this->siteApi}v0/tasks/$taskId/info";
        $args = array("version" => $version);
        $response =$this->client->call("TaskMetaData", $request,HttpMethodEnum::GET, null, $args);
        return $response;
    }

    public function isTaskClaimed($taskId, $userId = null)
    {
        $request = "{$this->siteApi}v0/tasks/$taskId/claimed";
        $args=$userId ? array("userID" => $userId) : null;
        $response =$this->client->call(null, $request,HttpMethodEnum::GET, null, $args);
        return $response;
    }

    public function getUserClaimedTask($taskId)
    {
        $request = "{$this->siteApi}v0/tasks/$taskId/user";
        $response =$this->client->call("User", $request);
        return $response;
    }

    public function createTask($task)
    {
        $request = "{$this->siteApi}v0/tasks";
        $response =$this->client->call("Task", $request,HttpMethodEnum::POST, $task);
        return $response;
    }

    public function updateTask($task)
    {
        $request = "{$this->siteApi}v0/tasks/{$task->getId()}";
        $response =$this->client->call("Task", $request,HttpMethodEnum::PUT, $task);
        return $response;
    }

    public function deleteTask($taskId)
    {
        $request = "{$this->siteApi}v0/tasks/$taskId";
        $response =$this->client->call(null, $request, HttpMethodEnum::DELETE);
    }

    public function addTaskPreReq($taskId, $preReqId)
    {
        $request = "{$this->siteApi}v0/tasks/$taskId/prerequisites/$preReqId";
        $response =$this->client->call(null, $request, HttpMethodEnum::PUT);
    }

    public function removeTaskPreReq($taskId, $preReqId)
    {
        $request = "{$this->siteApi}v0/tasks/$taskId/prerequisites/$preReqId";
        $response =$this->client->call(null, $request, HttpMethodEnum::DELETE);
    }

    public function archiveTask($taskId, $userId)
    {
        $request = "{$this->siteApi}v0/tasks/archiveTask/$taskId/user/$userId";
        $response =$this->client->call(null, $request, HttpMethodEnum::PUT);
        return $response;
    }

    public function setTaskTags($task)
    {
        $request = "{$this->siteApi}v0/tasks/$taskId/tags";
        $response =$this->client->call(null, $request, HttpMethodEnum::PUT, $task);
    }

    public function sendFeedback($taskId, $userIds, $feedback)// change to new feed back email
    {
        $feedbackData = new FeedbackEmail();
        $feedbackData->setTaskId($taskId);
        $userIds = is_array($userIds) ? $userIds : array($userIds);
        foreach ($userIds as $userId) {
            $feedbackData->addUserId($userId);
        }
        $feedbackData->setFeedback($feedback);
        $request = "{$this->siteApi}v0/tasks/{$feedbackData->getTaskId()}/feedback";
        $response =$this->client->call(null,$request, HttpMethodEnum::PUT, $feedbackData);
    }

    public function saveTaskFile($taskId, $filename, $userId, $fileData, $version = null, $convert = false)
    {
        $request = "{$this->siteApi}v0/tasks/$taskId/file/$filename/$userId";
        $args = array();
        if ($version) {
            $args["version"] = $version;
        }
        if ($convert) {
            $args['convertFromXliff'] = $convert;
        }

        $response = $this->client->call(null,$request, HttpMethodEnum::PUT, null, $args,$fileData);
    }

    public function uploadOutputFile($taskId, $userId, $fileData, $convert = false)
    {
        $request = "{$this->siteApi}v0/tasks/uploadOutputFile/$taskId/$userId";

        $args = null;
        if ($convert) {
            $args= array('convertFromXliff' => $convert);
        }

        $response = $this->client->call(null,$request, HttpMethodEnum::PUT, null, $args,$fileData);
    }
}

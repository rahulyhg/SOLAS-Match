<?php

require_once __DIR__."/../models/MembershipRequest.php";
require_once __DIR__."/../models/ArchivedTask.php";
require_once __DIR__."/../models/PasswordResetRequest.php";
require_once __DIR__."/../models/PasswordReset.php";
require_once __DIR__."/../models/Register.php";
require_once __DIR__."/../models/Country.php";
require_once __DIR__."/../models/Language.php";
require_once __DIR__."/../models/Login.php";
require_once __DIR__."/../models/Badge.php";
require_once __DIR__."/../models/Tag.php";
require_once __DIR__."/../models/Organisation.php";
require_once __DIR__."/../models/TaskMetadata.php";
require_once __DIR__."/../models/User.php";
require_once __DIR__."/../models/Task.php";
require_once __DIR__."/../models/Project.php";
require_once __DIR__."/../models/ArchivedProject.php";
require_once __DIR__."/../models/Statistic.php";
require_once __DIR__."/../models/ProjectFile.php";

class ModelFactory
{
    public static function buildModel($modelName, $modelData)
    {
        $ret = null;

        switch($modelName)
        {
            case "MembershipRequest" :
                $ret = ModelFactory::generateMembershipRequest($modelData);
                break;
            case "ArchivedTask" :
                $ret = ModelFactory::generateArchivedTask($modelData);
                break;
            case "PasswordReset" :
                $ret = ModelFactory::generatePasswordReset($modelData);
                break;
            case "PasswordResetRequest" :
                $ret = ModelFactory::generatePasswordResetRequest($modelData);
                break;
            case "Register" :
                $ret = ModelFactory::generateRegister($modelData);
                break;
            case "Country" :
                $ret = ModelFactory::generateCountry($modelData);
                break;
            case "Language" :
                $ret = ModelFactory::generateLanguage($modelData);
                break;
            case "Login" :
                $ret = ModelFactory::generateLogin($modelData);
                break;
            case "Badge" :
                $ret = ModelFactory::generateBadge($modelData);
                break;
            case "Tag" :
                $ret = ModelFactory::generateTag($modelData);
                break ;
            case "Organisation" :
                $ret = ModelFactory::generateOrganisation($modelData);
                break;
            case "TaskMetadata" :
                $ret = ModelFactory::generateTaskMetadata($modelData);
                break;
            case "User" :
                $ret = ModelFactory::generateUser($modelData);
                break;
            case "Task" :
                $ret = ModelFactory::generateTask($modelData);
                break;
            case "Project" :
                $ret = ModelFactory::generateProject($modelData);
                break;
            case "ArchivedProject" :
                $ret = ModelFactory::generateArchivedProject($modelData);
                break;
            case "Statistic" :
                $ret = ModelFactory::generateStatistic($modelData);
                break;
            case "ProjectFile" :
                $ret = ModelFactory::generateProjectFile($modelData);
                break;
            default :
                echo "Unable to build model $modelName";
        }

        return $ret;
    }

    private static function generateMembershipRequest($modelData)
    {
        $ret = new MembershipRequest();
        $ret ->setId($modelData["id"]);
        if (isset($modelData['user_id'])) {
            $ret->setUserId($modelData['user_id']);
        }
        if (isset($modelData['org_id'])) {
            $ret->setOrgId($modelData['org_id']);
        }
        if (isset($modelData['request-datetime'])) {
            $ret->setRequestTime($modelData['request-datetime']);
        }
        return $ret;
    }

    private static function generateArchivedTask($modelData)
    {
        $ret = new ArchivedTask();

        if (isset($modelData['id'])) {
            $ret->setId($modelData['id']);
        }
        if (isset($modelData['project_id'])) {
            $ret->setProjectId($modelData['project_id']);
        }
        if (isset($modelData['title'])) {
            $ret->setTitle($modelData['title']);
        }
        if (isset($modelData['comment'])) {
            $ret->setComment($modelData['comment']);
        }
        if (isset($modelData['deadline'])) {
            $ret->setDeadline($modelData['deadline']);
        }
        if (isset($modelData['word-count'])) {
            $ret->setWordCount($modelData['word-count']);
        }
        if (isset($modelData['created-time'])) {
            $ret->setCreatedTime($modelData['created-time']);
        }
        if (isset($modelData['language_id-source'])) {
            $ret->setSourceLanguageCode($modelData['language_id-source']);
        }
        if (isset($modelData['language_id-target'])) {
            $ret->setTargetLanguageCode($modelData['language_id-target']);
        }
        if (isset($modelData['country_id-source'])) {
            $ret->setSourceCountryCode($modelData['country_id-source']);
        }
        if (isset($modelData['country_id-target'])) {
            $ret->setTargetCountryCode($modelData['country_id-target']);
        }
        if (isset($modelData['taskType'])) {
            $ret->setTaskType($modelData['taskType']);
        }
        if (isset($modelData['taskStatus'])) {
            $ret->setTaskStatus($modelData['taskStatus']);
        }
        if (isset($modelData['published'])) {
            $ret->setPublished($modelData['published']);
        }
        if (isset($modelData['user_id-claimed'])) {
            $ret->setTranslatorId($modelData['user_id-claimed']);
        }
        if (isset($modelData['user_id-archived'])) {
            $ret->setArchiveUserId($modelData['user_id-archived']);
        }
        if (isset($modelData['archive-date'])) {
            $ret->setArchiveDate($modelData['archive-date']);
        }
        
        return $ret;
    }

    private static function generatePasswordReset($modelData)
    {
        $ret = new PasswordReset();
        
        if (isset($modelData['password'])) {
            $ret->setPassword($modelData['password']);
        }
        if (isset($modelData['key'])) {
            $ret->setKey($modelData['key']);
        }

        return $ret;
    }

    private static function generatePasswordResetRequest($modelData)
    {
        $ret = new PasswordResetRequest();

        if (isset($modelData['user_id'])) {
            $ret->setUserId($modelData['user_id']);
        }
        if (isset($modelData['uid'])) {
            $ret->setKey($modelData['uid']);
        }
        if (isset($modelData['request-time'])) {
            $ret->setRequestTime($modelData['request-time']);
        }

        return $ret;
    }

    private static function generateRegister($modelData)
    {
        $ret = new Register();

        if (isset($modelData['email'])) {
            $ret->setEmail($modelData['email']);
        }
        if (isset($modelData['password'])) {
            $ret->setPassword($modelData['password']);
        }

        return $ret;
    }

    private static function generateCountry($modelData)
    {
        $ret = new Country();

        if (isset($modelData['id'])) {
            $ret->setId($modelData['id']);
        }
        if (isset($modelData['code'])) {
            $ret->setCode($modelData['code']);
        }
        if (isset($modelData['country'])) {
            $ret->setName($modelData['country']);
        }

        return $ret;
    }

    private static function generateLanguage($modelData)
    {
        $ret = new Language();

        if (isset($modelData['id'])) {
            $ret->setId($modelData['id']);
        }
        if (isset($modelData['code'])) {
            $ret->setCode($modelData['code']);
        }
        if (isset($modelData['language'])) {
            $ret->setName($modelData['language']);
        }

        return $ret;
    }

    private static function generateLogin($modelData)
    {
        $ret = new Login();

        if (isset($modelData['email'])) {
            $ret->setEmail($modelData['email']);
        }
        if (isset($modelData['password'])) {
            $ret->setPassword($modelData['password']);
        }

        return $ret;
    }

    private static function generateBadge($modelData)
    {
        $ret = new Badge();

        if (isset($modelData['id'])) {
            $ret->setId($modelData['id']);
        }
        if (isset($modelData['title'])) {
            $ret->setTitle($modelData['title']);
        }
        if (isset($modelData['description'])) {
            $ret->setDescription($modelData['description']);
        }
        if (isset($modelData['owner_id'])) {
            $ret->setOwnerId($modelData['owner_id']);
        }

        return $ret;
    }

    private static function generateTag($modelData)
    {
        $ret = new Tag();

        if (isset($modelData['id'])) {
            $ret->setId($modelData['id']);
        }
        if (isset($modelData['label'])) {
            $ret->setLabel($modelData['label']);
        }

        return $ret;
    }

    private static function generateOrganisation($modelData)
    {
        $ret = new Organisation();

        if (isset($modelData['id'])) {
            $ret->setId($modelData['id']);
        }
        if (isset($modelData['name'])) {
            $ret->setName($modelData['name']);
        }
        if (isset($modelData['home-page'])) {
            $ret->setHomePage($modelData['home-page']);
        }
        if (isset($modelData['biography'])) {
            $ret->setBiography($modelData['biography']);
        }

        return $ret;
    }

    private static function generateTaskMetadata($modelData)
    {
        $ret = new TaskMetadata();

        if (isset($modelData['task_id'])) {
            $ret->setId($modelData['task_id']);
        }
        if (isset($modelData['version_id'])) {
            $ret->setVersion($modelData['version_id']);
        }
        if (isset($modelData['filename'])) {
            $ret->setFilename($modelData['filename']);
        }
        if (isset($modelData['content-type'])) {
            $ret->setContentType($modelData['content-type']);
        }
        if (isset($modelData['user_id'])) {
            $ret->setUserId($modelData['user_id']);
        }
        if (isset($modelData['upload-time'])) {
            $ret->setUploadTime($modelData['upload-time']);
        }

        return $ret;
    }

    private static function generateUser($modelData)
    {
        $ret = new User();

        if (isset($modelData['id'])) {
            $ret->setUserId($modelData['id']);
        }
        if (isset($modelData['email'])) {
            $ret->setEmail($modelData['email']);
        }
        if (isset($modelData['nonce'])) {
            $ret->setNonce($modelData['nonce']);
        }
        if (isset($modelData['password'])) {
            $ret->setPassword($modelData['password']);
        }
        if (isset($modelData['display-name'])) {
            $ret->setDisplayName($modelData['display-name']);
        }
        if (isset($modelData['biography'])) {
            $ret->setBiography($modelData['biography']);
        }
        if (isset($modelData['language_id'])) {
            $ret->setNativeLangId($modelData['language_id']);
        }
        if (isset($modelData['country_id'])) {
            $ret->setNativeRegionId($modelData['country_id']);
        }
        if (isset($modelData['created-time'])) {
            $ret->setCreatedTime($modelData['created-time']);
        }

        return $ret;
    }

    private static function generateTask($modelData)
    {
        $ret = new Task();

        if (isset($modelData['id'])) {
            $ret->setId($modelData['id']);
        }
        if (isset($modelData['project_id'])) {
            $ret->setProjectId($modelData['project_id']);
        }
        if (isset($modelData['title'])) {
            $ret->setTitle($modelData['title']);
        }
        if (isset($modelData['comment'])) {
            $ret->setComment($modelData['comment']);
        }
        if (isset($modelData['deadline'])) {
            $ret->setDeadline($modelData['deadline']);
        }
        if (isset($modelData['word-count'])) {
            $ret->setWordCount($modelData['word-count']);
        }
        if (isset($modelData['created-time'])) {
            $ret->setCreatedTime($modelData['created-time']);
        }
        if (isset($modelData['language_id-source'])) {
            $ret->setSourceLanguageCode($modelData['language_id-source']);
        }
        if (isset($modelData['language_id-target'])) {
            $ret->setTargetLanguageCode($modelData['language_id-target']);
        }
        if (isset($modelData['country_id-source'])) {
            $ret->setSourceCountryCode($modelData['country_id-source']);
        }
        if (isset($modelData['country_id-target'])) {
            $ret->setTargetCountryCode($modelData['country_id-target']);
        }
        if (isset($modelData['task-type_id'])) {
            $ret->setTaskType($modelData['task-type_id']);
        }
        if (isset($modelData['task-status_id'])) {
            $ret->setTaskStatus($modelData['task-status_id']);
        }
        if (isset($modelData['published'])) {
            $ret->setPublished($modelData['published']);
        }
        
        return $ret;
    }

    private static function generateProject($modelData)
    {
        $ret = new Project();

        if(isset($modelData['id'])) {
            $ret->setId($modelData['id']);
        }
        if(isset($modelData['title'])) {
            $ret->setTitle($modelData['title']);
        }
        if(isset($modelData['description'])) {
            $ret->setDescription($modelData['description']);
        }
        if(isset($modelData['deadline'])) {
            $ret->setDeadline($modelData['deadline']);
        }
        if(isset($modelData['organisation_id'])) {
            $ret->setOrganisationId($modelData['organisation_id']);
        }
        if(isset($modelData['impact'])) {
            $ret->setImpact($modelData['impact']);
        }
        if(isset($modelData['reference'])) {
            $ret->setReference($modelData['reference']);
        }
        if(isset($modelData['word-count'])) {
            $ret->setWordCount($modelData['word-count']);
        }
        if(isset($modelData['created'])) {
            $ret->setCreatedTime($modelData['created']);
        }
        if(isset($modelData['status'])) {
            $ret->setStatus($modelData['status']);
        }
        if(isset($modelData['language_id'])) {
            $ret->setSourceLanguageCode($modelData['language_id']);
        }
        if(isset($modelData['country_id'])) {
            $ret->setSourceCountryCode($modelData['country_id']);
        }

        return $ret;
    }

    private static function generateArchivedProject($modelData)
    {
        $ret = new ArchivedProject();

        if(isset($modelData['id'])) {
            $ret->setId($modelData['id']);
        }
        if(isset($modelData['title'])) {
            $ret->setTitle($modelData['title']);
        }
        if(isset($modelData['description'])) {
            $ret->setDescription($modelData['description']);
        }
        if(isset($modelData['impact'])) {
            $ret->setImpact($modelData['impact']);
        }
        if(isset($modelData['deadline'])) {
            $ret->setDeadline($modelData['deadline']);
        }
        if(isset($modelData['organisation_id'])) {
            $ret->setOrganisationId($modelData['organisation_id']);
        }
        if(isset($modelData['reference'])) {
            $ret->setReference($modelData['reference']);
        }
        if(isset($modelData['word-count'])) {
            $ret->setWordCount($modelData['word-count']);
        }
        if(isset($modelData['created'])) {
            $ret->setCreatedTime($modelData['created']);
        }
        if(isset($modelData['language_id'])) {
            $ret->setLanguageCode($modelData['language_id']);
        }
        if(isset($modelData['country_id'])) {
            $ret->setCountryCode($modelData['country_id']);
        }
        if(isset($modelData['archived-date'])) {
            $ret->setArchivedDate($modelData['archived-date']);
        }
        if(isset($modelData['user_id-archived'])) {
            $ret->setTranslatorId($modelData['user_id-archived']);
        }

        return $ret;
    }
    
    
    private static function generateStatistic($modelData)
    {
        $ret = new Statistic();

        if(isset($modelData['name'])) {
            $ret->setName($modelData['name']);
        }
        if(isset($modelData['value'])) {
            $ret->setValue($modelData['value']);
        }
        
        return $ret;
    }
    
    private static function generateProjectFile($modelData)
    {
        $ret = new ProjectFile();

        if(isset($modelData['project_id'])) {
            $ret->setProjectId($modelData['project_id']);
        }
        if(isset($modelData['filename'])) {
            $ret->setFilename($modelData['filename']);
        }
        if(isset($modelData['file-token'])) {
            $ret->setToken($modelData['file-token']);
        }
        if(isset($modelData['user_id'])) {
            $ret->setUserId($modelData['user_id']);
        }
        if(isset($modelData['mime-type'])) {
            $ret->setMime($modelData['mime-type']);
        }
        
        return $ret;
    }
}

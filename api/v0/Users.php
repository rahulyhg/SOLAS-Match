<?php

namespace SolasMatch\API\V0;

use \SolasMatch\Common as Common;
use \SolasMatch\API\DAO as DAO;
use \SolasMatch\API\Lib as Lib;
use \SolasMatch\API as API;
use SolasMatch\API\DAO\AdminDao;

require_once __DIR__.'/../../Common/protobufs/models/OAuthResponse.php';
require_once __DIR__."/../../Common/protobufs/models/PasswordResetRequest.php";
require_once __DIR__."/../../Common/protobufs/models/PasswordReset.php";
require_once __DIR__."/../../Common/lib/Settings.class.php";
require_once __DIR__."/../DataAccessObjects/UserDao.class.php";
require_once __DIR__."/../DataAccessObjects/TaskDao.class.php";
require_once __DIR__."/../lib/Notify.class.php";
require_once __DIR__."/../lib/Middleware.php";
require_once '/repo/neon-php/neon.php';


class Users
{
    public static function init()
    {
        $app = \Slim\Slim::getInstance();

        $app->group('/v0', function () use ($app) {
            $app->group('/users', function () use ($app) {
                $app->group('/:userId', function () use ($app) {
                    $app->group('/trackedTasks', function () use ($app) {

                        /* Routes starting /v0/users/:userId/trackedTasks */
                        $app->put(
                            '/:taskId/',
                            '\SolasMatch\API\Lib\Middleware::authUserOwnsResource',
                            '\SolasMatch\API\V0\Users::addUserTrackedTasksById'
                        );

                        $app->delete(
                            '/:taskId/',
                            '\SolasMatch\API\Lib\Middleware::authUserOwnsResource',
                            '\SolasMatch\API\V0\Users::deleteUserTrackedTasksById'
                        );
                    });
                    $app->group('/badges', function () use ($app) {

                        /* Routes starting /v0/users/:userId/badges */
                        $app->put(
                            '/:badgeId/',
                            '\SolasMatch\API\Lib\Middleware::authenticateUserForOrgBadge',
                            '\SolasMatch\API\V0\Users::addUserbadgesByID'
                        );

                        $app->delete(
                            '/:badgeId/',
                            '\SolasMatch\API\Lib\Middleware::authenticateUserOrOrgForOrgBadge',
                            '\SolasMatch\API\V0\Users::deleteUserbadgesByID'
                        );
                    });

                    $app->group('/tasks', function () use ($app) {

                        /* Routes starting /v0/users/:userId/tasks */
                        $app->get(
                            '/:taskId/review(:format)/',
                            '\SolasMatch\API\Lib\Middleware::authUserOrOrgForTask',
                            '\SolasMatch\API\V0\Users::getUserTaskReview'
                        );
                        
                        $app->post(
                            '/:taskId/',
                            '\SolasMatch\API\Lib\Middleware::authenticateTaskNotClaimed',
                            '\SolasMatch\API\V0\Users::userClaimTask'
                        );
                        
                        $app->delete(
                            '/:taskId(:format)/',
                            '\SolasMatch\API\Lib\Middleware::authUserOrOrgForTask',
                            '\SolasMatch\API\V0\Users::userUnClaimTask'
                        );
                    });

                    $app->group('/tags', function () use ($app) {

                        /* Routes starting /v0/users/:userId/tags */
                        $app->put(
                            '/:tagId/',
                            '\SolasMatch\API\Lib\Middleware::authUserOwnsResource',
                            '\SolasMatch\API\V0\Users::addUserTagById'
                        );

                        $app->delete(
                            '/:tagId/',
                            '\SolasMatch\API\Lib\Middleware::authUserOwnsResource',
                            '\SolasMatch\API\V0\Users::deleteUserTagById'
                        );
                    });

                    $app->group('/projects', function () use ($app) {

                        /* Routes starting /v0/users/:userId/projects */
                        $app->get(
                            '/:projectId/',
                            '\SolasMatch\API\Lib\Middleware::authUserOwnsResource',
                            '\SolasMatch\API\V0\Users::userTrackProject'
                        );

                        $app->delete(
                            '/:projectId/',
                            '\SolasMatch\API\Lib\Middleware::authUserOwnsResource',
                            '\SolasMatch\API\V0\Users::userUnTrackProject'
                        );

                        $app->put(
                            '/:projectId/',
                            '\SolasMatch\API\Lib\Middleware::authUserOwnsResource',
                            '\SolasMatch\API\V0\Users::userTrackProject'
                        );
                    });

                    $app->group('/organisations', function () use ($app) {

                        /* Routes starting /v0/users/:userId/organisations */
                        $app->put(
                            '/:organisationId/',
                            '\SolasMatch\API\Lib\Middleware::authUserOwnsResource',
                            '\SolasMatch\API\V0\Users::userTrackOrganisation'
                        );

                        $app->delete(
                            '/:organisationId/',
                            '\SolasMatch\API\Lib\Middleware::authUserOwnsResource',
                            '\SolasMatch\API\V0\Users::userUnTrackOrganisation'
                        );
                    });

                    /* Routes starting /v0/users/:userId */
                    $app->get(
                        '/filteredClaimedTasks/:orderBy/:limit/:offset/:taskType/:taskStatus(:format)/',
                        '\SolasMatch\API\Lib\Middleware::authUserOwnsResource',
                        '\SolasMatch\API\V0\Users::getFilteredUserClaimedTasks'
                    );

                    $app->get(
                        '/filteredClaimedTasksCount/:taskType/:taskStatus(:format)/',
                        '\SolasMatch\API\Lib\Middleware::authUserOwnsResource',
                        '\SolasMatch\API\V0\Users::getFilteredUserClaimedTasksCount'
                    );
                    
                    $app->get(
                        '/recentTasks/:limit/:offset(:format)/',
                        '\SolasMatch\API\Lib\Middleware::authUserOwnsResource',
                        '\SolasMatch\API\V0\Users::getUserRecentTasks'
                    );
                    
                    $app->get(
                        '/recentTasksCount(:format)/',
                        '\SolasMatch\API\Lib\Middleware::authUserOwnsResource',
                        '\SolasMatch\API\V0\Users::getUserRecentTasksCount'
                    );

                    $app->put(
                        '/requestReference(:format)/',
                        '\SolasMatch\API\Lib\Middleware::authUserOwnsResource',
                        '\SolasMatch\API\V0\Users::userRequestReference'
                    );

                    $app->get(
                        '/realName(:format)/',
                        '\SolasMatch\API\Lib\Middleware::authenticateUserMembership',
                        '\SolasMatch\API\V0\Users::getUserRealName'
                    );

                    $app->get(
                        '/verified(:format)/',
                        '\SolasMatch\API\V0\Users::isUserVerified'
                    );

                    $app->get(
                        '/orgs(:format)/',
                        '\SolasMatch\API\Lib\Middleware::isloggedIn',
                        '\SolasMatch\API\V0\Users::getUserOrgs'
                    );

                    $app->get(
                        '/badges(:format)/',
                        '\SolasMatch\API\Lib\Middleware::isloggedIn',
                        '\SolasMatch\API\V0\Users::getUserbadges'
                    );

                    $app->post(
                        '/badges(:format)/',
                        '\SolasMatch\API\Lib\Middleware::isloggedIn',
                        '\SolasMatch\API\V0\Users::addUserbadges'
                    );

                    $app->get(
                        '/tags(:format)/',
                        '\SolasMatch\API\Lib\Middleware::isloggedIn',
                        '\SolasMatch\API\V0\Users::getUserTags'
                    );

                    $app->post(
                        '/tags(:format)/',
                        '\SolasMatch\API\Lib\Middleware::authUserOwnsResource',
                        '\SolasMatch\API\V0\Users::addUserTag'
                    );

                    $app->get(
                        '/taskStreamNotification(:format)/',
                        '\SolasMatch\API\Lib\Middleware::authUserOwnsResource',
                        '\SolasMatch\API\V0\Users::getUserTaskStreamNotification'
                    );

                    $app->delete(
                        '/taskStreamNotification(:format)/',
                        '\SolasMatch\API\Lib\Middleware::authUserOwnsResource',
                        '\SolasMatch\API\V0\Users::removeUserTaskStreamNotification'
                    );

                    $app->put(
                        '/taskStreamNotification(:format)/',
                        '\SolasMatch\API\Lib\Middleware::authUserOwnsResource',
                        '\SolasMatch\API\V0\Users::updateTaskStreamNotification'
                    );

                    $app->get(
                        '/tasks(:format)/',
                        '\SolasMatch\API\Lib\Middleware::authUserOwnsResource',
                        '\SolasMatch\API\V0\Users::getUserTasks'
                    );

                    $app->get(
                        '/topTasksCount(:format)/',
                        '\SolasMatch\API\Lib\Middleware::isloggedIn',
                        '\SolasMatch\API\V0\Users::getUserTopTasksCount'
                    );
                    
                    $app->get(
                        '/topTasks(:format)/',
                        '\SolasMatch\API\Lib\Middleware::isloggedIn',
                        '\SolasMatch\API\V0\Users::getUserTopTasks'
                    );
                    
                    $app->get(
                        '/archivedTasks/:limit/:offset(:format)/',
                        '\SolasMatch\API\Lib\Middleware::isloggedIn',
                        '\SolasMatch\API\V0\Users::getUserArchivedTasks'
                    );
                    
                    $app->get(
                        '/archivedTasksCount(:format)/',
                        '\SolasMatch\API\Lib\Middleware::isloggedIn',
                        '\SolasMatch\API\V0\Users::getUserArchivedTasksCount'
                    );

                    $app->get(
                        '/trackedTasks(:format)/',
                        '\SolasMatch\API\Lib\Middleware::authUserOwnsResource',
                        '\SolasMatch\API\V0\Users::getUserTrackedTasks'
                    );

                    $app->post(
                        '/trackedTasks(:format)/',
                        '\SolasMatch\API\Lib\Middleware::authUserOwnsResource',
                        '\SolasMatch\API\V0\Users::addUserTrackedTasks'
                    );

                    $app->get(
                        '/projects(:format)/',
                        '\SolasMatch\API\Lib\Middleware::authUserOwnsResource',
                        '\SolasMatch\API\V0\Users::getUserTrackedProjects'
                    );

                    $app->get(
                        '/personalInfo(:format)/',
                        '\SolasMatch\API\Lib\Middleware::authUserOwnsResource',
                        '\SolasMatch\API\V0\Users::getUserPersonalInfo'
                    );

                    $app->post(
                        '/personalInfo(:format)/',
                        '\SolasMatch\API\Lib\Middleware::authUserOwnsResource',
                        '\SolasMatch\API\V0\Users::createUserPersonalInfo'
                    );

                    $app->put(
                        '/personalInfo(:format)/',
                        '\SolasMatch\API\Lib\Middleware::authUserOwnsResource',
                        '\SolasMatch\API\V0\Users::updateUserPersonalInfo'
                    );

                    $app->get(
                        '/secondaryLanguages(:format)/',
                        '\SolasMatch\API\Lib\Middleware::isloggedIn',
                        '\SolasMatch\API\V0\Users::getSecondaryLanguages'
                    );

                    $app->post(
                        '/secondaryLanguages(:format)/',
                        '\SolasMatch\API\Lib\Middleware::authUserOwnsResource',
                        '\SolasMatch\API\V0\Users::createSecondaryLanguage'
                    );

                    $app->get(
                        '/organisations(:format)/',
                        '\SolasMatch\API\Lib\Middleware::authUserOwnsResource',
                        '\SolasMatch\API\V0\Users::getUserTrackedOrganisations'
                    );
                });

                $app->group('/:uuid', function () use ($app) {

                    /* Routes starting /v0/users/:uuid */
                    $app->get(
                        '/registered(:format)/',
                        '\SolasMatch\API\V0\Users::getRegisteredUser'
                    );

                    $app->post(
                        '/finishRegistration(:format)/',
                        '\SolasMatch\API\V0\Users::finishRegistration'
                    );

                    $app->post(
                        '/manuallyFinishRegistration(:format)/',
                        '\SolasMatch\API\V0\Users::finishRegistrationManually'
                    );
                });

                $app->group('/email/:email', function () use ($app) {

                    /* Routes starting /v0/users/email/:email */
                    $app->get(
                        '/passwordResetRequest/time(:format)/',
                        '\SolasMatch\API\V0\Users::getPasswordResetRequestTime'
                    );

                    $app->get(
                        '/passwordResetRequest(:format)/',
                        '\SolasMatch\API\V0\Users::hasUserRequestedPasswordReset'
                    );
                    
                    $app->get(
                        '/getBannedComment(:format)/',
                        '\SolasMatch\API\Lib\Middleware::authenticateIsUserBanned',
                        '\SolasMatch\API\V0\Users::getBannedComment'
                    );

                    $app->post(
                        '/passwordResetRequest(:format)/',
                        '\SolasMatch\API\V0\Users::createPasswordResetRequest'
                    );
                });

                /* Routes starting /v0/users */
                $app->delete(
                    '/removeSecondaryLanguage/:userId/:languageCode/:countryCode/',
                    '\SolasMatch\API\Lib\Middleware::authUserOwnsResource',
                    '\SolasMatch\API\V0\Users::deleteSecondaryLanguage'
                );

                $app->get(
                    '/subscribedToOrganisation/:userId/:organisationId/',
                    '\SolasMatch\API\Lib\Middleware::authUserOwnsResource',
                    '\SolasMatch\API\V0\Users::userSubscribedToOrganisation'
                );

                $app->delete(
                    '/leaveOrg/:userId/:orgId/',
                    '\SolasMatch\API\Lib\Middleware::authUserOrAdminForOrg',
                    '\SolasMatch\API\V0\Users::userLeaveOrg'
                );

                $app->get(
                    '/:email/auth/code(:format)/',
                    '\SolasMatch\API\V0\Users::getAuthCode'
                );

                $app->get(
                    '/subscribedToTask/:userId/:taskId/',
                    '\SolasMatch\API\Lib\Middleware::authUserOwnsResource',
                    '\SolasMatch\API\V0\Users::userSubscribedToTask'
                );

                $app->get(
                    '/subscribedToProject/:userId/:projectId/',
                    '\SolasMatch\API\Lib\Middleware::authUserOwnsResource',
                    '\SolasMatch\API\V0\Users::userSubscribedToProject'
                );

                $app->get(
                    '/isBlacklistedForTask/:userId/:taskId/',
                    '\SolasMatch\API\Lib\Middleware::isloggedIn',
                    '\SolasMatch\API\V0\Users::isBlacklistedForTask'
                );
                
                $app->get(
                        '/isBlacklistedForTaskByAdmin/:userId/:taskId/',
                        '\SolasMatch\API\Lib\Middleware::isloggedIn',
                        '\SolasMatch\API\V0\Users::isBlacklistedForTaskByAdmin'
                );

                $app->put(
                    '/assignBadge/:email/:badgeId/',
                    '\SolasMatch\API\V0\Users::assignBadge'
                );

                $app->get(
                    '/getClaimedTasksCount/:userId/',
                    '\SolasMatch\API\Lib\Middleware::authUserOwnsResource',
                    '\SolasMatch\API\V0\Users::getUserClaimedTasksCount'
                );

                $app->post(
                    '/authCode/login(:format)/',
                    '\SolasMatch\API\V0\Users::getAccessToken'
                );

                $app->post(
                    '/gplus/login(:format)/',
                    '\SolasMatch\API\V0\Users::loginWithGooglePlus'
                );

                $app->get(
                    '/getByEmail/:email/',
                    '\SolasMatch\API\Lib\Middleware::registerValidation',
                    '\SolasMatch\API\V0\Users::getUserByEmail'
                );

                $app->get(
                    '/passwordReset/:key/',
                    '\SolasMatch\API\V0\Users::getResetRequest'
                );

                $app->get(
                    '/getCurrentUser(:format)/',
                    '\SolasMatch\API\V0\Users::getCurrentUser'
                );

                $app->get(
                    '/login(:format)/',
                    '\SolasMatch\API\V0\Users::getLoginTemplate'
                );

                $app->post(
                    '/login(:format)/',
                    '\SolasMatch\API\V0\Users::login'
                );

                $app->get(
                    '/passwordReset(:format)/',
                    '\SolasMatch\API\Lib\Middleware::isloggedIn',
                    '\SolasMatch\API\V0\Users::getResetTemplate'
                );

                $app->post(
                    '/passwordReset(:format)/',
                    '\SolasMatch\API\V0\Users::resetPassword'
                );

                $app->get(
                    '/register(:format)/',
                    '\SolasMatch\API\V0\Users::getRegisterTemplate'
                );

                $app->post(
                    '/register(:format)/',
                    '\SolasMatch\API\V0\Users::register'
                );

                $app->post(
                    '/changeEmail(:format)/',
                    '\SolasMatch\API\V0\Users::changeEmail'
                );

                $app->get(
                    '/:userId/',
                    '\SolasMatch\API\Lib\Middleware::isloggedIn',
                    '\SolasMatch\API\V0\Users::getUser'
                );

                $app->put(
                    '/:userId/',
                    '\SolasMatch\API\Lib\Middleware::authUserOwnsResource',
                    '\SolasMatch\API\V0\Users::updateUser'
                );

                $app->delete(
                    '/:userId/',
                    '\SolasMatch\API\Lib\Middleware::authUserOwnsResource',
                    '\SolasMatch\API\V0\Users::deleteUser'
                );
            });

            /* Routes starting /v0 */
            $app->get(
                '/users(:format)/',
                '\SolasMatch\API\V0\Users::getUsers'
            );
        });
    }

    public static function addUserTrackedTasksById($userId, $taskId, $format = ".json")
    {
        if (!is_numeric($taskId) && strstr($taskId, '.')) {
            $taskId = explode('.', $taskId);
            $format = '.'.$taskId[1];
            $taskId = $taskId[0];
        }
        $data = DAO\UserDao::trackTask($userId, $taskId);
        API\Dispatcher::sendResponse(null, $data, null, $format);
    }

    public static function deleteUserTrackedTasksById($userId, $taskId, $format = ".json")
    {
        if (!is_numeric($taskId) && strstr($taskId, '.')) {
            $taskId = explode('.', $taskId);
            $format = '.'.$taskId[1];
            $taskId = $taskId[0];
        }
        $data = DAO\UserDao::ignoreTask($userId, $taskId);
        API\Dispatcher::sendResponse(null, $data, null, $format);
    }

    public static function deleteUserbadgesByID($userId, $badgeId, $format = ".json")
    {
        if (!is_numeric($badgeId) && strstr($badgeId, '.')) {
            $badgeId = explode('.', $badgeId);
            $format = '.'.$badgeId[1];
            $badgeId = $badgeId[0];
        }
        API\Dispatcher::sendResponse(null, DAO\BadgeDao::removeUserBadge($userId, $badgeId), null, $format);
    }

    public static function addUserbadgesByID($userId, $badgeId, $format = ".json")
    {
        if (!is_numeric($badgeId) && strstr($badgeId, '.')) {
            $badgeId = explode('.', $badgeId);
            $format = '.'.$badgeId[1];
            $badgeId = $badgeId[0];
        }
        API\Dispatcher::sendResponse(null, DAO\BadgeDao::assignBadge($userId, $badgeId), null, $format);
    }

    public static function getUserTags($userId, $format = ".json")
    {
        $limit = API\Dispatcher::clenseArgs('limit', Common\Enums\HttpMethodEnum::GET, null);
        API\Dispatcher::sendResponse(null, DAO\UserDao::getUserTags($userId, $limit), null, $format);
    }

    public static function addUserTag($userId, $format = ".json")
    {
        $data = API\Dispatcher::getDispatcher()->request()->getBody();
        $client = new Common\Lib\APIHelper($format);
        $data = $client->deserialize($data, '\SolasMatch\Common\Protobufs\Models\Tag');
        $data = DAO\UserDao::likeTag($userId, $data->getId());
        if (is_array($data)) {
            $data = $data[0];
        }
        API\Dispatcher::sendResponse(null, $data, null, $format);
    }

    public static function getUserTaskStreamNotification($userId, $format = ".json")
    {
        $data = DAO\UserDao::getUserTaskStreamNotification($userId);
        API\Dispatcher::sendResponse(null, $data, null, $format);
    }

    public static function getUserTaskReview($userId, $taskId, $format = '.json')
    {
        $reviews = DAO\TaskDao::getTaskReviews(null, $taskId, $userId);
        API\Dispatcher::sendResponse(null, $reviews[0], null, $format);
    }

    public static function userUnClaimTask($userId, $taskId, $format = ".json")
    {
        if (strstr($taskId, '.')) {
            $taskId = explode('.', $taskId);
            $format = '.'.$taskId[1];
            $taskId = $taskId[0];
        }
        $feedback = API\Dispatcher::getDispatcher()->request()->getBody();
        $feedback = trim($feedback);
        if ($feedback != '') {
            API\Dispatcher::sendResponse(null, DAO\TaskDao::unClaimTask($taskId, $userId, $feedback), null, $format);
        } else {
            API\Dispatcher::sendResponse(null, DAO\TaskDao::unClaimTask($taskId, $userId), null, $format);
        }
        Lib\Notify::sendTaskRevokedNotifications($taskId, $userId);
    }

    public static function addUserTagById($userId, $tagId, $format = ".json")
    {
        if (!is_numeric($tagId) && strstr($tagId, '.')) {
            $tagId = explode('.', $tagId);
            $format = '.'.$tagId[1];
            $tagId = $tagId[0];
        }
        $data = DAO\UserDao::likeTag($userId, $tagId);
        if (is_array($data)) {
            $data = $data[0];
        }
        API\Dispatcher::sendResponse(null, $data, null, $format);
    }

    public static function deleteUserTagById($userId, $tagId, $format = ".json")
    {
        if (!is_numeric($tagId) && strstr($tagId, '.')) {
            $tagId = explode('.', $tagId);
            $format = '.'.$tagId[1];
            $tagId = $tagId[0];
        }
        $data = DAO\UserDao::removeTag($userId, $tagId);
        if (is_array($data)) {
            $data = $data[0];
        }
        API\Dispatcher::sendResponse(null, $data, null, $format);
    }

    public static function userTrackProject($userId, $projectId, $format = ".json")
    {
        if (!is_numeric($projectId) && strstr($projectId, '.')) {
            $projectId = explode('.', $projectId);
            $format = '.'.$projectId[1];
            $projectId = $projectId[0];
        }
        $data = DAO\UserDao::trackProject($projectId, $userId);
        API\Dispatcher::sendResponse(null, $data, null, $format);
    }

    public static function userUnTrackProject($userId, $projectId, $format = ".json")
    {
        if (!is_numeric($projectId) && strstr($projectId, '.')) {
            $projectId = explode('.', $projectId);
            $format = '.'.$projectId[1];
            $projectId = $projectId[0];
        }
        $data = DAO\UserDao::unTrackProject($projectId, $userId);
        API\Dispatcher::sendResponse(null, $data, null, $format);
    }

    public static function userTrackOrganisation($userId, $organisationId, $format = ".json")
    {
        if (!is_numeric($organisationId) && strstr($organisationId, '.')) {
            $organisationId = explode('.', $organisationId);
            $format = '.'.$organisationId[1];
            $organisationId = $organisationId[0];
        }
        $data = DAO\UserDao::trackOrganisation($userId, $organisationId);
        API\Dispatcher::sendResponse(null, $data, null, $format);
    }

    public static function userUnTrackOrganisation($userId, $organisationId, $format = ".json")
    {
        if (!is_numeric($organisationId) && strstr($organisationId, '.')) {
            $organisationId = explode('.', $organisationId);
            $format = '.'.$organisationId[1];
            $organisationId = $organisationId[0];
        }
        $data = DAO\UserDao::unTrackOrganisation($userId, $organisationId);
        API\Dispatcher::sendResponse(null, $data, null, $format);
    }

    public static function userRequestReference($userId, $format = ".json")
    {
        DAO\UserDao::requestReference($userId);
        API\Dispatcher::sendResponse(null, null, null, $format);
    }

    public static function getUserRealName($userId, $format = '.json')
    {
        API\Dispatcher::sendResponse(null, DAO\UserDao::getUserRealName($userId), null, $format);
    }

    public static function isUserVerified($userId, $format = '.json')
    {
        $ret = DAO\UserDao::isUserVerified($userId);
        API\Dispatcher::sendResponse(null, $ret, null, $format);
    }

    public static function getUserOrgs($userId, $format = ".json")
    {
        API\Dispatcher::sendResponse(null, DAO\UserDao::findOrganisationsUserBelongsTo($userId), null, $format);
    }

    public static function getUserbadges($userId, $format = ".json")
    {
        API\Dispatcher::sendResponse(null, DAO\UserDao::getUserBadges($userId), null, $format);
    }

    public static function addUserbadges($userId, $format = ".json")
    {
        $data = API\Dispatcher::getDispatcher()->request()->getBody();
        $client = new Common\Lib\APIHelper($format);
        $data = $client->deserialize($data, '\SolasMatch\Common\Protobufs\Models\Badge');
        API\Dispatcher::sendResponse(null, DAO\BadgeDao::assignBadge($userId, $data->getId()), null, $format);
    }

    public static function removeUserTaskStreamNotification($userId, $format = ".json")
    {
        $ret = DAO\UserDao::removeTaskStreamNotification($userId);
        API\Dispatcher::sendResponse(null, $ret, null, $format);
    }

    public static function updateTaskStreamNotification($userId, $format = ".json")
    {
        $data = API\Dispatcher::getDispatcher()->request()->getBody();
        $client = new Common\Lib\APIHelper($format);
        $data = $client->deserialize($data, '\SolasMatch\Common\Protobufs\Models\UserTaskStreamNotification');
        $ret = DAO\UserDao::requestTaskStreamNotification($data);
        API\Dispatcher::sendResponse(null, $ret, null, $format);
    }

    public static function getUserTasks($userId, $format = ".json")
    {
        $limit = API\Dispatcher::clenseArgs('limit', Common\Enums\HttpMethodEnum::GET, 10);
        $offset = API\Dispatcher::clenseArgs('offset', Common\Enums\HttpMethodEnum::GET, 0);
        API\Dispatcher::sendResponse(null, DAO\TaskDao::getUserTasks($userId, $limit, $offset), null, $format);
    }

    public static function userClaimTask($userId, $taskId, $format = ".json")
    {
        if (!is_numeric($taskId) && strstr($taskId, '.')) {
            $taskId = explode('.', $taskId);
            $format = '.'.$taskId[1];
            $taskId = $taskId[0];
        }
        API\Dispatcher::sendResponse(null, DAO\TaskDao::claimTask($taskId, $userId), null, $format);
        Lib\Notify::notifyUserClaimedTask($userId, $taskId);
        Lib\Notify::notifyOrgClaimedTask($userId, $taskId);
    }

    public static function getUserTopTasks($userId, $format = ".json")
    {
        $limit = API\Dispatcher::clenseArgs('limit', Common\Enums\HttpMethodEnum::GET, 5);
        $offset = API\Dispatcher::clenseArgs('offset', Common\Enums\HttpMethodEnum::GET, 0);
        $filter = API\Dispatcher::clenseArgs('filter', Common\Enums\HttpMethodEnum::GET, '');
        $strict = API\Dispatcher::clenseArgs('strict', Common\Enums\HttpMethodEnum::GET, false);
        $filters = Common\Lib\APIHelper::parseFilterString($filter);
        $filter = "";
        $taskType = '';
        $sourceLanguageCode = '';
        $targetLanguageCode = '';
        if (isset($filters['taskType']) && $filters['taskType'] != '') {
            $taskType = $filters['taskType'];
        }
        if (isset($filters['sourceLanguage']) && $filters['sourceLanguage'] != '') {
            $sourceLanguageCode = $filters['sourceLanguage'];
        }
        if (isset($filters['targetLanguage']) && $filters['targetLanguage'] != '') {
            $targetLanguageCode = $filters['targetLanguage'];
        }
        $dao = new DAO\TaskDao();
        $data = $dao->getUserTopTasks(
            $userId,
            $strict,
            $limit,
            $offset,
            $taskType,
            $sourceLanguageCode,
            $targetLanguageCode
        );
        API\Dispatcher::sendResponse(null, $data, null, $format);
    }

    public static function getUserTopTasksCount($userId, $format = ".json")
    {
        $filter = API\Dispatcher::clenseArgs('filter', Common\Enums\HttpMethodEnum::GET, '');
        $strict = API\Dispatcher::clenseArgs('strict', Common\Enums\HttpMethodEnum::GET, false);
        $filters = Common\Lib\APIHelper::parseFilterString($filter);
        $filter = "";
        $taskType = '';
        $sourceLanguageCode = '';
        $targetLanguageCode = '';
        if (isset($filters['taskType']) && $filters['taskType'] != '') {
            $taskType = $filters['taskType'];
        }
        if (isset($filters['sourceLanguage']) && $filters['sourceLanguage'] != '') {
            $sourceLanguageCode = $filters['sourceLanguage'];
        }
        if (isset($filters['targetLanguage']) && $filters['targetLanguage'] != '') {
            $targetLanguageCode = $filters['targetLanguage'];
        }
        $dao = new DAO\TaskDao();
        $data = $dao->getUserTopTasksCount(
            $userId,
            $strict,
            $taskType,
            $sourceLanguageCode,
            $targetLanguageCode
        );
        API\Dispatcher::sendResponse(null, $data, null, $format);
    }
    
    
    public static function getFilteredUserClaimedTasks(
            $userId,
            $orderBy,
            $limit,
            $offset,
            $taskType,
            $taskStatus,
            $format = ".json"
    ) {
        if (!is_numeric($taskStatus) && strstr($taskStatus, '.')) {
            $taskStatus = explode('.', $taskStatus);
            $format = '.'.$taskStatus[1];
            $taskStatus = $taskStatus[0];
        }

        API\Dispatcher::sendResponse(
            null,
            DAO\TaskDao::getFilteredUserClaimedTasks(
                $userId,
                $orderBy,
                $limit,
                $offset,
                $taskType,
                $taskStatus
            ),
            null,
            $format
        );
    }

    public static function getFilteredUserClaimedTasksCount(
            $userId,
            $taskType,
            $taskStatus,
            $format = ".json"
    ) {
        if (!is_numeric($taskStatus) && strstr($taskStatus, '.')) {
            $taskStatus = explode('.', $taskStatus);
            $format = '.'.$taskStatus[1];
            $taskStatus = $taskStatus[0];
        }

        API\Dispatcher::sendResponse(
            null,
            DAO\TaskDao::getFilteredUserClaimedTasksCount(
                $userId,
                $taskType,
                $taskStatus
            ),
            null,
            $format
        );
    }
    
    public static function getUserRecentTasks(
            $userId,
            $limit,
            $offset,
            $format = ".json"
    ) {
        if (!is_numeric($offset) && strstr($offset, '.')) {
            $offset = explode('.', $offset);
            $format = '.'.$offset[1];
            $offset = $offset[0];
        }
        API\Dispatcher::sendResponse(
        null,
        DAO\TaskDao::getUserRecentTasks(
        $userId,
        $limit,
        $offset
        ),
        null,
        $format
        );
    }

    public static function getUserRecentTasksCount(
            $userId,
            $format = ".json"
    ) {
        API\Dispatcher::sendResponse(
        null,
        DAO\TaskDao::getUserRecentTasksCount(
        $userId
        ),
        null,
        $format
        );
    }

    public static function getUserArchivedTasks($userId, $limit, $offset, $format = ".json")
    {
        if (!is_numeric($offset) && strstr($offset, '.')) {
            $offset = explode('.', $offset);
            $format = '.'.$offset[1];
            $offset = $offset[0];
        }
        
        $data = DAO\TaskDao::getUserArchivedTasks($userId, $limit, $offset);
        API\Dispatcher::sendResponse(null, $data, null, $format);
    }
    
    public static function getUserArchivedTasksCount($userId, $format = ".json")
    {
        $data = DAO\TaskDao::getUserArchivedTasksCount($userId);
        API\Dispatcher::sendResponse(null, $data, null, $format);
    }

    public static function getUserTrackedTasks($userId, $format = ".json")
    {
        $data = DAO\UserDao::getTrackedTasks($userId);
        API\Dispatcher::sendResponse(null, $data, null, $format);
    }

    public static function addUserTrackedTasks($userId, $format = ".json")
    {
        $data = API\Dispatcher::getDispatcher()->request()->getBody();
        $client = new Common\Lib\APIHelper($format);
        $data = $client->deserialize($data, '\SolasMatch\Common\Protobufs\Models\Task');
        $data = DAO\UserDao::trackTask($userId, $data->getId());
        API\Dispatcher::sendResponse(null, $data, null, $format);
    }

    public static function getUserTrackedProjects($userId, $format = ".json")
    {
        $data = DAO\UserDao::getTrackedProjects($userId);
        API\Dispatcher::sendResponse(null, $data, null, $format);
    }

    public static function getUserPersonalInfo($userId, $format = ".json")
    {
        if (!is_numeric($userId) && strstr($userId, '.')) {
            $userId = explode('.', $userId);
            $format = '.'.$userId[1];
            $userId = $userId[0];
        }
        $data = DAO\UserDao::getPersonalInfo(null, $userId);
        API\Dispatcher::sendResponse(null, $data, null, $format);
    }

    public static function createUserPersonalInfo($userId, $format = ".json")
    {
        if (!is_numeric($userId) && strstr($userId, '.')) {
            $userId = explode('.', $userId);
            $format = '.'.$userId[1];
            $userId = $userId[0];
        }
        $data = API\Dispatcher::getDispatcher()->request()->getBody();
        $client = new Common\Lib\APIHelper($format);
        $data = $client->deserialize($data, "\SolasMatch\Common\Protobufs\Models\UserPersonalInformation");
        API\Dispatcher::sendResponse(null, DAO\UserDao::savePersonalInfo($data), null, $format);
    }

    public static function updateUserPersonalInfo($userId, $format = ".json")
    {
        if (!is_numeric($userId) && strstr($userId, '.')) {
            $userId = explode('.', $userId);
            $format = '.'.$userId[1];
            $userId = $userId[0];
        }
        $data = API\Dispatcher::getDispatcher()->request()->getBody();
        $client = new Common\Lib\APIHelper($format);
        $data = $client->deserialize($data, '\SolasMatch\Common\Protobufs\Models\UserPersonalInformation');
        $data = DAO\UserDao::savePersonalInfo($data);
        API\Dispatcher::sendResponse(null, $data, null, $format);
    }

    public static function getSecondaryLanguages($userId, $format = ".json")
    {
        if (!is_numeric($userId) && strstr($userId, '.')) {
            $userId = explode('.', $userId);
            $format = '.'.$userId[1];
            $userId = $userId[0];
        }
        $data = DAO\UserDao::getSecondaryLanguages($userId);
        API\Dispatcher::sendResponse(null, $data, null, $format);
    }

    public static function createSecondaryLanguage($userId, $format = ".json")
    {
        $data = API\Dispatcher::getDispatcher()->request()->getBody();
        $client = new Common\Lib\APIHelper($format);
        $data = $client->deserialize($data, "\SolasMatch\Common\Protobufs\Models\Locale");
        API\Dispatcher::sendResponse(null, DAO\UserDao::createSecondaryLanguage($userId, $data), null, $format);
    }

    public static function getUserTrackedOrganisations($userId, $format = ".json")
    {
        $data = DAO\UserDao::getTrackedOrganisations($userId);
        API\Dispatcher::sendResponse(null, $data, null, $format);
    }

    public static function getRegisteredUser($uuid, $format = '.json')
    {
        $user = DAO\UserDao::getRegisteredUser($uuid);
        API\Dispatcher::sendResponse(null, $user, null, $format);
    }

    public static function finishRegistration($uuid, $format = '.json')
    {
        $user = DAO\UserDao::getRegisteredUser($uuid);
        if ($user != null) {
            error_log("finishRegistration($uuid) " . $user->getId());
            $ret = DAO\UserDao::finishRegistration($user->getId());
            API\Dispatcher::sendResponse(null, $ret, null, $format);
        } else {
            API\Dispatcher::sendResponse(null, "Invalid UUID", Common\Enums\HttpStatusEnum::UNAUTHORIZED, $format);
        }
    }

    public static function finishRegistrationManually($email, $format = '.json')
    {
        error_log("finishRegistrationManually($email)");
        $ret = DAO\UserDao::finishRegistrationManually($email);
        API\Dispatcher::sendResponse(null, $ret, null, $format);
    }

    public static function getPasswordResetRequestTime($email, $format = ".json")
    {
        $resetRequest = DAO\UserDao::getPasswordResetRequests($email);
        API\Dispatcher::sendResponse(null, $resetRequest->getRequestTime(), null, $format);
    }

    public static function hasUserRequestedPasswordReset($email, $format = ".json")
    {
        $data = DAO\UserDao::hasRequestedPasswordReset($email) ? 1 : 0;
        API\Dispatcher::sendResponse(null, $data, null, $format);
    }

    public static function createPasswordResetRequest($email, $format = ".json")
    {
        $user = DAO\UserDao::getUser(null, $email);
        if ($user) {
            API\Dispatcher::sendResponse(null, DAO\UserDao::createPasswordReset($user), null, $format);
            Lib\Notify::sendPasswordResetEmail($user->getId());
        } else {
            API\Dispatcher::sendResponse(null, null, null, $format);
        }
    }

    public static function deleteSecondaryLanguage($userId, $languageCode, $countryCode, $format = ".json")
    {
        if (strstr($countryCode, '.')) {
            $countryCode = explode('.', $countryCode);
            $format = '.'.$countryCode[1];
            $countryCode = $countryCode[0];
        }
        $data = DAO\UserDao::deleteSecondaryLanguage($userId, $languageCode, $countryCode);
        API\Dispatcher::sendResponse(null, $data, null, $format);
    }

    public static function userSubscribedToOrganisation($userId, $organisationId, $format = ".json")
    {
        if (!is_numeric($organisationId) && strstr($organisationId, '.')) {
            $organisationId = explode('.', $organisationId);
            $format = '.'.$organisationId[1];
            $organisationId = $organisationId[0];
        }
        API\Dispatcher::sendResponse(
            null,
            DAO\UserDao::isSubscribedToOrganisation($userId, $organisationId),
            null,
            $format
        );
    }

    public static function userLeaveOrg($userId, $orgId, $format = ".json")
    {
        if (!is_numeric($orgId) && strstr($orgId, '.')) {
            $orgId = explode('.', $orgId);
            $format = '.'.$orgId[1];
            $orgId = $orgId[0];
        }
        $data = DAO\OrganisationDao::revokeMembership($orgId, $userId);
        if (is_array($data)) {
            $data = $data[0];
        }
        API\Dispatcher::sendResponse(null, $data, null, $format);
    }

    public static function getAuthCode($email, $format = '.json')
    {
        $user = DAO\UserDao::getUser(null, $email);
        if (!$user) {
            error_log("apiRegister($email) in getAuthCode()");
            DAO\UserDao::apiRegister($email, md5($email), false);
            $user = DAO\UserDao::getUser(null, $email);
            DAO\UserDao::finishRegistration($user->getId());
            //Set new user's personal info to show their preferred language as English.
            $newUser = DAO\UserDao::getUser(null, $user->getEmail());
            $userInfo = new Common\Protobufs\Models\UserPersonalInformation();
            $english = DAO\LanguageDao::getLanguage(null, "en");
            $userInfo->setUserId($newUser->getId());
            $userInfo->setLanguagePreference($english->getId());
            $personal_info = DAO\UserDao::savePersonalInfo($userInfo);
            self::update_user_with_neon_data($newUser, $personal_info);
        }
        $params = array();
        try {
            if (DAO\AdminDao::isUserBanned($user->getId())) {
                throw new \Exception("User is banned");
            }
            $server = API\Dispatcher::getOauthServer();
            $authCodeGrant = $server->getGrantType('authorization_code');
            $params = $authCodeGrant->checkAuthoriseParams();
            $authCode = $authCodeGrant->newAuthoriseRequest('user', $user->getId(), $params);
        } catch (\Exception $e) {
            DAO\UserDao::logLoginAttempt($user->getId(), $email, 0);
            if (!isset($params['redirect_uri'])) {
                API\Dispatcher::getDispatcher()->redirect(
                    API\Dispatcher::getDispatcher()->request()->getReferrer().
                    "?error=auth_failed&error_message={$e->getMessage()}"
                );
            } else {
                API\Dispatcher::getDispatcher()->redirect(
                    $params['redirect_uri']."?error=auth_failed&error_message={$e->getMessage()}"
                );
            }
        }
        API\Dispatcher::getDispatcher()->redirect($params['redirect_uri']."?code=$authCode");
    }

    public static function userSubscribedToTask($userId, $taskId, $format = ".json")
    {
        if (!is_numeric($taskId) && strstr($taskId, '.')) {
            $taskId = explode('.', $taskId);
            $format = '.'.$taskId[1];
            $taskId = $taskId[0];
        }
        API\Dispatcher::sendResponse(null, DAO\UserDao::isSubscribedToTask($userId, $taskId), null, $format);
    }

    public static function userSubscribedToProject($userId, $projectId, $format = ".json")
    {
        if (!is_numeric($projectId) && strstr($projectId, '.')) {
            $projectId = explode('.', $projectId);
            $format = '.'.$projectId[1];
            $projectId = $projectId[0];
        }
        API\Dispatcher::sendResponse(null, DAO\UserDao::isSubscribedToProject($userId, $projectId), null, $format);
    }

    public static function isBlacklistedForTask($userId, $taskId, $format = ".json")
    {
        if (!is_numeric($taskId) && strstr($taskId, '.')) {
            $taskId = explode('.', $taskId);
            $format = '.'.$taskId[1];
            $taskId = $taskId[0];
        }
        API\Dispatcher::sendResponse(null, DAO\UserDao::isBlacklistedForTask($userId, $taskId), null, $format);
    }
    
    public static function isBlacklistedForTaskByAdmin($userId, $taskId, $format = ".json")
    {
        if (!is_numeric($taskId) && strstr($taskId, '.')) {
            $taskId = explode('.', $taskId);
            $format = '.'.$taskId[1];
            $taskId = $taskId[0];
        }
        API\Dispatcher::sendResponse(null, DAO\UserDao::isBlacklistedForTaskByAdmin($userId, $taskId), null, $format);
    }

    public static function assignBadge($email, $badgeId, $format = ".json")
    {
        if (!is_numeric($badgeId) && strstr($badgeId, '.')) {
            $badgeId = explode('.', $badgeId);
            $format = '.'.$badgeId[1];
            $badgeId = $badgeId[0];
        }
        $ret = false;
        $user = DAO\UserDao::getUser(null, $email);
        $ret = DAO\BadgeDao::assignBadge($user->getId(), $badgeId);
        API\Dispatcher::sendResponse(null, $ret, null, $format);
    }

    public static function getUserClaimedTasksCount($userId, $format = '.json')
    {
        if (!is_numeric($userId) && strstr($userId, '.')) {
            $userId = explode('.', $userId);
            $format = '.'.$userId[1];
            $userId = $userId[0];
        }
        $data = DAO\TaskDao::getUserTasksCount($userId);
        API\Dispatcher::sendResponse(null, $data, null, $format);
    }
    
    
    public static function loginWithGooglePlus($format = '.json')
    {
        try {
            $data = API\Dispatcher::getDispatcher()->request()->getBody();
            $parsed_data = array();
            parse_str($data, $parsed_data);
            $access_token = $parsed_data['token'];
            
            //validate token
            $client = new Common\Lib\APIHelper("");
            $request =  Common\Lib\Settings::get('googlePlus.token_validation_endpoint');
            $args = null;
            if ($access_token) {
                $args = array("access_token" =>  $access_token );
            } 
            
            $ret = $client->externalCall(
                null,
                $request,
                Common\Enums\HttpMethodEnum::GET,
                null,
                $args
            );

            $response = json_decode($ret);
            $email = "";
            if(isset($response->audience))
            {
                $client_id = Common\Lib\Settings::get('googlePlus.client_id'); 
                if ($client_id != $response->audience)
                {
                    throw new \Exception("Received token is not intended for this application.");
                } else {
                    if (isset($response->email))
                    {
                        $email = $response->email;
                    } else {
                        //see https://developers.google.com/accounts/docs/OAuth2UserAgent#callinganapi
                        $request = Common\Lib\Settings::get('googlePlus.userinfo_endpoint');
                        $ret = $client->externalCall(
                            null,
                            $request,
                            Common\Enums\HttpMethodEnum::GET,
                            null,
                            null,
                            null,
                            $access_token
                        );
                        $userInfo = json_decode($ret);
                        $email = $userInfo->email;
                    }
                }
            }
    
            if (empty($email)) {
                throw new \Exception("Unable to obtain user's email address from Google.");
            } else {
                 API\Dispatcher::sendResponse(null, $email, null, $format, null);    
            }

        } catch (\Exception $e) {
            API\Dispatcher::sendResponse(null, $e->getMessage(), Common\Enums\HttpStatusEnum::BAD_REQUEST, $format);
        }
    }

    public static function getAccessToken($format = '.json')
    {
        try {
            $server = API\Dispatcher::getOauthserver();
            $authCodeGrant = $server->getGrantType('authorization_code');
            $accessToken = $authCodeGrant->completeFlow();

            $oAuthToken = new Common\Protobufs\Models\OAuthResponse();
            $oAuthToken->setToken($accessToken['access_token']);
            $oAuthToken->setTokenType($accessToken['token_type']);
            $oAuthToken->setExpires($accessToken['expires']);
            $oAuthToken->setExpiresIn($accessToken['expires_in']);

            $user = DAO\UserDao::getLoggedInUser($accessToken['access_token']);
            $user->setPassword("");
            $user->setNonce("");

            DAO\UserDao::logLoginAttempt($user->getId(), $user->getEmail(), 1);

            API\Dispatcher::sendResponse(null, $user, null, $format, $oAuthToken);
        } catch (\Exception $e) {
            API\Dispatcher::sendResponse(null, $e->getMessage(), Common\Enums\HttpStatusEnum::BAD_REQUEST, $format);
        }
    }

    public static function getUserByEmail($email, $format = ".json")
    {
        if (!is_numeric($email) && strstr($email, '.')) {
            $temp = array();
            $temp = explode('.', $email);
            $lastIndex = sizeof($temp)-1;
            if ($lastIndex > 0) {
                $email = $temp[0];
                for ($i = 1; $i < $lastIndex; $i++) {
                    $email = "{$email}.{$temp[$i]}";
                }
                if ($temp[$lastIndex] != "json") {
                    $email = "{$email}.{$temp[$lastIndex]}";
                }
            }
        }
        $data = DAO\UserDao::getUser(null, $email);
        API\Dispatcher::sendResponse(null, $data, null, $format);
    }

    public static function getResetRequest($key, $format = ".json")
    {
        if (!is_numeric($key) && strstr($key, '.')) {
            $key = explode('.', $key);
            $format = '.'.$key[1];
            $key = $key[0];
        }
        $data = DAO\UserDao::getPasswordResetRequests(null, $key);
        API\Dispatcher::sendResponse(null, $data, null, $format);
    }

    public static function getCurrentUser($format = ".json")
    {
        $user = DAO\UserDao::getLoggedInUser();
        API\Dispatcher::sendResponse(null, $user, null, $format);
    }

    public static function getLoginTemplate($format = ".json")
    {
        $data = new Common\Protobufs\Models\Login();
        $data->setEmail("sample@example.com");
        $data->setPassword("sample_password");
        API\Dispatcher::sendResponse(null, $data, null, $format);
    }

    public static function login($format = ".json")
    {
        $body = API\Dispatcher::getDispatcher()->request()->getBody();
        $client = new Common\Lib\APIHelper($format);
        $loginData = $client->deserialize($body, "\SolasMatch\Common\Protobufs\Models\Login");
        $params = array();
        $params['client_id'] = API\Dispatcher::clenseArgs('client_id', Common\Enums\HttpMethodEnum::GET, null);
        $params['client_secret'] = API\Dispatcher::clenseArgs('client_secret', Common\Enums\HttpMethodEnum::GET, null);
        $params['username'] = $loginData->getEmail();
        $params['password'] = $loginData->getPassword();
        try {
            $server = API\Dispatcher::getOauthServer();
            $response = $server->getGrantType('password')->completeFlow($params);
            $oAuthResponse = new Common\Protobufs\Models\OAuthResponse();
            $oAuthResponse->setToken($response['access_token']);
            $oAuthResponse->setTokenType($response['token_type']);
            $oAuthResponse->setExpires($response['expires']);
            $oAuthResponse->setExpiresIn($response['expires_in']);

            $user = DAO\UserDao::getLoggedInUser($response['access_token']);
            $user->setPassword("");
            $user->setNonce("");
            API\Dispatcher::sendResponse(null, $user, null, $format, $oAuthResponse);
        } catch (Common\Exceptions\SolasMatchException $e) {
            API\Dispatcher::sendResponse(null, $e->getMessage(), $e->getCode(), $format);
        } catch (\Exception $e) {
            API\Dispatcher::sendResponse(null, $e->getMessage(), Common\Enums\HttpStatusEnum::UNAUTHORIZED, $format);
        }
    }

    public static function getResetTemplate($format = ".json")
    {
        $data = Common\Lib\ModelFactory::buildModel("PasswordReset", array());
        API\Dispatcher::sendResponse(null, $data, null, $format);
    }

    public static function resetPassword($format = ".json")
    {
        $data = API\Dispatcher::getDispatcher()->request()->getBody();
        $client = new Common\Lib\APIHelper($format);
        $data = $client->deserialize($data, '\SolasMatch\Common\Protobufs\Models\PasswordReset');
        $result = DAO\UserDao::passwordReset($data->getPassword(), $data->getKey());
        API\Dispatcher::sendResponse(null, $result, null, $format);
    }

    public static function getRegisterTemplate($format = ".json")
    {
        $data = new Common\Protobufs\Models\Register();
        $data->setPassword("test");
        $data->setEmail("test@test.rog");
        API\Dispatcher::sendResponse(null, $data, null, $format);
    }

    public static function register($format = ".json")
    {
        $data = API\Dispatcher::getDispatcher()->request()->getBody();
        $client = new Common\Lib\APIHelper($format);
        $data = $client->deserialize($data, "\SolasMatch\Common\Protobufs\Models\Register");
        error_log("apiRegister() in register() " . $data->getEmail());
        $registered = DAO\UserDao::apiRegister($data->getEmail(), $data->getPassword());
        //Set new user's personal info to show their preferred language as English.
        $newUser = DAO\UserDao::getUser(null, $data->getEmail());
        $userInfo = new Common\Protobufs\Models\UserPersonalInformation();
        $english = DAO\LanguageDao::getLanguage(null, "en");
        $userInfo->setUserId($newUser->getId());
        $userInfo->setLanguagePreference($english->getId());
        $personal_info = DAO\UserDao::savePersonalInfo($userInfo);
        self::update_user_with_neon_data($newUser, $personal_info);
        
        API\Dispatcher::sendResponse(null, $registered, null, $format);
    }

    public static function update_user_with_neon_data($newUser, $userInfo)
    {
$from_neon_to_trommons_pair = array(
'Afrikaans' => array('af', 'ZA'),
'Albanian' => array('sq', 'AL'),
'Amharic' => array('am', 'AM'),
'Arabic' => array('ar', 'SA'),
'Aragonese' => array('an', 'ES'),
'Armenian' => array('hy', 'AM'),
'Asturian' => array('ast', 'ES'),
'Azerbaijani' => array('az', 'AZ'),
'Basque' => array('eu', 'ES'),
'Bengali' => array('bn', 'IN'),
'Belarus' => array('be', 'BY'),
'Belgian French' => array('fr', 'BE'),
'Bosnian' => array('bs', 'BA'),
'Breton' => array('br', 'FR'),
'Bulgarian' => array('bg', 'BG'),
'Burmese' => array('my', 'MM'),
'Catalan' => array('ca', 'ES'),
'Catalan Valencian' => array('ca', '--'),
'Cebuano' => array('cb', 'PH'),
'Chinese Simplified' => array('zh', 'CN'),
'Chinese Traditional' => array('zh', 'TW'),
'Croatian' => array('hr', 'HR'),
'Czech' => array('cs', 'CZ'),
'Danish' => array('da', 'DK'),
'Dutch' => array('nl', 'NL'),
'English' => array('en', 'GB'),
'English US' => array('en', 'US'),
'Esperanto' => array('eo', '--'),
'Estonian' => array('et', 'EE'),
'Faroese' => array('fo', 'FO'),
'Fula' => array('ff', '--'),
'Finnish' => array('fi', 'FI'),
'Flemish' => array('nl', 'BE'),
'French' => array('fr', 'FR'),
'French Canada' => array('fr', 'CA'),
'Galician' => array('gl', 'ES'),
'Georgian' => array('ka', 'GE'),
'German' => array('de', 'DE'),
'Greek' => array('el', 'GR'),
'Gujarati' => array('gu', 'IN'),
'Haitian Creole French' => array('ht', 'HT'),
'Hausa' => array('ha', '--'),
'Hawaiian' => array('haw', 'US'),
'Hebrew' => array('he', 'IL'),
'Hindi' => array('hi', 'IN'),
'Hungarian' => array('hu', 'HU'),
'Icelandic' => array('is', 'IS'),
'Indonesian' => array('id', 'ID'),
'Irish Gaelic' => array('ga', 'IE'),
'Italian' => array('it', 'IT'),
'Japanese' => array('ja', 'JP'),
'Kanuri' => array('kr', '--'),
'Kazakh' => array('kk', 'KZ'),
'Khmer' => array('km', 'KH'),
'Korean' => array('ko', 'KR'),
'Kurdish Kurmanji' => array('ku', '--'),
'Kurdish Sorani' => array('ku', '--'),
'Kyrgyz' => array('ky', 'KG'),
'Latvian' => array('lv', 'LV'),
'Lingala' => array('ln', '--'),
'Lithuanian' => array('lt', 'LT'),
'Macedonian' => array('mk', 'MK'),
'Malagasy' => array('mg', 'MG'),
'Malay' => array('ms', 'MY'),
'Malayalam' => array('ml', 'IN'),
'Maltese' => array('mt', 'MT'),
'Maori' => array('mi', 'NZ'),
'Mongolian' => array('mn', 'MN'),
'Montenegrin' => array('sr', 'ME'),
'Ndebele' => array('nr', 'ZA'),
'Nepali' => array('ne', 'NP'),
'Norwegian Bokmål' => array('no', 'NO'),
'Norwegian Nynorsk' => array('nn', 'NO'),
'Nyanja' => array('ny', '--'),
'Occitan' => array('oc', 'FR'),
'Occitan Aran' => array('oc', 'ES'),
'Oriya' => array('or', 'IN'),
'Panjabi' => array('pa', 'IN'),
'Pashto' => array('ps', 'PK'),
'Dari' => array('prs', '--'),
'Persian' => array('fa', 'IR'),
'Polish' => array('pl', 'PL'),
'Portuguese' => array('pt', 'PT'),
'Portuguese Brazil' => array('pt', 'BR'),
'Quechua' => array('qu', '--'),
'Rohingya' => array('rhg', 'MM'),
'Rohingyalish' => array('rhl', 'MM'),
'Romanian' => array('ro', 'RO'),
'Russian' => array('ru', 'RU'),
'Serbian Latin' => array('sr', '--'),
'Serbian Cyrillic' => array('sr', 'RS'),
'Sesotho' => array('nso', 'ZA'),
'Setswana (South Africa)' => array('tn', 'ZA'),
'Slovak' => array('sk', 'SK'),
'Slovenian' => array('sl', 'SI'),
'Somali' => array('so', 'SO'),
'Spanish' => array('es', 'ES'),
'Spanish Latin America' => array('es', 'MX'),
'Spanish Colombia' => array('es', 'CO'),
'Swahili' => array('swh', 'KE'),
'Swedish' => array('sv', 'SE'),
'Swiss German' => array('de', 'CH'),
'Tagalog' => array('tl', 'PH'),
'Tamil' => array('ta', 'IN'),
'Telugu' => array('te', 'IN'),
'Tatar' => array('tt', 'RU'),
'Thai' => array('th', 'TH'),
'Tigrinya' => array('ti', '--'),
'Tsonga' => array('ts', 'ZA'),
'Turkish' => array('tr', 'TR'),
'Turkmen' => array('tk', 'TM'),
'Ukrainian' => array('uk', 'UA'),
'Urdu' => array('ur', 'PK'),
'Uzbek' => array('uz', 'UZ'),
'Vietnamese' => array('vi', 'VN'),
'Welsh' => array('cy', 'GB'),
'Xhosa' => array('xh', 'ZA'),
'Yoruba' => array('yo', 'NG'),
'Zulu' => array('zu', 'ZA'),
'Hmong' => array('hmn', 'CN'),
'Karen' => array('kar', 'MM'),
'Rundi' => array('run', 'BI'),
'Assamese' => array('asm', 'IN'),
'Garo' => array('grt', 'IN'),
'Khasi' => array('kha', 'IN'),
'Konkani' => array('kok', 'IN'),
'Manipuri' => array('mni', 'IN'),
'Mizo' => array('lus', 'IN'),
'Chadian Arabic' => array('shu', 'TD'),
'Kamba' => array('kam', 'KE'),
'Margi' => array('mrt', 'NG'),
'Borana' => array('gax', 'KE'),
'Meru' => array('mer', 'KE'),
'Kalenjin' => array('kln', 'KE'),
'Luo' => array('luo', 'KE'),
'Kikuyu' => array('ki', 'KE'),
'Maa' => array('cma', 'KE'),
'Mijikenda' => array('nyf', 'KE'),
'Luhya' => array('luy', 'KE'),
'Kisii' => array('guz', 'KE'),

'Fijian' => array('fj', 'FJ'),
'Bislama' => array('bi', 'VU'),
'Tok Pisin' => array('tpi', 'PG'),
'Tongan' => array('ton', 'TO'),

'Saint Lucian Creole French' => array('acf', 'LC'),
'acf' => 'acf-LC',

'Antigua and Barbuda Creole English' => array('aig', 'AG'),
'aig' => 'aig-AG',

'Bahamas Creole English' => array('bah', 'BS'),
'bah' => 'bah-BS',

'Bemba' => array('bem', 'ZM'),
'bem' => 'bem-ZM',

'Bajan' => array('bjs', 'BB'),
'bjs' => 'bjs-BB',

Tibetan bo  bod-CN
'' => array('', ''),
'' => '-',

Chamorro  ch  cha-GU
'' => array('', ''),
'' => '-',

Coptic  cop cop-XNA
'' => array('', ''),
'' => '-',

'Seselwa Creole French' => array('crs', 'SC'),
'crs' => 'crs-SC',

Maldivian dv  div-DV
'' => array('', ''),
'' => '-',

Dzongkha  dz  dzo-BT
'' => array('', ''),
'' => '-',

Jamaican Creole English en  jam-JM
'' => array('', ''),
'' => '-',

'Filipino' => array('fil', 'PH'),
'fil' => 'fil-',

Fanagalo  fn  fn-FNG
'' => array('', ''),
'' => '-',

'Grenadian Creole English' => array('gcl', 'GD'),
'gcl' => 'gcl-GD',

'Manx Gaelic' => array('gv', 'IM'),
'gv' => 'gv-IM',

'Guyanese Creole English' => array('gyn', 'GY'),
'gyn' => 'gyn-',

'Kabylian' => array('kab', 'DZ'),
'kab' => 'kab-DZ',

'Kabuverdianu' => array('kea', 'CV'),
'kea' => 'kea-CV',

Inuktitut Greenlandic kl  kal-GL
'' => array('', ''),
'' => '-',

Mende men men-MEN
'' => array('', ''),
'' => '-',

'Morisyen' => array('mfe', 'MU'),
'mfe' => 'mfe-',

'Marshallese' => array('mh', 'MH'),
'mh' => 'mh-MH',

'Niuean' => array('niu', 'NIU'),
'niu' => 'niu-',

Norwegian no  nb-NO
'' => array('', ''),
'' => '-',
[[[
        {
            "localized":[{
                "en":"Norwegian"
            }],
            "isocode":"no",
            "enabled":true,
            "rtl":false,
            "rfc3066code":"no-NO"
        }
    ,
        {
            "localized":[{
                "en":"Norwegian Bokmål"
            }],
            "isocode":"nb",
            "enabled":true,
            "rtl":false,
            "rfc3066code":"nb-NO"
        }
    ,
        {
            "localized":[{
                "en":"Norwegian Nynorsk"
            }],
            "isocode":"nn",
            "enabled":true,
            "rtl":false,
            "rfc3066code":"nn-NO"
        }
]]]

'ory' => array('ory', 'IN'),
'Odia' => 'ory-IN',

'Palauan' => array('pau', 'PW'),
'pau' => 'pau-PW',

!!'PIS' not a Trommons contry
'Pijin' => array('pis', 'PIS'),
'pis' => 'pis-PIS',

'Potawatomi' => array('pot', 'US'),
'pot' => 'pot-US',

!!'POV' not a trmmons country
'Crioulo Upper Guinea' => array('', 'POV'),
'pov' => 'pov-POV',

Uma ppk ps-PK
'' => array('', ''),
'' => '-',

'Balkan Gipsy' => array('rm', 'RO'),
'rm' => 'rm-RO',

Kirundi rn  run-RN
'' => array('', ''),
'' => '-',

'KIN' not a trommons country
'Kinyarwanda' => array('rw', 'KIN'),
'rw' => 'rw-KIN',
[[
('RWANDA','RW')
]]

'Sango' => array('sg', 'SG'),
'sg' => 'sg-SG',

Samoan  sm  smo-WS
'' => array('', ''),
'' => '-',

Shona sn  sna-ZW
'' => array('', ''),
'' => '-',

Sranan Tongo  srn srn-SRN
'' => array('', ''),
'' => '-',

'Sotho Southern' => array('st', 'ST'),
'st' => 'st-ST',

'Vincentian Creole English' => array('svc', 'VC'),
'svc' => 'svc-VC',

'Syriac (Aramaic)' => array('syc', 'TR'),
'syc' => 'syc-TR',

NO 'TET' Country
'Tetum' => array('tet', 'TET'),
'tet' => 'tet-',

NO 'TKL' country
'Tokelauan' => array('tkl', 'TKL'),
'tkl' => 'tkl-TKL',

'Tamashek (Tuareg)' => array('tmh', 'DZ'),
'tmh' => 'tmh-DZ',

Tswana  tn  tsn-BW
'' => array('', ''),
'' => '-',

'Tuvaluan' => array('tvl', 'TV'),
'tvl' => 'tvl-TVL',

'Virgin Islands Creole English' => array('vic', 'US'),
'vic' => 'vic-US',

NO 'WLS' Country
'Wallisian' => array('wls', 'WLS'),
'wls' => 'wls-',

'Wolof' => array('wo', 'SN'),
'wo' => 'wo-SN',

Classical Greek XNA grc-GR
'' => array('', ''),
'' => '-',

Mistake
'Comorian Ngazidja' => array('zdj', 'KM'),
'zdj' => 'zdj-KM',

'Chinese Trad. (Hong Kong)' => array('zh', 'HK'),
'zh' => 'zh-HK',

insert into Languages ( `en-name`,`code`) values ('Ghotuo','aaa'),('AlumuTesu','aab'),('Ari','aac'),('Amal','aad'),('Aranadan','aaf'),('Ambrak','aag'),('ArifamaMiniafia','aai'),('Ankave','aak'),('Afade','aal'),('Aramanik','aam'),('Algerian Saharan Arabic','aao'),('Eastern Abnaki','aaq'),('Afar','aa'),('Arvanitika Albanian','aat'),('Abau','aau'),('Solong','aaw'),('Mandobo Atas','aax'),('Aariya','aay'),('Amarasi','aaz'),('Bankon','abb'),('Ambala Ayta','abc'),('Manide','abd'),('Western Abnaki','abe'),('Abai Sungai','abf'),('Abaga','abg'),('Tajiki Arabic','abh'),('Abidji','abi'),('AkaBea','abj'),('Abkhazian','ab'),('Lampung Nyo','abl'),('Abanyom','abm'),('Abua','abn'),('Abon','abo'),('Abellen Ayta','abp'),('Abaza','abq'),('Abron','abr'),('Ambonese Malay','abs'),('Ambulas','abt'),('Abure','abu'),('Baharna Arabic','abv'),('Pal','abw'),('Inabaknon','abx'),('Aneme Wake','aby'),('Abui','abz'),('Achagua','aca'),('Gikyode','acd'),('Achinese','ace'),('Saint Lucian Creole French','acf'),('Acoli','ach'),('AkaCari','aci'),('AkaKora','ack'),('AkarBale','acl'),('Mesopotamian Arabic','acm'),('Achang','acn'),('Eastern Acipa','acp'),('Achi','acr'),('Achterhoeks','act'),('AchuarShiwiar','acu'),('Achumawi','acv'),('Hijazi Arabic','acw'),('Omani Arabic','acx'),('Cypriot Arabic','acy'),('Acheron','acz'),('Adangme','ada'),('Adabe','adb'),('Dzodinka','add'),('Adele','ade'),('Dhofari Arabic','adf'),('Andegerebinha','adg'),('Adhola','adh'),('Adi','adi'),('Adioukrou','adj'),('Galo','adl'),('Adang','adn'),('Abu','ado'),('Adap','adp'),('Adangbe','adq'),('Adonara','adr'),('Adamorobe Sign Language','ads'),('Adnyamathanha','adt'),('Aduge','adu'),('Amundava','adw'),('Amdo Tibetan','adx'),('Adyghe','ady'),('Adzera','adz'),('Areba','aea'),('Tunisian Arabic','aeb'),('Saidi Arabic','aec'),('Argentine Sign Language','aed'),('Northeast Pashayi','aee'),('Haeke','aek'),('Ambele','ael'),('Arem','aem'),('Armenian Sign Language','aen'),('Aer','aeq'),('Eastern Arrernte','aer'),('Alsea','aes'),('Akeu','aeu'),('Ambakich','aew'),('Amerax','aex'),('Amele','aey'),('Aeka','aez'),('Gulf Arabic','afb'),('Andai','afd'),('Putukwam','afe'),('Afghan Sign Language','afg'),('Afrihili','afh'),('Akrukay','afi'),('Nanubae','afk'),('Defaka','afn'),('Eloyi','afo'),('Tapei','afp'),('Afrikaans','af'),('AfroSeminole Creole','afs'),('Afitti','aft'),('Awutu','afu'),('Obokuitai','afz'),('Aguano','aga'),('Legbo','agb'),('Agatu','agc'),('Agarabi','agd'),('Angal','age'),('Arguni','agf'),('Angor','agg'),('Ngelima','agh'),('Agariya','agi'),('Argobba','agj'),('Isarog Agta','agk'),('Fembe','agl'),('Angaataha','agm'),('Agutaynen','agn'),('Tainae','ago'),('Paranan','agp'),('Aghem','agq'),('Aguaruna','agr'),('Esimbi','ags'),('Central Cagayan Agta','agt'),('Aguacateco','agu'),('Remontado Dumagat','agv'),('Kahua','agw'),('Aghul','agx'),('Southern Alta','agy'),('Mt Iriga Agta','agz'),('Ahanta','aha'),('Axamb','ahb'),('Ahe','ahe'),('Qimant','ahg'),('Aghu','ahh'),('Tiagbamrin Aizi','ahi'),('Akha','ahk'),('Igo','ahl'),('Mobumrin Aizi','ahm'),('Ahom','aho'),('Aproumu Aizi','ahp'),('Ahirani','ahr'),('Ashe','ahs'),('Ahtena','aht'),('Arosi','aia'),('Ainu China','aib'),('Ainbai','aic'),('Alngith','aid'),('Amara','aie'),('Agi','aif'),('Antigua and Barbuda Creole English','aig'),('AiCham','aih'),('Assyrian NeoAramaic','aii'),('Lishanid Noshan','aij'),('Ake','aik'),('Aimele','ail'),('Aimol','aim'),('Ainu Japan','ain'),('Aiton','aio'),('Burumakok','aip'),('Aimaq','aiq'),('Airoran','air'),('Nataoran Amis','ais'),('Arikem','ait'),('Aari','aiw'),('Aighon','aix'),('Ali','aiy'),('Aja Sudan','aja'),('Aja Benin','ajg'),('Andajin','ajn'),('South Levantine Arabic','ajp'),('JudeoTunisian Arabic','ajt'),('JudeoMoroccan Arabic','aju'),('Ajawa','ajw'),('Amri Karbi','ajz'),('Akan','ak'),('Batak Angkola','akb'),('Mpur','akc'),('UkpetEhom','akd'),('Akawaio','ake'),('Akpa','akf'),('Anakalangu','akg'),('Angal Heneng','akh'),('Aiome','aki'),('AkaJeru','akj'),('Akkadian','akk'),('Aklanon','akl'),('AkaBo','akm'),('Amikoana','akn'),('Akurio','ako'),('Siwu','akp'),('Ak','akq'),('Araki','akr'),('Akaselem','aks'),('Akolet','akt'),('Akum','aku'),('Akhvakh','akv'),('Akwa','akw'),('AkaKede','akx'),('AkaKol','aky'),('Alabama','akz'),('Alago','ala'),('Albanian','sq'),('Qawasqar','alc'),('Alladian','ald'),('Aleut','ale'),('Alege','alf'),('Alawa','alh'),('Amaimon','ali'),('Alangan','alj'),('Alak','alk'),('Allar','all'),('Amblong','alm'),('Gheg Albanian','aln'),('LarikeWakasihu','alo'),('Alune','alp'),('Algonquin','alq'),('Alutor','alr'),('Tosk Albanian','als'),('Southern Altai','alt'),('Amol','alx'),('Alyawarr','aly'),('Alur','alz'),('Ambo','amb'),('Amahuaca','amc'),('HamerBanna','amf'),('Amurdak','amg'),('Amharic','am'),('Amis','ami'),('Amdang','amj'),('Ambai','amk'),('WarJaintia','aml'),('Ama Papua New Guinea','amm'),('Amanab','amn'),('Amo','amo'),('Alamblak','amp'),('Amahai','amq'),('Amarakaeri','amr'),('Southern AmamiOshima','ams'),('Amto','amt'),('Guerrero Amuzgo','amu'),('Ambelau','amv'),('Western NeoAramaic','amw'),('Anmatyerre','amx'),('Ami','amy'),('Atampaya','amz'),('Andaqui','ana'),('Andoa','anb'),('Ngas','anc'),('Ansus','and'),('Animere','anf'),('Old English ca 4501100','ang'),('Nend','anh'),('Andi','ani'),('Anor','anj'),('Goemai','ank'),('AnuHkongso Chin','anl'),('Anal','anm'),('Obolo','ann'),('Andoque','ano'),('Angika','anp'),('Jarawa India','anq'),('Andh','anr'),('Anserma','ans'),('Antakarinya','ant'),('Anuak','anu'),('Denya','anv'),('Anaang','anw'),('AndraHus','anx'),('Anyin','any'),('Anem','anz'),('Angolar','aoa'),('Abom','aob'),('Pemon','aoc'),('Andarum','aod'),('Angal Enen','aoe'),('Bragat','aof'),('Angoram','aog'),('Arma','aoh'),('Anindilyakwa','aoi'),('Mufian','aoj'),('Alor','aol'),('Bumbita Arapesh','aon'),('Aore','aor'),('Taikat','aos'),('Atorada','aox'),('Uab Meto','aoz'),('North Levantine Arabic','apc'),('Sudanese Arabic','apd'),('Bukiyip','ape'),('Pahanan Agta','apf'),('Ampanang','apg'),('Athpariya','aph'),('Jicarilla Apache','apj'),('Kiowa Apache','apk'),('Lipan Apache','apl'),('MescaleroChiricahua Apache','apm'),('Ambul','apo'),('Apma','app'),('APucikwar','apq'),('AropLokep','apr'),('AropSissano','aps'),('Apatani','apt'),('Alapmunte','apv'),('Western Apache','apw'),('Aputai','apx'),('Safeyoka','apz'),('Archi','aqc'),('Ampari Dogon','aqd'),('Arigidi','aqg'),('Atohwaim','aqm'),('Northern Alta','aqn'),('Atakapa','aqp'),('Akuntsu','aqz'),('Arabic','ar'),('Standard Arabic','arb'),('Official Aramaic 700300 BCE','arc'),('Arabana','ard'),('Western Arrarnta','are'),('Arafundi','arf'),('Aragonese','an'),('Arhuaco','arh'),('Arikara','ari'),('Arapaso','arj'),('Arabela','arl'),('Armenian','hy'),('Mapudungun','arn'),('Araona','aro'),('Arapaho','arp'),('Algerian Arabic','arq'),('Karo Brazil','arr'),('Najdi Arabic','ars'),('Arbore','arv'),('Arawak','arw'),('Moroccan Arabic','ary'),('Egyptian Arabic','arz'),('Asu Tanzania','asa'),('Assiniboine','asb'),('Casuarina Coast Asmat','asc'),('Asas','asd'),('American Sign Language','ase'),('Australian Sign Language','asf'),('Cishingini','asg'),('Abishira','ash'),('Buruwai','asi'),('Nsari','asj'),('Ashkun','ask'),('Asilulu','asl'),('Assamese','asm'),('Dano','aso'),('Algerian Sign Language','asp'),('Austrian Sign Language','asq'),('Asuri','asr'),('Ipulo','ass'),('Asturian','ast'),('Tocantins Asurini','asu'),('Asoa','asv'),('Australian Aborigines Sign Language','asw'),('Muratayak','asx'),('Yaosakor Asmat','asy'),('As','asz'),('PeleAta','ata'),('Zaiwa','atb'),('Atsahuaca','atc'),('Ata Manobo','atd'),('Atemble','ate'),('Atuence','atf'),('Ivbie NorthOkpelaArhe','atg'),('Atikamekw','atj'),('Ati','atk'),('Mt Iraya Agta','atl'),('Ata','atm'),('Ashtiani','atn'),('Atong','ato'),('Pudtol Atta','atp'),('AralleTabulahan','atq'),('WaimiriAtroari','atr'),('Gros Ventre','ats'),('Pamplona Atta','att'),('Reel','atu'),('Northern Altai','atv'),('Atsugewi','atw'),('Arutani','atx'),('Aneityum','aty'),('Arta','atz'),('Asumboa','aua'),('Alugu','aub'),('Waorani','auc'),('Anuta','aud'),('Aguna','aug'),('Aushi','auh'),('Anuki','aui'),('Awjilah','auj'),('Heyo','auk'),('Aulua','aul'),('Asu Nigeria','aum'),('Molmo One','aun'),('Auyokawa','auo'),('Makayam','aup'),('Anus','auq'),('Aruek','aur'),('Austral','aut'),('Auye','auu'),('Auvergnat','auv'),('Awyi','auw'),('Awiyaana','auy'),('Uzbeki Arabic','auz'),('Avaric','av'),('Avau','avb'),('AlviriVidari','avd'),('Avestan','ae'),('Avikam','avi'),('Kotava','avk'),('Eastern Egyptian Bedawi Arabic','avl'),('Angkamuthi','avm'),('Avatime','avn'),('Agavotaguerra','avo'),('Aushiri','avs'),('Au','avt'),('Avokaya','avu'),('Awadhi','awa'),('Awa Papua New Guinea','awb'),('Cicipu','awc'),('Anguthimri','awg'),('Awbono','awh'),('Aekyom','awi'),('Awabakal','awk'),('Arawum','awm'),('Awngi','awn'),('Awak','awo'),('Awera','awr'),('South Awyu','aws'),('Central Awyu','awu'),('Jair Awyu','awv'),('Awun','aww'),('Awara','awx'),('Edera Awyu','awy'),('Abipon','axb'),('Ayerrerenge','axe'),('Yaka Central African Republic','axk'),('Middle Armenian','axm'),('Xaragure','axx'),('Awar','aya'),('Ayizo Gbe','ayb'),('Southern Aymara','ayc'),('Ayabadhu','ayd'),('Ayere','aye'),('Ginyanga','ayg'),('Hadrami Arabic','ayh'),('Leyigha','ayi'),('Akuku','ayk'),('Libyan Arabic','ayl'),('Aymara','ay'),('Sanaani Arabic','ayn'),('Ayoreo','ayo'),('North Mesopotamian Arabic','ayp'),('Ayi Papua New Guinea','ayq'),('Central Aymara','ayr'),('Sorsogon Ayta','ays'),('Magbukun Ayta','ayt'),('Ayu','ayu'),('Ayi China','ayx'),('Tayabas Ayta','ayy'),('Mai Brat','ayz'),('Azha','aza'),('South Azerbaijani','azb'),('Eastern Durango Nahuatl','azd'),('Azerbaijani','az'),('San Pedro Amuzgos Amuzgo','azg'),('North Azerbaijani','azj'),('Ipalapa Amuzgo','azm'),('Western Durango Nahuatl','azn'),('Awing','azo'),('Adzera','azr'),('Faire Atta','azt'),('Highland Puebla Nahuatl','azz'),('Babatana','baa'),('Badui','bac'),('Nubaca','baf'),('Tuki','bag'),('Bahamas Creole English','bah'),('Barakai','baj'),('Bashkir','ba'),('Baluchi','bal'),('Bambara','bm'),('Balinese','ban'),('Waimaha','bao'),('Bantawa','bap'),('Basque','eu'),('Bavarian','bar'),('Basa Cameroon','bas'),('Bada Nigeria','bau'),('Vengo','bav'),('BambiliBambui','baw'),('Bamun','bax'),('Batuley','bay'),('Tunen','baz'),('Baatonum','bba'),('Barai','bbb'),('Batak Toba','bbc'),('Bau','bbd'),('Bangba','bbe'),('Baibai','bbf'),('Barama','bbg'),('Bugan','bbh'),('Barombi','bbi'),('Babanki','bbk'),('Bats','bbl'),('Babango','bbm'),('Uneapa','bbn'),('West Central Banda','bbp'),('Bamali','bbq'),('Girawa','bbr'),('Bakpinka','bbs'),('Mburku','bbt'),('Kulung Nigeria','bbu'),('Karnai','bbv'),('Baba','bbw'),('Bubia','bbx'),('Befang','bby'),('Babalia Creole Arabic','bbz'),('Central Bai','bca'),('BainoukSamik','bcb'),('Southern Balochi','bcc'),('North Babar','bcd'),('Bamenyam','bce'),('Bamu','bcf'),('Baga Binari','bcg'),('Bariai','bch'),('Bardi','bcj'),('Bunaba','bck'),('Central Bikol','bcl'),('Bannoni','bcm'),('Bali Nigeria','bcn'),('Kaluli','bco'),('Bali Democratic Republic of Congo','bcp'),('Bench','bcq'),('Babine','bcr'),('Kohumono','bcs'),('Bendi','bct'),('Awad Bing','bcu'),('ShooMindaNye','bcv'),('Bana','bcw'),('Pamona','bcx'),('Bacama','bcy'),('BainoukGunyaamolo','bcz'),('Bayot','bda'),('Basap','bdb'),('Bunama','bdd'),('Bade','bde'),('Biage','bdf'),('Bonggi','bdg'),('Baka Sudan','bdh'),('Burun','bdi'),('Bai','bdj'),('Budukh','bdk'),('Indonesian Bajau','bdl'),('Buduma','bdm'),('Baldemu','bdn'),('Morom','bdo'),('Bende','bdp'),('Bahnar','bdq'),('West Coast Bajau','bdr'),('Burunge','bds'),('Bokoto','bdt'),('Oroko','bdu'),('Bodo Parja','bdv'),('Baham','bdw'),('BudongBudong','bdx'),('Bandjalang','bdy'),('Badeshi','bdz'),('Beaver','bea'),('Bebele','beb'),('IceveMaci','bec'),('Bedoanas','bed'),('Byangsi','bee'),('Benabena','bef'),('Belait','beg'),('Biali','beh'),('Beja','bej'),('Bebeli','bek'),('Belarusian','be'),('Bemba Zambia','bem'),('Bengali','bn'),('Beami','beo'),('Besoa','bep'),('Beembe','beq'),('Besme','bes'),('Blagar','beu'),('Betawi','bew'),('Jur Modo','bex'),('Beli Papua New Guinea','bey'),('Bena Tanzania','bez'),('Bari','bfa'),('Pauri Bareli','bfb'),('Northern Bai','bfc'),('Bafut','bfd'),('Betaf','bfe'),('Bofi','bff'),('Busang Kayan','bfg'),('Blafe','bfh'),('British Sign Language','bfi'),('Bafanji','bfj'),('Ban Khor Sign Language','bfk'),('Mmen','bfm'),('Bunak','bfn'),('Malba Birifor','bfo'),('Beba','bfp'),('Badaga','bfq'),('Bazigar','bfr'),('Southern Bai','bfs'),('Balti','bft'),('Gahri','bfu'),('Bondo','bfw'),('Bantayanon','bfx'),('Bagheli','bfy'),('Mahasu Pahari','bfz'),('GwamhiWuri','bga'),('Bobongko','bgb'),('Haryanvi','bgc'),('Rathwi Bareli','bgd'),('Bauria','bge'),('Bangandu','bgf'),('Bugun','bgg'),('Bogan','bgh'),('Giangan','bgi'),('Bangolan','bgj'),('Bit','bgk'),('Bo Laos','bgl'),('Baga Mboteni','bgm'),('Western Balochi','bgn'),('Baga Koga','bgo'),('Eastern Balochi','bgp'),('Bagri','bgq'),('Bawm Chin','bgr'),('Tagabawa','bgs'),('Bughotu','bgt'),('Mbongno','bgu'),('WarkayBipim','bgv'),('Bhatri','bgw'),('Balkan Gagauz Turkish','bgx'),('Benggoi','bgy'),('Banggai','bgz'),('Bharia','bha'),('Bhili','bhb'),('Biga','bhc'),('Bhadrawahi','bhd'),('Bhaya','bhe'),('Odiai','bhf'),('Binandere','bhg'),('Bukharic','bhh'),('Bhilali','bhi'),('Bahing','bhj'),('Albay Bicolano','bhk'),('Bimin','bhl'),('Bathari','bhm'),('Bohtan NeoAramaic','bhn'),('Bhojpuri','bho'),('Bima','bhp'),('Tukang Besi South','bhq'),('Bara Malagasy','bhr'),('Buwal','bhs'),('Bhattiyali','bht'),('Bhunjia','bhu'),('Bahau','bhv'),('Biak','bhw'),('Bhalay','bhx'),('Bhele','bhy'),('Bada Indonesia','bhz'),('Badimaya','bia'),('Bissa','bib'),('Bikaru','bic'),('Bidiyo','bid'),('Bepour','bie'),('Biafada','bif'),('Biangai','big'),('Bihari languages','bh'),('Bisu','bii'),('VaghatYaBijimLegeri','bij'),('Bikol','bik'),('Bile','bil'),('Bimoba','bim'),('Bini','bin'),('Nai','bio'),('Bila','bip'),('Bipi','biq'),('Bisorio','bir'),('Bislama','bi'),('Berinomo','bit'),('Biete','biu'),('Southern Birifor','biv'),('Kol Cameroon','biw'),('Bijori','bix'),('Birhor','biy'),('Baloi','biz'),('Budza','bja'),('Banggarla','bjb'),('Bariji','bjc'),('Bandjigali','bjd'),('BiaoJiao Mien','bje'),('Barzani Jewish NeoAramaic','bjf'),('Bidyogo','bjg'),('Bahinemo','bjh'),('Burji','bji'),('Kanauji','bjj'),('Barok','bjk'),('Bulu Papua New Guinea','bjl'),('Bajelani','bjm'),('Banjar','bjn'),('MidSouthern Banda','bjo'),('Southern Betsimisaraka Malagasy','bjq'),('Binumarien','bjr'),('Bajan','bjs'),('BalantaGanja','bjt'),('Busuu','bju'),('Bedjond','bjv'),('Banao Itneg','bjx'),('Bayali','bjy'),('Baruga','bjz'),('Kyak','bka'),('Finallig','bkb'),('Baka Cameroon','bkc'),('Binukid','bkd'),('Bengkulu','bke'),('Beeke','bkf'),('Buraka','bkg'),('Bakoko','bkh'),('Baki','bki'),('Pande','bkj'),('Brokskat','bkk'),('Berik','bkl'),('Kom Cameroon','bkm'),('Bukitan','bkn'),('Boko Democratic Republic of Congo','bkp'),('Bakumpai','bkr'),('Northern Sorsoganon','bks'),('Boloki','bkt'),('Buhid','bku'),('Bekwarra','bkv'),('Bekwel','bkw'),('Baikeno','bkx'),('Bokyi','bky'),('Bungku','bkz'),('Siksika','bla'),('Bilua','blb'),('Bella Coola','blc'),('Bolango','bld'),('BalantaKentohe','ble'),('Buol','blf'),('Balau','blg'),('Kuwaa','blh'),('Bolia','bli'),('Bolongan','blj'),('Biloxi','bll'),('Beli Sudan','blm'),('Southern Catanduanes Bikol','bln'),('Anii','blo'),('Blablanga','blp'),('BaluanPam','blq'),('Blang','blr'),('Balaesang','bls'),('Tai Dam','blt'),('Hmong Njua','blu'),('Bolo','blv'),('Balangao','blw'),('MagIndi Ayta','blx'),('Notre','bly'),('Balantak','blz'),('Lame','bma'),('Bembe','bmb'),('Biem','bmc'),('Baga Manduri','bmd'),('Limassa','bme'),('Bom','bmf'),('Bamwe','bmg'),('Kein','bmh'),('Bagirmi','bmi'),('BoteMajhi','bmj'),('Ghayavi','bmk'),('Bomboli','bml'),('Northern Betsimisaraka Malagasy','bmm'),('Bina Papua New Guinea','bmn'),('Bambalang','bmo'),('Bulgebi','bmp'),('Bomu','bmq'),('Muinane','bmr'),('Bilma Kanuri','bms'),('Biao Mon','bmt'),('SombaSiawari','bmu'),('Bum','bmv'),('Bomwali','bmw'),('Baimak','bmx'),('Bemba Democratic Republic of Congo','bmy'),('Baramu','bmz'),('Bonerate','bna'),('Bookan','bnb'),('Bontok','bnc'),('Banda Indonesia','bnd'),('Bintauna','bne'),('Masiwang','bnf'),('Benga','bng'),('Bangi','bni'),('Eastern Tawbuid','bnj'),('Bierebo','bnk'),('Boon','bnl'),('Batanga','bnm'),('Bunun','bnn'),('Bantoanon','bno'),('Bola','bnp'),('Bantik','bnq'),('ButmasTur','bnr'),('Bundeli','bns'),('Bentong','bnu'),('Bonerif','bnv'),('Bisis','bnw'),('Bangubangu','bnx'),('Bintulu','bny'),('Beezen','bnz'),('Bora','boa'),('Aweer','bob'),('Bakung Kenyah','boc'),('Tibetan','bo'),('Mundabli','boe'),('Bolon','bof'),('Bamako Sign Language','bog'),('Boma','boh'),('Anjam','boj'),('Bonjo','bok'),('Bole','bol'),('Berom','bom'),('Bine','bon'),('Bonkiman','bop'),('Bogaya','boq'),('Bosnian','bs'),('Bongo','bot'),('Bondei','bou'),('Tuwuli','bov'),('Rema','bow'),('Buamu','box'),('Bodo Central African Republic','boy'),('Dakaka','bpa'),('Barbacoas','bpb'),('BandaBanda','bpd'),('Bonggo','bpg'),('Botlikh','bph'),('Bagupi','bpi'),('Binji','bpj'),('Orowe','bpk'),('Broome Pearling Lugger Pidgin','bpl'),('Biyom','bpm'),('Dzao Min','bpn'),('Anasi','bpo'),('Kaure','bpp'),('Banda Malay','bpq'),('Koronadal Blaan','bpr'),('Sarangani Blaan','bps'),('Barrow Point','bpt'),('Bongu','bpu'),('Bian Marind','bpv'),('Bo Papua New Guinea','bpw'),('Palya Bareli','bpx'),('Bishnupriya','bpy'),('Bilba','bpz'),('Tchumbuli','bqa'),('Bagusa','bqb'),('Boko Benin','bqc'),('Bung','bqd'),('NavarroLabourdin Basque','bqe'),('Baga Kaloum','bqf'),('BagoKusuntu','bqg'),('Baima','bqh'),('Bakhtiari','bqi'),('Bandial','bqj'),('Bilakura','bql'),('Wumboko','bqm'),('Bulgarian Sign Language','bqn'),('Balo','bqo'),('Busa','bqp'),('Biritai','bqq'),('Burusu','bqr'),('Bosngun','bqs'),('Bamukumbit','bqt'),('Boguru','bqu'),('Koro Wachi','bqv'),('Buru Nigeria','bqw'),('Baangi','bqx'),('Bengkala Sign Language','bqy'),('Bakaka','bqz'),('Braj','bra'),('Lave','brb'),('Berbice Creole Dutch','brc'),('Baraamu','brd'),('Breton','br'),('Bera','brf'),('Baure','brg'),('Brahui','brh'),('Mokpwe','bri'),('Bieria','brj'),('Birked','brk'),('Birwa','brl'),('Barambu','brm'),('Boruca','brn'),('Brokkat','bro'),('Barapasi','brp'),('Breri','brq'),('Birao','brr'),('Baras','brs'),('Bitare','brt'),('Eastern Bru','bru'),('Western Bru','brv'),('Bellari','brw'),('Bodo India','brx'),('Burui','bry'),('Bilbil','brz'),('Abinomn','bsa'),('Brunei Bisaya','bsb'),('Bassari','bsc'),('Sarawak Bisaya','bsd'),('Wushi','bse'),('Bauchi','bsf'),('Bashkardi','bsg'),('Kati','bsh'),('Bassossi','bsi'),('Bangwinji','bsj'),('Burushaski','bsk'),('BasaGumna','bsl'),('Busami','bsm'),('BarasanaEduria','bsn'),('Buso','bso'),('Baga Sitemu','bsp'),('Bassa','bsq'),('BassaKontagora','bsr'),('Akoose','bss'),('Basketo','bst'),('Bahonsuai','bsu'),('Baiso','bsw'),('Yangkam','bsx'),('Sabah Bisaya','bsy'),('Souletin Basque','bsz'),('Bata','bta'),('Beti Cameroon','btb'),('Bati Cameroon','btc'),('Batak Dairi','btd'),('GamoNingi','bte'),('Birgit','btf'),('Biatah Bidayuh','bth'),('Burate','bti'),('Bacanese Malay','btj'),('Bhatola','btl'),('Batak Mandailing','btm'),('Ratagnon','btn'),('Rinconada Bikol','bto'),('Budibud','btp'),('Batek','btq'),('Baetora','btr'),('Batak Simalungun','bts'),('BeteBendi','btt'),('Batu','btu'),('Bateri','btv'),('Butuanon','btw'),('Batak Karo','btx'),('Bobot','bty'),('Batak AlasKluet','btz'),('Buriat','bua'),('Bua','bub'),('Bushi','buc'),('Ntcham','bud'),('Beothuk','bue'),('Bushoong','buf'),('Buginese','bug'),('Younuo Bunu','buh'),('Bongili','bui'),('BasaGurmana','buj'),('Bugawac','buk'),('Bulgarian','bg'),('Bulu Cameroon','bum'),('Sherbro','bun'),('Terei','buo'),('Busoa','bup'),('Brem','buq'),('Burmese','my'),('Bokobaru','bus'),('Bungain','but'),('Budu','buu'),('Bun','buv'),('Bubi','buw'),('Boghom','bux'),('Bullom So','buy'),('Bukwen','buz'),('Barein','bva'),('Bube','bvb'),('Baelelea','bvc'),('Baeggu','bvd'),('Berau Malay','bve'),('Boor','bvf'),('Bonkeng','bvg'),('Bure','bvh'),('Belanda Viri','bvi'),('Baan','bvj'),('Bukat','bvk'),('Bolivian Sign Language','bvl'),('Bamunka','bvm'),('Buna','bvn'),('Bolgo','bvo'),('Birri','bvq'),('Burarra','bvr'),('Belgian Sign Language','bvs'),('Bati Indonesia','bvt'),('Bukit Malay','bvu'),('Baniva','bvv'),('Boga','bvw'),('Dibole','bvx'),('Baybayanon','bvy'),('Bauzi','bvz'),('Bwatoo','bwa'),('NamosiNaitasiriSerua','bwb'),('Bwile','bwc'),('Bwaidoka','bwd'),('Bwe Karen','bwe'),('Boselewa','bwf'),('Barwe','bwg'),('Bishuo','bwh'),('Baniwa','bwi'),('Bauwaki','bwk'),('Bwela','bwl'),('Biwat','bwm'),('Wunai Bunu','bwn'),('Boro Ethiopia','bwo'),('Mandobo Bawah','bwp'),('BuraPabir','bwr'),('Bomboma','bws'),('BafawBalong','bwt'),('Buli Ghana','bwu'),('Bahau River Kenyah','bwv'),('Bwa','bww'),('BuNao Bunu','bwx'),('Cwi Bwamu','bwy'),('Bwisi','bwz'),('Bauro','bxa'),('Belanda Bor','bxb'),('Molengue','bxc'),('Pela','bxd'),('Birale','bxe'),('Bilur','bxf'),('Bangala','bxg'),('Buhutu','bxh'),('Pirlatapa','bxi'),('Bayungu','bxj'),('Bukusu','bxk'),('Jalkunan','bxl'),('Mongolia Buriat','bxm'),('Burduna','bxn'),('Barikanchi','bxo'),('Bebil','bxp'),('Beele','bxq'),('Russia Buriat','bxr'),('Busam','bxs'),('Buxinhua','bxt'),('China Buriat','bxu'),('Berakou','bxv'),('Bankagooma','bxw'),('Borna Democratic Republic of Congo','bxx'),('Binahari','bxz'),('Batak','bya'),('Bikya','byb'),('Ubaghara','byc'),('Pouye','bye'),('Bete','byf'),('Baygo','byg'),('Bhujel','byh'),('Buyu','byi'),('Bina Nigeria','byj'),('Biao','byk'),('Bayono','byl'),('Bidyara','bym'),('Bilin','byn'),('Biyo','byo'),('Bumaji','byp'),('Basay','byq'),('Baruya','byr'),('Burak','bys'),('Berti','byt'),('Buyang','byu'),('Medumba','byv'),('Belhariya','byw'),('Qaqet','byx'),('Buya','byy'),('Banaro','byz'),('Bandi','bza'),('Andio','bzb'),('Southern Betsimisaraka Malagasy','bzc'),('Bribri','bzd'),('Jenaama Bozo','bze'),('Boikin','bzf'),('Babuza','bzg'),('Mapos Buang','bzh'),('Bisu','bzi'),('Belize Kriol English','bzj'),('Nicaragua Creole English','bzk'),('Boano Sulawesi','bzl'),('Bolondo','bzm'),('Boano Maluku','bzn'),('Bozaba','bzo'),('Kemberano','bzp'),('Buli Indonesia','bzq'),('Biri','bzr'),('Brazilian Sign Language','bzs'),('Brithenig','bzt'),('Burmeso','bzu'),('Bebe','bzv'),('Basa Nigeria','bzw'),('Obanliku','bzy'),('Evant','bzz'),('Garifuna','cab'),('Chuj','cac'),('Caddo','cad'),('Lehar','cae'),('Southern Carrier','caf'),('Cahuarano','cah'),('Kaqchikel','cak'),('Carolinian','cal'),('Chambri','can'),('Chipaya','cap'),('Car Nicobarese','caq'),('Galibi Carib','car'),('Catalan','ca'),('Callawalla','caw'),('Chiquitano','cax'),('Cayuga','cay'),('Canichana','caz'),('Carapana','cbc'),('Carijona','cbd'),('Chipiajes','cbe'),('Chimila','cbg'),('Cagua','cbh'),('Chachi','cbi'),('Ede Cabe','cbj'),('Chavacano','cbk'),('Bualkhaw Chin','cbl'),('Yepocapa Southwestern Cakchiquel','cbm'),('Nyahkur','cbn'),('Izora','cbo'),('CashiboCacataibo','cbr'),('Cashinahua','cbs'),('Chayahuita','cbt'),('CandoshiShapra','cbu'),('Cacua','cbv'),('Kinabalian','cbw'),('Carabayo','cby'),('Cauca','cca'),('Chamicuro','ccc'),('Cafundo Creole','ccd'),('Chopi','cce'),('Samba Daka','ccg'),('Atsam','cch'),('Kasanga','ccj'),('CutchiSwahili','ccl'),('Malaccan Creole Malay','ccm'),('Comaltepec Chinantec','cco'),('Chakma','ccp'),('Chaungtha','ccq'),('Cacaopera','ccr'),('Northern Zhuang','ccx'),('Southern Zhuang','ccy'),('Choni','cda'),('Chenchu','cde'),('Chiru','cdf'),('Chamari','cdg'),('Chambeali','cdh'),('Chodri','cdi'),('Churahi','cdj'),('Chepang','cdm'),('Chaudangsi','cdn'),('Min Dong Chinese','cdo'),('CindaRegiTiyal','cdr'),('Chadian Sign Language','cds'),('Chadong','cdy'),('Koda','cdz'),('Lower Chehalis','cea'),('Cebuano','ceb'),('Chamacoco','ceg'),('Eastern Khumi Chin','cek'),('Cen','cen'),('Czech','cs'),('DijimBwilim','cfa'),('Cara','cfd'),('Como Karim','cfg'),('Falam Chin','cfm'),('Changriwa','cga'),('Kagayanen','cgc'),('Chiga','cgg'),('Chocangacakha','cgk'),('Chamorro','ch'),('Chibcha','chb'),('Catawba','chc'),('Highland Oaxaca Chontal','chd'),('Chechen','ce'),('Tabasco Chontal','chf'),('Chagatai','chg'),('Chinook','chh'),('Chinese','zh'),('Chuukese','chk'),('Cahuilla','chl'),('Mari Russia','chm'),('Chinook jargon','chn'),('Choctaw','cho'),('Chipewyan','chp'),('Quiotepec Chinantec','chq'),('Cherokee','chr'),('Chumash','chs'),('Church Slavic','cu'),('Chuvash','cv'),('Chuwabu','chw'),('Chantyal','chx'),('Cheyenne','chy'),('CiaCia','cia'),('Ci Gbe','cib'),('Chickasaw','cic'),('Chimariko','cid'),('Cineni','cie'),('Chinali','cih'),('Chitkuli Kinnauri','cik'),('Cimbrian','cim'),('Cinta Larga','cin'),('Chiapanec','cip'),('Tiri','cir'),('Chittagonian','cit'),('Chippewa','ciw'),('Chaima','ciy'),('Western Cham','cja'),('Chru','cje'),('Upper Chehalis','cjh'),('Chamalal','cji'),('Chokwe','cjk'),('Eastern Cham','cjm'),('Chenapian','cjn'),('Chorotega','cjr'),('Shor','cjs'),('Chuave','cjv'),('Jinyu Chinese','cjy'),('Khumi Awa Chin','cka'),('Central Kurdish','ckb'),('Northern Cakchiquel','ckc'),('South Central Cakchiquel','ckd'),('Eastern Cakchiquel','cke'),('Southern Cakchiquel','ckf'),('Chak','ckh'),('Santo Domingo Xenacoj Cakchiquel','ckj'),('Acatenango Southwestern Cakchiquel','ckk'),('Cibak','ckl'),('Kaang Chin','ckn'),('Anufo','cko'),('Kajakse','ckq'),('Kairak','ckr'),('Tayo','cks'),('Chukot','ckt'),('Koasati','cku'),('Kavalan','ckv'),('Western Cakchiquel','ckw'),('Caka','ckx'),('CakfemMushere','cky'),('Ron','cla'),('Chilcotin','clc'),('Chaldean NeoAramaic','cld'),('Lealao Chinantec','cle'),('Chilisso','clh'),('Chakali','cli'),('Laitu Chin','clj'),('IduMishmi','clk'),('Chala','cll'),('Clallam','clm'),('Lowland Oaxaca Chontal','clo'),('Lautu Chin','clt'),('Caluyanun','clu'),('Chulym','clw'),('Eastern Highland Chatino','cly'),('Maa','cma'),('Cerma','cme'),('Classical Mongolian','cmg'),('Chimakum','cmk'),('Campalagian','cml'),('Michigamea','cmm'),('Mandarin Chinese','cmn'),('Central Mnong','cmo'),('MroKhimi Chin','cmr'),('Messapic','cms'),('Camtho','cmt'),('Changthang','cna'),('Chinbon Chin','cnb'),('Northern Qiang','cng'),('Haka Chin','cnh'),('Khumi Chin','cnk'),('Lalana Chinantec','cnl'),('Con','cno'),('Central Asmat','cns'),('Tepetotutla Chinantec','cnt'),('Chenoua','cnu'),('Ngawn Chin','cnw'),('Middle Cornish','cnx'),('Cocos Islands Malay','coa'),('Chicomuceltec','cob'),('Cocopa','coc'),('CocamaCocamilla','cod'),('Koreguaje','coe'),('Colorado','cof'),('Chong','cog'),('ChonyiDzihanaKauma','coh'),('Cochimi','coj'),('Santa Teresa Cora','cok'),('ColumbiaWenatchi','col'),('Comanche','com'),('Comox','coo'),('Coptic','cop'),('Coquille','coq'),('Cornish','kw'),('Corsican','co'),('Caquinte','cot'),('Wamey','cou'),('Cao Miao','cov'),('Cowlitz','cow'),('Nanti','cox'),('Coyaima','coy'),('Chochotec','coz'),('Palantla Chinantec','cpa'),('Cappadocian Greek','cpg'),('Chinese Pidgin English','cpi'),('Cherepon','cpn'),('Capiznon','cps'),('PuXian Chinese','cpx'),('Chuanqiandian Cluster Miao','cqd'),('Chilean Quechua','cqu'),('Chara','cra'),('Island Carib','crb'),('Lonwolwol','crc'),('Cree','cr'),('Caramanta','crf'),('Michif','crg'),('Crimean Tatar','crh'),('Southern East Cree','crj'),('Plains Cree','crk'),('Northern East Cree','crl'),('Moose Cree','crm'),('El Nayar Cora','crn'),('Crow','cro'),('Carolina Algonquian','crr'),('Seselwa Creole French','crs'),('Chaura','crv'),('Chrau','crw'),('Carrier','crx'),('Cori','cry'),('Chiltepec Chinantec','csa'),('Kashubian','csb'),('Catalan Sign Language','csc'),('Chiangmai Sign Language','csd'),('Czech Sign Language','cse'),('Cuba Sign Language','csf'),('Chilean Sign Language','csg'),('Asho Chin','csh'),('Coast Miwok','csi'),('JolaKasa','csk'),('Chinese Sign Language','csl'),('Central Sierra Miwok','csm'),('Colombian Sign Language','csn'),('Sochiapam Chinantec','cso'),('Croatia Sign Language','csq'),('Costa Rican Sign Language','csr'),('Southern Ohlone','css'),('Northern Ohlone','cst'),('Sumtu Chin','csv'),('Swampy Cree','csw'),('Siyin Chin','csy'),('Coos','csz'),('Tataltepec Chatino','cta'),('Chetco','ctc'),('Tedim Chin','ctd'),('Tepinapa Chinantec','cte'),('Chittagonian','ctg'),('Thaiphum Chin','cth'),('Tila Chol','cti'),('Tlacoatzintepec Chinantec','ctl'),('Chitimacha','ctm'),('Chhintange','ctn'),('Western Highland Chatino','ctp'),('Northern Catanduanes Bikol','cts'),('Wayanad Chetti','ctt'),('Chol','ctu'),('Zacatepec Chatino','ctz'),('Cua','cua'),('Cubeo','cub'),('Usila Chinantec','cuc'),('Cung','cug'),('Chuka','cuh'),('Cuiba','cui'),('Mashco Piro','cuj'),('San Blas Kuna','cuk'),('Culina','cul'),('Cumeral','cum'),('Cumanagoto','cuo'),('Cun','cuq'),('Chhulung','cur'),('Teutila Cuicatec','cut'),('Tai Ya','cuu'),('Cuvok','cuv'),('Chukwa','cuw'),('Tepeuxila Cuicatec','cux'),('Chug','cvg'),('Valle Nacional Chinantec','cvn'),('Kabwa','cwa'),('Maindo','cwb'),('Woods Cree','cwd'),('Kwere','cwe'),('Chewong','cwg'),('Kuwaataay','cwt'),('Nopala Chatino','cya'),('Cayubaba','cyb'),('Welsh','cy'),('Cuyonon','cyo'),('Huizhou Chinese','czh'),('Knaanic','czk'),('Zenzontepec Chatino','czn'),('Min Zhong Chinese','czo'),('Zotung Chin','czt'),('Dambi','dac'),('Marik','dad'),('Duupa','dae'),('Dan','daf'),('Dagbani','dag'),('Gwahatike','dah'),('Day','dai'),('Dar Fur Daju','daj'),('Dakota','dak'),('Dahalo','dal'),('Damakawa','dam'),('Danish','da'),('Daai Chin','dao'),('Nisi India','dap'),('Dandami Maria','daq'),('Dargwa','dar'),('DahoDoo','das'),('Darang Deng','dat'),('Dar Sila Daju','dau'),('Taita','dav'),('Davawenyo','daw'),('Dayi','dax'),('Dao','daz'),('Bangi Me','dba'),('Deno','dbb'),('Dadiya','dbd'),('Dabe','dbe'),('Edopi','dbf'),('Dogul Dom Dogon','dbg'),('Doka','dbi'),('Dyirbal','dbl'),('Duguri','dbm'),('Duriankere','dbn'),('Dulbu','dbo'),('Duwai','dbp'),('Daba','dbq'),('Dabarre','dbr'),('Ben Tey Dogon','dbt'),('Bondum Dom Dogon','dbu'),('Dungu','dbv'),('Bankan Tey Dogon','dbw'),('Dibiyaso','dby'),('Deccan','dcc'),('Negerhollands','dcr'),('Dadi Dadi','dda'),('Dongotono','ddd'),('Doondo','dde'),('Fataluku','ddg'),('West Goodenough','ddi'),('Jaru','ddj'),('Dendi Benin','ddn'),('Dido','ddo'),('Dhudhuroa','ddr'),('Donno So Dogon','dds'),('DaweraDaweloor','ddw'),('Dagik','dec'),('Dedua','ded'),('Dewoin','dee'),('Dezfuli','def'),('Degema','deg'),('Dehwari','deh'),('Demisa','dei'),('Dek','dek'),('Delaware','del'),('Dem','dem'),('Slave Athapascan','den'),('Pidgin Delaware','dep'),('Dendi Central African Republic','deq'),('Deori','der'),('Desano','des'),('German','de'),('Domung','dev'),('Dengese','dez'),('Southern Dagaare','dga'),('Bunoge Dogon','dgb'),('Casiguran Dumagat Agta','dgc'),('Dagaari Dioula','dgd'),('Degenan','dge'),('Doga','dgg'),('Dghwede','dgh'),('Northern Dagara','dgi'),('Dagba','dgk'),('Andaandi','dgl'),('Dagoman','dgn'),('Dogri individual language','dgo'),('Dogrib','dgr'),('Dogoso','dgs'),('Degaru','dgu'),('Daungwurrung','dgw'),('Doghoro','dgx'),('Daga','dgz'),('Dhanwar India','dha'),('Dhundari','dhd'),('Dhangu','dhg'),('Dhimal','dhi'),('Dhalandji','dhl'),('Zemba','dhm'),('Dhanki','dhn'),('Dhodia','dho'),('Dhargari','dhr'),('Dhaiso','dhs'),('Dhurga','dhu'),('Dehu','dhv'),('Dhanwar Nepal','dhw'),('Dia','dia'),('South Central Dinka','dib'),('Lakota Dida','dic'),('Didinga','did'),('Dieri','dif'),('Digo','dig'),('Kumiai','dih'),('Dimbong','dii'),('Dai','dij'),('Southwestern Dinka','dik'),('Dilling','dil'),('Dime','dim'),('Dinka','din'),('Dibo','dio'),('Northeastern Dinka','dip'),('Dimli individual language','diq'),('Dirim','dir'),('Dimasa','dis'),('Dirari','dit'),('Diriku','diu'),('Dhivehi','dv'),('Northwestern Dinka','diw'),('Dixon Reef','dix'),('Diuwe','diy'),('Ding','diz'),('Djadjawurrung','dja'),('Djinba','djb'),('Dar Daju Daju','djc'),('Djamindjung','djd'),('Zarma','dje'),('Djangun','djf'),('Djinang','dji'),('Djeebbana','djj'),('Eastern Maroon Creole','djk'),('Djiwarli','djl'),('Jamsay Dogon','djm'),('Djauan','djn'),('Jangkang','djo'),('Djambarrpuyngu','djr'),('Kapriman','dju'),('Djawi','djw'),('Dakpakha','dka'),('Dakka','dkk'),('Kolum So Dogon','dkl'),('Kuijau','dkr'),('Southeastern Dinka','dks'),('Mazagway','dkx'),('Dolgan','dlg'),('Dalmatian','dlm'),('Darlong','dln'),('Duma','dma'),('Mombo Dogon','dmb'),('Dimir','dmc'),('Madhi Madhi','dmd'),('Dugwor','dme'),('Upper Kinabatangan','dmg'),('Domaaki','dmk'),('Dameli','dml'),('Dama','dmm'),('Kemedzung','dmo'),('East Damar','dmr'),('Dampelas','dms'),('Dubu','dmu'),('Dumpas','dmv'),('Dema','dmx'),('Demta','dmy'),('Upper Grand Valley Dani','dna'),('Daonda','dnd'),('Ndendeule','dne'),('Dungan','dng'),('Lower Grand Valley Dani','dni'),('Dengka','dnk'),('Danaru','dnr'),('Mid Grand Valley Dani','dnt'),('Danau','dnu'),('Danu','dnv'),('Western Dani','dnw'),('Dom','doa'),('Dobu','dob'),('Northern Dong','doc'),('Doe','doe'),('Domu','dof'),('Dong','doh'),('Dogri macrolanguage','doi'),('Dondo','dok'),('Doso','dol'),('Toura Papua New Guinea','don'),('Dongo','doo'),('Lukpa','dop'),('Dominican Sign Language','doq'),('Dass','dot'),('Dombe','dov'),('Doyayo','dow'),('Bussa','dox'),('Dompo','doy'),('Dorze','doz'),('Papar','dpp'),('Dair','drb'),('Minderico','drc'),('Darmiya','drd'),('Dolpo','dre'),('Rungus','drg'),('Darkhat','drh'),('Paakantyi','drl'),('West Damar','drn'),('DaroMatu Melanau','dro'),('Dura','drq'),('Dororo','drr'),('Gedeo','drs'),('Drents','drt'),('Rukai','dru'),('Darwazi','drw'),('Darai','dry'),('Lower Sorbian','dsb'),('Dutch Sign Language','dse'),('Daasanach','dsh'),('Disa','dsi'),('Danish Sign Language','dsl'),('Dusner','dsn'),('Desiya','dso'),('Tadaksahak','dsq'),('Daur','dta'),('LabukKinabatangan Kadazan','dtb'),('Ditidaht','dtd'),('Adithinngithigh','dth'),('Ana Tinga Dogon','dti'),('Tene Kan Dogon','dtk'),('Tomo Kan Dogon','dtm'),('Tommo So Dogon','dto'),('Central Dusun','dtp'),('Lotud','dtr'),('Toro So Dogon','dts'),('Toro Tegu Dogon','dtt'),('Tebul Ure Dogon','dtu'),('Dotyali','dty'),('Duala','dua'),('Dubli','dub'),('Duna','duc'),('HunSaare','dud'),('Umiray Dumaget Agta','due'),('Dumbea','duf'),('Duruma','dug'),('Dungra Bhil','duh'),('Dumun','dui'),('Dhuwal','duj'),('Uyajitaya','duk'),('Alabat Island Agta','dul'),('Middle Dutch ca 10501350','dum'),('Dusun Deyah','dun'),('Dupaninan Agta','duo'),('Duano','dup'),('Dusun Malang','duq'),('Dii','dur'),('Dumi','dus'),('Dutch','nl'),('Drung','duu'),('Duvle','duv'),('Dusun Witu','duw'),('Duungooma','dux'),('Dicamay Agta','duy'),('Duli','duz'),('Duau','dva'),('Diri','dwa'),('Walo Kumbe Dogon','dwl'),('Dawro','dwr'),('Dutton World Speedwords','dws'),('Dawawa','dww'),('Dyan','dya'),('Dyaberdyaber','dyb'),('Dyugun','dyd'),('Villa Viciosa Agta','dyg'),('Djimini Senoufo','dyi'),('Land Dayak','dyk'),('Yanda Dom Dogon','dym'),('Dyangadi','dyn'),('JolaFonyi','dyo'),('Dyula','dyu'),('Dyaabugay','dyy'),('Tunzu','dza'),('Daza','dzd'),('Dazaga','dzg'),('Dzalakha','dzl'),('Dzando','dzn'),('Dzongkha','dz'),('Ebughu','ebg'),('Eastern Bontok','ebk'),('TekeEbo','ebo'),('Embu','ebu'),('Eteocretan','ecr'),('Ecuadorian Sign Language','ecs'),('Eteocypriot','ecy'),('E','eee'),('Efai','efa'),('Efe','efe'),('Efik','efi'),('Ega','ega'),('Emilian','egl'),('Eggon','ego'),('Egyptian Ancient','egy'),('Ehueun','ehu'),('Eipomek','eip'),('Eitiep','eit'),('Askopan','eiv'),('Ejamat','eja'),('Ekajuk','eka'),('Ekit','eke'),('Ekari','ekg'),('Eki','eki'),('Standard Estonian','ekk'),('Kol Bangladesh','ekl'),('Elip','ekm'),('Koti','eko'),('Ekpeye','ekp'),('Yace','ekr'),('Eastern Kayah','eky'),('Elepi','ele'),('El Hugeirat','elh'),('Nding','eli'),('Elkei','elk'),('Greek, Modern (1453-)','el'),('Eleme','elm'),('El Molo','elo'),('Elpaputih','elp'),('Elu','elu'),('Elamite','elx'),('EmaiIulehaOra','ema'),('Embaloh','emb'),('Emerillon','eme'),('Eastern Meohang','emg'),('MussauEmira','emi'),('Eastern Maninkakan','emk'),('EmilianoRomagnolo','eml'),('Mamulique','emm'),('Eman','emn'),('Emok','emo'),('Pacific Gulf Yupik','ems'),('Eastern Muria','emu'),('Emplawas','emw'),('Erromintxela','emx'),('Epigraphic Mayan','emy'),('Apali','ena'),('Markweeta','enb'),('En','enc'),('Ende','end'),('Forest Enets','enf'),('English','en'),('Tundra Enets','enh'),('Enim','eni'),('Middle English 11001500','enm'),('Engenni','enn'),('Enggano','eno'),('Enga','enq'),('Emumu','enr'),('Enu','enu'),('Enwan Edu State','env'),('Enwan Akwa Ibom State','enw'),('Epie','epi'),('Esperanto','eo'),('Eravallan','era'),('Sie','erg'),('Eruwa','erh'),('Ogea','eri'),('South Efate','erk'),('Horpa','ero'),('Erre','err'),('Ersu','ers'),('Eritai','ert'),('Erokwanas','erw'),('Ese Ejja','ese'),('Eshtehardi','esh'),('North Alaskan Inupiatun','esi'),('Northwest Alaska Inupiatun','esk'),('Egypt Sign Language','esl'),('Esuma','esm'),('Salvadoran Sign Language','esn'),('Estonian Sign Language','eso'),('Esselen','esq'),('Central Siberian Yupik','ess'),('Estonian','et'),('Central Yupik','esu'),('Etebi','etb'),('Etchemin','etc'),('Ethiopian Sign Language','eth'),('Eton Vanuatu','etn'),('Eton Cameroon','eto'),('Edolo','etr'),('Yekhee','ets'),('Etruscan','ett'),('Ejagham','etu'),('Eten','etx'),('Semimi','etz'),('Europanto','eur'),('Even','eve'),('Uvbie','evh'),('Evenki','evn'),('Ewe','ee'),('Ewondo','ewo'),('Extremaduran','ext'),('Eyak','eya'),('Keiyo','eyo'),('Uzekwe','eze'),('Fasu','faa'),('Wagi','fad'),('Fagani','faf'),('Finongan','fag'),('Baissa Fali','fah'),('Faiwol','fai'),('Faita','faj'),('Fang Cameroon','fak'),('South Fali','fal'),('Fam','fam'),('Fang Equatorial Guinea','fan'),('Faroese','fo'),('Palor','fap'),('Fataleka','far'),('Persian','fa'),('Fanti','fat'),('Fayu','fau'),('Fala','fax'),('Southwestern Fars','fay'),('Northwestern Fars','faz'),('West Albay Bikol','fbl'),('Quebec Sign Language','fcs'),('Feroge','fer'),('Foia Foia','ffi'),('Maasina Fulfulde','ffm'),('Fongoro','fgr'),('Nobiin','fia'),('Fyer','fie'),('Fijian','fj'),('Filipino','fil'),('Finnish','fi'),('Fipa','fip'),('Firan','fir'),('Tornedalen Finnish','fit'),('Fiwaga','fiw'),('Izere','fiz'),('Kven Finnish','fkv'),('Foau','flh'),('Fali','fli'),('North Fali','fll'),('Falam Chin','flm'),('Flinders Island','fln'),('Fuliiru','flr'),('Tsotsitaal','fly'),('Far Western Muria','fmu'),('Fanagalo','fng'),('Fania','fni'),('Foodo','fod'),('Foi','foi'),('Foma','fom'),('Fon','fon'),('Fore','for'),('Siraya','fos'),('Fernando Po Creole English','fpe'),('Fas','fqs'),('French','fr'),('Cajun French','frc'),('Fordata','frd'),('Western Frisian','fri'),('Frankish','frk'),('Middle French ca 14001600','frm'),('Old French 842ca 1400','fro'),('Arpitan','frp'),('Forak','frq'),('Northern Frisian','frr'),('Eastern Frisian','frs'),('Fortsenal','frt'),('Western Frisian','fy'),('Finnish Sign Language','fse'),('French Sign Language','fsl'),('FinlandSwedish Sign Language','fss'),('Adamawa Fulfulde','fub'),('Pulaar','fuc'),('East Futuna','fud'),('Borgu Fulfulde','fue'),('Pular','fuf'),('Western Niger Fulfulde','fuh'),('Bagirmi Fulfulde','fui'),('Ko','fuj'),('Fulah','ff'),('Fum','fum'),('CentralEastern Niger Fulfulde','fuq'),('Friulian','fur'),('FutunaAniwa','fut'),('Furu','fuu'),('Nigerian Fulfulde','fuv'),('Fuyug','fuy'),('Fur','fvr'),('Fwe','fwe'),('Ga','gaa'),('Gabri','gab'),('Mixed Great Andamanese','gac'),('Gaddang','gad'),('Guarequena','gae'),('Gende','gaf'),('Gagauz','gag'),('Alekano','gah'),('Borei','gai'),('Gadsup','gaj'),('Gamkonora','gak'),('Galoli','gal'),('Kandawo','gam'),('Gan Chinese','gan'),('Gants','gao'),('Gal','gap'),('Galeya','gar'),('Adiwasi Garasia','gas'),('Kenati','gat'),('Mudhili Gadaba','gau'),('Gabutamon','gav'),('Nobonob','gaw'),('BoranaArsiGuji Oromo','gax'),('Gayo','gay'),('West Central Oromo','gaz'),('Gbaya Central African Republic','gba'),('Kaytetye','gbb'),('Garawa','gbc'),('Karadjeri','gbd'),('Niksek','gbe'),('Gaikundi','gbf'),('Gbanziri','gbg'),('Defi Gbe','gbh'),('Galela','gbi'),('Bodo Gadaba','gbj'),('Gaddi','gbk'),('Gamit','gbl'),('Garhwali','gbm'),('Northern Grebo','gbo'),('GbayaBossangoa','gbp'),('GbayaBozoum','gbq'),('Gbagyi','gbr'),('Gbesi Gbe','gbs'),('Gagadu','gbu'),('Gbanu','gbv'),('Eastern Xwla Gbe','gbx'),('Gbari','gby'),('Zoroastrian Dari','gbz'),('Mali','gcc'),('Ganggalida','gcd'),('Galice','gce'),('Guadeloupean Creole French','gcf'),('Grenadian Creole English','gcl'),('Gaina','gcn'),('Guianese Creole French','gcr'),('Colonia Tovar German','gct'),('Gade Lohar','gda'),('Pottangi Ollar Gadaba','gdb'),('Gugu Badhun','gdc'),('Gedaged','gdd'),('Gude','gde'),('GudufGava','gdf'),('Gadjerawang','gdh'),('Gundi','gdi'),('Gurdjar','gdj'),('Gadang','gdk'),('Dirasha','gdl'),('Laal','gdm'),('Umanakaina','gdn'),('Ghodoberi','gdo'),('Mehri','gdq'),('Wipi','gdr'),('Ghandruk Sign Language','gds'),('Gudu','gdu'),('Godwari','gdx'),('Geruma','gea'),('Kire','geb'),('Gboloo Grebo','gec'),('Gade','ged'),('Gengle','geg'),('Hutterite German','geh'),('Gebe','gei'),('Gen','gej'),('Yiwom','gek'),('Geman Deng','gen'),('Georgian','ka'),('Geme','geq'),('GeserGorom','ges'),('Gera','gew'),('Garre','gex'),('Enya','gey'),('Geez','gez'),('Patpatar','gfk'),('Gafat','gft'),('Gao','gga'),('Gbii','ggb'),('Gugadj','ggd'),('Guragone','gge'),('Gurgula','ggg'),('GarrehAjuran','ggh'),('Kungarakany','ggk'),('Ganglau','ggl'),('Eastern Gurung','ggn'),('Southern Gondi','ggo'),('Aghu Tharnggalu','ggr'),('Gitua','ggt'),('Gagu','ggu'),('Gogodala','ggw'),('HibernoScottish Gaelic','ghc'),('Southern Ghale','ghe'),('Northern Ghale','ghh'),('Geko Karen','ghk'),('Ghulfan','ghl'),('Ghanongga','ghn'),('Ghomara','gho'),('Ghera','ghr'),('GuhuSamane','ghs'),('Kuke','ght'),('Kitja','gia'),('Gibanawa','gib'),('Gail','gic'),('Gidar','gid'),('Goaria','gig'),('Gilbertese','gil'),('Gimi Eastern Highlands','gim'),('Hinukh','gin'),('Gelao','gio'),('Gimi West New Britain','gip'),('Green Gelao','giq'),('Red Gelao','gir'),('North Giziga','gis'),('Gitxsan','git'),('Mulao','giu'),('White Gelao','giw'),('Gilima','gix'),('Giyug','giy'),('South Giziga','giz'),('Geji','gji'),('Kachi Koli','gjk'),('Gonja','gjn'),('Gujari','gju'),('Guya','gka'),('Ndai','gke'),('Gokana','gkn'),('KokNar','gko'),('Guinea Kpelle','gkp'),('Scottish Gaelic','gd'),('Bon Gula','glc'),('Nanai','gld'),('Irish','ga'),('Galician','gl'),('Northwest Pashayi','glh'),('Guliguli','gli'),('Gula Iro','glj'),('Gilaki','glk'),('Galambu','glo'),('GlaroTwabo','glr'),('Gula Chad','glu'),('Manx','gv'),('Glavda','glw'),('Gule','gly'),('Gambera','gma'),('Middle High German ca 10501500','gmh'),('Middle Low German','gml'),('GbayaMbodomo','gmm'),('Gimnime','gmn'),('GamoGofaDawro','gmo'),('Gumalu','gmu'),('Gamo','gmv'),('Magoma','gmx'),('Mycenaean Greek','gmy'),('Kaansa','gna'),('Gangte','gnb'),('Guanche','gnc'),('ZulgoGemzek','gnd'),('Ganang','gne'),('Ngangam','gng'),('Lere','gnh'),('Gooniyandi','gni'),('Gangulu','gnl'),('Ginuman','gnm'),('Gumatj','gnn'),('Northern Gondi','gno'),('Gana','gnq'),('Gureng Gureng','gnr'),('Guntai','gnt'),('Gnau','gnu'),('Ganzi','gnz'),('Guro','goa'),('Playero','gob'),('Gorakor','goc'),('Gongduk','goe'),('Gofa','gof'),('Gogo','gog'),('Old High German ca 7501050','goh'),('Gobasi','goi'),('Gowlan','goj'),('Gowli','gok'),('Gola','gol'),('Goan Konkani','gom'),('Gondi','gon'),('Gone Dau','goo'),('Yeretuar','gop'),('Gorap','goq'),('Gorontalo','gor'),('Gronings','gos'),('Gothic','got'),('Gavar','gou'),('Gorowa','gow'),('Gobu','gox'),('Goundo','goy'),('Gozarkhani','goz'),('GupaAbawa','gpa'),('Ghanaian Pidgin English','gpe'),('Taiap','gpn'),('Guiqiong','gqi'),('Guana Brazil','gqn'),('Gor','gqr'),('Qau','gqu'),('Rajput Garasia','gra'),('Grebo','grb'),('Ancient Greek to 1453','grc'),('GuruntumMbaaru','grd'),('Madi','grg'),('GbiriNiragu','grh'),('Ghari','gri'),('Southern Grebo','grj'),('Kota Marudu Talantang','grm'),('Guarani','gn'),('Groma','gro'),('Gorovu','grq'),('Taznatit','grr'),('Gresi','grs'),('Garo','grt'),('Kistane','gru'),('Central Grebo','grv'),('Gweda','grw'),('Guriaso','grx'),('Barclayville Grebo','gry'),('Guramalum','grz'),('Gascon','gsc'),('Ghanaian Sign Language','gse'),('German Sign Language','gsg'),('Gusilay','gsl'),('Guatemalan Sign Language','gsm'),('Gusan','gsn'),('Southwest Gbaya','gso'),('Wasembo','gsp'),('Greek Sign Language','gss'),('Swiss German','gsw'),('Gbatiri','gti'),('Shiki','gua'),('Wayuu','guc'),('Gurinji','gue'),('Gupapuyngu','guf'),('Guahibo','guh'),('Gujarati','gu'),('Gumuz','guk'),('Sea Island Creole English','gul'),('Guambiano','gum'),('Guayabero','guo'),('Gunwinggu','gup'),('Farefare','gur'),('Guinean Sign Language','gus'),('Gey','guv'),('Gun','guw'),('Gusii','guz'),('Guana Paraguay','gva'),('Guanano','gvc'),('Duwet','gve'),('Golin','gvf'),('Gulay','gvl'),('Gurmana','gvm'),('KukuYalanji','gvn'),('Western Gurung','gvr'),('Gumawana','gvs'),('Guyani','gvy'),('Mbato','gwa'),('Gwa','gwb'),('Kalami','gwc'),('Gawwada','gwd'),('Gweno','gwe'),('Gowro','gwf'),('Moo','gwg'),('Awngthim','gwm'),('Gwandara','gwn'),('Gwere','gwr'),('GawarBati','gwt'),('Guwamu','gwu'),('Kwini','gww'),('Gua','gwx'),('Northwest Gbaya','gya'),('Garus','gyb'),('Kayardild','gyd'),('Gyem','gye'),('Gungabula','gyf'),('Gbayi','gyg'),('Gyele','gyi'),('Gayil','gyl'),('Guyanese Creole English','gyn'),('Guarayu','gyr'),('Gunya','gyy'),('Ganza','gza'),('Gazi','gzi'),('Gane','gzn'),('Han','haa'),('Hanoi Sign Language','hab'),('Gurani','hac'),('Hatam','had'),('Eastern Oromo','hae'),('Haiphong Sign Language','haf'),('Hanga','hag'),('Hahon','hah'),('Haida','hai'),('Hajong','haj'),('Hakka Chinese','hak'),('Halang','hal'),('Hewa','ham'),('Hangaza','han'),('Hupla','hap'),('Ha','haq'),('Harari','har'),('Haisla','has'),('Haitian','ht'),('Hausa','ha'),('Havu','hav'),('Hawaiian','haw'),('Southern Haida','hax'),('Haya','hay'),('Hazaragi','haz'),('Hamba','hba'),('Huba','hbb'),('Heiban','hbn'),('Ancient Hebrew','hbo'),('Habu','hbu'),('Andaman Creole Hindi','hca'),('Huichol','hch'),('Northern Haida','hdn'),('Honduras Sign Language','hds'),('Hadiyya','hdy'),('Northern Qiandong Miao','hea'),('Hebrew','he'),('Helong','heg'),('Hehe','heh'),('Heiltsuk','hei'),('Hemba','hem'),('Herero','hz'),('Haigwai','hgw'),('Hoia Hoia','hhi'),('Kerak','hhr'),('Hoyahoya','hhy'),('Lamang','hia'),('Hibito','hib'),('Hidatsa','hid'),('Fiji Hindi','hif'),('Kamwe','hig'),('Pamosu','hih'),('Hinduri','hii'),('Hijuk','hij'),('SeitKaitetu','hik'),('Hiligaynon','hil'),('Hindi','hi'),('Tsoa','hio'),('Hittite','hit'),('Hiw','hiw'),('Haji','hji'),('Kahe','hka'),('Hunde','hke'),('HunjaraKaina Ke','hkk'),('Hong Kong Sign Language','hks'),('Halia','hla'),('Halbi','hlb'),('Halang Doan','hld'),('Hlersu','hle'),('Matu Chin','hlt'),('Hieroglyphic Luwian','hlu'),('Southern Mashan Hmong','hma'),('Humburi Senni Songhay','hmb'),('Central Huishui Hmong','hmc'),('Large Flowery Miao','hmd'),('Eastern Huishui Hmong','hme'),('Hmong Don','hmf'),('Southwestern Guiyang Hmong','hmg'),('Southwestern Huishui Hmong','hmh'),('Northern Huishui Hmong','hmi'),('Ge','hmj'),('Maek','hmk'),('Luopohe Hmong','hml'),('Central Mashan Hmong','hmm'),('Hmong','hmn'),('Hiri Motu','ho'),('Northern Mashan Hmong','hmp'),('Eastern Qiandong Miao','hmq'),('Hmar','hmr'),('Southern Qiandong Miao','hms'),('Hamtai','hmt'),('Hamap','hmu'),('Western Mashan Hmong','hmw'),('Southern Guiyang Hmong','hmy'),('Hmong Shua','hmz'),('Mina Cameroon','hna'),('Southern Hindko','hnd'),('Chhattisgarhi','hne'),('Hani','hni'),('Hmong Njua','hnj'),('Hanunoo','hnn'),('Northern Hindko','hno'),('Caribbean Hindustani','hns'),('Hung','hnu'),('Hoava','hoa'),('Mari Madang Province','hob'),('Ho','hoc'),('Holma','hod'),('Horom','hoe'),('Holikachuk','hoi'),('Hadothi','hoj'),('Holu','hol'),('Homa','hom'),('Holoholo','hoo'),('Hopi','hop'),('Horo','hor'),('Ho Chi Minh City Sign Language','hos'),('Hote','hot'),('Hovongan','hov'),('Honi','how'),('Holiya','hoy'),('Hozo','hoz'),('Hpon','hpo'),('Hrangkhol','hra'),('Hre','hre'),('Haruku','hrk'),('Horned Miao','hrm'),('Haroi','hro'),('Horuru','hrr'),('Hruso','hru'),('Croatian','hr'),('Hunsrik','hrx'),('Harzani','hrz'),('Upper Sorbian','hsb'),('Southeastern Huastec','hsf'),('Hungarian Sign Language','hsh'),('Hausa Sign Language','hsl'),('Xiang Chinese','hsn'),('Harsusi','hss'),('Hoti','hti'),('Minica Huitoto','hto'),('Hadza','hts'),('Hitu','htu'),('Middle Hittite','htx'),('Huambisa','hub'),('Huaulu','hud'),('San Francisco Del Mar Huave','hue'),('Humene','huf'),('Huachipaeri','hug'),('Huilliche','huh'),('Huli','hui'),('Northern Guiyang Hmong','huj'),('Hulung','huk'),('Hula','hul'),('Hungana','hum'),('Hungarian','hu'),('Hu','huo'),('Hupa','hup'),('Tsat','huq'),('Halkomelem','hur'),('Huastec','hus'),('Humla','hut'),('Murui Huitoto','huu'),('San Mateo Del Mar Huave','huv'),('Hukumina','huw'),('Hunzib','huz'),('Haitian Vodoun Culture Language','hvc'),('San Dionisio Del Mar Huave','hve'),('Haveke','hvk'),('Sabu','hvn'),('Hwana','hwo'),('Hya','hya'),('Iaai','iai'),('Iatmul','ian'),('Iapama','iap'),('Purari','iar'),('Iban','iba'),('Ibibio','ibb'),('Iwaidja','ibd'),('Akpes','ibe'),('Ibanag','ibg'),('Ibilo','ibi'),('Ibaloi','ibl'),('Agoi','ibm'),('Ibino','ibn'),('Igbo','ig'),('Ibuoro','ibr'),('Ibu','ibu'),('Ibani','iby'),('Ede Ica','ica'),('Icelandic','is'),('Etkywan','ich'),('Icelandic Sign Language','icl'),('Islander Creole English','icr'),('IdakhoIsukhaTiriki','ida'),('IndoPortuguese','idb'),('Idon','idc'),('Ede Idaca','idd'),('Idere','ide'),('Idi','idi'),('Ido','io'),('Indri','idr'),('Idesa','ids'),('Idoma','idu'),('Amganad Ifugao','ifa'),('Batad Ifugao','ifb'),('Ifo','iff'),('Tuwali Ifugao','ifk'),('TekeFuumu','ifm'),('Mayoyao Ifugao','ifu'),('KeleyI Kallahan','ify'),('Ebira','igb'),('Igede','ige'),('Igana','igg'),('Igala','igl'),('Kanggape','igm'),('Ignaciano','ign'),('Isebe','igo'),('Interglossa','igs'),('Igwe','igw'),('Iha Based Pidgin','ihb'),('Ihievbe','ihi'),('Iha','ihp'),('Bidhawal','ihw'),('Sichuan Yi','ii'),('Izon','ijc'),('Biseni','ije'),('Ede Ije','ijj'),('Kalabari','ijn'),('Southeast Ijo','ijs'),('Eastern Canadian Inuktitut','ike'),('Iko','iki'),('Ika','ikk'),('Ikulu','ikl'),('OlulumoIkom','iko'),('Ikpeshi','ikp'),('Inuinnaqtun','ikt'),('Inuktitut','iu'),('IkuGoraAnkwa','ikv'),('Ikwere','ikw'),('Ik','ikx'),('Ikizu','ikz'),('Ile Ape','ila'),('Ila','ilb'),('Interlingue','ie'),('GarigIlgar','ilg'),('Ili Turki','ili'),('Ilongot','ilk'),('Iranun','ill'),('Iloko','ilo'),('International Sign','ils'),('Ilue','ilv'),('Talur','ilw'),('Mala Malasar','ima'),('Imeraguen','ime'),('Anamgura','imi'),('Miluk','iml'),('Imonda','imn'),('Imbongu','imo'),('Imroing','imr'),('Marsian','ims'),('Milyan','imy'),('Interlingua International Auxiliary Language Association','ia'),('Inga','inb'),('Indonesian','id'),('Ingush','inh'),('Jungle Inga','inj'),('Indonesian Sign Language','inl'),('Minaean','inm'),('Isinai','inn'),('InokeYate','ino'),('Indian Sign Language','ins'),('Intha','int'),('Inor','ior'),('TumaIrumu','iou'),('IowaOto','iow'),('Ipili','ipi'),('Inupiaq','ik'),('Ipiko','ipo'),('Iquito','iqu'),('Iresim','ire'),('Irarutu','irh'),('Irigwe','iri'),('Iraqw','irk'),('Ir','irr'),('Irula','iru'),('Kamberau','irx'),('Iraya','iry'),('Isabi','isa'),('Isconahua','isc'),('Isnag','isd'),('Italian Sign Language','ise'),('Irish Sign Language','isg'),('Esan','ish'),('NkemNkum','isi'),('Ishkashimi','isk'),('Masimasi','ism'),('Isanzu','isn'),('Isoko','iso'),('Israeli Sign Language','isr'),('Istriot','ist'),('Isu Menchum Division','isu'),('Italian','it'),('Binongan Itneg','itb'),('Itene','ite'),('Inlaod Itneg','iti'),('JudeoItalian','itk'),('Itelmen','itl'),('Itu Mbon Uzo','itm'),('Itonama','ito'),('Iteri','itr'),('Isekiri','its'),('Maeng Itneg','itt'),('Itutang','itu'),('Itawit','itv'),('Ito','itw'),('Itik','itx'),('Moyadan Itneg','ity'),('Iu Mien','ium'),('Ibatan','ivb'),('Ivatan','ivv'),('IWak','iwk'),('Iwam','iwm'),('Iwur','iwo'),('Sepik Iwam','iws'),('Ixcatec','ixc'),('Nebaj Ixil','ixi'),('Chajul Ixil','ixj'),('Ixil','ixl'),('Iyayu','iya'),('Mesaka','iyo'),('Yaka Congo','iyx'),('Ingrian','izh'),('IziEzaaIkwoMgbo','izi'),('Izere','izr'),('Hyam','jab'),('Jahanka','jad'),('Yabem','jae'),('Jara','jaf'),('Jah Hut','jah'),('Western Jacalteco','jai'),('Zazao','jaj'),('Jakun','jak'),('Yalahatan','jal'),('Jamaican Creole English','jam'),('Jandai','jan'),('Yanyuwa','jao'),('Yaqay','jaq'),('Jarawa Nigeria','jar'),('New Caledonian Javanese','jas'),('Jakati','jat'),('Yaur','jau'),('Javanese','jv'),('Jambi Malay','jax'),('Yannhangu','jay'),('Jawe','jaz'),('JudeoBerber','jbe'),('Arandai','jbj'),('Barikewa','jbk'),('Nafusi','jbn'),('Lojban','jbo'),('JofotekBromnya','jbr'),('Jukun Takum','jbu'),('Yawijibaya','jbw'),('Jamaican Country Sign Language','jcs'),('Krymchak','jct'),('Jad','jda'),('Jadgali','jdg'),('JudeoTat','jdt'),('Jebero','jeb'),('Jerung','jee'),('Jeng','jeg'),('Jeh','jeh'),('Yei','jei'),('Jeri Kuo','jek'),('Yelmek','jel'),('Dza','jen'),('Jere','jer'),('Manem','jet'),('Jonkor Bourmataguil','jeu'),('Ngbee','jgb'),('JudeoGeorgian','jge'),('Gwak','jgk'),('Ngomba','jgo'),('Jehai','jhi'),('Jhankot Sign Language','jhs'),('Jina','jia'),('Jibu','jib'),('Tol','jic'),('Bu','jid'),('Jilbe','jie'),('Djingili','jig'),('sTodsde','jih'),('Jiiddu','jii'),('Jilim','jil'),('Jimi Cameroon','jim'),('Jiamao','jio'),('Guanyinqiao','jiq'),('Jita','jit'),('Youle Jinuo','jiu'),('Shuar','jiv'),('Buyuan Jinuo','jiy'),('Bankal','jjr'),('Mobwa Karen','jkm'),('Kubo','jko'),('Paku Karen','jkp'),('Koro India','jkr'),('Labir','jku'),('Ngile','jle'),('Jamaican Sign Language','jls'),('Dima','jma'),('Zumbun','jmb'),('Machame','jmc'),('Yamdena','jmd'),('Jimi Nigeria','jmi'),('Jumli','jml'),('Makuri Naga','jmn'),('Kamara','jmr'),('Mashi Nigeria','jms'),('Mouwase','jmw'),('Western Juxtlahuaca Mixtec','jmx'),('Jangshung','jna'),('Jandavra','jnd'),('Yangman','jng'),('Janji','jni'),('Yemsa','jnj'),('Rawat','jnl'),('Jaunsari','jns'),('Joba','job'),('Wojenaka','jod'),('Jordanian Sign Language','jos'),('Jowulu','jow'),('Jewish Palestinian Aramaic','jpa'),('Japanese','ja'),('JudeoPersian','jpr'),('Jaqaru','jqr'),('Jarai','jra'),('JudeoArabic','jrb'),('Jiru','jrr'),('Jorto','jrt'),('Japanese Sign Language','jsl'),('Wannu','jub'),('Jurchen','juc'),('Worodougou','jud'),('Ngadjuri','jui'),('Wapan','juk'),('Jirel','jul'),('Jumjum','jum'),('Juang','jun'),('Jiba','juo'),('Jumla Sign Language','jus'),('Jutish','jut'),('Ju','juu'),('Juray','juy'),('Javindo','jvd'),('Caribbean Javanese','jvn'),('JwiraPepesa','jwi'),('Jiarong','jya'),('JudeoYemeni Arabic','jye'),('Jaya','jyy'),('KaraKalpak','kaa'),('Kabyle','kab'),('Kachin','kac'),('Adara','kad'),('Ketangalan','kae'),('Katso','kaf'),('Kajaman','kag'),('Kara Central African Republic','kah'),('Karekare','kai'),('Jju','kaj'),('Kayapa Kallahan','kak'),('Kalaallisut','kl'),('Kamba Kenya','kam'),('Kannada','kn'),('Xaasongaxango','kao'),('Bezhta','kap'),('Capanahua','kaq'),('Kashmiri','ks'),('Kanuri','kr'),('Kawi','kaw'),('Kao','kax'),('Kazakh','kk'),('Kalarko','kba'),('Kabardian','kbd'),('Kanju','kbe'),('Kakauhua','kbf'),('Khamba','kbg'),('Kaptiau','kbi'),('Kari','kbj'),('Grass Koiari','kbk'),('Kanembu','kbl'),('Iwal','kbm'),('Kare Central African Republic','kbn'),('Keliko','kbo'),('Kamano','kbq'),('Kafa','kbr'),('Kande','kbs'),('Abadi','kbt'),('Kabutra','kbu'),('Dera Indonesia','kbv'),('Kaiep','kbw'),('Ap Ma','kbx'),('Manga Kanuri','kby'),('Duhwa','kbz'),('Khanty','kca'),('Kawacha','kcb'),('Lubila','kcc'),('Kaivi','kce'),('Ukaan','kcf'),('Tyap','kcg'),('Vono','kch'),('Kamantan','kci'),('Kobiana','kcj'),('Kalanga','kck'),('Kela Papua New Guinea','kcl'),('Gula Central African Republic','kcm'),('Nubi','kcn'),('Kinalakna','kco'),('Kanga','kcp'),('Kamo','kcq'),('Katla','kcr'),('Koenoem','kcs'),('Kaian','kct'),('Kami Tanzania','kcu'),('Kete','kcv'),('Kabwari','kcw'),('KachamaGanjule','kcx'),('Korandje','kcy'),('Konongo','kcz'),('Worimi','kda'),('Kutu','kdc'),('Yankunytjatjara','kdd'),('Makonde','kde'),('Mamusi','kdf'),('Seba','kdg'),('Tem','kdh'),('Kumam','kdi'),('Karamojong','kdj'),('Numee','kdk'),('Tsikimba','kdl'),('Kagoma','kdm'),('Kunda','kdn'),('KaningdonNindem','kdp'),('Koch','kdq'),('Karaim','kdr'),('Lahu Shi','kds'),('Kuy','kdt'),('Kadaru','kdu'),('Kado','kdv'),('Koneraw','kdw'),('Kam','kdx'),('Keder','kdy'),('Kwaja','kdz'),('Kabuverdianu','kea'),('Keiga','kec'),('Kerewe','ked'),('Eastern Keres','kee'),('Kpessi','kef'),('Tese','keg'),('Keak','keh'),('Kei','kei'),('Kadar','kej'),('Kela Democratic Republic of Congo','kel'),('Kemak','kem'),('Kenyang','ken'),('Kakwa','keo'),('Kaikadi','kep'),('Kamar','keq'),('Kera','ker'),('Kugbo','kes'),('Ket','ket'),('Akebu','keu'),('Kanikkaran','kev'),('West Kewa','kew'),('Kukna','kex'),('Kupia','key'),('Kukele','kez'),('Kodava','kfa'),('Northwestern Kolami','kfb'),('KondaDora','kfc'),('Korra Koraga','kfd'),('Kota India','kfe'),('Koya','kff'),('Kudiya','kfg'),('Kurichiya','kfh'),('Kannada Kurumba','kfi'),('Kemiehua','kfj'),('Kinnauri','kfk'),('Kung','kfl'),('Khunsari','kfm'),('Kuk','kfn'),('Korwa','kfp'),('Korku','kfq'),('Kachchi','kfr'),('Bilaspuri','kfs'),('Kanjari','kft'),('Katkari','kfu'),('Kurmukar','kfv'),('Kharam Naga','kfw'),('Kullu Pahari','kfx'),('Kumaoni','kfy'),('Koyaga','kga'),('Kawe','kgb'),('Kasseng','kgc'),('Kataang','kgd'),('Komering','kge'),('Kube','kgf'),('Kusunda','kgg'),('Upper Tanudan Kalinga','kgh'),('Selangor Sign Language','kgi'),('Gamale Kham','kgj'),('Kunggari','kgl'),('Karingani','kgn'),('Krongo','kgo'),('Kaingang','kgp'),('Kamoro','kgq'),('Abun','kgr'),('Kumbainggar','kgs'),('Somyev','kgt'),('Kobol','kgu'),('Karas','kgv'),('Karon Dori','kgw'),('Kamaru','kgx'),('Kyerung','kgy'),('Khasi','kha'),('Tukang Besi North','khc'),('Korowai','khe'),('Khuen','khf'),('Khams Tibetan','khg'),('Kehu','khh'),('Kuturmi','khj'),('Halh Mongolian','khk'),('Lusi','khl'),('Central Khmer','km'),('Khandesi','khn'),('Khotanese','kho'),('Kapori','khp'),('Koyra Chiini Songhay','khq'),('Kharia','khr'),('Kasua','khs'),('Khamti','kht'),('Nkhumbi','khu'),('Khvarshi','khv'),('Khowar','khw'),('Kanu','khx'),('Kele Democratic Republic of Congo','khy'),('Keapara','khz'),('Kim','kia'),('Koalib','kib'),('Kickapoo','kic'),('Koshin','kid'),('Kibet','kie'),('Eastern Parbate Kham','kif'),('Kimaama','kig'),('Kilmeri','kih'),('Kitsai','kii'),('Kilivila','kij'),('Kikuyu','ki'),('Kariya','kil'),('Karagas','kim'),('Kinyarwanda','rw'),('Kiowa','kio'),('Sheshi Kham','kip'),('Kosadle','kiq'),('Kirghiz','ky'),('Kis','kis'),('Agob','kit'),('Kirmanjki individual language','kiu'),('Kimbu','kiv'),('Northeast Kiwai','kiw'),('Khiamniungan Naga','kix'),('Kirikiri','kiy'),('Kisi','kiz'),('Mlap','kja'),('Coastal Konjo','kjc'),('Southern Kiwai','kjd'),('Kisar','kje'),('Khalaj','kjf'),('Khmu','kjg'),('Khakas','kjh'),('Zabana','kji'),('Khinalugh','kjj'),('Highland Konjo','kjk'),('Western Parbate Kham','kjl'),('Kunjen','kjn'),('Harijan Kinnauri','kjo'),('Pwo Eastern Karen','kjp'),('Western Keres','kjq'),('Kurudu','kjr'),('East Kewa','kjs'),('Phrae Pwo Karen','kjt'),('Kashaya','kju'),('Ramopa','kjx'),('Erave','kjy'),('Bumthangkha','kjz'),('Kakanda','kka'),('Kwerisa','kkb'),('Odoodee','kkc'),('Kinuku','kkd'),('Kakabe','kke'),('Kalaktang Monpa','kkf'),('Mabaka Valley Kalinga','kkg'),('Kagulu','kki'),('Kako','kkj'),('Kokota','kkk'),('Kosarek Yale','kkl'),('Kiong','kkm'),('Kon Keu','kkn'),('Karko','kko'),('Gugubera','kkp'),('Kaiku','kkq'),('KirBalar','kkr'),('Giiwo','kks'),('Koi','kkt'),('Tumi','kku'),('Kangean','kkv'),('TekeKukuya','kkw'),('Kohin','kkx'),('Guguyimidjir','kky'),('Kaska','kkz'),('KlamathModoc','kla'),('Kiliwa','klb'),('Kolbila','klc'),('Gamilaraay','kld'),('Kulung Nepal','kle'),('Kendeje','klf'),('Tagakaulo','klg'),('Weliki','klh'),('Kalumpang','kli'),('Turkic Khalaj','klj'),('Kono Nigeria','klk'),('Kagan Kalagan','kll'),('Migum','klm'),('Kalenjin','kln'),('Kapya','klo'),('Kamasa','klp'),('Rumu','klq'),('Khaling','klr'),('Kalasha','kls'),('Nukna','klt'),('Klao','klu'),('Maskelynes','klv'),('Lindu','klw'),('Koluwawa','klx'),('Kalao','kly'),('Kabola','klz'),('Konni','kma'),('Kimbundu','kmb'),('Southern Dong','kmc'),('Majukayang Kalinga','kmd'),('Bakole','kme'),('Kare Papua New Guinea','kmf'),('Kalam','kmh'),('Kami Nigeria','kmi'),('Kumarbhag Paharia','kmj'),('Limos Kalinga','kmk'),('Tanudan Kalinga','kml'),('Kom India','kmm'),('Awtuw','kmn'),('Kwoma','kmo'),('Gimme','kmp'),('Kwama','kmq'),('Northern Kurdish','kmr'),('Kamasau','kms'),('Kemtuik','kmt'),('Kanite','kmu'),('Komo Democratic Republic of Congo','kmw'),('Waboda','kmx'),('Koma','kmy'),('Khorasani Turkish','kmz'),('Dera Nigeria','kna'),('Lubuagan Kalinga','knb'),('Central Kanuri','knc'),('Konda','knd'),('Kankanaey','kne'),('Mankanya','knf'),('Koongo','kng'),('Kayan River Kenyah','knh'),('Kanufi','kni'),('Western Kanjobal','knj'),('Kuranko','knk'),('Keninjal','knl'),('Konkani individual language','knn'),('Kono Sierra Leone','kno'),('Kwanja','knp'),('Kintaq','knq'),('Kaningra','knr'),('Kensiu','kns'),('Kono Guinea','knu'),('Tabo','knv'),('KungEkoka','knw'),('Kendayan','knx'),('Kanyok','kny'),('Konomala','koa'),('Kohoroxitari','kob'),('Kpati','koc'),('Kodi','kod'),('KacipoBalesi','koe'),('Kubi','kof'),('Cogui','kog'),('Koyo','koh'),('KomiPermyak','koi'),('Sara Dunjo','koj'),('Konkani macrolanguage','kok'),('Kol Papua New Guinea','kol'),('Komi','kv'),('Kongo','kg'),('Konzo','koo'),('Waube','kop'),('Kota Gabon','koq'),('Korean','ko'),('Kosraean','kos'),('Lagwan','kot'),('Koke','kou'),('KuduCamo','kov'),('Kugama','kow'),('Coxima','kox'),('Koyukon','koy'),('Korak','koz'),('Kutto','kpa'),('Mullu Kurumba','kpb'),('Curripaco','kpc'),('Koba','kpd'),('Kpelle','kpe'),('Komba','kpf'),('Kapingamarangi','kpg'),('Kplang','kph'),('Kofei','kpi'),('Kpan','kpk'),('Kpala','kpl'),('Koho','kpm'),('Ikposo','kpo'),('Paku Karen','kpp'),('KorupunSela','kpq'),('KorafeYegha','kpr'),('Tehit','kps'),('Karata','kpt'),('Kafoa','kpu'),('KomiZyrian','kpv'),('Kobon','kpw'),('Mountain Koiali','kpx'),('Koryak','kpy'),('Kupsabiny','kpz'),('Mum','kqa'),('Kovai','kqb'),('DoromuKoki','kqc'),('Koy Sanjaq Surat','kqd'),('Kalagan','kqe'),('Kakabai','kqf'),('Khe','kqg'),('Kisankasa','kqh'),('Koitabu','kqi'),('Koromira','kqj'),('Kotafon Gbe','kqk'),('Kyenele','kql'),('Khisa','kqm'),('Kaonde','kqn'),('Eastern Krahn','kqo'),('Krenak','kqq'),('Kimaragang','kqr'),('Northern Kissi','kqs'),('Klias River Kadazan','kqt'),('Seroa','kqu'),('Okolod','kqv'),('Kandas','kqw'),('Mser','kqx'),('Koorete','kqy'),('Korana','kqz'),('Kumhali','kra'),('Karkin','krb'),('KarachayBalkar','krc'),('KairuiMidiki','krd'),('Koro Vanuatu','krf'),('North Korowai','krg'),('Kurama','krh'),('Krio','kri'),('KinarayA','krj'),('Kerek','krk'),('Karelian','krl'),('Krim','krm'),('Sapo','krn'),('Korop','krp'),('Krui','krq'),('Gbaya Sudan','krs'),('Tumari Kanuri','krt'),('Kurukh','kru'),('Kavet','krv'),('Western Krahn','krw'),('Karon','krx'),('Kryts','kry'),('Sota Kanum','krz'),('ShuwaZamani','ksa'),('Shambala','ksb'),('Southern Kalinga','ksc'),('Kuanua','ksd'),('Kuni','kse'),('Bafia','ksf'),('Kusaghe','ksg'),('Krisa','ksi'),('Uare','ksj'),('Kansa','ksk'),('Kumalu','ksl'),('Kumba','ksm'),('Kasiguranin','ksn'),('Kofa','kso'),('Kaba','ksp'),('Kwaami','ksq'),('Borong','ksr'),('Southern Kisi','kss'),('Khamyang','ksu'),('Kusu','ksv'),('Kedang','ksx'),('Kharia Thar','ksy'),('Kodaku','ksz'),('Katua','kta'),('Kambaata','ktb'),('Kholok','ktc'),('Kokata','ktd'),('Nubri','kte'),('Kwami','ktf'),('Kalkutung','ktg'),('Karanga','kth'),('North Muyu','kti'),('Plapo Krumen','ktj'),('Kaniet','ktk'),('Koroshi','ktl'),('Kurti','ktm'),('Kuot','kto'),('Kaduo','ktp'),('Katabaga','ktq'),('Kota Marudu Tinagas','ktr'),('South Muyu','kts'),('Ketum','ktt'),('Kituba Democratic Republic of Congo','ktu'),('Eastern Katu','ktv'),('Kato','ktw'),('Kuanyama','kj'),('Kutep','kub'),('Kwinsu','kuc'),('Kuman','kue'),('Western Katu','kuf'),('Kupa','kug'),('Kushi','kuh'),('Kuria','kuj'),('Kulere','kul'),('Kumyk','kum'),('Kunama','kun'),('Kumukio','kuo'),('Kunimaipa','kup'),('Karipuna','kuq'),('Kurdish','ku'),('Kusaal','kus'),('Kutenai','kut'),('Upper Kuskokwim','kuu'),('Kur','kuv'),('Kpagua','kuw'),('Kukatja','kux'),('Kunza','kuz'),('Bagvalal','kva'),('Kubu','kvb'),('Kove','kvc'),('Kui Indonesia','kvd'),('Kalabakan','kve'),('Kabalai','kvf'),('KuniBoazi','kvg'),('Komodo','kvh'),('Kwang','kvi'),('Psikye','kvj'),('Korean Sign Language','kvk'),('Kayaw','kvl'),('Kendem','kvm'),('Border Kuna','kvn'),('Dobel','kvo'),('Kompane','kvp'),('Geba Karen','kvq'),('Kerinci','kvr'),('Kunggara','kvs'),('Lahta Karen','kvt'),('Yinbaw Karen','kvu'),('Kola','kvv'),('Wersing','kvw'),('Parkari Koli','kvx'),('Yintale Karen','kvy'),('Tsakwambo','kvz'),('Kwa','kwb'),('Likwala','kwc'),('Kwaio','kwd'),('Kwerba','kwe'),('Sara Kaba Deme','kwg'),('Kowiai','kwh'),('AwaCuaiquer','kwi'),('Kwanga','kwj'),('Kwakiutl','kwk'),('Kofyar','kwl'),('Kwambi','kwm'),('Kwangali','kwn'),('Kwomtari','kwo'),('Kodia','kwp'),('Kwak','kwq'),('Kwer','kwr'),('Kwese','kws'),('Kwesten','kwt'),('Kwakum','kwu'),('Kwinti','kww'),('Khirwar','kwx'),('San Salvador Kongo','kwy'),('Kwadi','kwz'),('Kairiru','kxa'),('Krobu','kxb'),('Konso','kxc'),('Brunei','kxd'),('Kakihum','kxe'),('Manumanaw Karen','kxf'),('Katingan','kxg'),('Karo Ethiopia','kxh'),('Keningau Murut','kxi'),('Kulfa','kxj'),('Zayein Karen','kxk'),('Nepali Kurux','kxl'),('Northern Khmer','kxm'),('KanowitTanjong Melanau','kxn'),('Wadiyara Koli','kxp'),('Koro Papua New Guinea','kxr'),('Kangjia','kxs'),('Koiwat','kxt'),('Kui India','kxu'),('Kuvi','kxv'),('Konai','kxw'),('Likuba','kxx'),('Kayong','kxy'),('Kerewo','kxz'),('Kwaya','kya'),('Butbut Kalinga','kyb'),('Kyaka','kyc'),('Karey','kyd'),('Krache','kye'),('Kouya','kyf'),('Keyagana','kyg'),('Karok','kyh'),('Kiput','kyi'),('Karao','kyj'),('Kamayo','kyk'),('Kalapuya','kyl'),('Kpatili','kym'),('Northern Binukidnon','kyn'),('Kelon','kyo'),('Kang','kyp'),('Kenga','kyq'),('Baram Kayan','kys'),('Kayagar','kyt'),('Western Kayah','kyu'),('Kayort','kyv'),('Kudmali','kyw'),('Rapoisi','kyx'),('Kambaira','kyy'),('Western Karaboro','kza'),('Kaibobo','kzb'),('Bondoukou Kulango','kzc'),('Kadai','kzd'),('Kosena','kze'),('Kikai','kzg'),('KenuziDongola','kzh'),('Kelabit','kzi'),('Coastal Kadazan','kzj'),('Kazukuru','kzk'),('Kayeli','kzl'),('Kais','kzm'),('Kokola','kzn'),('Kaningi','kzo'),('Kaidipang','kzp'),('Kaike','kzq'),('Karang','kzr'),('Sugut Dusun','kzs'),('Tambunan Dusun','kzt'),('Kayupulau','kzu'),('Komyandaret','kzv'),('Kamarian','kzx'),('Kango Tshopo District','kzy'),('Kalabra','kzz'),('Southern Subanen','laa'),('Linear A','lab'),('Lacandon','lac'),('Ladino','lad'),('Pattani','lae'),('Lafofa','laf'),('Langi','lag'),('Lahnda','lah'),('Lambya','lai'),('Lango Uganda','laj'),('Laka Nigeria','lak'),('Lalia','lal'),('Lamba','lam'),('Laru','lan'),('Lao','lo'),('Laka Chad','lap'),('Qabiao','laq'),('Larteh','lar'),('Lama Togo','las'),('Latin','la'),('Laba','lau'),('Latvian','lv'),('Lauje','law'),('Tiwa','lax'),('Lama Myanmar','lay'),('Aribwatsa','laz'),('Lui','lba'),('Label','lbb'),('Lakkia','lbc'),('Lak','lbe'),('Tinani','lbf'),('Laopang','lbg'),('Ladakhi','lbj'),('Central Bontok','lbk'),('Libon Bikol','lbl'),('Lodhi','lbm'),('Lamet','lbn'),('Laven','lbo'),('Wampar','lbq'),('Lohorung','lbr'),('Libyan Sign Language','lbs'),('Lachi','lbt'),('Labu','lbu'),('LavatburaLamusong','lbv'),('Tolaki','lbw'),('Lawangan','lbx'),('LamuLamu','lby'),('Lardil','lbz'),('Legenyem','lcc'),('Lola','lcd'),('Loncong','lce'),('Lubu','lcf'),('Luchazi','lch'),('Lisela','lcl'),('Tungag','lcm'),('Western Lawa','lcp'),('Luhu','lcq'),('LisabataNuniali','lcs'),('Luri','ldd'),('Lenyima','ldg'),('LamjaDengsaTola','ldh'),('Laari','ldi'),('Lemoro','ldj'),('Leelau','ldk'),('Kaan','ldl'),('Landoma','ldm'),('Loo','ldo'),('Tso','ldp'),('Lufu','ldq'),('LegaShabunda','lea'),('LalaBisa','leb'),('Leco','lec'),('Lendu','led'),('Lelemi','lef'),('Lengua','leg'),('Lenje','leh'),('Lemio','lei'),('Lengola','lej'),('Leipon','lek'),('Lele Democratic Republic of Congo','lel'),('Nomaande','lem'),('Lenca','len'),('Leti Cameroon','leo'),('Lepcha','lep'),('Lembena','leq'),('Lenkau','ler'),('Lese','les'),('LesingGelimi','let'),('Kara Papua New Guinea','leu'),('Lamma','lev'),('Ledo Kaili','lew'),('Luang','lex'),('Lemolang','ley'),('Lezghian','lez'),('Lefa','lfa'),('Lingua Franca Nova','lfn'),('Lungga','lga'),('Laghu','lgb'),('Lugbara','lgg'),('Laghuu','lgh'),('Lengilu','lgi'),('Lingarak','lgk'),('Wala','lgl'),('LegaMwenga','lgm'),('Opuuo','lgn'),('Logba','lgq'),('Lengo','lgr'),('Pahi','lgt'),('Longgu','lgu'),('Ligenza','lgz'),('Laha Viet Nam','lha'),('Laha Indonesia','lhh'),('Lahu Shi','lhi'),('Lahul Lohar','lhl'),('Lhomi','lhm'),('Lahanan','lhn'),('Lhokpu','lhp'),('LoToga','lht'),('Lahu','lhu'),('WestCentral Limba','lia'),('Likum','lib'),('Hlai','lic'),('Nyindrou','lid'),('Likila','lie'),('Limbu','lif'),('Ligbi','lig'),('Lihir','lih'),('Lingkhim','lii'),('Ligurian','lij'),('Lika','lik'),('Lillooet','lil'),('Limburgan','li'),('Lingala','ln'),('Liki','lio'),('Sekpele','lip'),('Libido','liq'),('Liberian English','lir'),('Lisu','lis'),('Lithuanian','lt'),('Logorik','liu'),('Liv','liv'),('Col','liw'),('Liabuku','lix'),('BandaBambari','liy'),('Libinza','liz'),('Rampi','lje'),('Laiyolo','lji'),('Lampung Api','ljp'),('Lakalei','lka'),('Kabras','lkb'),('Kucong','lkc'),('Kenyi','lke'),('Lakha','lkh'),('Laki','lki'),('Remun','lkj'),('LaekoLibuat','lkl'),('Lakon','lkn'),('Khayo','lko'),('Kisa','lks'),('Lakota','lkt'),('Lokoya','lky'),('LalaRoba','lla'),('Lolo','llb'),('Lele Guinea','llc'),('Ladin','lld'),('Lele Papua New Guinea','lle'),('Hermit','llf'),('Lole','llg'),('Lamu','llh'),('TekeLaali','lli'),('Ladji Ladji','llj'),('Lelak','llk'),('Lilau','lll'),('Lasalimu','llm'),('Lele Chad','lln'),('Khlor','llo'),('North Efate','llp'),('Lolak','llq'),('Lithuanian Sign Language','lls'),('Lau','llu'),('Lauan','llx'),('East Limba','lma'),('Merei','lmb'),('Limilngan','lmc'),('Lumun','lmd'),('South Lembata','lmf'),('Lamogai','lmg'),('Lambichhong','lmh'),('Lombi','lmi'),('West Lembata','lmj'),('Lamkang','lmk'),('Hano','lml'),('Lamam','lmm'),('Lambadi','lmn'),('Lombard','lmo'),('Limbum','lmp'),('Lamatuka','lmq'),('Lamalera','lmr'),('Limousin','lms'),('Lematang','lmt'),('Lamenu','lmu'),('Lomaiviti','lmv'),('Lake Miwok','lmw'),('Laimbue','lmx'),('Lamboya','lmy'),('Lumbee','lmz'),('Langbashe','lna'),('Mbalanhu','lnb'),('Languedocien','lnc'),('Lundayeh','lnd'),('Langobardic','lng'),('Lanoh','lnh'),('Leningitij','lnj'),('South Central Banda','lnl'),('Langam','lnm'),('Lorediakarkar','lnn'),('Lango Sudan','lno'),('Lintang','lnt'),('Longuda','lnu'),('Lonzo','lnz'),('Loloda','loa'),('Lobi','lob'),('Inonhan','loc'),('Berawan','lod'),('Saluan','loe'),('Logol','lof'),('Logo','log'),('Narim','loh'),('Lou','loj'),('Loko','lok'),('Mongo','lol'),('Loma Liberia','lom'),('Malawi Lomwe','lon'),('Lombo','loo'),('Lopa','lop'),('Lobala','loq'),('Loniu','los'),('Otuho','lot'),('Louisiana Creole French','lou'),('Lopi','lov'),('Tampias Lobu','low'),('Loun','lox'),('Loke','loy'),('Lozi','loz'),('Lelepa','lpa'),('Lepki','lpe'),('Long Phuri Naga','lpn'),('Lipo','lpo'),('Lopit','lpx'),('Northern Luri','lrc'),('Laurentian','lre'),('Laragia','lrg'),('Marachi','lri'),('Loarki','lrk'),('Lari','lrl'),('Marama','lrm'),('Lorang','lrn'),('Laro','lro'),('Southern Yamphu','lrr'),('Larantuka Malay','lrt'),('Larevat','lrv'),('Lemerig','lrz'),('Lasgerdi','lsa'),('Lishana Deni','lsd'),('Lusengo','lse'),('Lyons Sign Language','lsg'),('Lish','lsh'),('Lashi','lsi'),('Latvian Sign Language','lsl'),('Saamia','lsm'),('Laos Sign Language','lso'),('Panamanian Sign Language','lsp'),('Aruop','lsr'),('Lasi','lss'),('Trinidad and Tobago Sign Language','lst'),('Mauritian Sign Language','lsy'),('Late Middle Chinese','ltc'),('Latgalian','ltg'),('Leti Indonesia','lti'),('Tsotso','lto'),('Tachoni','lts'),('Latu','ltu'),('Luxembourgish','lb'),('LubaLulua','lua'),('LubaKatanga','lu'),('Aringa','luc'),('Ludian','lud'),('Luvale','lue'),('Laua','luf'),('Ganda','lg'),('Luiseno','lui'),('Luna','luj'),('Lunanakha','luk'),('Luimbi','lum'),('Lunda','lun'),('Luo Kenya and Tanzania','luo'),('Lumbu','lup'),('Lucumi','luq'),('Laura','lur'),('Mizo','lus'),('Lushootseed','lut'),('LumbaYakkha','luu'),('Luwati','luv'),('Luo Cameroon','luw'),('Luyia','luy'),('Southern Luri','luz'),('Lavukaleve','lvk'),('Standard Latvian','lvs'),('Levuka','lvu'),('Lwalu','lwa'),('Lewo Eleng','lwe'),('Wanga','lwg'),('White Lachi','lwh'),('Eastern Lawa','lwl'),('Laomian','lwm'),('Luwo','lwo'),('Lewotobi','lwt'),('Lewo','lww'),('Layakha','lya'),('Lyngngam','lyg'),('Luyana','lyn'),('Literary Chinese','lzh'),('Litzlitz','lzl'),('Leinong Naga','lzn'),('Laz','lzz'),('Yutanduchi Mixtec','mab'),('Macedonian','mk'),('Madurese','mad'),('BoRukul','mae'),('Mafa','maf'),('Magahi','mag'),('Marshallese','mh'),('Maithili','mai'),('Makasar','mak'),('Malayalam','ml'),('Mam','mam'),('Mandingo','man'),('Maori','mi'),('Marathi','mr'),('Masai','mas'),('San Francisco Matlatzinca','mat'),('Huautla Mazatec','mau'),('Mampruli','maw'),('North Moluccan Malay','max'),('Malay macrolanguage','ms'),('Central Mazahua','maz'),('Higaonon','mba'),('Western Bukidnon Manobo','mbb'),('Macushi','mbc'),('Dibabawon Manobo','mbd'),('Molale','mbe'),('Baba Malay','mbf'),('Mangseng','mbh'),('Ilianen Manobo','mbi'),('Malol','mbk'),('Ombamba','mbm'),('Mbo Cameroon','mbo'),('Malayo','mbp'),('Maisin','mbq'),('Sarangani Manobo','mbs'),('Matigsalug Manobo','mbt'),('MbulaBwazza','mbu'),('Mbulungish','mbv'),('Maring','mbw'),('Mari East Sepik Province','mbx'),('Memoni','mby'),('Amoltepec Mixtec','mbz'),('Maca','mca'),('Machiguenga','mcb'),('Bitur','mcc'),('Sharanahua','mcd'),('Itundujia Mixtec','mce'),('Mapoyo','mcg'),('Maquiritari','mch'),('Mese','mci'),('Mvanip','mcj'),('Mbunda','mck'),('Macaguaje','mcl'),('Malaccan Creole Portuguese','mcm'),('Masana','mcn'),('Makaa','mcp'),('Ese','mcq'),('Menya','mcr'),('Mambai','mcs'),('Mengisa','mct'),('Cameroon Mambila','mcu'),('Minanibai','mcv'),('Mawa Chad','mcw'),('Mpiemo','mcx'),('South Watut','mcy'),('Mawan','mcz'),('Mada Nigeria','mda'),('Morigi','mdb'),('Male Papua New Guinea','mdc'),('Mbum','mdd'),('Maba Chad','mde'),('Moksha','mdf'),('Massalat','mdg'),('Maguindanaon','mdh'),('Mamvu','mdi'),('Mangbetu','mdj'),('Mangbutu','mdk'),('Maltese Sign Language','mdl'),('Mayogo','mdm'),('Mbati','mdn'),('Southwest Gbaya','mdo'),('Mbala','mdp'),('Mbole','mdq'),('Mandar','mdr'),('Maria Papua New Guinea','mds'),('Mbere','mdt'),('Mboko','mdu'),('Mbosi','mdw'),('Dizin','mdx'),('Male Ethiopia','mdy'),('Menka','mea'),('Ikobi','meb'),('Mara','mec'),('Melpa','med'),('Mengen','mee'),('Megam','mef'),('Mea','meg'),('Southwestern Tlaxiaco Mixtec','meh'),('Midob','mei'),('Meyah','mej'),('Mekeo','mek'),('Central Melanau','mel'),('Mangala','mem'),('Mende Sierra Leone','men'),('Kedah Malay','meo'),('Miriwung','mep'),('Merey','meq'),('Meru','mer'),('Masmaje','mes'),('Mato','met'),('Motu','meu'),('Mann','mev'),('Maaka','mew'),('Hassaniyya','mey'),('Menominee','mez'),('Pattani Malay','mfa'),('Bangka','mfb'),('Mba','mfc'),('MendankweNkwen','mfd'),('Morisyen','mfe'),('Naki','mff'),('Mixifore','mfg'),('Matal','mfh'),('Wandala','mfi'),('Mefele','mfj'),('North Mofu','mfk'),('Putai','mfl'),('Marghi South','mfm'),('Cross River Mbembe','mfn'),('Mbe','mfo'),('Makassar Malay','mfp'),('Moba','mfq'),('Marithiel','mfr'),('Mexican Sign Language','mfs'),('Mokerang','mft'),('Mbwela','mfu'),('Mandjak','mfv'),('Mulaha','mfw'),('Melo','mfx'),('Mayo','mfy'),('Mabaan','mfz'),('Middle Irish 9001200','mga'),('Mararit','mgb'),('Morokodo','mgc'),('Moru','mgd'),('Mango','mge'),('Maklew','mgf'),('Mpumpong','mgg'),('MakhuwaMeetto','mgh'),('Lijili','mgi'),('Abureni','mgj'),('Mawes','mgk'),('MaleuKilenge','mgl'),('Mambae','mgm'),('Mbangi','mgn'),('Eastern Magar','mgp'),('Malila','mgq'),('MambweLungu','mgr'),('Manda Tanzania','mgs'),('Mongol','mgt'),('Mailu','mgu'),('Matengo','mgv'),('Matumbi','mgw'),('Omati','mgx'),('Mbunga','mgy'),('Mbugwe','mgz'),('Manda India','mha'),('Mahongwe','mhb'),('Mocho','mhc'),('Mbugu','mhd'),('Besisi','mhe'),('Mamaa','mhf'),('Margu','mhg'),('Maskoy Pidgin','mhh'),('Mogholi','mhj'),('Mungaka','mhk'),('Mauwake','mhl'),('MakhuwaMoniga','mhm'),('Mashi Zambia','mho'),('Balinese Malay','mhp'),('Mandan','mhq'),('Eastern Mari','mhr'),('Buru Indonesia','mhs'),('Mandahuaca','mht'),('DigaroMishmi','mhu'),('Arakanese','mhv'),('Mbukushu','mhw'),('Maru','mhx'),('Mor Mor Islands','mhz'),('Miami','mia'),('Mandaic','mid'),('Ocotepec Mixtec','mie'),('MofuGudur','mif'),('San Miguel El Grande Mixtec','mig'),('Chayuco Mixtec','mih'),('Abar','mij'),('Mikasuki','mik'),('Alacatlatzala Mixtec','mim'),('Minangkabau','min'),('Pinotepa Nacional Mixtec','mio'),('ApascoApoala Mixtec','mip'),('Isthmus Mixe','mir'),('Uncoded languages','mis'),('Southern Puebla Mixtec','mit'),('Cacaloxtepec Mixtec','miu'),('Mimi','miv'),('Akoye','miw'),('Mixtepec Mixtec','mix'),('Ayutla Mixtec','miy'),('Coatzospan Mixtec','miz'),('Mahei','mja'),('San Juan Colorado Mixtec','mjc'),('Northwest Maidu','mjd'),('Muskum','mje'),('Tu','mjg'),('Mwera Nyasa','mjh'),('Kim Mun','mji'),('Mawak','mjj'),('Matukar','mjk'),('Mandeali','mjl'),('Medebur','mjm'),('Ma Papua New Guinea','mjn'),('Malankuravan','mjo'),('Malapandaram','mjp'),('Malaryan','mjq'),('Malavedan','mjr'),('Miship','mjs'),('Sauria Paharia','mjt'),('MannaDora','mju'),('Mannan','mjv'),('Karbi','mjw'),('Mahali','mjx'),('Mahican','mjy'),('Majhi','mjz'),('Mbre','mka'),('Mal Paharia','mkb'),('Siliput','mkc'),('Mawchi','mke'),('Miya','mkf'),('Mak China','mkg'),('Dhatki','mki'),('Mokilese','mkj'),('Byep','mkk'),('Mokole','mkl'),('Moklen','mkm'),('Kupang Malay','mkn'),('Mingang Doso','mko'),('Moikodi','mkp'),('Bay Miwok','mkq'),('Malas','mkr'),('Silacayoapan Mixtec','mks'),('Vamale','mkt'),('Konyanka Maninka','mku'),('Mafea','mkv'),('Kituba Congo','mkw'),('Kinamiging Manobo','mkx'),('East Makian','mky'),('Makasae','mkz'),('Malo','mla'),('Mbule','mlb'),('Cao Lan','mlc'),('Malakhel','mld'),('Manambu','mle'),('Mal','mlf'),('Malagasy','mg'),('Mape','mlh'),('Malimpung','mli'),('Miltu','mlj'),('Ilwana','mlk'),('Malua Bay','mll'),('Mulam','mlm'),('Malango','mln'),('Mlomp','mlo'),('Bargam','mlp'),('Western Maninkakan','mlq'),('Vame','mlr'),('Masalit','mls'),('Maltese','mt'),('Motlav','mlv'),('Moloko','mlw'),('Malfaxal','mlx'),('Malay individual language','mly'),('Malaynon','mlz'),('Mama','mma'),('Momina','mmb'),('Maonan','mmd'),('Mae','mme'),('Mundat','mmf'),('North Ambrym','mmg'),('Musar','mmi'),('Majhwar','mmj'),('MukhaDora','mmk'),('Man Met','mml'),('Maii','mmm'),('Mamanwa','mmn'),('Mangga Buang','mmo'),('Siawi','mmp'),('Musak','mmq'),('Western Xiangxi Miao','mmr'),('Southern Mam','mms'),('Malalamai','mmt'),('Mmaala','mmu'),('Miriti','mmv'),('Emae','mmw'),('Madak','mmx'),('Migaama','mmy'),('Mabaale','mmz'),('Mbula','mna'),('Muna','mnb'),('Manchu','mnc'),('Naba','mne'),('Mundani','mnf'),('Eastern Mnong','mng'),('Mono Democratic Republic of Congo','mnh'),('Manipuri','mni'),('Munji','mnj'),('Mandinka','mnk'),('Tiale','mnl'),('Mapena','mnm'),('Southern Mnong','mnn'),('Min Bei Chinese','mnp'),('Minriq','mnq'),('Mono USA','mnr'),('Mansi','mns'),('Maykulan','mnt'),('Mer','mnu'),('RennellBellona','mnv'),('Mon','mnw'),('Manikion','mnx'),('Manyawa','mny'),('Moni','mnz'),('Mwan','moa'),('Moinba','mob'),('Mobilian','mod'),('Montagnais','moe'),('MoheganMontaukNarragansett','mof'),('Mongondow','mog'),('Mohawk','moh'),('Mboi','moi'),('Monzombo','moj'),('Morori','mok'),('Moldavian','mo'),('Mangue','mom'),('Mongolian','mn'),('Monom','moo'),('Mor Bomberai Peninsula','moq'),('Moro','mor'),('Mossi','mos'),('Mogum','mou'),('Mohave','mov'),('Moi Congo','mow'),('Molima','mox'),('Shekkacho','moy'),('Mukulu','moz'),('Mpoto','mpa'),('Mullukmulluk','mpb'),('Mangarayi','mpc'),('Machinere','mpd'),('Majang','mpe'),('Tajumulco Mam','mpf'),('Marba','mpg'),('Maung','mph'),('Mpade','mpi'),('Martu Wangka','mpj'),('Mbara Chad','mpk'),('Middle Watut','mpl'),('Mindiri','mpn'),('Miu','mpo'),('Migabac','mpp'),('Vangunu','mpr'),('Dadibi','mps'),('Mian','mpt'),('Mungkip','mpv'),('Mapidian','mpw'),('MisimaPanaeati','mpx'),('Mapia','mpy'),('Mpi','mpz'),('Maba Indonesia','mqa'),('Mbuko','mqb'),('Mangole','mqc'),('Madang','mqd'),('Matepi','mqe'),('Momuna','mqf'),('Kota Bangun Kutai Malay','mqg'),('Tlazoyaltepec Mixtec','mqh'),('Mariri','mqi'),('Mamasa','mqj'),('Rajah Kabunsuwan Manobo','mqk'),('Mbelime','mql'),('South Marquesan','mqm'),('Moronene','mqn'),('Modole','mqo'),('Manipa','mqp'),('Minokok','mqq'),('Mander','mqr'),('West Makian','mqs'),('Mok','mqt'),('Mandari','mqu'),('Mosimo','mqv'),('Murupi','mqw'),('Mamuju','mqx'),('Manggarai','mqy'),('Pano','mqz'),('Mlabri','mra'),('Marino','mrb'),('Maricopa','mrc'),('Western Magar','mrd'),('Elseng','mrf'),('Mising','mrg'),('Mara Chin','mrh'),('Western Mari','mrj'),('Hmwaveke','mrk'),('Mortlockese','mrl'),('Merlav','mrm'),('Cheke Holo','mrn'),('Mru','mro'),('Morouas','mrp'),('North Marquesan','mrq'),('Maria India','mrr'),('Maragus','mrs'),('Marghi Central','mrt'),('Mono Cameroon','mru'),('Mangareva','mrv'),('Maranao','mrw'),('Maremgi','mrx'),('Mandaya','mry'),('Marind','mrz'),('Masbatenyo','msb'),('Sankaran Maninka','msc'),('Yucatec Maya Sign Language','msd'),('Musey','mse'),('Mekwei','msf'),('Moraid','msg'),('Masikoro Malagasy','msh'),('Sabah Malay','msi'),('Ma Democratic Republic of Congo','msj'),('Mansaka','msk'),('Molof','msl'),('Agusan Manobo','msm'),('Mombum','mso'),('Caac','msq'),('Mongolian Sign Language','msr'),('West Masela','mss'),('Cataelano Mandaya','mst'),('Musom','msu'),('Maslam','msv'),('Mansoanka','msw'),('Moresada','msx'),('Aruamu','msy'),('Momare','msz'),('Cotabato Manobo','mta'),('Anyin Morofo','mtb'),('Munit','mtc'),('Mualang','mtd'),('Mono Solomon Islands','mte'),('Murik Papua New Guinea','mtf'),('Una','mtg'),('Munggui','mth'),('Maiwa Papua New Guinea','mti'),('Moskona','mtj'),('Montol','mtl'),('Mator','mtm'),('Matagalpa','mtn'),('Totontepec Mixe','mto'),('Muong','mtq'),('Mewari','mtr'),('Yora','mts'),('Mota','mtt'),('Tututepec Mixtec','mtu'),('Southern Binukidnon','mtw'),('Nabi','mty'),('Tacanec','mtz'),('Mundang','mua'),('Mubi','mub'),('Mednyj Aleut','mud'),('Media Lengua','mue'),('Musgu','mug'),('Musi','mui'),('Mabire','muj'),('Mugom','muk'),('Multiple languages','mul'),('Maiwala','mum'),('Nyong','muo'),('Malvi','mup'),('Eastern Xiangxi Miao','muq'),('Murle','mur'),('Creek','mus'),('Western Muria','mut'),('Yaaku','muu'),('Muthuvan','muv'),('Mundari','muw'),('BoUng','mux'),('Muyang','muy'),('Mursi','muz'),('Manam','mva'),('Mattole','mvb'),('Central Mam','mvc'),('Mamboru','mvd'),('Marwari Pakistan','mve'),('Peripheral Mongolian','mvf'),('Mulgi','mvh'),('Miyako','mvi'),('Mekmek','mvk'),('Mbara Australia','mvl'),('Muya','mvm'),('Minaveha','mvn'),('Marovo','mvo'),('Duri','mvp'),('Moere','mvq'),('Marau','mvr'),('Massep','mvs'),('Mpotovoro','mvt'),('Marfa','mvu'),('Tagal Murut','mvv'),('Machinga','mvw'),('Meoswar','mvx'),('Indus Kohistani','mvy'),('Mesqan','mvz'),('Mwatebu','mwa'),('Juwal','mwb'),('Are','mwc'),('Mudbura','mwd'),('Mwera Chimwera','mwe'),('MurrinhPatha','mwf'),('Aiklep','mwg'),('MoukAria','mwh'),('Labo','mwi'),('Maligo','mwj'),('Kita Maninkakan','mwk'),('Mirandese','mwl'),('Sar','mwm'),('Nyamwanga','mwn'),('Central Maewo','mwo'),('Kala Lagaw Ya','mwp'),('Marwari','mwr'),('MwimbiMuthambi','mws'),('Moken','mwt'),('Mittu','mwu'),('Mentawai','mwv'),('Hmong Daw','mww'),('Mediak','mwx'),('Mosiro','mwy'),('Moingi','mwz'),('Northwest Oaxaca Mixtec','mxa'),('Manyika','mxc'),('Modang','mxd'),('MeleFila','mxe'),('Malgbe','mxf'),('Mbangala','mxg'),('Mvuba','mxh'),('Mozarabic','mxi'),('MijuMishmi','mxj'),('Monumbo','mxk'),('Maxi Gbe','mxl'),('Meramera','mxm'),('Moi Indonesia','mxn'),('Mbowe','mxo'),('Tlahuitoltepec Mixe','mxp'),('Juquila Mixe','mxq'),('Murik Malaysia','mxr'),('Huitepec Mixtec','mxs'),('Jamiltepec Mixtec','mxt'),('Mada Cameroon','mxu'),('Namo','mxw'),('Mahou','mxx'),('Central Masela','mxz'),('Mbay','myb'),('Mayeka','myc'),('Maramba','myd'),('Myene','mye'),('Bambassi','myf'),('Manta','myg'),('Makah','myh'),('Mina India','myi'),('Mangayat','myj'),('Mamara Senoufo','myk'),('Moma','myl'),('Anfillo','myo'),('Forest Maninka','myq'),('Muniche','myr'),('Mesmes','mys'),('Sangab Mandaya','myt'),('Erzya','myv'),('Muyuw','myw'),('Masaaba','myx'),('Macuna','myy'),('Classical Mandaic','myz'),('Tumzabt','mzb'),('Madagascar Sign Language','mzc'),('Malimba','mzd'),('Morawa','mze'),('Aiku','mzf'),('Monastic Sign Language','mzg'),('Manya','mzj'),('Nigeria Mambila','mzk'),('Mumuye','mzm'),('Mazanderani','mzn'),('Matipuhy','mzo'),('Movima','mzp'),('Mori Atas','mzq'),('Macanese','mzs'),('Mintil','mzt'),('Inapang','mzu'),('Manza','mzv'),('Deg','mzw'),('Mawayana','mzx'),('Mozambican Sign Language','mzy'),('Maiadomu','mzz'),('Namla','naa'),('Narak','nac'),('Nijadali','nad'),('Nabak','naf'),('Naga Pidgin','nag'),('Nalu','naj'),('Nakanai','nak'),('Nalik','nal'),('Min Nan Chinese','nan'),('Naaba','nao'),('Neapolitan','nap'),('Nama Namibia','naq'),('Iguta','nar'),('Naasioi','nas'),('Hungworo','nat'),('Nauru','na'),('Navajo','nv'),('Nawuri','naw'),('Nakwi','nax'),('Narrinyeri','nay'),('Coatepec Nahuatl','naz'),('Nyemba','nba'),('Ndoe','nbb'),('Chang Naga','nbc'),('Ngbinda','nbd'),('Konyak Naga','nbe'),('Naxi','nbf'),('Nagarchal','nbg'),('Ngamo','nbh'),('Mao Naga','nbi'),('Ngarinman','nbj'),('Nake','nbk'),('South Ndebele','nr'),('Kuri','nbn'),('Nkukoli','nbo'),('Nnam','nbp'),('Nggem','nbq'),('NumanaNunkuGbantuNumbu','nbr'),('Namibian Sign Language','nbs'),('Na','nbt'),('Rongmei Naga','nbu'),('Ngamambo','nbv'),('Southern Ngbandi','nbw'),('Ngura','nbx'),('Ningera','nby'),('Iyo','nca'),('Central Nicobarese','ncb'),('Ponam','ncc'),('Nachering','ncd'),('Yale','nce'),('Notsi','ncf'),('Central Huasteca Nahuatl','nch'),('Classical Nahuatl','nci'),('Northern Puebla Nahuatl','ncj'),('Nakara','nck'),('Nambo','ncm'),('Nauna','ncn'),('Sibe','nco'),('Ndaktup','ncp'),('Ncane','ncr'),('Nicaraguan Sign Language','ncs'),('Chothe Naga','nct'),('Chumburung','ncu'),('Central Puebla Nahuatl','ncx'),('Natchez','ncz'),('Ndasa','nda'),('Kenswei Nsei','ndb'),('Ndau','ndc'),('NdeNseleNta','ndd'),('North Ndebele','nd'),('Nadruvian','ndf'),('Ndengereko','ndg'),('Ndali','ndh'),('Samba Leko','ndi'),('Ndamba','ndj'),('Ndaka','ndk'),('Ndolo','ndl'),('Ndam','ndm'),('Ngundi','ndn'),('Ndonga','ng'),('Ndo','ndp'),('Ndombe','ndq'),('Ndoola','ndr'),('Low German','nds'),('Ndunga','ndt'),('Dugun','ndu'),('Ndut','ndv'),('Ndobo','ndw'),('Nduga','ndx'),('Lutos','ndy'),('Ndogo','ndz'),('Nedebang','nec'),('NdeGbite','ned'),('Nefamese','nef'),('Negidal','neg'),('Nyenkha','neh'),('NeoHittite','nei'),('Neko','nej'),('Neku','nek'),('Nemi','nem'),('Nengone','nen'),('Nepali macrolanguage','ne'),('North Central Mixe','neq'),('Yahadian','ner'),('Bhoti Kinnauri','nes'),('Nete','net'),('Neo','neu'),('Nyaheun','nev'),('Newari','new'),('Neme','nex'),('Neyo','ney'),('Nez Perce','nez'),('Dhao','nfa'),('Ahwai','nfd'),('Nyeng','nfg'),('Shakara','nfk'),('Ayiwo','nfl'),('Nafaanra','nfr'),('Mfumte','nfu'),('Ngbaka','nga'),('Northern Ngbandi','ngb'),('Ngombe Democratic Republic of Congo','ngc'),('Ngando Central African Republic','ngd'),('Ngemba','nge'),('Ngbaka Manza','ngg'),('Ngizim','ngi'),('Ngie','ngj'),('Ngalkbun','ngk'),('Lomwe','ngl'),('Ngwo','ngn'),('Ngoni','ngo'),('Ngulu','ngp'),('Ngurimi','ngq'),('Engdewu','ngr'),('Gvoko','ngs'),('Ngeq','ngt'),('Guerrero Nahuatl','ngu'),('Nagumi','ngv'),('Ngwaba','ngw'),('Nggwahyi','ngx'),('Tibea','ngy'),('Ngungwel','ngz'),('Nhanda','nha'),('Beng','nhb'),('Tabasco Nahuatl','nhc'),('Eastern Huasteca Nahuatl','nhe'),('Nhuwala','nhf'),('Tetelcingo Nahuatl','nhg'),('Nahari','nhh'),('Tlalitzlipa Nahuatl','nhj'),('IsthmusCosoleacaque Nahuatl','nhk'),('Morelos Nahuatl','nhm'),('Central Nahuatl','nhn'),('Takuu','nho'),('IsthmusPajapan Nahuatl','nhp'),('Huaxcaleca Nahuatl','nhq'),('Naro','nhr'),('Southeastern Puebla Nahuatl','nhs'),('Ometepec Nahuatl','nht'),('Noone','nhu'),('Temascaltepec Nahuatl','nhv'),('Western Huasteca Nahuatl','nhw'),('IsthmusMecayapan Nahuatl','nhx'),('Northern Oaxaca Nahuatl','nhy'),('Nias','nia'),('Nakame','nib'),('Ngandi','nid'),('Niellim','nie'),('Nek','nif'),('Ngalakan','nig'),('Nyiha Tanzania','nih'),('Nii','nii'),('Ngaju','nij'),('Southern Nicobarese','nik'),('Nila','nil'),('Nilamba','nim'),('Ninzo','nin'),('Nganasan','nio'),('Nandi','niq'),('Nimboran','nir'),('Nimi','nis'),('Southeastern Kolami','nit'),('Niuean','niu'),('Gilyak','niv'),('Nimo','niw'),('Hema','nix'),('Ngiti','niy'),('Ningil','niz'),('Nzanyi','nja'),('Nocte Naga','njb'),('Ndonde Hamba','njd'),('Lotha Naga','njh'),('Gudanji','nji'),('Njen','njj'),('Njalgulgule','njl'),('Angami Naga','njm'),('Liangmai Naga','njn'),('Ao Naga','njo'),('Njerep','njr'),('Nisa','njs'),('NdyukaTrio Pidgin','njt'),('Ngadjunmaya','nju'),('Kunyi','njx'),('Njyem','njy'),('Nyishi','njz'),('Nkoya','nka'),('Khoibu Naga','nkb'),('Nkongho','nkc'),('Koireng','nkd'),('Duke','nke'),('Inpui Naga','nkf'),('Nekgini','nkg'),('Khezha Naga','nkh'),('Thangal Naga','nki'),('Nakai','nkj'),('Nokuku','nkk'),('Namat','nkm'),('Nkangala','nkn'),('Nkonya','nko'),('Niuatoputapu','nkp'),('Nkami','nkq'),('Nukuoro','nkr'),('North Asmat','nks'),('Nyika Tanzania','nkt'),('Bouna Kulango','nku'),('Nyika Malawi and Zambia','nkv'),('Nkutu','nkw'),('Nkoroo','nkx'),('Khiamniungan Naga','nky'),('Nkari','nkz'),('Ngombale','nla'),('Nalca','nlc'),('East Nyala','nle'),('Gela','nlg'),('Grangali','nli'),('Nyali','nlj'),('Ninia Yali','nlk'),('Nihali','nll'),('Durango Nahuatl','nln'),('Ngul','nlo'),('Ngarla','nlr'),('Nchumbulu','nlu'),('Orizaba Nahuatl','nlv'),('Nahali','nlx'),('Nyamal','nly'),('Maram Naga','nma'),('Big Nambas','nmb'),('Ngam','nmc'),('Ndumu','nmd'),('Mzieme Naga','nme'),('Tangkhul Naga India','nmf'),('Kwasio','nmg'),('Monsang Naga','nmh'),('Nyam','nmi'),('Ngombe Central African Republic','nmj'),('Namakura','nmk'),('Ndemli','nml'),('Manangba','nmm'),('Moyon Naga','nmo'),('Nimanbur','nmp'),('Nambya','nmq'),('Nimbari','nmr'),('Letemboi','nms'),('Namonuito','nmt'),('Northeast Maidu','nmu'),('Ngamini','nmv'),('Nimoa','nmw'),('Nama Papua New Guinea','nmx'),('Namuyi','nmy'),('Nawdm','nmz'),('Nyangumarta','nna'),('Nande','nnb'),('Nancere','nnc'),('West Ambae','nnd'),('Ngandyera','nne'),('Ngaing','nnf'),('Maring Naga','nng'),('Ngiemboon','nnh'),('North Nuaulu','nni'),('Nyangatom','nnj'),('Nankina','nnk'),('Northern Rengma Naga','nnl'),('Namia','nnm'),('Ngete','nnn'),('Norwegian Nynorsk','nn'),('Wancho Naga','nnp'),('Ngindo','nnq'),('Narungga','nnr'),('Ningye','nns'),('Nanticoke','nnt'),('Dwang','nnu'),('Nugunu Australia','nnv'),('Southern Nuni','nnw'),('Ngong','nnx'),('Nyangga','nny'),('Woun Meu','noa'),('Nuk','noc'),('Northern Thai','nod'),('Nimadi','noe'),('Nomane','nof'),('Nogai','nog'),('Nomu','noh'),('Noiri','noi'),('Nonuya','noj'),('Nooksack','nok'),('Old Norse','non'),('Nootka','noo'),('Numanggang','nop'),('Ngongo','noq'),('Norwegian','no'),('Eastern Nisu','nos'),('Nomatsiguenga','not'),('EwageNotu','nou'),('Novial','nov'),('Nyambo','now'),('Noy','noy'),('Nayi','noz'),('Nar Phu','npa'),('Nupbikha','npb'),('PonyoGongwang Naga','npg'),('Phom Naga','nph'),('Nepali individual language','npi'),('Southeastern Puebla Nahuatl','npl'),('Mondropolon','npn'),('Pochuri Naga','npo'),('Nipsan','nps'),('Puimei Naga','npu'),('Napu','npy'),('Southern Nago','nqg'),('Kura Ede Nago','nqk'),('Ndom','nqm'),('Nen','nqn'),('Akyaung Ari Naga','nqy'),('Ngom','nra'),('Nara','nrb'),('Noric','nrc'),('Southern Rengma Naga','nre'),('Narango','nrg'),('Chokri Naga','nri'),('Ngarluma','nrl'),('Narom','nrm'),('Norn','nrn'),('North Picene','nrp'),('Norra','nrr'),('Northern Kalapuya','nrt'),('Narua','nru'),('Ngurmbur','nrx'),('Lala','nrz'),('Sangtam Naga','nsa'),('Nshi','nsc'),('Southern Nisu','nsd'),('Nsenga','nse'),('Northwestern Nisu','nsf'),('Ngasa','nsg'),('Ngoshie','nsh'),('Nigerian Sign Language','nsi'),('Naskapi','nsk'),('Norwegian Sign Language','nsl'),('Sumi Naga','nsm'),('Nehan','nsn'),('Pedi','nso'),('Nepalese Sign Language','nsp'),('Northern Sierra Miwok','nsq'),('Maritime Sign Language','nsr'),('Nali','nss'),('Tase Naga','nst'),('Sierra Negra Nahuatl','nsu'),('Southwestern Nisu','nsv'),('Navut','nsw'),('Nsongo','nsx'),('Nasal','nsy'),('Nisenan','nsz'),('Nathembo','nte'),('Natioro','nti'),('Ngaanyatjarra','ntj'),('IkomaNataIsenye','ntk'),('Nateni','ntm'),('Ntomba','nto'),('Northern Tepehuan','ntp'),('Delo','ntr'),('Natagaimas','nts'),('Nottoway','ntw'),('Tangkhul Naga Myanmar','ntx'),('Mantsi','nty'),('Natanzi','ntz'),('Yuaga','nua'),('Nukuini','nuc'),('Ngala','nud'),('Ngundu','nue'),('Nusu','nuf'),('Nungali','nug'),('Ndunda','nuh'),('Ngumbi','nui'),('Nyole','nuj'),('Nuuchahnulth','nuk'),('Nusa Laut','nul'),('Anong','nun'),('NupeNupeTako','nup'),('Nukumanu','nuq'),('Nukuria','nur'),('Nuer','nus'),('Nung Viet Nam','nut'),('Ngbundu','nuu'),('Northern Nuni','nuv'),('Nguluwan','nuw'),('Mehek','nux'),('Nunggubuyu','nuy'),('Tlamacazapa Nahuatl','nuz'),('Nasarian','nvh'),('Namiae','nvm'),('Nyokon','nvo'),('Nawathinehena','nwa'),('Nyabwa','nwb'),('Classical Newari','nwc'),('Ngwe','nwe'),('Ngayawung','nwg'),('Southwest Tanna','nwi'),('NyamusaMolo','nwm'),('Nauo','nwo'),('Nawaru','nwr'),('Middle Newar','nwx'),('NottowayMeherrin','nwy'),('Nauete','nxa'),('Ngando Democratic Republic of Congo','nxd'),('Nage','nxe'),('Nindi','nxi'),('Nyadu','nxj'),('Koki Naga','nxk'),('South Nuaulu','nxl'),('Numidian','nxm'),('Ngawun','nxn'),('Naxi','nxq'),('Ninggerum','nxr'),('Narau','nxu'),('Nafri','nxx'),('Nyanja','ny'),('Nyangbo','nyb'),('Nyangali','nyc'),('Nyore','nyd'),('Nyengo','nye'),('Giryama','nyf'),('Nyindu','nyg'),('Nyigina','nyh'),('Ama Sudan','nyi'),('Nyanga','nyj'),('Nyaneka','nyk'),('Nyeu','nyl'),('Nyamwezi','nym'),('Nyankole','nyn'),('Nyoro','nyo'),('Nayini','nyq'),('Nyiha Malawi','nyr'),('Nyunga','nys'),('Nyawaygi','nyt'),('Nyungwe','nyu'),('Nyulnyul','nyv'),('Nyaw','nyw'),('Nganyaywana','nyx'),('NyakyusaNgonde','nyy'),('Tigon Mbembe','nza'),('Njebi','nzb'),('Nzima','nzi'),('Nzakara','nzk'),('Zeme Naga','nzm'),('New Zealand Sign Language','nzs'),('TekeNzikou','nzu'),('Nzakambay','nzy'),('Nanga Dama Dogon','nzz'),('Orok','oaa'),('Oroch','oac'),('Old Aramaic up to 700 BCE','oar'),('Old Avar','oav'),('Southern Bontok','obk'),('Oblo','obl'),('Moabite','obm'),('Obo Manobo','obo'),('Old Burmese','obr'),('Old Breton','obt'),('Obulom','obu'),('Ocaina','oca'),('Occidental','occ'),('Old Chinese','och'),('Occitan post 1500','oc'),('Old Cornish','oco'),('Atzingo Matlatzinca','ocu'),('Odut','oda'),('Od','odk'),('Old Dutch','odt'),('Odual','odu'),('Ofo','ofo'),('Old Frisian','ofs'),('Efutop','ofu'),('Ogbia','ogb'),('Ogbah','ogc'),('Old Georgian','oge'),('Ogbogolo','ogg'),('Ogan','ogn'),('Khana','ogo'),('Ogbronuagum','ogu'),('Old Hittite','oht'),('Old Hungarian','ohu'),('Oirata','oia'),('Inebu One','oin'),('Northwestern Ojibwa','ojb'),('Central Ojibwa','ojc'),('Eastern Ojibwa','ojg'),('Ojibwa','oj'),('Old Japanese','ojp'),('Severn Ojibwa','ojs'),('Ontong Java','ojv'),('Western Ojibwa','ojw'),('Okanagan','oka'),('Okobo','okb'),('Okodia','okd'),('Okpe Southwestern Edo','oke'),('Koko Babangk','okg'),('Koreshe Rostam','okh'),('Okiek','oki'),('OkoJuwoi','okj'),('Kwamtim One','okk'),('Old Kentish Sign Language','okl'),('Middle Korean 10th16th cent','okm'),('OkiNoErabu','okn'),('Old Korean 3rd9th cent','oko'),('Kirike','okr'),('OkoEniOsayen','oks'),('Oku','oku'),('Orokaiva','okv'),('Okpe Northwestern Edo','okx'),('Walungge','ola'),('Mochi','old'),('Olekha','ole'),('Oloma','olm'),('Livvi','olo'),('Olrat','olr'),('OmahaPonca','oma'),('East Ambae','omb'),('Mochica','omc'),('Omejes','ome'),('Omagua','omg'),('Omi','omi'),('Omok','omk'),('Ombo','oml'),('Minoan','omn'),('Utarmbung','omo'),('Old Manipuri','omp'),('Old Marathi','omr'),('Omotik','omt'),('Omurano','omu'),('South Tairora','omw'),('Old Mon','omx'),('Ona','ona'),('Lingao','onb'),('Oneida','one'),('Olo','ong'),('Onin','oni'),('Onjob','onj'),('Kabore One','onk'),('Onobasulu','onn'),('Onondaga','ono'),('Sartang','onp'),('Northern One','onr'),('Ono','ons'),('Ontenu','ont'),('Unua','onu'),('Old Nubian','onw'),('Onin Based Pidgin','onx'),('Ong','oog'),('Oorlams','oor'),('Old Ossetic','oos'),('Okpamheri','opa'),('Old Persian','ope'),('Kopkaka','opk'),('Oksapmin','opm'),('Opao','opo'),('Opata','opt'),('Oroha','ora'),('Orma','orc'),('Oring','org'),('Oroqen','orh'),('Oriya macrolanguage','or'),('Orokaiva','ork'),('Oromo','om'),('Orang Kanaq','orn'),('Orokolo','oro'),('Oruma','orr'),('Orang Seletar','ors'),('Adivasi Oriya','ort'),('Ormuri','oru'),('Old Russian','orv'),('Oro Win','orw'),('Oro','orx'),('Oriya individual language','ory'),('Ormu','orz'),('Osage','osa'),('Oscan','osc'),('Osing','osi'),('Ososo','oso'),('Old Spanish','osp'),('Ossetian','os'),('Osatu','ost'),('Southern One','osu'),('Old Saxon','osx'),('Ottoman Turkish 15001928','ota'),('Old Tibetan','otb'),('Ot Danum','otd'),('Mezquital Otomi','ote'),('Oti','oti'),('Old Turkish','otk'),('Tilapa Otomi','otl'),('Eastern Highland Otomi','otm'),('Tenango Otomi','otn'),('Otoro','otr'),('Temoaya Otomi','ott'),('Otuke','otu'),('Ottawa','otw'),('Texcatepec Otomi','otx'),('Old Tamil','oty'),('Ixtenco Otomi','otz'),('Tagargrent','oua'),('GlioOubi','oub'),('Oune','oue'),('Old Uighur','oui'),('Ouma','oum'),('Owiniga','owi'),('Old Welsh','owl'),('Oy','oyb'),('Oyda','oyd'),('Wayampi','oym'),('Koonzime','ozm'),('Pacoh','pac'),('Pagibete','pae'),('Pangasinan','pag'),('Tenharim','pah'),('Pe','pai'),('IpekaTapuia','paj'),('Pahlavi','pal'),('Pampanga','pam'),('Punjabi/Panjabi','pa'),('Northern Paiute','pao'),('Papiamento','pap'),('Parya','paq'),('Panamint','par'),('Papasena','pas'),('Papitalai','pat'),('Palauan','pau'),('Pawnee','paw'),('Pech','pay'),('Patamona','pbc'),('Mezontla Popoloca','pbe'),('Coyotepec Popoloca','pbf'),('Paraujano','pbg'),('Parkwa','pbi'),('Mak Nigeria','pbl'),('Kpasam','pbn'),('Papel','pbo'),('Badyara','pbp'),('Pangwa','pbr'),('Central Pame','pbs'),('Southern Pashto','pbt'),('Northern Pashto','pbu'),('Pnar','pbv'),('Pyu','pby'),('Palu','pbz'),('Pear','pcb'),('Bouyei','pcc'),('Picard','pcd'),('Ruching Palaung','pce'),('Paliyan','pcf'),('Paniya','pcg'),('Pardhan','pch'),('Duruwa','pci'),('Parenga','pcj'),('Paite Chin','pck'),('Pardhi','pcl'),('Nigerian Pidgin','pcm'),('Piti','pcn'),('Pacahuara','pcp'),('Panang','pcr'),('Pyapun','pcw'),('Anam','pda'),('Pennsylvania German','pdc'),('Pa Di','pdi'),('Podena','pdn'),('Padoe','pdo'),('Plautdietsch','pdt'),('Kayan','pdu'),('Peranakan Indonesian','pea'),('Eastern Pomo','peb'),('Southern Pesisir','pec'),('Mala Papua New Guinea','ped'),('Taje','pee'),('Northeastern Pomo','pef'),('Pengo','peg'),('Bonan','peh'),('ChichimecaJonaz','pei'),('Northern Pomo','pej'),('Penchal','pek'),('Pekal','pel'),('Phende','pem'),('Penesak','pen'),('Old Persian ca 600400 BC','peo'),('Kunja','pep'),('Southern Pomo','peq'),('Iranian Persian','pes'),('Petats','pex'),('Petjo','pey'),('Eastern Penan','pez'),('Peere','pfe'),('Pfaelzisch','pfl'),('Sudanese Creole Arabic','pga'),('Pangwali','pgg'),('Pagi','pgi'),('Rerep','pgk'),('Primitive Irish','pgl'),('Paelignian','pgn'),('Pangseng','pgs'),('Pagu','pgu'),('Pongyong','pgy'),('PaHng','pha'),('Phudagi','phd'),('Phuong','phg'),('Phukha','phh'),('Phake','phk'),('Phalura','phl'),('Phimbi','phm'),('Phoenician','phn'),('Phunoi','pho'),('PahariPotwari','phr'),('Phu Thai','pht'),('Phuan','phu'),('Pahlavani','phv'),('Phangduwali','phw'),('Pima Bajo','pia'),('Yine','pib'),('Pinji','pic'),('Piaroa','pid'),('Piro','pie'),('Pingelapese','pif'),('Pisabo','pig'),('PitcairnNorfolk','pih'),('Pini','pii'),('Pijao','pij'),('Yom','pil'),('Powhatan','pim'),('Piame','pin'),('Piapoco','pio'),('Pero','pip'),('Piratapuyo','pir'),('Pijin','pis'),('Pitta Pitta','pit'),('PintupiLuritja','piu'),('Pileni','piv'),('Pimbwe','piw'),('Piu','pix'),('PiyaKwonci','piy'),('Pije','piz'),('Pitjantjatjara','pjt'),('Pokomo','pkb'),('Paekche','pkc'),('PakTong','pkg'),('Pankhu','pkh'),('Pakanha','pkn'),('Pukapuka','pkp'),('Attapady Kurumba','pkr'),('Pakistan Sign Language','pks'),('Maleng','pkt'),('Paku','pku'),('Miani','pla'),('Polonombauk','plb'),('Central Palawano','plc'),('Polari','pld'),('Paulohi','plh'),('Pali','pi'),('Polci','plj'),('Kohistani Shina','plk'),('Shwe Palaung','pll'),('Palembang','plm'),('Palenquero','pln'),('Oluta Popoluca','plo'),('Palpa','plp'),('Palaic','plq'),('Palaka Senoufo','plr'),('San Marcos Tlalcoyalco Popoloca','pls'),('Plateau Malagasy','plt'),('Southwest Palawano','plv'),('Bolyu','ply'),('Paluan','plz'),('Paama','pma'),('Pambia','pmb'),('Palumata','pmc'),('Pallanganmiddang','pmd'),('Pwaamei','pme'),('Pamona','pmf'),('Northern Pumi','pmi'),('Southern Pumi','pmj'),('Pamlico','pmk'),('Lingua Franca','pml'),('Pomo','pmm'),('Pam','pmn'),('Pom','pmo'),('Northern Pame','pmq'),('Paynamar','pmr'),('Piemontese','pms'),('Tuamotuan','pmt'),('Mirpur Panjabi','pmu'),('Plains Miwok','pmw'),('Poumei Naga','pmx'),('Papuan Malay','pmy'),('Southern Pame','pmz'),('Punan BahBiau','pna'),('Western Panjabi','pnb'),('Pannei','pnc'),('Western Penan','pne'),('Pongu','png'),('Penrhyn','pnh'),('Aoheng','pni'),('Paunaka','pnk'),('Punan Batu 1','pnm'),('PinaiHagahai','pnn'),('Panobo','pno'),('Pancana','pnp'),('Pana Burkina Faso','pnq'),('Panim','pnr'),('Ponosakan','pns'),('Pontic','pnt'),('Jiongnai Bunu','pnu'),('Pinigura','pnv'),('Panytyima','pnw'),('PhongKniang','pnx'),('Pinyin','pny'),('Pana Central African Republic','pnz'),('Eastern Pokomam','poa'),('Poqomam','poc'),('Ponares','pod'),('San Juan Atzingo Popoloca','poe'),('Poke','pof'),('Highland Popoluca','poi'),('Lower Pokomo','poj'),('Polish','pl'),('Southeastern Pomo','pom'),('Pohnpeian','pon'),('Central Pomo','poo'),('Pwapwa','pop'),('Texistepec Popoluca','poq'),('Portuguese','pt'),('Sayula Popoluca','pos'),('Potawatomi','pot'),('Southern Pokomam','pou'),('Upper Guinea Crioulo','pov'),('San Felipe Otlaltepec Popoloca','pow'),('Polabian','pox'),('Pogolo','poy'),('Pao','ppa'),('Papi','ppe'),('Paipai','ppi'),('Uma','ppk'),('Pipil','ppl'),('Papuma','ppm'),('Papapana','ppn'),('Folopa','ppo'),('Pelende','ppp'),('Pei','ppq'),('Piru','ppr'),('Pare','ppt'),('Papora','ppu'),('MalecitePassamaquoddy','pqm'),('Parachi','prc'),('ParsiDari','prd'),('Principense','pre'),('Paranan','prf'),('Prussian','prg'),('Porohanon','prh'),('Parauk','prk'),('Peruvian Sign Language','prl'),('Kibiri','prm'),('Prasuni','prn'),('Parsi','prp'),('Puri','prr'),('Dari','prs'),('Phai','prt'),('Puragi','pru'),('Parawen','prw'),('Purik','prx'),('Pray 3','pry'),('Providencia Sign Language','prz'),('Asue Awyu','psa'),('Persian Sign Language','psc'),('Plains Indian Sign Language','psd'),('Central Malay','pse'),('Penang Sign Language','psg'),('Southwest Pashayi','psh'),('Southeast Pashayi','psi'),('Puerto Rican Sign Language','psl'),('Pauserna','psm'),('Panasuan','psn'),('Polish Sign Language','pso'),('Philippine Sign Language','psp'),('Pasi','psq'),('Portuguese Sign Language','psr'),('Kaulong','pss'),('Central Pashto','pst'),('Port Sandwich','psw'),('Piscataway','psy'),('Pai Tavytera','pta'),('Pintiini','pti'),('Patani','ptn'),('Patep','ptp'),('Piamatsina','ptr'),('Enrekang','ptt'),('Bambam','ptu'),('Port Vato','ptv'),('Pentlatch','ptw'),('Pathiya','pty'),('Western Highland Purepecha','pua'),('Purum','pub'),('Punan Merap','puc'),('Punan Aput','pud'),('Puelche','pue'),('Punan Merah','puf'),('Phuie','pug'),('Puinave','pui'),('Punan Tubu','puj'),('Pu Ko','puk'),('Puma','pum'),('Pubian','pun'),('Puoc','puo'),('Pulabu','pup'),('Puquina','puq'),('Pushto','ps'),('Putoh','put'),('Punu','puu'),('Puluwatese','puw'),('Puare','pux'),('Purum Naga','puz'),('Pawaia','pwa'),('Panawa','pwb'),('Gapapaiwa','pwg'),('Molbog','pwm'),('Paiwan','pwn'),('Pwo Western Karen','pwo'),('Powari','pwr'),('Pwo Northern Karen','pww'),('Quetzaltepec Mixe','pxm'),('Pye Krumen','pye'),('Fyam','pym'),('Paraguayan Sign Language','pys'),('Puyuma','pyu'),('Pyu Myanmar','pyx'),('Pyen','pyy'),('Para Naga','pzn'),('Quapaw','qua'),('Quechua','qu'),('Lambayeque Quechua','quf'),('Chimborazo Highland Quichua','qug'),('South Bolivian Quechua','quh'),('Quileute','qui'),('Chachapoyas Quechua','quk'),('North Bolivian Quechua','qul'),('Sipacapense','qum'),('Quinault','qun'),('Southern Pastaza Quechua','qup'),('Quinqui','quq'),('Yanahuanca Pasco Quechua','qur'),('Santiago del Estero Quichua','qus'),('Sacapulteco','quv'),('Tena Lowland Quichua','quw'),('Yauyos Quechua','qux'),('Ayacucho Quechua','quy'),('Cusco Quechua','quz'),('AmboPasco Quechua','qva'),('Cajamarca Quechua','qvc'),('Imbabura Highland Quichua','qvi'),('Loja Highland Quichua','qvj'),('Cajatambo North Lima Quechua','qvl'),('MargosYarowilcaLauricocha Quechua','qvm'),('Napo Lowland Quechua','qvo'),('Pacaraos Quechua','qvp'),('Huaylla Wanca Quechua','qvw'),('Queyu','qvy'),('Northern Pastaza Quichua','qvz'),('Corongo Ancash Quechua','qwa'),('Classical Quechua','qwc'),('Huaylas Ancash Quechua','qwh'),('Kuman Russia','qwm'),('Sihuas Ancash Quechua','qws'),('KwalhioquaTlatskanai','qwt'),('Chincha Quechua','qxc'),('Salasaca Highland Quichua','qxl'),('Northern Conchucos Ancash Quechua','qxn'),('Southern Conchucos Ancash Quechua','qxo'),('Puno Quechua','qxp'),('Southern Qiang','qxs'),('Santa Ana de Tusi Pasco Quechua','qxt'),('Jauja Wanca Quechua','qxw'),('Quenya','qya'),('Quiripi','qyp'),('Dungmali','raa'),('Camling','rab'),('Rasawa','rac'),('Rade','rad'),('Ranau','rae'),('Western Meohang','raf'),('Logooli','rag'),('Rabha','rah'),('Ramoaaina','rai'),('Rajasthani','raj'),('TuluBohuai','rak'),('Ralte','ral'),('Canela','ram'),('Riantana','ran'),('Rao','rao'),('Rapanui','rap'),('Saam','raq'),('Rarotongan','rar'),('Tegali','ras'),('Razajerdi','rat'),('Raute','rau'),('Sampang','rav'),('Rawang','raw'),('Rang','rax'),('Rapa','ray'),('Rahambuu','raz'),('Rumai Palaung','rbb'),('Northern Bontok','rbk'),('Miraya Bikol','rbl'),('Barababaraba','rbp'),('Rudbari','rdb'),('Rerau','rea'),('Rembong','reb'),('Rejang Kayan','ree'),('Kara Tanzania','reg'),('Reli','rei'),('Rejang','rej'),('Rendille','rel'),('Remo','rem'),('Rengao','ren'),('Rer Bare','rer'),('Reshe','res'),('Retta','ret'),('Reyesano','rey'),('Roria','rga'),('RomanoGreek','rge'),('Rangkas','rgk'),('Romagnol','rgn'),('Southern Roglai','rgs'),('Ringgou','rgu'),('Rohingya','rhg'),('Yahang','rhp'),('Riang India','ria'),('Rien','rie'),('Tarifit','rif'),('Riang Myanmar','ril'),('Nyaturu','rim'),('Nungu','rin'),('Ribun','rir'),('Ritarungo','rit'),('Riung','riu'),('Rajbanshi','rjb'),('Rajong','rjg'),('Raji','rji'),('Rajbanshi','rjs'),('Kraol','rka'),('Rikbaktsa','rkb'),('RakahangaManihiki','rkh'),('Rakhine','rki'),('Marka','rkm'),('Rangpuri','rkt'),('Rama','rma'),('Rembarunga','rmb'),('Carpathian Romani','rmc'),('Traveller Danish','rmd'),('Angloromani','rme'),('Kalo Finnish Romani','rmf'),('Traveller Norwegian','rmg'),('Murkim','rmh'),('Lomavren','rmi'),('Romkun','rmk'),('Baltic Romani','rml'),('Roma','rmm'),('Balkan Romani','rmn'),('Sinte Romani','rmo'),('Rempi','rmp'),('Romanian Sign Language','rms'),('Domari','rmt'),('Tavringer Romani','rmu'),('Romanova','rmv'),('Welsh Romani','rmw'),('Romam','rmx'),('Vlax Romani','rmy'),('Marma','rmz'),('Runa','rna'),('Ruund','rnd'),('Ronga','rng'),('Ranglong','rnl'),('Roon','rnn'),('Rongpo','rnp'),('Nari Nari','rnr'),('Rungwa','rnw'),('Cacgia Roglai','roc'),('Rogo','rod'),('Ronji','roe'),('Rombo','rof'),('Northern Roglai','rog'),('Romansh','rm'),('Romblomanon','rol'),('Romany','rom'),('Romanian','ro'),('Rotokas','roo'),('Kriol','rop'),('Rongga','ror'),('Runga','rou'),('DelaOenale','row'),('Repanbitip','rpn'),('Rapting','rpt'),('Ririo','rri'),('Waima','rro'),('Arritinngithigh','rrt'),('RomanoSerbian','rsb'),('Rennellese Sign Language','rsi'),('Russian Sign Language','rsl'),('Rungtu Chin','rtc'),('Ratahan','rth'),('Rotuman','rtm'),('Rathawi','rtw'),('Gungu','rub'),('Ruuli','ruc'),('Rusyn','rue'),('Luguru','ruf'),('Roviana','rug'),('Ruga','ruh'),('Rufiji','rui'),('Che','ruk'),('Rundi','run'),('Istro Romanian','ruo'),('MacedoRomanian','rup'),('Megleno Romanian','ruq'),('Russian','ru'),('Rutul','rut'),('Lanas Lobu','ruu'),('Mala Nigeria','ruy'),('Ruma','ruz'),('Rawo','rwa'),('Rwa','rwk'),('Amba Uganda','rwm'),('Rawa','rwo'),('Marwari India','rwr'),('Rawas','rws'),('Northern AmamiOshima','ryn'),('Yaeyama','rys'),('Central Okinawan','ryu'),('Saba','saa'),('Buglere','sab'),('Meskwaki','sac'),('Sandawe','sad'),('Safaliba','saf'),('Sango','sg'),('Yakut','sah'),('Sahu','saj'),('Sake','sak'),('Samaritan Aramaic','sam'),('Sanskrit','sa'),('Sause','sao'),('Samburu','saq'),('Saraveca','sar'),('Sasak','sas'),('Santali','sat'),('Saleman','sau'),('SaafiSaafi','sav'),('Sawi','saw'),('Sa','sax'),('Saya','say'),('Saurashtra','saz'),('Ngambay','sba'),('Simbo','sbb'),('Kele Papua New Guinea','sbc'),('Southern Samo','sbd'),('Saliba','sbe'),('Shabo','sbf'),('Seget','sbg'),('SoriHarengan','sbh'),('Seti','sbi'),('Surbakhal','sbj'),('Safwa','sbk'),('Botolan Sambal','sbl'),('Sagala','sbm'),('Sindhi Bhil','sbn'),('Sangu Tanzania','sbp'),('Sileibi','sbq'),('Sembakung Murut','sbr'),('Subiya','sbs'),('Kimki','sbt'),('Stod Bhoti','sbu'),('Sabine','sbv'),('Simba','sbw'),('Seberuang','sbx'),('Soli','sby'),('Sara Kaba','sbz'),('Sansu','sca'),('Chut','scb'),('Serbian','sr'),('Dongxiang','sce'),('San Miguel Creole French','scf'),('Sanggau','scg'),('Sakachep','sch'),('Sri Lankan Creole Malay','sci'),('Sadri','sck'),('Shina','scl'),('Sicilian','scn'),('Scots','sco'),('Helambu Sherpa','scp'),('North Slavey','scs'),('Shumcho','scu'),('Sheni','scv'),('Sha','scw'),('Sicel','scx'),('Shabak','sdb'),('Sassarese Sardinian','sdc'),('Semendo','sdd'),('Surubu','sde'),('Sarli','sdf'),('Savi','sdg'),('Southern Kurdish','sdh'),('Sindang Kelingi','sdi'),('Suundi','sdj'),('Sos Kundi','sdk'),('Saudi Arabian Sign Language','sdl'),('Semandang','sdm'),('Gallurese Sardinian','sdn'),('BukarSadung Bidayuh','sdo'),('Sherdukpen','sdp'),('Oraon Sadri','sdr'),('Sened','sds'),('Shuadit','sdt'),('Sarudu','sdu'),('Sibu Melanau','sdx'),('Sallands','sdz'),('Semai','sea'),('Shempire Senoufo','seb'),('Sechelt','sec'),('Sedang','sed'),('Seneca','see'),('Cebaara Senoufo','sef'),('Segeju','seg'),('Sena','seh'),('Seri','sei'),('Sene','sej'),('Sekani','sek'),('Selkup','sel'),('Suarmin','seo'),('Serrano','ser'),('Koyraboro Senni Songhai','ses'),('Sentani','set'),('SeruiLaut','seu'),('Nyarafolo Senoufo','sev'),('Sewa Bay','sew'),('Secoya','sey'),('Senthang Chin','sez'),('Langue des signes de Belgique Francophone','sfb'),('Eastern Subanen','sfe'),('Small Flowery Miao','sfm'),('South African Sign Language','sfs'),('Sehwi','sfw'),('Old Irish to 900','sga'),('Magantsi Ayta','sgb'),('Kipsigis','sgc'),('Surigaonon','sgd'),('Segai','sge'),('SwissGerman Sign Language','sgg'),('Shughni','sgh'),('Suga','sgi'),('Surgujia','sgj'),('Sangkong','sgk'),('SanglechiIshkashimi','sgl'),('Singa','sgm'),('Songa','sgo'),('Singpho','sgp'),('Sangisari','sgr'),('Samogitian','sgs'),('Brokpake','sgt'),('Salas','sgu'),('Sebat Bet Gurage','sgw'),('Sierra Leone Sign Language','sgx'),('Sanglechi','sgy'),('Sursurunga','sgz'),('ShallZwall','sha'),('Ninam','shb'),('Sonde','shc'),('Kundal Shahi','shd'),('Sheko','she'),('Shua','shg'),('Shoshoni','shh'),('Tachelhit','shi'),('Shatt','shj'),('Shilluk','shk'),('Shendu','shl'),('Shahrudi','shm'),('Shan','shn'),('Shanga','sho'),('ShipiboConibo','shp'),('Sala','shq'),('Shi','shr'),('Shuswap','shs'),('Shasta','sht'),('Chadian Arabic','shu'),('Shehri','shv'),('Shwai','shw'),('She','shx'),('Tachawit','shy'),('Syenara Senoufo','shz'),('Akkala Sami','sia'),('Sebop','sib'),('Malinguat','sic'),('Sidamo','sid'),('Simaa','sie'),('Siamou','sif'),('Paasaal','sig'),('Zire','sih'),('Shom Peng','sii'),('Numbami','sij'),('Sikiana','sik'),('Tumulung Sisaala','sil'),('Mende Papua New Guinea','sim'),('Sinhala','si'),('Sikkimese','sip'),('Sonia','siq'),('Siri','sir'),('Siuslaw','sis'),('Sinagen','siu'),('Sumariup','siv'),('Siwai','siw'),('Sumau','six'),('Sivandi','siy'),('Siwi','siz'),('Epena','sja'),('Sajau Basap','sjb'),('Kildin Sami','sjd'),('Pite Sami','sje'),('Assangori','sjg'),('Kemi Sami','sjk'),('Sajalong','sjl'),('Mapun','sjm'),('Sindarin','sjn'),('Xibe','sjo'),('Surjapuri','sjp'),('SiarLak','sjr'),('Senhaja De Srair','sjs'),('Ter Sami','sjt'),('Ume Sami','sju'),('Shawnee','sjw'),('Skagit','ska'),('Saek','skb'),('Ma Manda','skc'),('Southern Sierra Miwok','skd'),('Seke Vanuatu','ske'),('Sakalava Malagasy','skg'),('Sikule','skh'),('Sika','ski'),('Seke Nepal','skj'),('Sok','skk'),('Selako','skl'),('Kutong','skm'),('Kolibugan Subanon','skn'),('Seko Tengah','sko'),('Sekapan','skp'),('Sininkere','skq'),('Seraiki','skr'),('Maia','sks'),('Sakata','skt'),('Sakao','sku'),('Skou','skv'),('Skepi Creole Dutch','skw'),('Seko Padang','skx'),('Sikaiana','sky'),('Sekar','skz'),('Kahumamahon Saluan','slb'),('Sissala','sld'),('Sholaga','sle'),('SwissItalian Sign Language','slf'),('Selungai Murut','slg'),('Southern Puget Sound Salish','slh'),('Lower Silesian','sli'),('Slovak','sk'),('SaltYui','sll'),('Pangutaran Sama','slm'),('Salinan','sln'),('Lamaholot','slp'),('Salchuq','slq'),('Salar','slr'),('Singapore Sign Language','sls'),('Sila','slt'),('Selaru','slu'),('Slovenian','sl'),('Sialum','slw'),('Salampasu','slx'),('Selayar','sly'),('Southern Sami','sma'),('Simbari','smb'),('Som','smc'),('Sama','smd'),('Northern Sami','se'),('Auwe','smf'),('Simbali','smg'),('Samei','smh'),('Lule Sami','smj'),('Bolinao','smk'),('Central Sama','sml'),('Musasa','smm'),('Inari Sami','smn'),('Samoan','sm'),('Samaritan','smp'),('Samo','smq'),('Simeulue','smr'),('Skolt Sami','sms'),('Simte','smt'),('Somray','smu'),('Samvedi','smv'),('Sumbawa','smw'),('Samba','smx'),('Semnani','smy'),('Simeku','smz'),('Shona','sn'),('Sebuyau','snb'),('Sinaugoro','snc'),('Sindhi','sd'),('Bau Bidayuh','sne'),('Noon','snf'),('Sanga Democratic Republic of Congo','sng'),('Shinabo','snh'),('Sensi','sni'),('Riverain Sango','snj'),('Soninke','snk'),('Sangil','snl'),('Siona','snn'),('Snohomish','sno'),('Siane','snp'),('Sangu Gabon','snq'),('Sihan','snr'),('South West Bay','sns'),('Senggi','snu'),('Selee','snw'),('Sam','snx'),('SaniyoHiyewe','sny'),('Sinsauru','snz'),('Thai Song','soa'),('Sobei','sob'),('So Democratic Republic of Congo','soc'),('Songoora','sod'),('Songomeno','soe'),('Sogdian','sog'),('Aka','soh'),('Sonha','soi'),('Soi','soj'),('Sokoro','sok'),('Solos','sol'),('Somali','so'),('Songo','soo'),('Songe','sop'),('Kanasi','soq'),('Somrai','sor'),('Seeku','sos'),('Southern Sotho','st'),('Southern Thai','sou'),('Sonsorol','sov'),('Sowanda','sow'),('Swo','sox'),('Miyobe','soy'),('Temi','soz'),('Spanish','es'),('Sepa Indonesia','spb'),('Saep','spd'),('Sepa Papua New Guinea','spe'),('Sian','spg'),('Saponi','spi'),('Sengo','spk'),('Selepet','spl'),('Akukem','spm'),('Spokane','spo'),('Supyire Senoufo','spp'),('LoretoUcayali Spanish','spq'),('Saparua','spr'),('Saposa','sps'),('Spiti Bhoti','spt'),('Sapuan','spu'),('Sambalpuri','spv'),('South Picene','spx'),('Sabaot','spy'),('ShamaSambuga','sqa'),('Shau','sqh'),('Albanian Sign Language','sqk'),('Suma','sqm'),('Susquehannock','sqn'),('Sorkhei','sqo'),('Sou','sqq'),('Siculo Arabic','sqr'),('Sri Lankan Sign Language','sqs'),('Soqotri','sqt'),('Squamish','squ'),('Saruga','sra'),('Sora','srb'),('Logudorese Sardinian','src'),('Sardinian','sc'),('Sara','sre'),('Nafi','srf'),('Sulod','srg'),('Sarikoli','srh'),('Siriano','sri'),('Serawai','srj'),('Serudung Murut','srk'),('Isirawa','srl'),('Saramaccan','srm'),('Sranan Tongo','srn'),('Campidanese Sardinian','sro'),('Serer','srr'),('Sarsi','srs'),('Sauri','srt'),('Southern Sorsoganon','srv'),('Serua','srw'),('Sirmauri','srx'),('Sera','sry'),('Shahmirzadi','srz'),('Southern Sama','ssb'),('SubaSimbiti','ssc'),('Siroi','ssd'),('Balangingi','sse'),('Thao','ssf'),('Seimat','ssg'),('Shihhi Arabic','ssh'),('Sansi','ssi'),('Sausi','ssj'),('Sunam','ssk'),('Western Sisaala','ssl'),('Semnam','ssm'),('Waata','ssn'),('Sissano','sso'),('Spanish Sign Language','ssp'),('SwissFrench Sign Language','ssr'),('Sinasina','sst'),('Susuami','ssu'),('Shark Bay','ssv'),('Swati','ss'),('Samberigi','ssx'),('Saho','ssy'),('Sengseng','ssz'),('Settla','sta'),('Northern Subanen','stb'),('Santa Cruz','stc'),('Sentinel','std'),('LianaSeti','ste'),('Seta','stf'),('Trieng','stg'),('Shelta','sth'),('Bulo Stieng','sti'),('Matya Samo','stj'),('Arammba','stk'),('Stellingwerfs','stl'),('Setaman','stm'),('Owa','stn'),('Stoney','sto'),('Southeastern Tepehuan','stp'),('Saterfriesisch','stq'),('Straits Salish','str'),('Shumashti','sts'),('Budeh Stieng','stt'),('Samtao','stu'),('Satawalese','stw'),('Sulka','sua'),('Suku','sub'),('Western Subanon','suc'),('Suena','sue'),('Tarpia','suf'),('Suganga','sug'),('Suba','suh'),('Suki','sui'),('Shubi','suj'),('Sukuma','suk'),('Surigaonon','sul'),('SumoMayangna','sum'),('Sundanese','su'),('Suri','suq'),('Mwaghavul','sur'),('Susu','sus'),('Subtiaba','sut'),('Sungkai','suu'),('Puroik','suv'),('Sumbwa','suw'),('Sumerian','sux'),('Sunwar','suz'),('Svan','sva'),('UlauSuain','svb'),('Vincentian Creole English','svc'),('Serili','sve'),('Slovakian Sign Language','svk'),('Savara','svr'),('Savosavo','svs'),('Skalvian','svx'),('Swahili macrolanguage','sw'),('Maore Comorian','swb'),('Congo Swahili','swc'),('Swedish','sv'),('Sere','swf'),('Swabian','swg'),('Swahili individual language','swh'),('Sui','swi'),('Sira','swj'),('Malawi Sena','swk'),('Swedish Sign Language','swl'),('Samosa','swm'),('Sawknah','swn'),('Shanenawa','swo'),('Suau','swp'),('Sharwa','swq'),('Saweru','swr'),('Seluwasan','sws'),('Sawila','swt'),('Suwawa','swu'),('Shekhawati','swv'),('Sowa','sww'),('Sarua','swy'),('Suba','sxb'),('Sicanian','sxc'),('Sighu','sxe'),('Shixing','sxg'),('Southern Kalapuya','sxk'),('Selian','sxl'),('Samre','sxm'),('Sangir','sxn'),('Sorothaptic','sxo'),('Saaroa','sxr'),('Sasaru','sxs'),('Upper Saxon','sxu'),('Saxwe Gbe','sxw'),('Siang','sya'),('Central Subanen','syb'),('Classical Syriac','syc'),('Seki','syi'),('Sukur','syk'),('Sylheti','syl'),('Maya Samo','sym'),('Senaya','syn'),('Suoy','syo'),('Syriac','syr'),('Sinyar','sys'),('Kagate','syw'),('AlSayyid Bedouin Sign Language','syy'),('Semelai','sza'),('Ngalum','szb'),('Semaq Beri','szc'),('Seru','szd'),('Seze','sze'),('Sengele','szg'),('Sizaki','szk'),('Silesian','szl'),('Sula','szn'),('Suabo','szp'),('Isu Fako Division','szv'),('Sawai','szw'),('Lower Tanana','taa'),('Tabassaran','tab'),('Lowland Tarahumara','tac'),('Tause','tad'),('Tariana','tae'),('Tagoi','tag'),('Tahitian','ty'),('Eastern Tamang','taj'),('Tala','tak'),('Tal','tal'),('Tamil','ta'),('Tangale','tan'),('Yami','tao'),('Taabwa','tap'),('Tamasheq','taq'),('Central Tarahumara','tar'),('Tay Boi','tas'),('Tatar','tt'),('Upper Tanana','tau'),('Tatuyo','tav'),('Tai','taw'),('Tamki','tax'),('Atayal','tay'),('Tocho','taz'),('Tapeba','tbb'),('Takia','tbc'),('Kaki Ae','tbd'),('Tanimbili','tbe'),('Mandara','tbf'),('North Tairora','tbg'),('Thurawal','tbh'),('Gaam','tbi'),('Tiang','tbj'),('Calamian Tagbanwa','tbk'),('Tboli','tbl'),('Tagbu','tbm'),('Barro Negro Tunebo','tbn'),('Tawala','tbo'),('Taworta','tbp'),('Tumtum','tbr'),('Tanguat','tbs'),('Tembo Kitembo','tbt'),('Tubar','tbu'),('Tobo','tbv'),('Tagbanwa','tbw'),('Kapin','tbx'),('Tabaru','tby'),('Ditammari','tbz'),('Ticuna','tca'),('Tanacross','tcb'),('Datooga','tcc'),('Tafi','tcd'),('Southern Tutchone','tce'),('Tamagario','tcg'),('Turks And Caicos Creole English','tch'),('Tchitchege','tck'),('Taman Myanmar','tcl'),('Tanahmerah','tcm'),('Tichurong','tcn'),('Taungyo','tco'),('Tawr Chin','tcp'),('Kaiy','tcq'),('Torres Strait Creole','tcs'),('Southeastern Tarahumara','tcu'),('Toda','tcx'),('Tulu','tcy'),('Thado Chin','tcz'),('Tagdal','tda'),('Panchpargania','tdb'),('Tiranige Diga Dogon','tde'),('Talieng','tdf'),('Western Tamang','tdg'),('Thulung','tdh'),('Tomadino','tdi'),('Tajio','tdj'),('Tambas','tdk'),('Sur','tdl'),('Tondano','tdn'),('Teme','tdo'),('Tita','tdq'),('Todrah','tdr'),('Doutai','tds'),('Tetun Dili','tdt'),('Tempasuk Dusun','tdu'),('Toro','tdv'),('TandroyMahafaly Malagasy','tdx'),('Tadyawan','tdy'),('Temiar','tea'),('Tetete','teb'),('Terik','tec'),('Tepo Krumen','ted'),('Huehuetla Tepehua','tee'),('Teressa','tef'),('TekeTege','teg'),('Tehuelche','teh'),('Torricelli','tei'),('Ibali Teke','tek'),('Telugu','te'),('Timne','tem'),('Tama Colombia','ten'),('Teso','teo'),('Tepecano','tep'),('Temein','teq'),('Tereno','ter'),('Tengger','tes'),('Tetum','tet'),('Soo','teu'),('Teor','tev'),('Tewa USA','tew'),('Tennet','tex'),('Tulishi','tey'),('Tofin Gbe','tfi'),('Tanaina','tfn'),('Tefaro','tfo'),('Teribe','tfr'),('Ternate','tft'),('Sagalla','tga'),('Tobilung','tgb'),('Tigak','tgc'),('Ciwogai','tgd'),('Eastern Gorkha Tamang','tge'),('Chalikha','tgf'),('Tangga','tgg'),('Tobagonian Creole English','tgh'),('Lawunuia','tgi'),('Tagin','tgj'),('Tajik','tg'),('Tagalog','tl'),('Tandaganon','tgn'),('Sudest','tgo'),('Tangoa','tgp'),('Tring','tgq'),('Tareng','tgr'),('Nume','tgs'),('Central Tagbanwa','tgt'),('Tanggu','tgu'),('TinguiBoto','tgv'),('Tagwana Senoufo','tgw'),('Tagish','tgx'),('Togoyo','tgy'),('Tagalaka','tgz'),('Thai','th'),('Tai Hang Tong','thc'),('Thayore','thd'),('Chitwania Tharu','the'),('Thangmi','thf'),('Northern Tarahumara','thh'),('Tai Long','thi'),('Tharaka','thk'),('Dangaura Tharu','thl'),('Aheu','thm'),('Thachanadan','thn'),('Thompson','thp'),('Kochila Tharu','thq'),('Rana Tharu','thr'),('Thakali','ths'),('Tahltan','tht'),('Thuri','thu'),('Tahaggart Tamahaq','thv'),('Thudam','thw'),('The','thx'),('Tha','thy'),('Tayart Tamajeq','thz'),('Tidikelt Tamazight','tia'),('Tira','tic'),('Tidong','tid'),('Tingal','tie'),('Tifal','tif'),('Tigre','tig'),('Timugon Murut','tih'),('Tiene','tii'),('Tilung','tij'),('Tikar','tik'),('Tillamook','til'),('Timbe','tim'),('Tindi','tin'),('Teop','tio'),('Trimuris','tip'),('Tigrinya','ti'),('Masadiit Itneg','tis'),('Tinigua','tit'),('Adasen','tiu'),('Tiv','tiv'),('Tiwi','tiw'),('Southern Tiwa','tix'),('Tiruray','tiy'),('Tai Hongjin','tiz'),('Tajuasohn','tja'),('Tunjung','tjg'),('Northern Tujia','tji'),('Tai Laing','tjl'),('Timucua','tjm'),('Tonjon','tjn'),('Temacine Tamazight','tjo'),('Southern Tujia','tjs'),('Tjurruru','tju'),('Buksa','tkb'),('Tukudede','tkd'),('Takwane','tke'),('Tesaka Malagasy','tkg'),('Takpa','tkk'),('Tokelau','tkl'),('Takelma','tkm'),('TokuNoShima','tkn'),('Tikopia','tkp'),('Tee','tkq'),('Tsakhur','tkr'),('Takestani','tks'),('Kathoriya Tharu','tkt'),('Upper Necaxa Totonac','tku'),('Teanu','tkw'),('Tangko','tkx'),('Takua','tkz'),('Southwestern Tepehuan','tla'),('Tobelo','tlb'),('Yecuatla Totonac','tlc'),('Talaud','tld'),('Southern Marakwet','tle'),('Telefol','tlf'),('Tofanma','tlg'),('Klingon','tlh'),('Tlingit','tli'),('TalingaBwisi','tlj'),('Taloki','tlk'),('Tetela','tll'),('Tolomako','tlm'),('Talodi','tlo'),('Tai Loi','tlq'),('Talise','tlr'),('Tambotalo','tls'),('Teluti','tlt'),('Tulehu','tlu'),('Taliabu','tlv'),('South Wemale','tlw'),('Khehek','tlx'),('Talysh','tly'),('Tama Chad','tma'),('Katbol','tmb'),('Tumak','tmc'),('Haruai','tmd'),('TobaMaskoy','tmf'),('Tamashek','tmh'),('Tutuba','tmi'),('Samarokena','tmj'),('Northwestern Tamang','tmk'),('Tamnim Citak','tml'),('Tai Thanh','tmm'),('Taman Indonesia','tmn'),('Temoq','tmo'),('Tumleo','tmq'),('Jewish Babylonian Aramaic ca 2001200 CE','tmr'),('Tima','tms'),('Tasmate','tmt'),('Iau','tmu'),('Tembo Motembo','tmv'),('Temuan','tmw'),('Tomyang','tmx'),('Tami','tmy'),('Tamanaku','tmz'),('Tacana','tna'),('Western Tunebo','tnb'),('Angosturas Tunebo','tnd'),('Tinoc Kallahan','tne'),('Tangshewi','tnf'),('Tobanga','tng'),('Maiani','tnh'),('Tandia','tni'),('Tanjong','tnj'),('Kwamera','tnk'),('Lenakel','tnl'),('Tabla','tnm'),('North Tanna','tnn'),('Toromono','tno'),('Whitesands','tnp'),('Taino','tnq'),('Bedik','tnr'),('Tenis','tns'),('Tontemboan','tnt'),('Tay Khang','tnu'),('Tangchangya','tnv'),('Tonsawang','tnw'),('Tanema','tnx'),('Tongwe','tny'),('Tonga Thailand','tnz'),('Toba','tob'),('Coyutla Totonac','toc'),('Toma','tod'),('Tomedes','toe'),('Gizrra','tof'),('Tonga Nyasa','tog'),('Gitonga','toh'),('Tonga Zambia','toi'),('Tojolabal','toj'),('Tolowa','tol'),('Tombulu','tom'),('Tonga Tonga Islands','to'),('Papantla Totonac','top'),('Toposa','toq'),('TogboVara Banda','tor'),('Highland Totonac','tos'),('PatlaChicontla Totonac','tot'),('Tho','tou'),('Upper Taromi','tov'),('Jemez','tow'),('Tobian','tox'),('Topoiyo','toy'),('To','toz'),('Taupota','tpa'),('Tippera','tpe'),('Tarpia','tpf'),('Kula','tpg'),('Tok Pisin','tpi'),('Tupinikin','tpk'),('Tampulma','tpm'),('Tai Pao','tpo'),('Pisaflores Tepehua','tpp'),('Tukpa','tpq'),('Tlachichilco Tepehua','tpt'),('Tampuan','tpu'),('Tanapag','tpv'),('Trumai','tpy'),('Tinputz','tpz'),('Lehali','tql'),('Turumsa','tqm'),('Tenino','tqn'),('Toaripi','tqo'),('Tomoip','tqp'),('Tunni','tqq'),('Torona','tqr'),('Western Totonac','tqt'),('Touo','tqu'),('Tonkawa','tqw'),('Tirahi','tra'),('Terebu','trb'),('Copala Triqui','trc'),('Turi','trd'),('East Tarangan','tre'),('Trinidadian Creole English','trf'),('Turaka','trh'),('Toram','trj'),('Traveller Scottish','trl'),('Tregami','trm'),('Trinitario','trn'),('Tarao Naga','tro'),('Kok Borok','trp'),('Taushiro','trr'),('Chicahuaxtla Triqui','trs'),('Tunggare','trt'),('Turoyo','tru'),('Taroko','trv'),('Torwali','trw'),('TringgusSembaan Bidayuh','trx'),('Turung','try'),('Tsaangi','tsa'),('Tsamai','tsb'),('Tswa','tsc'),('Tsakonian','tsd'),('Tunisian Sign Language','tse'),('Southwestern Tamang','tsf'),('Tausug','tsg'),('Tsuvan','tsh'),('Tsimshian','tsi'),('Tshangla','tsj'),('Tseku','tsk'),('Turkish Sign Language','tsm'),('Tswana','tn'),('Tsonga','ts'),('Northern Toussian','tsp'),('Thai Sign Language','tsq'),('Akei','tsr'),('Taiwan Sign Language','tss'),('Tondi Songway Kiini','tst'),('Tsou','tsu'),('Tsogo','tsv'),('Tsishingini','tsw'),('Mubami','tsx'),('Tebul Sign Language','tsy'),('Purepecha','tsz'),('Tutelo','tta'),('Gaa','ttb'),('Tektiteko','ttc'),('Tauade','ttd'),('Bwanabwana','tte'),('Tuotomb','ttf'),('Tutong','ttg'),('Tobati','tti'),('Tooro','ttj'),('Totoro','ttk'),('Totela','ttl'),('Northern Tutchone','ttm'),('Towei','ttn'),('Tombelala','ttp'),('Tawallammat Tamajaq','ttq'),('Tera','ttr'),('Northeastern Thai','tts'),('Muslim Tat','ttt'),('Torau','ttu'),('Titan','ttv'),('Long Wat','ttw'),('Tutong 1','ttx'),('Sikaritai','tty'),('Tsum','ttz'),('Wiarumus','tua'),('Mutu','tuc'),('Tuyuca','tue'),('Central Tunebo','tuf'),('Tunia','tug'),('Taulil','tuh'),('Tupuri','tui'),('Tugutil','tuj'),('Turkmen','tk'),('Tula','tul'),('Tumbuka','tum'),('Tunica','tun'),('Tucano','tuo'),('Tedaga','tuq'),('Turkish','tr'),('Tuscarora','tus'),('Tututni','tuu'),('Turkana','tuv'),('Tugen','tuy'),('Turka','tuz'),('Vaghua','tva'),('Tsuvadi','tvd'),('Southeast Ambrym','tvk'),('Tuvalu','tvl'),('TelaMasbuar','tvm'),('Tavoyan','tvn'),('Tidore','tvo'),('Taveta','tvs'),('Tutsa Naga','tvt'),('Tunen','tvu'),('Sedoa','tvw'),('Timor Pidgin','tvy'),('Twana','twa'),('Western Tawbuid','twb'),('Teshenawa','twc'),('Twents','twd'),('Tewa Indonesia','twe'),('Northern Tiwa','twf'),('Tereweng','twg'),('Twi','tw'),('Tawara','twl'),('Tawang Monpa','twm'),('Twendi','twn'),('Tswapong','two'),('Ere','twp'),('Tasawaq','twq'),('Southwestern Tarahumara','twr'),('Termanu','twu'),('Tuwari','tww'),('Tewe','twx'),('Tawoyan','twy'),('Tombonuo','txa'),('Tokharian B','txb'),('Tsetsaut','txc'),('Totoli','txe'),('Tangut','txg'),('Thracian','txh'),('Ikpeng','txi'),('Tomini','txm'),('West Tarangan','txn'),('Toto','txo'),('Tii','txq'),('Tartessian','txr'),('Tonsea','txs'),('Citak','txt'),('Tatana','txx'),('Tanosy Malagasy','txy'),('Tauya','tya'),('Kyenga','tye'),('TekeTsaayi','tyi'),('Tai Do','tyj'),('Thu Lao','tyl'),('Kombai','tyn'),('Thaypan','typ'),('Tai Daeng','tyr'),('Kua','tyu'),('Tuvinian','tyv'),('TekeTyee','tyx'),('Tanzanian Sign Language','tza'),('Chamula Tzotzil','tzc'),('Tzeltal','tzh'),('Central Atlas Tamazight','tzm'),('Tugun','tzn'),('Tzotzil','tzo'),('Western Tzutujil','tzt'),('Tabriak','tzx'),('Kuan','uan'),('Tairuma','uar'),('Ubang','uba'),('Ubi','ubi'),('Upper Baram Kenyah','ubm'),('Ubir','ubr'),('UmbuUngu','ubu'),('Ubykh','uby'),('Uda','uda'),('Udihe','ude'),('Muduga','udg'),('Udi','udi'),('Ujir','udj'),('Wuzlam','udl'),('Udmurt','udm'),('Uduk','udu'),('Kioko','ues'),('Ufim','ufi'),('Ugaritic','uga'),('KukuUgbanh','ugb'),('Ughele','uge'),('Ugandan Sign Language','ugn'),('Ugong','ugo'),('Uruguayan Sign Language','ugy'),('Uhami','uha'),('Damal','uhn'),('Uighur','ug'),('Uisai','uis'),('Iyive','uiv'),('Tanjijili','uji'),('Kaburi','uka'),('Ukuriguma','ukg'),('Ukhwejo','ukh'),('Ukrainian Sign Language','ukl'),('UkpeBayobiri','ukp'),('Ukwa','ukq'),('Ukrainian','uk'),('Ukue','uku'),('UkwuaniAbohNdoni','ukw'),('KuukYak','uky'),('Fungwa','ula'),('Ulukwumi','ulb'),('Ulch','ulc'),('Usku','ulf'),('Ulithian','uli'),('Meriam','ulk'),('Ullatan','ull'),('Unserdeutsch','uln'),('Ulwa','ulw'),('Umatilla','uma'),('Umbundu','umb'),('Marrucinian','umc'),('Umbindhamu','umd'),('Umbuygamu','umg'),('Ukit','umi'),('Umon','umm'),('Makyan Naga','umn'),('Umpila','ump'),('Umbugarla','umr'),('Pendau','ums'),('Munsee','umu'),('North Watut','una'),('Undetermined','und'),('Uneme','une'),('Ngarinyin','ung'),('Unami','unm'),('Worora','unp'),('Mundari','unr'),('Munda','unx'),('Unde Kaili','unz'),('Uokha','uok'),('Umeda','upi'),('UripivWalaRanoAtchin','upv'),('Urarina','ura'),('Urningangg','urc'),('Urdu','ur'),('Uru','ure'),('Uradhi','urf'),('Urigina','urg'),('Urhobo','urh'),('Urim','uri'),('Urali','url'),('Urapmin','urm'),('Uruangnirin','urn'),('Ura Papua New Guinea','uro'),('UruPaIn','urp'),('Lehalurup','urr'),('Urat','urt'),('Urumi','uru'),('Uruava','urv'),('Sop','urw'),('Urimo','urx'),('Orya','ury'),('UruEuWauWau','urz'),('Usarufa','usa'),('Ushojo','ush'),('Usui','usi'),('Usaghade','usk'),('Uspanteco','usp'),('Uya','usu'),('Otank','uta'),('UteSouthern Paiute','ute'),('Amba Solomon Islands','utp'),('Etulo','utr'),('Utu','utu'),('Urum','uum'),('KulonPazeh','uun'),('Ura Vanuatu','uur'),('U','uuu'),('West Uvean','uve'),('Uri','uvh'),('Lote','uvl'),('KukuUwanh','uwa'),('DokoUyanga','uya'),('Uzbek','uz'),('Northern Uzbek','uzn'),('Southern Uzbek','uzs'),('Vaagri Booli','vaa'),('Vale','vae'),('Vafsi','vaf'),('Vagla','vag'),('VarhadiNagpuri','vah'),('Vai','vai'),('Vasekela Bushman','vaj'),('Vehes','val'),('Vanimo','vam'),('Valman','van'),('Vao','vao'),('Vaiphei','vap'),('Huarijio','var'),('Vasavi','vas'),('Vanuma','vau'),('Varli','vav'),('Wayu','vay'),('Southeast Babar','vbb'),('Southwestern Bontok','vbk'),('Venetian','vec'),('Veddah','ved'),('Veluws','vel'),('VemgoMabas','vem'),('Venda','ve'),('Veps','vep'),('Mom Jango','ver'),('Vaghri','vgr'),('Vlaamse Gebarentaal','vgt'),('Virgin Islands Creole English','vic'),('Vidunda','vid'),('Vietnamese','vi'),('Vili','vif'),('Viemo','vig'),('Vilela','vil'),('Vinza','vin'),('Vishavan','vis'),('Viti','vit'),('Iduna','viv'),('Kariyarra','vka'),('IjaZuba','vki'),('Kujarge','vkj'),('Kaur','vkk'),('Kulisusu','vkl'),('Kamakan','vkm'),('Kodeoha','vko'),('Korlai Creole Portuguese','vkp'),('Tenggarong Kutai Malay','vkt'),('Kurrama','vku'),('Kayu Agung','vky'),('Valpei','vlp'),('Vatrata','vlr'),('Vlaams','vls'),('Martuyhunira','vma'),('Mbabaram','vmb'),('Juxtlahuaca Mixtec','vmc'),('Mudu Koraga','vmd'),('East Masela','vme'),('Minigir','vmg'),('Maraghei','vmh'),('Miwa','vmi'),('Ixtayutla Mixtec','vmj'),('MakhuwaShirima','vmk'),('Malgana','vml'),('Mitlatongo Mixtec','vmm'),('MukoMuko','vmo'),('Soyaltepec Mazatec','vmp'),('Soyaltepec Mixtec','vmq'),('Marenje','vmr'),('Moksela','vms'),('Muluridyi','vmu'),('Valley Maidu','vmv'),('Makhuwa','vmw'),('Tamazola Mixtec','vmx'),('Ayautla Mazatec','vmy'),('Vano','vnk'),('Vinmavis','vnm'),('Vunapu','vnp'),('Voro','vor'),('Votic','vot'),('Varisi','vrs'),('Burmbar','vrt'),('Moldova Sign Language','vsi'),('Venezuelan Sign Language','vsl'),('Valencian Sign Language','vsv'),('Vitou','vto'),('Vumbu','vum'),('Vunjo','vun'),('Vute','vut'),('Awa China','vwa'),('Walla Walla','waa'),('Wab','wab'),('WascoWishram','wac'),('Wandamen','wad'),('Walser','wae'),('Watubela','wah'),('Wares','wai'),('Waffa','waj'),('Wolaytta','wal'),('Wampanoag','wam'),('Wan','wan'),('Wappo','wao'),('Wapishana','wap'),('Wageman','waq'),('Waray Philippines','war'),('Washo','was'),('Kaninuwa','wat'),('Waka','wav'),('Waiwai','waw'),('Watam','wax'),('Wayana','way'),('Wampur','waz'),('Warao','wba'),('Wabo','wbb'),('Waritai','wbe'),('Wara','wbf'),('Wanda','wbh'),('Vwanji','wbi'),('Alagwa','wbj'),('Waigali','wbk'),('Wakhi','wbl'),('Wa','wbm'),('Warlpiri','wbp'),('Waddar','wbq'),('Wagdi','wbr'),('Wanman','wbt'),('Wajarri','wbv'),('Woi','wbw'),('Waci Gbe','wci'),('Wandji','wdd'),('Wadaginam','wdg'),('Wadjiginy','wdj'),('Wadjigu','wdu'),('Wewaw','wea'),('Wedau','wed'),('Weh','weh'),('Kiunum','wei'),('Weme Gbe','wem'),('Wemale','weo'),('Westphalien','wep'),('Weri','wer'),('Cameroon Pidgin','wes'),('Perai','wet'),('Rawngtu Chin','weu'),('Wejewa','wew'),('Yafi','wfg'),('Wagaya','wga'),('Wagawaga','wgb'),('Wangganguru','wgg'),('Wahgi','wgi'),('Waigeo','wgo'),('Wirangu','wgu'),('Wagawaga','wgw'),('Warrgamay','wgy'),('Manusela','wha'),('North Wahgi','whg'),('Wahau Kenyah','whk'),('Wahau Kayan','whu'),('Southern Toussian','wib'),('Wichita','wic'),('WikEpa','wie'),('WikKeyangan','wif'),('WikNgathana','wig'),('Minidien','wii'),('WikIiyanh','wij'),('Wikalkan','wik'),('Wilawila','wil'),('WikMungkan','wim'),('HoChunk','win'),('Wintu','wit'),('Wiru','wiu'),('Vitu','wiv'),('Wirangu','wiw'),('Wiyot','wiy'),('Waja','wja'),('Warji','wji'),('Kumbaran','wkb'),('Wakde','wkd'),('Kalanadi','wkl'),('Kunduvadi','wku'),('Wakawaka','wkw'),('Walio','wla'),('Mwali Comorian','wlc'),('Wolane','wle'),('Kunbarlang','wlg'),('Waioli','wli'),('Wailaki','wlk'),('Wali Sudan','wll'),('Middle Welsh','wlm'),('Walloon','wa'),('Wolio','wlo'),('Wailapa','wlr'),('Wallisian','wls'),('Wuliwuli','wlu'),('Walak','wlw'),('Wali Ghana','wlx'),('Waling','wly'),('Mawa Nigeria','wma'),('Wambaya','wmb'),('Wamas','wmc'),('Wambule','wme'),('Wamin','wmi'),('Maiwa Indonesia','wmm'),('Waamwang','wmn'),('Wom Papua New Guinea','wmo'),('Wambon','wms'),('Walmajarri','wmt'),('Mwani','wmw'),('Womo','wmx'),('Wanambre','wnb'),('Wantoat','wnc'),('Wandarang','wnd'),('Waneci','wne'),('Wanggom','wng'),('Ndzwani Comorian','wni'),('Wanukaka','wnk'),('Wanggamala','wnm'),('Wano','wno'),('Wanap','wnp'),('Usan','wnu'),('Wanyi','wny'),('Tyaraity','woa'),('Wogeo','woc'),('Wolani','wod'),('Woleaian','woe'),('Gambian Wolof','wof'),('Wogamusin','wog'),('Kamang','woi'),('Longto','wok'),('Wolof','wo'),('Wom Nigeria','wom'),('Wongo','won'),('Manombai','woo'),('Woria','wor'),('Hanga Hundi','wos'),('Wawonii','wow'),('Weyto','woy'),('Maco','wpc'),('Warapu','wra'),('Warluwara','wrb'),('Warduji','wrd'),('Ware','wre'),('Warungu','wrg'),('Wiradhuri','wrh'),('Wariyangga','wri'),('Garrwa','wrk'),('Warlmanpa','wrl'),('Warumungu','wrm'),('Warnang','wrn'),('Worrorra','wro'),('Waropen','wrp'),('Wardaman','wrr'),('Waris','wrs'),('Waru','wru'),('Waruna','wrv'),('Gugu Warra','wrw'),('Wae Rana','wrx'),('Merwari','wry'),('Waray Australia','wrz'),('Warembori','wsa'),('Wusi','wsi'),('Waskia','wsk'),('Owenia','wsr'),('Wasa','wss'),('Wasu','wsu'),('WotapuriKatarqalai','wsv'),('Watiwa','wtf'),('Berta','wti'),('Watakataui','wtk'),('Mewati','wtm'),('Wotu','wtw'),('Wikngenchera','wua'),('Wunambal','wub'),('Wudu','wud'),('Wutunhua','wuh'),('Silimo','wul'),('Wumbvu','wum'),('Bungu','wun'),('Wurrugu','wur'),('Wutung','wut'),('Wu Chinese','wuu'),('WuvuluAua','wuv'),('Wulna','wux'),('Wauyai','wuy'),('Waama','wwa'),('Wakabunga','wwb'),('Wetamut','wwo'),('Warrwa','wwr'),('Wawa','www'),('Waxianghua','wxa'),('Wyandot','wya'),('WangaaybuwanNgiyambaa','wyb'),('Wymysorys','wym'),('Western Fijian','wyy'),('Andalusian Arabic','xaa'),('Sambe','xab'),('Kachari','xac'),('Adai','xad'),('Aequian','xae'),('Aghwan','xag'),('Kahayan','xah'),('Kalmyk','xal'),('Xamtanga','xan'),('Khao','xao'),('Apalachee','xap'),('Aquitanian','xaq'),('Karami','xar'),('Kamas','xas'),('Katawixi','xat'),('Kauwera','xau'),('Kawaiisu','xaw'),('Kayan Mahakam','xay'),('Kamba Brazil','xba'),('Lower Burdekin','xbb'),('Bactrian','xbc'),('Kombio','xbi'),('Middle Breton','xbm'),('Kenaboi','xbn'),('Bolgarian','xbo'),('Kambera','xbr'),('Cumbric','xcb'),('Camunic','xcc'),('Celtiberian','xce'),('Cisalpine Gaulish','xcg'),('Chemakum','xch'),('Classical Armenian','xcl'),('Comecrudo','xcm'),('Cotoname','xcn'),('Chorasmian','xco'),('Carian','xcr'),('Classical Tibetan','xct'),('Curonian','xcu'),('Chuvantsy','xcv'),('Coahuilteco','xcw'),('Cayuse','xcy'),('Dacian','xdc'),('Edomite','xdm'),('Malayic Dayak','xdy'),('Eblan','xeb'),('Hdi','xed'),('Kelo','xel'),('Kembayan','xem'),('EpiOlmec','xep'),('Kesawai','xes'),('KeoruAhia','xeu'),('Faliscan','xfa'),('Galatian','xga'),('Gbin','xgb'),('Galindan','xgl'),('Garza','xgr'),('Unggumi','xgu'),('Harami','xha'),('Hunnic','xhc'),('Hadrami','xhd'),('Khetrani','xhe'),('Xhosa','xh'),('Hernican','xhr'),('Hattic','xht'),('Hurrian','xhu'),('Khua','xhv'),('Xiandao','xia'),('Iberian','xib'),('Xiri','xii'),('Illyrian','xil'),('Xinca','xin'),('Indus Valley Language','xiv'),('Xipaya','xiy'),('Kalkoti','xka'),('Northern Nago','xkb'),('Mendalam Kayan','xkd'),('Kereho','xke'),('Khengkha','xkf'),('Kagoro','xkg'),('Karahawyana','xkh'),('Kenyan Sign Language','xki'),('Kajali','xkj'),('Mainstream Kenyah','xkl'),('Mahakam Kenyah','xkm'),('Kayan River Kayan','xkn'),('Kiorr','xko'),('Kabatei','xkp'),('Koroni','xkq'),('Kumbewaha','xks'),('Kantosi','xkt'),('Kaamba','xku'),('Kgalagadi','xkv'),('Kembra','xkw'),('Karore','xkx'),('Kurtokha','xkz'),('Kamula','xla'),('Loup B','xlb'),('Lycian','xlc'),('Lydian','xld'),('Lemnian','xle'),('Ligurian Ancient','xlg'),('Liburnian','xli'),('Alanic','xln'),('Loup A','xlo'),('Lepontic','xlp'),('Lusitanian','xls'),('Cuneiform Luwian','xlu'),('Elymian','xly'),('Mushungulu','xma'),('Mbonga','xmb'),('MakhuwaMarrevone','xmc'),('Mbudum','xmd'),('Median','xme'),('Mingrelian','xmf'),('Mengaka','xmg'),('KukuMuminh','xmh'),('Majera','xmj'),('Ancient Macedonian','xmk'),('Malaysian Sign Language','xml'),('Manado Malay','xmm'),('Manichaean Middle Persian','xmn'),('Morerebi','xmo'),('KukuMangk','xmq'),('Meroitic','xmr'),('Moroccan Sign Language','xms'),('Matbat','xmt'),('Kamu','xmu'),('Antankarana Malagasy','xmv'),('Tsimihety Malagasy','xmw'),('Maden','xmx'),('Mayaguduna','xmy'),('Mori Bawah','xmz'),('Ancient North Arabian','xna'),('Kanakanabu','xnb'),('Middle Mongolian','xng'),('Kuanhua','xnh'),('Northern Kankanay','xnn'),('AngloNorman','xno'),('Kangri','xnr'),('Kanashi','xns'),('Narragansett','xnt'),('Kenzi','xnz'),('Kokoda','xod'),('Soga','xog'),('Kominimung','xoi'),('Xokleng','xok'),('Komo Sudan','xom'),('Konkomba','xon'),('Kopar','xop'),('Korubo','xor'),('Kowaki','xow'),('Pecheneg','xpc'),('Liberia Kpelle','xpe'),('Phrygian','xpg'),('Pictish','xpi'),('Mpalitjanh','xpj'),('Kulina Pano','xpk'),('Pumpokol','xpm'),('Pochutec','xpo'),('PuyoPaekche','xpp'),('MoheganPequot','xpq'),('Parthian','xpr'),('Pisidian','xps'),('Punic','xpu'),('Puyo','xpy'),('Karakhanid','xqa'),('Qatabanian','xqt'),('Eastern Karaboro','xrb'),('Kreye','xre'),('KrikatiTimbira','xri'),('Armazic','xrm'),('Arin','xrn'),('Raetic','xrr'),('AranamaTamique','xrt'),('Marriammu','xru'),('Karawa','xrw'),('Sabaean','xsa'),('Sambal','xsb'),('Scythian','xsc'),('Sidetic','xsd'),('Sempan','xse'),('Shamang','xsh'),('Sio','xsi'),('Subi','xsj'),('Sakan','xsk'),('South Slavey','xsl'),('Kasem','xsm'),('Sanga Nigeria','xsn'),('Solano','xso'),('Silopi','xsp'),('MakhuwaSaka','xsq'),('Sherpa','xsr'),('Assan','xss'),('Sudovian','xsv'),('Saisiyat','xsy'),('Alcozauca Mixtec','xta'),('Chazumba Mixtec','xtb'),('KatchaKadugliMiri','xtc'),('DiuxiTilantongo Mixtec','xtd'),('Ketengban','xte'),('Transalpine Gaulish','xtg'),('Sinicahua Mixtec','xti'),('San Juan Teita Mixtec','xtj'),('Tijaltepec Mixtec','xtl'),('Northern Tlaxiaco Mixtec','xtn'),('Tokharian A','xto'),('San Miguel Piedras Mixtec','xtp'),('Tumshuqese','xtq'),('Early Tripuri','xtr'),('Sindihui Mixtec','xts'),('Tacahua Mixtec','xtt'),('Cuyamecalco Mixtec','xtu'),('Yoloxochitl Mixtec','xty'),('Tasmanian','xtz'),('Alu Kurumba','xua'),('Betta Kurumba','xub'),('Umiida','xud'),('Kunfal','xuf'),('Kunigami','xug'),('Jennu Kurumba','xuj'),('Umbrian','xum'),('Unggarranggu','xun'),('Kuo','xuo'),('Upper Umpqua','xup'),('Urartian','xur'),('Kuthant','xut'),('Kxoe','xuu'),('Venetic','xve'),('Kamviri','xvi'),('Vandalic','xvn'),('Volscian','xvo'),('Vestinian','xvs'),('Kwaza','xwa'),('Woccon','xwc'),('Xwela Gbe','xwe'),('Kwegu','xwg'),('Western Xwla Gbe','xwl'),('Written Oirat','xwo'),('Kwerba Mamberamo','xwr'),('Boro Ghana','xxb'),('Tambora','xxt'),('Yalakalore','xyl'),('Yorta Yorta','xyy'),('ZhangZhung','xzh'),('Zemgalian','xzm'),('Ancient Zapotec','xzp'),('Yaminahua','yaa'),('Yuhup','yab'),('Pass Valley Yali','yac'),('Yagua','yad'),('Yaka Democratic Republic of Congo','yaf'),('Yazgulyam','yah'),('Yagnobi','yai'),('BandaYangere','yaj'),('Yakama','yak'),('Yalunka','yal'),('Yamba','yam'),('Mayangna','yan'),('Yao','yao'),('Yapese','yap'),('Yaqui','yaq'),('Yabarana','yar'),('Nugunu Cameroon','yas'),('Yambeta','yat'),('Yuwana','yau'),('Yangben','yav'),('Yauma','yax'),('Agwagwune','yay'),('Lokaa','yaz'),('Yala','yba'),('Yemba','ybb'),('Yangbye','ybd'),('West Yugur','ybe'),('Yakha','ybh'),('Yamphu','ybi'),('Hasha','ybj'),('Bokha','ybk'),('Yukuben','ybl'),('Yaben','ybm'),('Yabong','ybo'),('Yawiyo','ybx'),('Yaweyuha','yby'),('Chesu','ych'),('Lolopo','ycl'),('Yucuna','ycn'),('Chepya','ycp'),('Eastern Yiddish','ydd'),('Yangum Dey','yde'),('Yidgha','ydg'),('Yoidik','ydk'),('Yiddish Sign Language','yds'),('Ravula','yea'),('Yeniche','yec'),('Yimas','yee'),('Yeni','yei'),('Yevanic','yej'),('Yela','yel'),('Yendang','yen'),('Tarok','yer'),('Nyankpa','yes'),('Yetfa','yet'),('Yerukula','yeu'),('Yapunda','yev'),('Yeyi','yey'),('Malyangapa','yga'),('Yangum Gel','ygl'),('Yagomi','ygm'),('Gepo','ygp'),('Yagaria','ygr'),('Yagwoia','ygw'),('Baha Buyang','yha'),('JudeoIraqi Arabic','yhd'),('Hlepho Phowa','yhl'),('Yinggarda','yia'),('Yinglish','yib'),('Yiddish','yi'),('Ache','yif'),('Wusa Nasu','yig'),('Western Yiddish','yih'),('Yidiny','yii'),('Yindjibarndi','yij'),('Dongshanba Lalo','yik'),('Yindjilandji','yil'),('Yimchungru Naga','yim'),('Yinchia','yin'),('Dayao Yi','yio'),('Pholo','yip'),('Miqie','yiq'),('North Awyu','yir'),('Yis','yis'),('Eastern Lalu','yit'),('Awu','yiu'),('Northern Nisu','yiv'),('Axi Yi','yix'),('Yir Yoront','yiy'),('Azhe','yiz'),('Yakan','yka'),('Northern Yukaghir','ykg'),('Yoke','yki'),('Yakaikeke','ykk'),('Khlula','ykl'),('Kap','ykm'),('Kuansi','ykn'),('Yasa','yko'),('Yekora','ykr'),('Kathu','ykt'),('Kuamasi','yku'),('Yakoma','yky'),('Yaul','yla'),('Yaleba','ylb'),('Yele','yle'),('Yelogu','ylg'),('Angguruk Yali','yli'),('Yil','yll'),('Limi','ylm'),('Langnian Buyang','yln'),('Naluo Yi','ylo'),('Yalarnnga','ylr'),('Aribwaung','ylu'),('Yamphe','yma'),('Yambes','ymb'),('Southern Muji','ymc'),('Muda','ymd'),('Yameo','yme'),('Yamongeri','ymg'),('Mili','ymh'),('Moji','ymi'),('Muji Yi','ymj'),('Makwe','ymk'),('Iamalele','yml'),('Maay','ymm'),('Yamna','ymn'),('Yangum Mon','ymo'),('Yamap','ymp'),('Qila Muji','ymq'),('Malasar','ymr'),('Mysian','yms'),('MatorTaygiKaragas','ymt'),('Northern Muji','ymx'),('Muzi','ymz'),('Aluo','yna'),('Yandruwandha','ynd'),('Yango','yng'),('Yangho','ynh'),('Naukan Yupik','ynk'),('Yangulam','ynl'),('Yana','ynn'),('Yong','yno'),('Yendang','ynq'),('Yansi','yns'),('Yahuna','ynu'),('Yoba','yob'),('Yogad','yog'),('Yonaguni','yoi'),('Yokuts','yok'),('Yola','yol'),('Yombe','yom'),('Yongkom','yon'),('Yoruba','yo'),('Yos','yos'),('Yotti','yot'),('Yoron','yox'),('Yoy','yoy'),('Phala','ypa'),('Labo Phowa','ypb'),('Phola','ypg'),('Phupha','yph'),('Pula Yi','ypl'),('Phuma','ypm'),('Ani Phowa','ypn'),('Alo Phola','ypo'),('Phupa','ypp'),('Puwa Yi','ypw'),('Phuza','ypz'),('Yerakai','yra'),('Yareba','yrb'),('Nenets','yrk'),('Nhengatu','yrl'),('Yerong','yrn'),('Yarsun','yrs'),('Yarawata','yrw'),('Yassic','ysc'),('Samatao','ysd'),('Sonaga','ysg'),('Yugoslavian Sign Language','ysl'),('Sani','ysn'),('Nisi China','yso'),('Southern Lolopo','ysp'),('Sirenik Yupik','ysr'),('YessanMayo','yss'),('Sanie','ysy'),('Talu','yta'),('Tanglang','ytl'),('Thopho','ytp'),('Yout Wam','ytw'),('Yucateco','yua'),('Yugambal','yub'),('Yuchi','yuc'),('JudeoTripolitanian Arabic','yud'),('Yue Chinese','yue'),('HavasupaiWalapaiYavapai','yuf'),('Yug','yug'),('KarkarYuri','yuj'),('Yuki','yuk'),('Yulu','yul'),('Quechan','yum'),('Bena Nigeria','yun'),('Yukpa','yup'),('Yuqui','yuq'),('Yurok','yur'),('Chan Santa Cruz Maya','yus'),('Yopno','yut'),('Yugh','yuu'),('Yau Morobe Province','yuw'),('Southern Yukaghir','yux'),('East Yugur','yuy'),('Yuracare','yuz'),('Yawa','yva'),('Yavitero','yvt'),('Kalou','ywa'),('Western Lalu','ywl'),('Wumeng Yi','ywm'),('Yawanawa','ywn'),('WudingLuquan Yi','ywq'),('Yawuru','ywr'),('Xishanba Lalo','ywt'),('Wumeng Nasu','ywu'),('Yawarawarga','yww'),('Yagara','yxg'),('Yabula Yabula','yxy'),('YuanjiangMojiang Yi','yym'),('Yau Sandaun Province','yyu'),('Ayizi','yyz'),('Zokhuo','yzk'),('Cajonos Zapotec','zad'),('Yareni Zapotec','zae'),('Ayoquesco Zapotec','zaf'),('Zaghawa','zag'),('Zangwal','zah'),('Isthmus Zapotec','zai'),('Zaramo','zaj'),('Zanaki','zak'),('Zauzou','zal'),('Ozolotepec Zapotec','zao'),('Zapotec','zap'),('Santo Domingo Albarradas Zapotec','zas'),('Tabaa Zapotec','zat'),('Zangskari','zau'),('Yatzachi Zapotec','zav'),('Mitla Zapotec','zaw'),('Xadani Zapotec','zax'),('ZayseZergulla','zay'),('Zari','zaz'),('Central Berawan','zbc'),('East Berawan','zbe'),('Blissymbols','zbl'),('Batui','zbt'),('West Berawan','zbw'),('Coatecas Altas Zapotec','zca'),('Central Hongshuihe Zhuang','zch'),('Ngazidja Comorian','zdj'),('Zeeuws','zea'),('Zenag','zeg'),('Eastern Hongshuihe Zhuang','zeh'),('Zenaga','zen'),('Kinga','zga'),('Guibei Zhuang','zgb'),('Minz Zhuang','zgm'),('Guibian Zhuang','zgn'),('Magori','zgr'),('Zhuang','za'),('Zhaba','zhb'),('Dai Zhuang','zhd'),('Zhire','zhi'),('Nong Zhuang','zhn'),('Zhoa','zhw'),('Zia','zia'),('Zimbabwe Sign Language','zib'),('Zimakani','zik'),('Zialo','zil'),('Mesme','zim'),('Zinza','zin'),('Ziriya','zir'),('Zigula','ziw'),('Zizilivakan','ziz'),('Kaimbulawa','zka'),('Koibal','zkb'),('Kadu','zkd'),('Koguryo','zkg'),('Khorezmian','zkh'),('Karankawa','zkk'),('Kanan','zkn'),('Kott','zko'),('Zakhring','zkr'),('Kitan','zkt'),('Kaurna','zku'),('Krevinian','zkv'),('Khazar','zkz'),('Liujiang Zhuang','zlj'),('Malay individual language','zlm'),('Lianshan Zhuang','zln'),('Liuqian Zhuang','zlq'),('Manda Australia','zma'),('Zimba','zmb'),('Margany','zmc'),('Maridan','zmd'),('Mangerr','zme'),('Mfinu','zmf'),('Marti Ke','zmg'),('Makolkol','zmh'),('Negeri Sembilan Malay','zmi'),('Maridjabin','zmj'),('Mandandanyi','zmk'),('Madngele','zml'),('Marimanindji','zmm'),('Mbangwe','zmn'),('Molo','zmo'),('Mpuono','zmp'),('Mituku','zmq'),('Maranunggu','zmr'),('Mbesa','zms'),('Maringarr','zmt'),('Muruwari','zmu'),('MbarimanGudhinma','zmv'),('Mbo Democratic Republic of Congo','zmw'),('Bomitaba','zmx'),('Mariyedi','zmy'),('Mbandja','zmz'),('Zan Gula','zna'),('Zande individual language','zne'),('Mang','zng'),('Manangkari','znk'),('Mangas','zns'),('Chimalapa Zoque','zoh'),('Zou','zom'),('Tabasco Zoque','zoq'),('Lachiguiri Zapotec','zpa'),('Yautepec Zapotec','zpb'),('Choapan Zapotec','zpc'),('Petapa Zapotec','zpe'),('San Pedro Quiatoni Zapotec','zpf'),('Guevea De Humboldt Zapotec','zpg'),('Totomachapan Zapotec','zph'),('Quiavicuzas Zapotec','zpj'),('Tlacolulita Zapotec','zpk'),('Mixtepec Zapotec','zpm'),('El Alto Zapotec','zpp'),('Zoogocho Zapotec','zpq'),('Santiago Xanica Zapotec','zpr'),('Chichicapan Zapotec','zpv'),('Zaniza Zapotec','zpw'),('San Baltazar Loxicha Zapotec','zpx'),('Mazaltepec Zapotec','zpy'),('Texmelucan Zapotec','zpz'),('Qiubei Zhuang','zqe'),('Kara Korea','zra'),('Mirgan','zrg'),('Zerenkel','zrn'),('Zarphatic','zrp'),('Mairasi','zrs'),('Sarasira','zsa'),('Kaskean','zsk'),('Zambian Sign Language','zsl'),('Standard Malay','zsm'),('Southern Rincon Zapotec','zsr'),('Sukurum','zsu'),('Lachirioag Zapotec','ztc'),('Elotepec Zapotec','zte'),('Santa Catarina Albarradas Zapotec','ztn'),('Loxicha Zapotec','ztp'),('Tilquiapan Zapotec','zts'),('Tejalapan Zapotec','ztt'),('Zaachila Zapotec','ztx'),('Yatee Zapotec','zty'),('Zeem','zua'),('Tokano','zuh'),('Zulu','zu'),('Kumzari','zum'),('Zuni','zun'),('Zumaya','zuy'),('Zay','zwa'),('No linguistic content','zxx'),('Yongbei Zhuang','zyb'),('Yang Zhuang','zyg'),('Youjiang Zhuang','zyj'),('Yongnan Zhuang','zyn'),('Zyphe','zyp'),('Zaza','zza'),('Zuojiang Zhuang','zzj'),('Rohingyalish','rhl'),('Karen','kar');

insert into Countries ( `en-name`,`code`) values ('ANY','--'),('AFGHANISTAN','AF'),('ÅLAND ISLANDS','AX'),('ALBANIA','AL'),('ALGERIA','DZ'),('AMERICAN SAMOA','AS'),('ANDORRA','AD'),('ANGOLA','AO'),('ANGUILLA','AI'),('ANTARCTICA','AQ'),('ANTIGUA AND BARBUDA','AG'),('ARGENTINA','AR'),('ARMENIA','AM'),('ARUBA','AW'),('AUSTRALIA','AU'),('AUSTRIA','AT'),('AZERBAIJAN','AZ'),('BAHAMAS','BS'),('BAHRAIN','BH'),('BANGLADESH','BD'),('BARBADOS','BB'),('BELARUS','BY'),('BELGIUM','BE'),('BELIZE','BZ'),('BENIN','BJ'),('BERMUDA','BM'),('BHUTAN','BT'),('BOLIVIA PLURINATIONAL STATE OF','BO'),('BONAIRE SINT EUSTATIUS AND SABA','BQ'),('BOSNIA AND HERZEGOVINA','BA'),('BOTSWANA','BW'),('BOUVET ISLAND','BV'),('BRAZIL','BR'),('BRITISH INDIAN OCEAN TERRITORY','IO'),('BRUNEI DARUSSALAM','BN'),('BULGARIA','BG'),('BURKINA FASO','BF'),('BURUNDI','BI'),('CAMBODIA','KH'),('CAMEROON','CM'),('CANADA','CA'),('CAPE VERDE','CV'),('CAYMAN ISLANDS','KY'),('CENTRAL AFRICAN REPUBLIC','CF'),('CHAD','TD'),('CHILE','CL'),('CHINA','CN'),('CHRISTMAS ISLAND','CX'),('COCOS KEELING ISLANDS','CC'),('COLOMBIA','CO'),('COMOROS','KM'),('CONGO','CG'),('CONGO THE DEMOCRATIC REPUBLIC OF THE','CD'),('COOK ISLANDS','CK'),('COSTA RICA','CR'),('CÔTE D''IVOIRE','CI'),('CROATIA','HR'),('CUBA','CU'),('CURAÇAO','CW'),('CYPRUS','CY'),('CZECH REPUBLIC','CZ'),('DENMARK','DK'),('DJIBOUTI','DJ'),('DOMINICA','DM'),('DOMINICAN REPUBLIC','DO'),('ECUADOR','EC'),('EGYPT','EG'),('EL SALVADOR','SV'),('EQUATORIAL GUINEA','GQ'),('ERITREA','ER'),('ESTONIA','EE'),('ETHIOPIA','ET'),('FALKLAND ISLANDS MALVINAS','FK'),('FAROE ISLANDS','FO'),('FIJI','FJ'),('FINLAND','FI'),('FRANCE','FR'),('FRENCH GUIANA','GF'),('FRENCH POLYNESIA','PF'),('FRENCH SOUTHERN TERRITORIES','TF'),('GABON','GA'),('GAMBIA','GM'),('GEORGIA','GE'),('GERMANY','DE'),('GHANA','GH'),('GIBRALTAR','GI'),('GREECE','GR'),('GREENLAND','GL'),('GRENADA','GD'),('GUADELOUPE','GP'),('GUAM','GU'),('GUATEMALA','GT'),('GUERNSEY','GG'),('GUINEA','GN'),('GUINEA-BISSAU','GW'),('GUYANA','GY'),('HAITI','HT'),('HEARD ISLAND AND MCDONALD ISLANDS','HM'),('HOLY SEE VATICAN CITY STATE','VA'),('HONDURAS','HN'),('HONG KONG','HK'),('HUNGARY','HU'),('ICELAND','IS'),('INDIA','IN'),('INDONESIA','ID'),('IRAN ISLAMIC REPUBLIC OF','IR'),('IRAQ','IQ'),('IRELAND','IE'),('ISLE OF MAN','IM'),('ISRAEL','IL'),('ITALY','IT'),('JAMAICA','JM'),('JAPAN','JP'),('JERSEY','JE'),('JORDAN','JO'),('KAZAKHSTAN','KZ'),('KENYA','KE'),('KIRIBATI','KI'),('KOREA DEMOCRATIC PEOPLE''S REPUBLIC OF','KP'),('KOREA REPUBLIC OF','KR'),('KUWAIT','KW'),('KYRGYZSTAN','KG'),('LAO PEOPLE''S DEMOCRATIC REPUBLIC','LA'),('LATVIA','LV'),('LEBANON','LB'),('LESOTHO','LS'),('LIBERIA','LR'),('LIBYA','LY'),('LIECHTENSTEIN','LI'),('LITHUANIA','LT'),('LUXEMBOURG','LU'),('MACAO','MO'),('MACEDONIA THE FORMER YUGOSLAV REPUBLIC OF','MK'),('MADAGASCAR','MG'),('MALAWI','MW'),('MALAYSIA','MY'),('MALDIVES','MV'),('MALI','ML'),('MALTA','MT'),('MARSHALL ISLANDS','MH'),('MARTINIQUE','MQ'),('MAURITANIA','MR'),('MAURITIUS','MU'),('MAYOTTE','YT'),('MEXICO','MX'),('MICRONESIA FEDERATED STATES OF','FM'),('MOLDOVA REPUBLIC OF','MD'),('MONACO','MC'),('MONGOLIA','MN'),('MONTENEGRO','ME'),('MONTSERRAT','MS'),('MOROCCO','MA'),('MOZAMBIQUE','MZ'),('MYANMAR','MM'),('NAMIBIA','NA'),('NAURU','NR'),('NEPAL','NP'),('NETHERLANDS','NL'),('NEW CALEDONIA','NC'),('NEW ZEALAND','NZ'),('NICARAGUA','NI'),('NIGER','NE'),('NIGERIA','NG'),('NIUE','NU'),('NORFOLK ISLAND','NF'),('NORTHERN MARIANA ISLANDS','MP'),('NORWAY','NO'),('OMAN','OM'),('PAKISTAN','PK'),('PALAU','PW'),('PALESTINIAN TERRITORY OCCUPIED','PS'),('PANAMA','PA'),('PAPUA NEW GUINEA','PG'),('PARAGUAY','PY'),('PERU','PE'),('PHILIPPINES','PH'),('PITCAIRN','PN'),('POLAND','PL'),('PORTUGAL','PT'),('PUERTO RICO','PR'),('QATAR','QA'),('RÉUNION','RE'),('ROMANIA','RO'),('RUSSIAN FEDERATION','RU'),('RWANDA','RW'),('SAINT BARTHÉLEMY','BL'),('SAINT HELENA ASCENSION AND TRISTAN DA CUNHA','SH'),('SAINT KITTS AND NEVIS','KN'),('SAINT LUCIA','LC'),('SAINT MARTIN FRENCH PART','MF'),('SAINT PIERRE AND MIQUELON','PM'),('SAINT VINCENT AND THE GRENADINES','VC'),('SAMOA','WS'),('SAN MARINO','SM'),('SAO TOME AND PRINCIPE','ST'),('SAUDI ARABIA','SA'),('SENEGAL','SN'),('SERBIA','RS'),('SEYCHELLES','SC'),('SIERRA LEONE','SL'),('SINGAPORE','SG'),('SINT MAARTEN DUTCH PART','SX'),('SLOVAKIA','SK'),('SLOVENIA','SI'),('SOLOMON ISLANDS','SB'),('SOMALIA','SO'),('SOUTH AFRICA','ZA'),('SOUTH GEORGIA AND THE SOUTH SANDWICH ISLANDS','GS'),('SOUTH SUDAN','SS'),('SPAIN','ES'),('SRI LANKA','LK'),('SUDAN','SD'),('SURINAME','SR'),('SVALBARD AND JAN MAYEN','SJ'),('SWAZILAND','SZ'),('SWEDEN','SE'),('SWITZERLAND','CH'),('SYRIAN ARAB REPUBLIC','SY'),('TAIWAN PROVINCE OF CHINA','TW'),('TAJIKISTAN','TJ'),('TANZANIA UNITED REPUBLIC OF','TZ'),('THAILAND','TH'),('TIMOR-LESTE','TL'),('TOGO','TG'),('TOKELAU','TK'),('TONGA','TO'),('TRINIDAD AND TOBAGO','TT'),('TUNISIA','TN'),('TURKEY','TR'),('TURKMENISTAN','TM'),('TURKS AND CAICOS ISLANDS','TC'),('TUVALU','TV'),('UGANDA','UG'),('UKRAINE','UA'),('UNITED ARAB EMIRATES','AE'),('UNITED KINGDOM','GB'),('UNITED STATES','US'),('UNITED STATES MINOR OUTLYING ISLANDS','UM'),('URUGUAY','UY'),('UZBEKISTAN','UZ'),('VANUATU','VU'),('VENEZUELA BOLIVARIAN REPUBLIC OF','VE'),('VIETNAM','VN'),('VIRGIN ISLANDS BRITISH','VG'),('VIRGIN ISLANDS U.S.','VI'),('WALLIS AND FUTUNA','WF'),('WESTERN SAHARA','EH'),('YEMEN','YE'),('ZAMBIA','ZM'),('ZIMBABWE','ZW');

);

$NEON_NATIVELANGFIELD = 64;
$NEON_SOURCE1FIELD    = 167;
$NEON_TARGET1FIELD    = 168;
$NEON_SOURCE2FIELD    = 169;
$NEON_TARGET2FIELD    = 170;
$NEON_LEVELFIELD      = 173;

        $email = $newUser->getEmail();
        error_log("update_user_with_neon_data($email)");

        $neon = new \Neon();

        $credentials = array(
            'orgId'  => Common\Lib\Settings::get('neon.org_id'),
            'apiKey' => Common\Lib\Settings::get('neon.api_key')
        );

        $loginResult = $neon->login($credentials);
        if (isset($loginResult['operationResult']) && $loginResult['operationResult'] === 'SUCCESS') {
            $search = array(
                'method' => 'account/listAccounts',
                'columns' => array(
                'standardFields' => array(
                    'Email 1',
                    'First Name',
                    'Last Name',
                    'Preferred Name',
                    'Company Name',
                    'Company ID'),
                'customFields' => array(
                    $NEON_NATIVELANGFIELD,
                    $NEON_SOURCE1FIELD,
                    $NEON_TARGET1FIELD,
                    $NEON_SOURCE2FIELD,
                    $NEON_TARGET2FIELD,
                    $NEON_LEVELFIELD),
                )
            );
            $search['criteria'] = array(array('Email', 'EQUAL', $email));

            $result = $neon->search($search);

            $neon->go(array('method' => 'common/logout'));

            if (empty($result) || empty($result['searchResults'])) {
                error_log("update_user_with_neon_data($email), no results from NeonCRM");
            } else {
                foreach ($result['searchResults'] as $r) {
                    $first_name = (empty($r['First Name'])) ? '' : $r['First Name'];
                    if (!empty($first_name)) break; // If we find a First Name, then we have found the good account and we should use this one "$r" (normally there will only be one account)
                }

                $last_name  = (empty($r['Last Name']))  ? '' : $r['Last Name'];
                if (!empty($first_name)) $userInfo->setFirstName($first_name);
                if (!empty($last_name))  $userInfo->setLastName($last_name);
                DAO\UserDao::savePersonalInfo($userInfo);

                $display_name = (empty($r['Preferred Name'])) ? '' : $r['Preferred Name'];
                if (!empty($display_name)) $newUser->setDisplayName($display_name);

                $nativelang = (empty($r['Native language'])) ? '' : $r['Native language'];
                if (!empty($from_neon_to_trommons_pair[$nativelang])) {
                    $locale = new Common\Protobufs\Models\Locale();
                    $locale->setLanguageCode($from_neon_to_trommons_pair[$nativelang][0]);
                    $locale->setCountryCode($from_neon_to_trommons_pair[$nativelang][1]);
                    $newUser->setNativeLocale($locale);
                }

                DAO\UserDao::save($newUser);

                $org_name    = (empty($r['Company Name'])) ? '' : $r['Company Name'];
                $org_id_neon = (empty($r['Company ID']))   ? '' : $r['Company ID'];

                error_log("first_name: $first_name, last_name: $last_name, display_name: $display_name, nativelang: $nativelang, org_name: $org_name, org_id_neon: $org_id_neon");

                $sourcelang1  = (empty($r['Primary Source Language']))   ? '' : $r['Primary Source Language'];
                $targetlang1  = (empty($r['Primary Target Language']))   ? '' : $r['Primary Target Language'];
                $sourcelang2  = (empty($r['Secondary Source Language'])) ? '' : $r['Secondary Source Language'];
                $targetlang2  = (empty($r['Secondary Target Language'])) ? '' : $r['Secondary Target Language'];

                $neon_quality_levels = array('unverified' => 1, 'verified' => 2, 'senior' => 3);
                if (empty($r['Level']) || empty($neon_quality_levels[$r['Level']])) {
                    $quality_level = 1;
                } else {
                    $quality_level = $neon_quality_levels[$r['Level']];
                }

                $user_id = $newUser->getId();
                if (!empty($from_neon_to_trommons_pair[$sourcelang1]) && !empty($from_neon_to_trommons_pair[$targetlang1]) && ($sourcelang1 != $targetlang1)) {
                    DAO\UserDao::createUserQualifiedPair($user_id, $from_neon_to_trommons_pair[$sourcelang1][0], $from_neon_to_trommons_pair[$sourcelang1][1], $from_neon_to_trommons_pair[$targetlang1][0], $from_neon_to_trommons_pair[$targetlang1][1], $quality_level);
                }
                if (!empty($from_neon_to_trommons_pair[$sourcelang1]) && !empty($from_neon_to_trommons_pair[$targetlang2]) && ($sourcelang1 != $targetlang2)) {
                    DAO\UserDao::createUserQualifiedPair($user_id, $from_neon_to_trommons_pair[$sourcelang1][0], $from_neon_to_trommons_pair[$sourcelang1][1], $from_neon_to_trommons_pair[$targetlang2][0], $from_neon_to_trommons_pair[$targetlang2][1], $quality_level);
                }
                if (!empty($from_neon_to_trommons_pair[$sourcelang2]) && !empty($from_neon_to_trommons_pair[$targetlang1]) && ($sourcelang2 != $targetlang1)) {
                    DAO\UserDao::createUserQualifiedPair($user_id, $from_neon_to_trommons_pair[$sourcelang2][0], $from_neon_to_trommons_pair[$sourcelang2][1], $from_neon_to_trommons_pair[$targetlang1][0], $from_neon_to_trommons_pair[$targetlang1][1], $quality_level);
                }
                if (!empty($from_neon_to_trommons_pair[$sourcelang2]) && !empty($from_neon_to_trommons_pair[$targetlang2]) && ($sourcelang2 != $targetlang2)) {
                    DAO\UserDao::createUserQualifiedPair($user_id, $from_neon_to_trommons_pair[$sourcelang2][0], $from_neon_to_trommons_pair[$sourcelang2][1], $from_neon_to_trommons_pair[$targetlang2][0], $from_neon_to_trommons_pair[$targetlang2][1], $quality_level);
                }

                $org_name = trim(str_replace(array('"', '<', '>'), '', $org_name)); // Only Trommons value with limitations (not filtered on output)

                if (!empty($org_id_neon) && $org_id_neon != 3783) { // Translators without Borders (TWb)

                    if ($org_id_matching_neon = DAO\UserDao::getOrgIDMatchingNeon($org_id_neon)) {
                        DAO\AdminDao::addOrgAdmin($user_id, $org_id_matching_neon);
                        error_log("update_user_with_neon_data($email), addOrgAdmin($user_id, $org_id_matching_neon)");

                    } elseif ($org = DAO\OrganisationDao::getOrg(null, $org_name)) { // unlikely?
                        DAO\UserDao::insertOrgIDMatchingNeon($org->getId(), $org_id_neon);

                        DAO\AdminDao::addOrgAdmin($user_id, $org->getId());
                        error_log("update_user_with_neon_data($email), addOrgAdmin($user_id, " . $org->getId() . "), $org_name existing");

                    } elseif (!empty($org_name)) {
                        $org = new Common\Protobufs\Models\Organisation();
                        $org->setName($org_name);
                        $org->setEmail($email);

                        $org = DAO\OrganisationDao::insertAndUpdate($org);
                        error_log("update_user_with_neon_data($email), created Org: $org_name");
                        if (!empty($org) && $org->getId() > 0) {
                            DAO\UserDao::insertOrgIDMatchingNeon($org->getId(), $org_id_neon);

                            DAO\AdminDao::addOrgAdmin($user_id, $org->getId());
                            error_log("update_user_with_neon_data($email), addOrgAdmin($user_id, " . $org->getId() . ')');
                            Lib\Notify::sendOrgCreatedNotifications($org->getId());
                        }
                    }
                }
            }
        } else {
            error_log("update_user_with_neon_data($email), could not connect to NeonCRM");
        }
    }

    public static function changeEmail($format = ".json")
    {
        $user = DAO\UserDao::getLoggedInUser();
        if (!is_null($user) && DAO\AdminDao::isAdmin($user->getId(), null)) {
            $data = API\Dispatcher::getDispatcher()->request()->getBody();
            $client = new Common\Lib\APIHelper($format);
            $data = $client->deserialize($data, "\SolasMatch\Common\Protobufs\Models\Register");

            // password field has been repurposed to hold User for which email is to be changed
            $registered = DAO\UserDao::changeEmail($data->getPassword(), $data->getEmail());
        }
        else {
            $registered = null;
        }
        API\Dispatcher::sendResponse(null, $registered, null, $format);
    }

    public static function getUser($userId, $format = ".json")
    {
        if (!is_numeric($userId) && strstr($userId, '.')) {
            $userId = explode('.', $userId);
            $format = '.'.$userId[1];
            $userId = $userId[0];
        }
        $data = DAO\UserDao::getUser($userId);
        if (!is_null($data)) {
            $data->setPassword("");
            $data->setNonce("");
        }
        API\Dispatcher::sendResponse(null, $data, null, $format);
    }

    public static function updateUser($userId, $format = ".json")
    {
        if (!is_numeric($userId) && strstr($userId, '.')) {
            $userId = explode('.', $userId);
            $format = '.'.$userId[1];
            $userId = $userId[0];
        }
        $data = API\Dispatcher::getDispatcher()->request()->getBody();
        $client = new Common\Lib\APIHelper($format);
        $data = $client->deserialize($data, '\SolasMatch\Common\Protobufs\Models\User');
        $data->setId($userId);
        $data = DAO\UserDao::save($data);
        API\Dispatcher::sendResponse(null, $data, null, $format);
    }

    public static function deleteUser($userId, $format = ".json")
    {
        if (!is_numeric($userId) && strstr($userId, '.')) {
            $userId = explode('.', $userId);
            $format = '.'.$userId[1];
            $userId = $userId[0];
        }
        error_log("deleteUser($userId)");
        DAO\UserDao::deleteUser($userId);
        API\Dispatcher::sendResponse(null, null, null, $format);
    }

    public static function getUsers($format = ".json")
    {
        API\Dispatcher::sendResponse(null, "display all users", null, $format);
    }
    
    public static function getBannedComment($email, $format = ".json")
    {
        $client = new Common\Lib\APIHelper($format);
        
        $user = DAO\UserDao::getUser(null, $email);
        $userId = $user->getId();
        $bannedUser = AdminDao::getBannedUser($userId);
        $bannedUser = $bannedUser[0];
        $comment = $bannedUser->getComment();
        
        API\Dispatcher::sendResponse(null, $comment, null, $format);
    }
}

Users::init();

<?php

require "ui/vendor/autoload.php";

mb_internal_encoding("UTF-8");
//header("Content-Type:application/xhtml+xml;charset=UTF-8");

SmartyView::$smartyDirectory = 'ui/vendor/smarty/smarty/distribution/libs';
SmartyView::$smartyCompileDirectory = 'ui/templating/templates_compiled';
SmartyView::$smartyTemplatesDirectory = 'ui/templating/templates';
SmartyView::$smartyExtensions = array(
    'ui/vendor/slim/extras/Views/Extension/Smarty'
);

\DrSlump\Protobuf::autoload();


//TODO remove all requires bar RoutHandlers
require_once 'HTTP/Request2.php';

require_once 'Common/Settings.class.php';
require_once 'Common/NotificationIntervalEnum.class.php';
require_once 'Common/lib/Authentication.class.php';
require_once 'Common/lib/ModelFactory.class.php';
require_once 'Common/lib/BadgeTypes.class.php';

require_once 'ui/lib/Middleware.class.php';
require_once 'ui/lib/TemplateHelper.php';
require_once 'ui/lib/UserSession.class.php';
require_once 'ui/lib/URL.class.php';
require_once 'ui/lib/GraphViewer.class.php';
require_once 'ui/lib/UIWorkflowBuilder.class.php';

require_once 'ui/RouteHandlers/UserRouteHandler.class.php';
require_once 'ui/RouteHandlers/OrgRouteHandler.class.php';
require_once 'ui/RouteHandlers/TaskRouteHandler.class.php';
require_once 'ui/RouteHandlers/TagRouteHandler.class.php';
require_once 'ui/RouteHandlers/BadgeRouteHandler.class.php';
require_once 'ui/RouteHandlers/ProjectRouteHandler.class.php';

require_once 'ui/DataAccessObjects/BadgeDao.class.php';
require_once 'ui/DataAccessObjects/CountryDao.class.php';
require_once 'ui/DataAccessObjects/LanguageDao.class.php';
require_once 'ui/DataAccessObjects/UserDao.class.php';
require_once 'ui/DataAccessObjects/TaskDao.class.php';
require_once 'ui/DataAccessObjects/TagDao.class.php';
require_once 'ui/DataAccessObjects/OrganisationDao.class.php';
require_once 'ui/DataAccessObjects/StatisticsDao.class.php';
require_once 'ui/DataAccessObjects/ProjectDao.class.php';
require_once 'ui/DataAccessObjects/TipDao.class.php';

require_once 'Common/models/User.php';
require_once 'Common/models/Tag.php';
require_once 'Common/models/Task.php';
require_once 'Common/models/Organisation.php';
require_once 'Common/models/Badge.php';
require_once 'Common/models/Language.php';
require_once 'Common/models/Country.php';
require_once 'Common/models/TaskMetadata.php';
require_once 'Common/models/MembershipRequest.php';

require_once 'Common/protobufs/emails/EmailMessage.php';
require_once 'Common/protobufs/emails/FeedbackEmail.php';

/**
 * Start the session
 */
session_start();
// Can we get away from the app's old system?
//require('app/includes/smarty.php');

/**
 * Initiate the app
 */
$app = new Slim(array(
    'debug' => true,
    'view' => new SmartyView(),
    'mode' => 'development' // default is development.
    //                   TODO get from config file, or set in environment..
    //                   .... $_ENV['SLIM_MODE'] = 'production';
));

$app->configureMode('production', function () use ($app) {
    $app->config(array(
        'log.enable' => true,
        'log.path' => '../logs', // Need to set this...
        'debug' => false
    ));
});

$app->configureMode('development', function () use ($app) {
    $app->config(array(
        'log.enable' => false,
        'debug' => true
    ));
});

/*
*
*   Routing options - List all URLs here
*
*/
{

    $route_handler = new UserRouteHandler();
    $route_handler->init();

    $route_handler = new OrgRouteHandler();
    $route_handler->init();

    $route_handler = new TaskRouteHandler();
    $route_handler->init();

    $route_handler = new TagRouteHandler();
    $route_handler->init();

    $route_handler = new BadgeRouteHandler();
    $route_handler->init();
    
    $route_handler = new ProjectRouteHandler();
    $route_handler->init();    
}

function isValidPost(&$app)
{
    return $app->request()->isPost() && sizeof($app->request()->post()) > 2;
}

/**
 * Set up application objects
 * 
 * Given that we don't have object factories implemented, we'll initialise them directly here.
 */
$app->hook('slim.before', function () use ($app)
{
    $userDao = new UserDao();
    if (!is_null(UserSession::getCurrentUserID()) &&
        $current_user = $userDao->getUser(array("id" => UserSession::getCurrentUserID()))) {
        $app->view()->appendData(array('user' => $current_user));
        $org_array = $userDao->getUserOrgs(UserSession::getCurrentUserID());
        if ($org_array && count($org_array) > 0) {
            $app->view()->appendData(array(
                'user_is_organisation_member' => true
            ));
        }

        $tasks = $userDao->getUserTasks(UserSession::getCurrentUserID());
        if($tasks && count($tasks) > 0) {
            $app->view()->appendData(array(
                        "user_has_active_tasks" => true
            ));
        }

        $isAdmin = $userDao->isAdmin(UserSession::getCurrentUserID());
        if ($isAdmin) {
            $app->view()->appendData(array(
                        'site_admin' => true
            ));
        }
    }
});

$app->run();

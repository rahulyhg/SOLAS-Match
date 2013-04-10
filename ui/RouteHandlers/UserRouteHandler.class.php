<?php

require_once "ui/DataAccessObjects/UserDao.class.php";
require_once "Common/models/Register.php";
require_once "Common/models/Login.php";
require_once "Common/models/PasswordResetRequest.php";
require_once "Common/models/PasswordReset.php";

class UserRouteHandler
{
    public function init()
    {
        $app = Slim::getInstance();
        $middleware = new Middleware();

        $app->get("/", array($this, "home"))->via("POST")->name("home");

        $app->get("/register", array($this, "register")
        )->via("GET", "POST")->name("register");

        $app->get("/:uid/password/reset", array($this, "passwordReset")
        )->via("POST")->name("password-reset");

        $app->get("/password/reset", array($this, "passResetRequest")
        )->via("POST")->name("password-reset-request");
        
        $app->get("/logout", array($this, "logout"))->name("logout");
        
        $app->get("/login", array($this, "login")
        )->via("GET", "POST")->name("login");

        $app->get("/:user_id/profile", array($this, "userPublicProfile")
        )->via("POST")->name("user-public-profile");

        $app->get("/profile", array($middleware, "authUserIsLoggedIn"), 
        array($this, "userPrivateProfile"))->via("POST")->name("user-private-profile");
    }

    public function home()
    {
        $app = Slim::getInstance();
        $tagDao = new TagDao();
        $taskDao = new TaskDao();
        $projectDao = new ProjectDao();
        $orgDao = new OrganisationDao();
        $userDao = new UserDao(); 
                
        $use_statistics = Settings::get("site.stats"); 
        if ($use_statistics == 'y') {
            $statsDao = new StatisticsDao();
            $statistics = $statsDao->getStats();    
            $statsArray = null;
            if($statistics) {
                $statsArray = array();
                foreach($statistics as $stat) {
                    $statsArray[$stat->getName()] = $stat;
                }
            }

            $app->view()->appendData(array(
                "statsArray" => $statsArray
            ));
        }
        
        $top_tags = $tagDao->getTopTags(10);        
        $app->view()->appendData(array(
            "top_tags" => $top_tags,
            "current_page" => "home",
        ));

        $current_user_id = UserSession::getCurrentUserID();
        
        if ($current_user_id == null) {
            $tasks = $taskDao->getTopTasks(10);
            for ($i = 0; $i < count($tasks); $i++) {
                $tasks[$i]['Project'] = $projectDao->getProject(array('id' => $tasks[$i]->getProjectId()));
                $tasks[$i]['Org'] = $orgDao->getOrganisation(array('id' => $tasks[$i]['Project']->getOrganisationId()));
            }

            $app->view()->appendData(array(
                "tasks" => $tasks
            ));

        } else {
            $taskTypes = array();
            $taskTypes[TaskTypeEnum::CHUNKING] = "Chunking";
            $taskTypes[TaskTypeEnum::TRANSLATION] = "Translation";
            $taskTypes[TaskTypeEnum::PROOFREADING] = "Proofreading";
            $taskTypes[TaskTypeEnum::POSTEDITING] = "Post-Editing";

            $langDao = new LanguageDao();
            $languageList = $langDao->getLanguage(null);

            $filter = array();
            $selectedType = "";
            $selectedSource = "";
            $selectedTarget = "";
            if ($app->request()->isPost()) {
                $post = (object) $app->request()->post();

                if (isset($post->taskType) && $post->taskType != '') {
                    $selectedType = $post->taskType;
                    $filter['taskType'] = $post->taskType;
                }

                if (isset($post->sourceLanguage) && $post->sourceLanguage != '') {
                    $selectedSource = $post->sourceLanguage;
                    $filter['sourceLanguage'] = $post->sourceLanguage;
                }

                if (isset($post->targetLanguage) && $post->targetLanguage != '') {
                    $selectedTarget = $post->targetLanguage;
                    $filter['targetLanguage'] = $post->targetLanguage;
                }
            }

            $tasks = $userDao->getUserTopTasks($current_user_id, 10, $filter);
            for ($i = 0; $i < count($tasks); $i++) {
                $tasks[$i]['Project'] = $projectDao->getProject(array('id' => $tasks[$i]->getProjectId()));
                $tasks[$i]['Org'] = $orgDao->getOrganisation(array('id' => $tasks[$i]['Project']->getOrganisationId()));
            }
            
            $app->view()->appendData(array(
                "taskTypes" => $taskTypes,
                "languageList" => $languageList,
                "selectedType" => $selectedType,
                "selectedSource" => $selectedSource,
                "selectedTarget" => $selectedTarget,
                "tasks" => $tasks
            ));
            
            $user_tags = $userDao->getUserTags($current_user_id);
            $app->view()->appendData(array(
                        "user_tags" => $user_tags
            ));
        }
        
        $numTaskTypes = Settings::get("ui.task_types");
        $taskTypeColours = array();
        
        for($i=1; $i <= $numTaskTypes; $i++) {
            $taskTypeColours[$i] = Settings::get("ui.task_{$i}_colour");
        }  
        
        $app->view()->appendData(array(
                     "taskTypeColours" => $taskTypeColours
        ));
        
        $app->render("index.tpl");
    }

    public function register()
    {
        $app = Slim::getInstance();
        $userDao = new UserDao();
        
        $use_openid = Settings::get("site.openid");
        $app->view()->setData("openid", $use_openid);
        if (isset($use_openid)) {
            if ($use_openid == "y" || $use_openid == "h") {
                $extra_scripts = "
                    <script type=\"text/javascript\" src=\"{$app->urlFor("home")}ui/js/openid-jquery.js\"></script>
                    <script type=\"text/javascript\" src=\"{$app->urlFor("home")}ui/js/openid-en.js\"></script>
                    <link type=\"text/css\" rel=\"stylesheet\" media=\"all\" href=\"{$app->urlFor("home")}resources/css/openid.css\" />";
                $app->view()->appendData(array("extra_scripts" => $extra_scripts));
            }   
        }   
        $error = null;
        $warning = null;
        if (isValidPost($app)) {
            $post = (object) $app->request()->post();
            
            if (!TemplateHelper::isValidEmail($post->email)) {
                $error = "The email address you entered was not valid. Please cheak for typos and try again.";
            } elseif (!TemplateHelper::isValidPassword($post->password)) {
                $error = "You didn\"t enter a password. Please try again.";
            }
            
            if (is_null($error)) {
                $response = $userDao->register($post->email, $post->password);
                if ($response) {
                    $user = $userDao->login($post->email, $post->password);
                    try {                        
                        if (!is_array($user) && !is_null($user)) {
                            UserSession::setSession($user->getUserId());
                        } else {
                            throw new InvalidArgumentException("Sorry, the  password or username entered is incorrect.
                                                                Please check the credentials used and try again.");    
                        }                    
                                       
                        
                        if (isset($_SESSION["previous_page"])) {
                            if (isset($_SESSION["old_page_vars"])) {
                                $app->redirect($app->urlFor($_SESSION["previous_page"], $_SESSION["old_page_vars"]));
                            } else {
                                $app->redirect($app->urlFor($_SESSION["previous_page"]));
                            }
                        }
                        $app->redirect($app->urlFor("user-public-profile", array("user_id" => $user->getUserId())));
                    } catch (InvalidArgumentException $e) {
                        $error = "<p>Unable to log in. Please check your email and password.";
                        $error .= " <a href=\"{$app->urlFor("login")}\">Try logging in again</a>";
                        $error .= " or <a href=\"{$app->urlFor("register")}\">register</a> for an account.</p>";
                        $error .= "<p>System error: <em> {$e->getMessage()}</em></p>";

                        $app->flash("error", $error);
                        $app->redirect($app->urlFor("login"));
                        echo $error;                                        
                    }
                } else {
                    $warning = "You have already created an account.
                        <a href=\"{$app->urlFor("login")}\">Please log in.</a>";
                }
            }
        }
        if ($error !== null) {
            $app->view()->appendData(array("error" => $error));
        }
        if ($warning !== null) {
            $app->view()->appendData(array("warning" => $warning));
        }
        $app->render("register.tpl");
    }

    public function passwordReset($uid)
    {
        $app = Slim::getInstance();
        $userDao = new UserDao();
        
        $reset_request = $userDao->getPasswordResetRequest($uid);
        if (!is_object($reset_request)) {
            $app->flash("error", "Incorrect Unique ID. Are you sure you copied the URL correctly?");
            $app->redirect($app->urlFor("home"));
        }
        
        $user_id = $reset_request->getUserId();
        $app->view()->setData("uid", $uid);
        if ($app->request()->isPost()) {
            $post = (object) $app->request()->post();

            if (isset($post->new_password) && TemplateHelper::isValidPassword($post->new_password)) {
                if (isset($post->confirmation_password) && 
                        $post->confirmation_password == $post->new_password) {

                    $response = $userDao->resetPassword($post->new_password, $uid);
                    if ($response) {
                        $app->flash("success", "You have successfully changed your password");
                        $app->redirect($app->urlFor("home"));
                    } else {
                        $app->flashNow("error", "Unable to change Password");
                    }
                } else {
                    $app->flashNow("error", "The passwords entered do not match.
                                        Please try again.");
                }
            } else {
                $app->flashNow("error", "Please check the password provided, and try again.
                                It was not found to be valid.");
            }
        }        
        $app->render("password-reset.tpl");
    }

    public function passResetRequest()
    {
        $app = Slim::getInstance();
        $userDao = new UserDao();
        
        if ($app->request()->isPost()) {
            $post = (object) $app->request()->post();
            if (isset($post->password_reset)) {
                if (isset($post->email_address) && $post->email_address != '') {
                    $user = $userDao->getUser(array('email' => $post->email_address)); 
                    if ($user) {  
                        $hasUserRequestedPwReset = $userDao->hasUserRequestedPasswordReset($user->getUserId());
                        $message = "";
                        if (!$hasUserRequestedPwReset) {
                            //send request
                            $userDao->requestPasswordReset($user->getUserId());
                            $app->flash("success", "Password reset request sent. Check your email
                                                    for further instructions.");
                            $app->redirect($app->urlFor("home"));
                        } else {
                            //get request time
                            $response = $userDao->getPasswordResetRequestTime($user->getUserId());
                            $app->flashNow("info", "Password reset request was already sent on $response.
                                                     Another email has been sent to your contact address.
                                                     Follow the link in this email to reset your password");
                            //Send request
                            $userDao->requestPasswordReset($user->getUserId());
                        }
                    } else {
                        $app->flashNow("error", "Please enter a valid email address");
                    }
                } else {
                    $app->flashNow("error", "Please enter a valid email address");
                }
            }
        }
        $app->render("user.reset-password.tpl");
    }
    
    public function logout()
    {
        $app = Slim::getInstance();
        UserSession::destroySession();    //TODO revisit when oauth is in place
        $app->redirect($app->urlFor("home"));
    }

    public function login()
    {
        $app = Slim::getInstance();
        $userDao = new UserDao();
        
        $error = null;
        $openid = new LightOpenID("http://".$_SERVER["HTTP_HOST"].$app->urlFor("home"));
        $use_openid = Settings::get("site.openid");
        $app->view()->setData("openid", $use_openid);
        if (isset($use_openid)) {
            if ($use_openid == "y" || $use_openid == "h") {
                $extra_scripts = "
                    <script type=\"text/javascript\" src=\"{$app->urlFor("home")}ui/js/openid-jquery.js\"></script>
                    <script type=\"text/javascript\" src=\"{$app->urlFor("home")}ui/js/openid-en.js\"></script>
                    <link type=\"text/css\" rel=\"stylesheet\" media=\"all\" href=\"{$app->urlFor("home")}resources/css/openid.css\" />";
                $app->view()->appendData(array("extra_scripts" => $extra_scripts));
            }
        }
        
        try {
            if (isValidPost($app)) {
                $post = (object) $app->request()->post();

                if (isset($post->login)) {
                    $user = $userDao->login($post->email, $post->password);
                    if (!is_array($user) && !is_null($user)) {
                        UserSession::setSession($user->getUserId());
                    } else {
                        throw new InvalidArgumentException("Sorry, the username or password entered is incorrect.
                            Please check the credentials used and try again.");    
                    }
                    
                    $app->redirect($app->urlFor("home"));
                } elseif (isset($post->password_reset)) {
                    $app->redirect($app->urlFor("password-reset-request"));
                }
            } elseif ($app->request()->isPost() || $openid->mode) {
                if($this->openIdLogin($openid, $app)){
                   $app->redirect($app->urlFor("home"));
                }  else {
                    $app->redirect($app->urlFor("user-public-profile", array("user_id" => UserSession::getCurrentUserID())));
                }
            }
            $app->render("login.tpl");
        } catch (InvalidArgumentException $e) {
            $error = "<p>Unable to log in. Please check your email and password.";
            $error .= " <a href=\"{$app->urlFor("login")}\">Try logging in again</a>";
            $error .= " or <a href=\"{$app->urlFor("register")}\">register</a> for an account.</p>";
            $error .= "<p>System error: <em> {$e->getMessage()} </em></p>";
            
            $app->flash("error", $error);
            $app->redirect($app->urlFor("login"));
            echo $error;
        }
    }
    
    public function openIdLogin($openid, $app)
    {       
        if (!$openid->mode) {
            try {
                $openid->identity = $openid->data["openid_identifier"];
                $openid->required = array("contact/email");
                $url = $openid->authUrl();
                $app->redirect($openid->authUrl());
            } catch (ErrorException $e) {
                echo $e->getMessage();
            }
        } elseif ($openid->mode == "cancel") {
            throw new InvalidArgumentException("User has canceled authentication!");
        } else {
            $retvals= $openid->getAttributes();
            if ($openid->validate()) {
                $userDao = new UserDao();
                $user = $userDao->getUser(array('email' => $retvals['contact/email']));
                if(is_array($user)) $user = $user[0];                    
                if(is_null($user)) {
                    $user = $userDao->register($retvals["contact/email"], md5($retvals["contact/email"]));
                    if(is_array($user)) $user = $user[0]; 
                    UserSession::setSession($user->getUserId());
                    return false;
                }
                UserSession::setSession($user->getUserId());
                
            }
            return true;
        }
    }        

    public static function userPrivateProfile()
    {
        $app = Slim::getInstance();
        $userDao = new UserDao();
        $user_id = UserSession::getCurrentUserID();
        $user = $userDao->getUser(array('id' => $user_id));

        $languageDao = new LanguageDao();
        $countryDao = new CountryDao();
        $languages = $languageDao->getLanguage(null);
        $countries = $countryDao->getCountries();
        
        if (!is_object($user)) {
            $app->flash("error", "Login required to access page");
            $app->redirect($app->urlFor("login"));
        }
        
        if ($app->request()->isPost()) {
            $displayName = $app->request()->post("name");
            if ($displayName != null) {
                $user->setDisplayName($displayName);
            }
            
            $userBio = $app->request()->post("bio");
            if ($userBio != null && $userBio != '') {
                $user->setBiography($userBio);
            }
            
            $nativeLang = $app->request()->post("nLanguage");
            $langCountry= $app->request()->post("nLanguageCountry");
            if ($nativeLang != null && $langCountry != null) {
                $user->setNativeLangId($nativeLang);
                $user->setNativeRegionId($langCountry);

                $badge_id = BadgeTypes::NATIVE_LANGUAGE;
                $userDao->addUserBadgeById($user_id, $badge_id);               
            }
            
            if ($user->getDisplayName() != ""
                    && $user->getNativeLangId() != "" && $user->getNativeRegionId() != "") {

                $userDao->updateUser($user);
                $badge_id = BadgeTypes::NATIVE_LANGUAGE;
                $userDao->addUserBadgeById($user_id, $badge_id);               
                $badge_id = BadgeTypes::PROFILE_FILLER;
                $userDao->addUserBadgeById($user_id, $badge_id);               
            }
            
            $app->redirect($app->urlFor("user-public-profile", array("user_id" => $user->getUserId())));
        }
        
        $app->view()->setData("languages", $languages);
        $app->view()->setData("countries", $countries);
        
       
        $app->render("user-private-profile.tpl");
    }

    public static function userPublicProfile($user_id)
    {
        $app = Slim::getInstance();
        $userDao = new UserDao();
        $orgDao = new OrganisationDao();

        $user = $userDao->getUser(array('id' => $user_id));
        if ($app->request()->isPost()) {
            $post = (object) $app->request()->post();
            
            if (isset($post->revokeBadge) && isset($post->badge_id) && $post->badge_id != ""){
                $badge_id = $post->badge_id;
                $userDao->removeUserBadge($user_id, $badge_id);
            }
                
            if (isset($post->revoke)) {
                $org_id = $post->org_id;
                $userDao->leaveOrganisation($user_id, $org_id); 
            } 
        }
                    
        $archivedJobs = $userDao->getUserArchivedTasks($user_id, 10);
        $user_tags = $userDao->getUserTags($user_id);
        $user_orgs = $userDao->getUserOrgs($user_id);
        $badges = $userDao->getUserBadges($user_id);

        $orgList = array();
        foreach ($badges as $badge) {
            if ($badge->getOwnerId() != null) {
                $org = $orgDao->getOrganisation(array('id' => $badge->getOwnerId()));
                $orgList[$badge->getOwnerId()] = $org;
            }
        }       
       
        $org_creation = Settings::get("site.organisation_creation");
            
        $extra_scripts = "<script type=\"text/javascript\" src=\"{$app->urlFor("home")}";
        $extra_scripts .= "resources/bootstrap/js/confirm-remove-badge.js\"></script>";

        $app->view()->setData("orgList", $orgList);
        $app->view()->appendData(array("badges" => $badges,
                                    "user_orgs" => $user_orgs,
                                    "current_page" => "user-profile",
                                    "archivedJobs" => $archivedJobs,
                                    "user_tags" => $user_tags,
                                    "this_user" => $user,
                                    "extra_scripts" => $extra_scripts,
                                    "org_creation" => $org_creation
        ));
                
        if (UserSession::getCurrentUserID() === $user_id) {
            $notifData = $userDao->getUserTaskStreamNotification($user_id);
            $interval = null;
            $lastSent = null;

            if ($notifData) {
                $interval = $notifData['interval'];
                switch ($interval) {
                    case NotificationIntervalEnum::DAILY:
                        $interval = "daily";
                        break;
                    case NotificationIntervalEnum::WEEKLY:
                        $interval = "weekly";
                        break;
                    case NotificationIntervalEnum::MONTHLY:
                        $interval = "monthly";
                        break;
                }

                if ($notifData['last-sent'] != null) {
                    $lastSent = date(Settings::get("ui.date_format"), strtotime($notifData['last-sent']));
                }
            }
            $app->view()->appendData(array(
                        "interval" => $interval,
                        "lastSent"       => $lastSent,
                        "private_access" => true
            ));
        }
                    
        $app->render("user-public-profile.tpl");
    }
    

    public static function isLoggedIn()
    {
        return (!is_null(UserSession::getCurrentUserId()));
    }     
}

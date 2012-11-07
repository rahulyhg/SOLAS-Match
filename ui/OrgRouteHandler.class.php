<?php

class OrgRouteHandler
{
    public function init()
    {
        $app = Slim::getInstance();
        $middleware = new Middleware();

        $app->get('/org/create', array($middleware, 'authUserIsLoggedIn'), 
        array($this, 'createOrg'))->via('POST')->name('create-org');

        $app->get('/org/request/:org_id', array($this, 'orgRequestMembership')
        )->name('org-request-membership');

        $app->get('/org/:org_id/request/:user_id/:accept', array($middleware, 'authUserForOrg'), 
        array($this, 'orgProcessRequest'))->name('org-process-request');

        $app->get('/org/request/queue/:org_id', array($middleware, 'authUserForOrg'), 
        array($this, 'orgRequestQueue'))->via("POST")->name('org-request-queue');

        $app->get('/org/private/:org_id', array($middleware, 'authUserForOrg'), 
        array($this, 'orgPrivateProfile'))->via('POST')->name('org-private-profile');

        $app->get('/org/profile/:org_id', array($this, 'orgPublicProfile')
        )->via('POST')->name('org-public-profile');

        $app->get('/org/:org_id/manage/:badge_id/', array($middleware, 'authUserForOrg'), 
        array($this, 'orgManageBadge'))->via("POST")->name('org-manage-badge');

        $app->get('/org/create/badge/:org_id/', array($middleware, 'authUserForOrg'), 
        array($this, 'orgCreateBadge'))->via('POST')->name('org-create-badge');

        $app->get("/org/search", array($this, 'orgSearch')
        )->via('POST')->name('org-search');
        
        $app->get('/org/:org_id/edit/:badge_id', array($middleware, 'authUserForOrg'), 
        array($this, 'orgEditBadge'))->via("POST")->name('org-edit-badge');        
        
        
    }

    public function createOrg()
    {
        $app = Slim::getInstance();

        if($app->request()->isPost()) {
            $post = (object) $app->request()->post();

            $org = new Organisation(null);
            if(isset($post->name) && $post->name != null) {
                $org->setName($post->name);
            }

            if(isset($post->home_page) && ($post->home_page != '' || $post->home_page != 'http://')) {
                $org->setHomePage($post->home_page);
            }

            if(isset($post->bio) && $post->bio != '') {
                $org->setBiography($post->bio);
            }

            if($org->getName() != '') {
                $org_dao = new OrganisationDao();
                if(!is_object($org_dao->find(array('name' => $org->getName())))) {      //wait for API support
                    if($new_org = $org_dao->save($org))     //wait for API support
                    {
                        $user_id = UserSession::getCurrentUserID();
                        $org_dao->acceptMemRequest($new_org->getId(), $user_id);    //wait for API support
                        $org_name = $org->getName();
                        $app->flashNow('success', "Organisation \"$org_name\" has been created. 
                                            Visit the <a href='".$app->urlFor("client-dashboard")."'>client dashboard</a> 
                                            to start uploading tasks.");
                    } else {
                        $app->flashNow('error', "Unable to save Organisation.");
                    }
                } else {
                    $org_name = $org->getName();
                    $app->flashNow('error', "An Organisation named \"$org_name\" is already registered
                                            with SOLAS Match. Please use a different name.");
                }
            } else {
                $app->flashNow('error', "You must specify a name for the organisation.");
            }
        }
        
        $app->render('create-org.tpl');
    }

    public function orgRequestMembership($org_id)
    {
        $app = Slim::getInstance();
        $client = new APIClient();

        $user_id = UserSession::getCurrentUserID();
        $request = APIClient::API_VERSION."/users/$user_id";
        $response = $client->call($request);
        $user = $client->cast('User', $response);
        
        $request = APIClient::API_VERSION."/users/$user_id/orgs";
        $user_orgs = (array)$client->call($request);
        if(is_null($user_orgs) || !in_array($org_id, $user_orgs)) {
            $org_dao = new OrganisationDao();
            if($org_dao->requestMembership($user->getUserId(), $org_id)) {  //wait for API support
                $app->flash("success", "Successfully requested membership.");
            } else {
                $app->flash("error", "You have already sent a membership request to this Organisation");
            }   
        } else {
            $app->flash("error", "You are already a member of this organisation");
        }   
        $app->redirect($app->urlFor('org-public-profile', array('org_id' => $org_id)));
    }

    public function orgRequestQueue($org_id)
    {
        $app = Slim::getInstance();
        $client = new APIClient();

        $request = APIClient::API_VERSION."/orgs/$org_id";
        $response = $client->call($request);
        $org = $client->cast('Organisation', $response);

        $user_dao = new UserDao();
        $org_dao = new OrganisationDao();
        
        if($app->request()->isPost()) {
            $post = (object)$app->request()->post();
            
            if(isset($post->email)) {
                if(User::isValidEmail($post->email)) {
                    $user = $user_dao->find(array('email' => $post->email));    //wait for API support
                
                    if(!is_null($user)) {
                        $user_id = $user->getUserId();
                        $request = APIClient::API_VERSION."/users/$user_id/orgs";
                        $user_orgs = (array)$request;
                    
                        if($user->getDisplayName() != '') {
                            $user_name = $user->getDisplayName();
                        } else {
                            $user_name = $user->getEmail();
                        }   
                        if(is_null($user_orgs) || !in_array($org_id, $user_orgs)) {
                            $org_dao->acceptMemRequest($org_id, $user->getUserId());    //wait for API support
                    
                            if($org->getName() != '') {
                                $org_name = $org->getName();
                            } else {
                                $org_name = "Organisation $org_id";
                            }   
                            $app->flashNow('success', "Successfully added $user_name as a member of $org_name");
                        } else {
                            $app->flashNow('error', "$user_name is already a member of this organisation");
                        }   
                    } else {
                        $email = $post->email;
                        $app->flashNow('error',
                            "The email address $email is not registered with this system.
                            Are you sure you have the right email addess?"
                        );
                    }
                } else {
                    $app->flashNow('error', 'You did not enter a valid email address');
                }
            } elseif(isset($post->accept)) {
                if($user_id = $post->user_id) {
                    $org_dao->acceptMemRequest($org_id, $user_id);      //wait for API support

                    $request = APIClient::API_VERSION."/users/$user_id";
                    $response = $client->call($request);
                    $user = $client->cast('User', $response);
                    Notify::notifyUserOrgMembershipRequest($user, $org, true);
                } else {
                    $app->flashNow("error", "Invalid User ID: $user_id");
                }
            } elseif(isset($post->refuse)) {
                if($user_id = $post->user_id) {
                    $org_dao->refuseMemRequest($org_id, $user_id);      //wait for API support
                    
                    $request = APIClient::API_VERSION."/users/$user_id";
                    $response = $client->call($request);
                    $user = $client->cast('User', $response);
                    Notify::notifyUserOrgMembershipRequest($user, $org, false);
                } else {
                    $app->flashNow("error", "Invalid User ID: $user_id");
                }
            }
        }
        
        $requests = $org_dao->getMembershipRequests($org_id);       //wait for API support
        $user_list = array();
        if(count($requests) > 0) {
            foreach($requests as $request) {
                $user_list[] = $user_dao->find(array('user_id' => $request['user_id']));
            }
        }
        
        $app->view()->setData('org', $org);
        $app->view()->appendData(array('user_list' => $user_list));
        
        $app->render('org.request_queue.tpl');
    }

    public function orgPrivateProfile($org_id)
    {
        $app = Slim::getInstance();
        $client = new APIClient();

        $request = APIClient::API_VERSION."/orgs/$org_id";
        $response = $client->call($request);
        $org = $client->cast('Organisation', $response);
        
        if($app->request()->isPost()) {
            $name = $app->request()->post('name');
            if($name != NULL) {
                $org->setName($name);
            }   
            
            $home_page = $app->request()->post('home_page');
            if($home_page != NULL) {
                $org->setHomePage($home_page);
            }   
            
            $bio = $app->request()->post('bio');
            if($bio != NULL) {
                $org->setBiography($bio);
            }   
            
            $org_dao = new OrganisationDao();
            $org_dao->save($org);       //Wait for API support
            $app->redirect($app->urlFor('org-public-profile', array('org_id' => $org->getId())));
        }   
        
        $app->view()->setData('org', $org);
        
        $app->render('org-private-profile.tpl');
    }

    public function orgPublicProfile($org_id)
    {
        $app = Slim::getInstance();
        $client = new APICLient();

        $request = APIClient::API_VERSION."/orgs/$org_id";
        $response = $client->call($request);
        $org = $client->cast('Organisation', $response);
        $org_dao = new OrganisationDao();
  
        //wait for API support      
//        $org_badges = array();
//        $request = APIClient::API_VERSION."/orgs/$org_id/badges";
//        $response = $client->call($request);
//        foreach($response as $stdObject) {
//            $org_badges[] = $client->cast('Badge', $stdObject);
//            
//            
//        }
        
        $badge_dao = new BadgeDao();
        
         if($app->request()->isPost()) {
            $post = (object) $app->request()->post();
                   
            if(isset($post->deleteBadge)) {
                $badge_id = $post->badge_id;
                $badge_dao->deleteBadge($badge_id);
            } 
            
            if(isset($post->title) && isset($post->description)) {
                
                if($post->title == '' || $post->description == '') {
                    $app->flash('error', "All fields must be filled out");
                } else {
                    $params = array();
                    $params['badge_id'] = $post->badge_id;             
                    $params['title'] = $post->title;
                    $params['description'] = $post->description;
                    $params['owner_id'] = null; 

                    $badge_dao = new BadgeDao();
                    $updatedBadge = new Badge($params);
                    $badge_dao->insertAndUpdateBadge($updatedBadge);       //wait for API support
                    $app->redirect($app->urlFor('org-public-profile', array('org_id' => $org_id)));
                }
            }
        }       
        
        $org_badges = $badge_dao->getOrgBadges($org_id);
        
        //wait for API support     
//        $request = APIClient::API_VERSION."/orgs/$org_id/members";
//        $org_member_ids = (array)$client->call($request);
        $org_member_ids = $org_dao->getOrgMembers($org_id);
        
        $org_members = array();
        if(count($org_member_ids) > 0) {
            foreach($org_member_ids as $org_mem) {
                $org_members[] = $org_mem['user_id'];
            }   
        }
        
        $app->view()->setData('current_page', 'org-public-profile');
        $app->view()->appendData(array('org' => $org,
                'org_members' => $org_members,
                'org_badges' => $org_badges
        ));
        
        $app->render('org-public-profile.tpl');
    }

    public function orgManageBadge($org_id, $badge_id)
    {
        $app = Slim::getInstance();
        $client = new APIClient();

        $request = APIClient::API_VERSION."/badges/$badge_id";
        $response = $client->call($request);
        $badge = $client->cast('Badge', $response);
        $badge_dao = new BadgeDao();
       
//        $user_list = array();
//        $request = APIClient::API_VERSION."/badges/$badge_id/users";
//        $response = $client->call($request);
//        foreach($response as $stdObject) {
//            $user_list[] = $client->cast('User', $stdObject);
//        }
        $user_dao = new UserDao();
        $user_list = $user_dao->getUsersWithBadge($badge);      //wait for API support (above)
        
        $extra_scripts = "<script type=\"text/javascript\" src=\"".$app->urlFor("home");
        $extra_scripts .= "resources/bootstrap/js/confirm-remove-badge.js\"></script>";
        
        $app->view()->setData('badge', $badge);
        $app->view()->appendData(array(
                    'org_id'        => $org_id,
                    'extra_scripts' =>$extra_scripts
        ));

        if($app->request()->isPost()) {
            $post = (object) $app->request()->post();
            
            if(isset($post->email) && $post->email != '') {
                if(User::isValidEmail($post->email)) {
                    $user = $user_dao->find(array('email' => $post->email));    //wait for API support
                    
                    if(!is_null($user)) {
                        $user_badges = array();
                        $user_id = $user->getUserId();
                        $request = APIClient::API_VERSION."/users/$user_id/badges";
                        $response = $client->call($request);
                        foreach($response as $badge_data) {
                            $user_badges[] = new Badge((array)$badge_data);
                        }
                        $badge_ids = array();
                        if(count($user_badges) > 0) {
                            foreach($user_badges as $badge_tmp) {
                                $badge_ids[] = $badge_tmp->getBadgeId();
                            }
                        }
                        
                        if(!in_array($badge_id, $badge_ids)) {
                            $badge_dao->assignBadge($user, $badge);     //wait for API support
                            
                            $user_name = '';
                            if($user->getDisplayName() != '') {
                                $user_name = $user->getDisplayName();
                            } else {
                                $user_name = $user->getEmail();
                            }
                            
                            $app->flashNow('success', "Successfully Assigned Badge \"".$badge->getTitle()."\" to user $user_name");
                        } else {
                            $app->flashNow('error', 'The user '.$post->email.' already has that badge');
                        }
                    } else {
                        $app->flashNow('error',
                            'The email address '.$post->email.' is not registered on the system. 
                            Are you using the correct email address?'
                        );
                    }
                } else {
                    $app->flashNow('error', "You did not enter a valid email address");
                }
            } elseif(isset($post->user_id) && $post->user_id != '') {
                $user_id = $post->user_id;
                $request = APIClient::API_VERSION."/users/$user_id";
                $response = $client->call($request);
                $user = $client->cast('User', $response);
                $badge_dao->removeUserBadge($user, $badge);     //wait for API support
                $user_name = '';
                if($user->getDisplayName() != '') {
                    $user_name = $user->getDisplayName();
                } else {
                    $user_name = $user->getEmail();
                }
                $app->flashNow('success', "Successfully removed badge form user $user_name");
            } else {
                $app->flashNow('error', "Incorrect POST data");
            }
        }
        
        //wait for API support      
//        $user_list = array();
//        $request = APIClient::API_VERSION."/badges/".$badge->getBadgeId()."/users";
//        $response = $client->call($request);
//        foreach($response as $stdObject) {
//            $user_list[] = $client->cast('User', $stdObject);
//        }
        $user_list = $user_dao->getUsersWithBadge($badge);
        
        $app->view()->appendData(array(
            'user_list' => $user_list
        ));
        
        $app->render('org.manage-badge.tpl');
    }

    public function orgCreateBadge($org_id)
    {
        $app = Slim::getInstance();

        if(isValidPost($app)) {
            $post = (object)$app->request()->post();
            
            if($post->title == '' || $post->description == '') {
                $app->flash('error', "All fields must be filled out");
            } else {
                $params = array();
                $params['title'] = $post->title;
                $params['description'] = $post->description;
                $params['owner_id'] = $org_id;
                
                $badge_dao = new BadgeDao();
                $badge = new Badge($params);
                $badge_dao->insertAndUpdateBadge($badge);       //wait for API support
                $app->redirect($app->urlFor('org-public-profile', array('org_id' => $org_id)));
            }
        }
        
        $app->view()->setData('org_id', $org_id);
        
        $app->render('org.create-badge.tpl');
    }

    public function orgSearch()
    {
        $app = Slim::getInstance();

        if($app->request()->isPost()) {
            $post = (object) $app->request()->post();
            
            if(isset($post->search_name) && $post->search_name != '') {
                $org_dao = new OrganisationDao();
                $found_orgs = $org_dao->searchForOrg($post->search_name);       //wait for API support
                
                if(count($found_orgs) < 1) {
                    $app->flashNow('error', 'No Organisations found');
                } else {
                    $app->view()->setData('found_orgs', $found_orgs);
                }
            }
        }
        
        $app->render('org-search.tpl');
    }
    
    public function orgEditBadge($org_id, $badge_id)
    {
        $app = Slim::getInstance();
        $client = new APIClient();
        

        $request = APIClient::API_VERSION."/badges/$badge_id";
        $response = $client->call($request);
        $badge = $client->cast('Badge', $response);

        $app->view()->setData('badge', $badge);
        
        $app->view()->appendData(array('org_id' => $org_id));        
        
        $app->render('org.edit-badge.tpl');
        
    }
    
}

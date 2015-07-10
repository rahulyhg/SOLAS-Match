{include file="header-gray.tpl"}



<section class="site-content">
      <h2>  <a  href="{urlFor name='home'}">{Settings::get('site.name')}</a></h2>
          <section class="form">
            <div class="row">
              <div class="col-md-4">
                  <div class="button-row">

                   <ul id="login">
               <!--    Log in
 -->

                  {if isset($openid)&& ($openid==='y'||$openid==='h' )}
    <!-- Simple OpenID Selector -->
    <form action="{urlFor name='login'}" method="post" id="openid_form">
        <input type="hidden" name="action" value="verify" />
        <fieldset>
            <!-- <legend>{Localisation::getTranslation('common_signin_or_create_new_account')}</legend> -->
            <div id="openid_choice">
              <!--   <p>{Localisation::getTranslation('common_please_click_your_account_provider')}</p> -->
                 {if isset($gplus) && ($gplus === 'y')}
                   <!--  <div id="gSignInWrapper"> -->
                     <!--    <div id="customGplusBtn" class="customGPlusSignIn"> -->
                           <!-- <span id="customGplusBtnIcon"></span> -->
                      <!--      <span class="btn btn-default btn-lg btn-block" id="customGplusBtnText">Google</span> -->
                        <!-- </div> -->
                  <!--   </div> -->
                {/if}
              <!--   <div id="openid_btns"></div> -->
            </div>
            <div id="openid_input_area">
                    <input id="openid_identifier" name="openid_identifier" type="text" />
                    <input id="openid_submit" type="submit" class="btn btn-primary" value="{Localisation::getTranslation('login_signin')}"/>
            </div>
            <noscript>
                 <p>
                    {Localisation::getTranslation('common_openid_is_service_that_allows_you_to_logon_to_many_different_websites_using_a_single_indentity')}
                    {sprintf(Localisation::getTranslation('login_0'), "http://openid.net/what/", "http://openid.net/get/")} 
                </p>
            </noscript>
        </fieldset>
    </form>
    <!-- /Simple OpenID Selector -->
{/if}




                       <li><a class="btn btn-default btn-lg btn-block" href="#" role="button">Google +</a></li>
                       <li><a class="btn btn-default btn-lg btn-block" href="#" role="button">Email</a></li>


                      </ul>
                  </div>
                        <div class="button-row">
                            <ul id="register">
                  Register

                  {if isset($openid)&& ($openid==='n'||$openid==='h' )}
    <form method="post" action="{urlFor name="register"}" class="well" accept-charset="utf-8">
         <!--    <label for="email"><strong>{Localisation::getTranslation('common_email')}</strong></label> -->
            <input class="btn btn-default btn-lg btn-block" type="text" name="email" id="email" placeholder="{Localisation::getTranslation('register_your_email')}"/>
           <!--  <label for="password"><strong>{Localisation::getTranslation('common_password')}</strong></label> -->
            <input class="btn btn-default btn-lg btn-block" type="password" name="password" id="password" placeholder="{Localisation::getTranslation('register_your_password')}"/>
            <p>
                <button type="submit" class="btn btn-success" name="submit">
                    <i class="icon-star icon-white"></i> {Localisation::getTranslation('common_register')}
                </button>
            </p>
    </form>
{/if}  

                      </ul>
                  </div>
              </div>
            </div>
          </section>

    </section>





<!-- <div class="page-header">
    <h1>{Localisation::getTranslation('register_register_on')} {Settings::get('site.name')}</h1>
</div> -->

{include file="handle-flash-messages.tpl"}
{if isset($error)}
    <div class="alert alert-error">
        <strong>{Localisation::getTranslation('common_error')}:</strong> {$error}
    </div>
{/if}

{if isset($warning)}
    <div class="alert">
        <strong>{Localisation::getTranslation("common_warning")}:</strong> {$warning}
    </div>
{/if}

    


{include file="footer.tpl"}

<?php
/**
* Copyright 2011 Facebook, Inc.
*
* Licensed under the Apache License, Version 2.0 (the "License"); you may
* not use this file except in compliance with the License. You may obtain
* a copy of the License at
*
* http://www.apache.org/licenses/LICENSE-2.0
*
* Unless required by applicable law or agreed to in writing, software
* distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
* WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
* License for the specific language governing permissions and limitations
* under the License.
*/

require 'fb/src/facebook.php';
$fbPage = "147906525337534";

// Create our Application instance (replace this with your appId and secret).
$facebook = new Facebook(array(
  'appId'   => Options::get('fbKey'),
  'secret'  => Options::get('fbSecret'),
  'cookie'  => true,
  'scope'   => 'publish_stream, manage_pages, user_photos, photo_upload'
));

// Get User ID
$fbApiUsername = $facebook->getUser();

// The authentication was successful, continue on
if ($fbApiUsername) {

  try {
    $fbApiUsername_profile = $facebook->api('/'.$fbPage);
  }

  catch (FacebookApiException $e)
  {
    error_log($e);
    $fbApiUsername = null;
  }

  #DEBUG
  if (isset($_REQUEST['debug']))
  {
    $logoutUrl = $facebook->getLogoutUrl();
    echo '<div class="container">
       <a class="btn btn-danger" href="'. $logoutUrl .'">FB Logout</a>
      </div>';
  }
}

# Could not get the user's details from the cookie, display the login urls
else
{
  $loginUrl = $facebook->getLoginUrl();
  file_get_contents($loginUrl);

  #DEBUG
  if (isset($_REQUEST['debug']))
  {
    echo '<div class="container">
            <a class="btn btn-success" href="'. $loginUrl . '">Force FB Login</a>
          </div>';
  }
}

#DEBUG
if (isset($_REQUEST['debug'])) echo "<br />SESSION INFO:" . print_r($_SESSION);

// USER TOKEN
// Get the token from the PHP headers and check if it's reflecting locally or not
if (isset($_SESSION['fb_' . Options::get('fbKey') . '_access_token']))
{
  // Compare the access token to that stored in the DB
  $access_token = $_SESSION['fb_' . Options::get('fbKey') . '_access_token'];
  $last_obtained = Options::get('fbUserToken');

  #DEBUG
  if (isset($_REQUEST['debug']))  echo "<br /><b>OLD User Token:</b> " . $last_obtained . "<br />";
  if (isset($_REQUEST['debug']))  echo "<br /><b>NEW User Token:</b> " . $access_token . "<br />";

  if ($access_token !== $last_obtained)
  {
    Options::set('fbUserToken', $access_token);
  }
}

// NOW LETS GET THE PAGE TOKEN
$faceBookPageToken = Options::get('fbPageToken');

# Get the account access token
$getPagesUrl = "https://graph.facebook.com/me/accounts?access_token=" . Options::get('fbUserToken');
$getPages = file_get_contents($getPagesUrl);
$getPages = json_decode($getPages);

# Loop through the pages and get the access token
foreach($getPages->data as $page)
{
  if ($page->id == $fbPage)
  {
    $access_token = $page->access_token;
    $last_obtained = Options::get('fbPageToken');

    #DEBUG
    if (isset($_REQUEST['debug']))  echo "<br /><b>New Page Token:</b> " . $access_token . "<br />";
    if (isset($_REQUEST['debug']))  echo "<br /><b>Old Page Token:</b> " . $last_obtained . "<br />";

    if ($access_token !== $last_obtained)
    {
      Options::set('fbPageToken', $access_token);
    }
    break;
  }
}

// Enable the site
$facebook->setFileUploadSupport("http://" . $_SERVER['SERVER_NAME']);
//printr($facebook);
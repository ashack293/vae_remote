<?php

require_once(dirname(__FILE__) . "/../lib/users.php");

class UsersTest extends VaeUnitTestCase {
  
  function testVaeUsersCallbackForgot() {
    $this->assertNull($_SESSION['__v:logged_in']);
    $_REQUEST['name'] = "Freefall";
    $tag = $this->callbackTag('<v:users:forgot email_field="genre" required="name" path="/artists" />');
    _vae_users_callback_forgot($tag);
    $this->assertFlash("We have sent you an E-Mail");
    $this->assertMail();
    $this->assertRedirect("/page");
  }
  
  function testVaeUsersCallbackForgotRedirect() {
    $this->assertNull($_SESSION['__v:logged_in']);
    $_REQUEST['name'] = "Freefall";
    $tag = $this->callbackTag('<v:users:forgot email_field="genre" required="name" path="/artists" redirect="/sent" />');
    _vae_users_callback_forgot($tag);
    $this->assertFlash("We have sent you an E-Mail");
    $this->assertMail();
    $this->assertRedirect("/sent");
  }
  
  function testVaeUsersCallbackForgotNoMatch() {
    $this->assertNull($_SESSION['__v:logged_in']);
    $_REQUEST['name'] = "Free";
    $tag = $this->callbackTag('<v:users:forgot email_field="genre" required="name" path="/artists" />');
    _vae_users_callback_forgot($tag);
    $this->assertErrors("didn't match");
    $this->assertNoMail();
    $this->assertRedirect("/page");
  }
  
  function testVaeUsersCallbackForgotRetunAction() {
    global $_VAE;
    $_REQUEST['__v:users_forgot_code'] = "code123";
    $_VAE['file_cache']["users:forgot-".$_REQUEST['__v:users_forgot_code']] = array('path' => "/artists", 'id' => 13421);
    $this->assertNull($_SESSION['__v:logged_in']);
    $tag = $this->callbackTag('<v:users:forgot email_field="genre" required="name" path="/artists" />');
    _vae_users_callback_forgot($tag);
    $this->assertFlash("You have been logged in");
    $this->assertEqual($_SESSION['__v:logged_in'], array('path' => "/artists", 'id' => 13421));
    $this->assertNoMail();
    $this->assertRedirect("/page");
  }
  
  function testVaeUsersCallbackForgotRetunActionRedirect() {
    global $_VAE;
    $_REQUEST['__v:users_forgot_code'] = "code123";
    $_VAE['file_cache']["users:forgot-".$_REQUEST['__v:users_forgot_code']] = array('path' => "/artists", 'id' => 13421);
    $this->assertNull($_SESSION['__v:logged_in']);
    $tag = $this->callbackTag('<v:users:forgot email_field="genre" required="name" path="/artists" redirect="/loggedin" />');
    _vae_users_callback_forgot($tag);
    $this->assertFlash("You have been logged in");
    $this->assertEqual($_SESSION['__v:logged_in'], array('path' => "/artists", 'id' => 13421));
    $this->assertNoMail();
    $this->assertRedirect("/loggedin");
  }
  
  function testVaeUsersCallbackLogin() {
    $this->assertNull($_SESSION['__v:logged_in']);
    $_REQUEST['name'] = "Freefall";
    $_REQUEST['genre'] = "Rock";
    $tag = $this->callbackTag('<v:users:login required="name,genre" path="/artists" redirect="/loggedin" />');
    _vae_users_callback_login($tag);
    $this->assertEqual($_SESSION['__v:logged_in'], array('path' => "/artists", 'id' => 13421));
    $this->assertRedirect("/loggedin");
  }
  
  function testVaeUsersCallbackLoginBadLogin() {
    $this->assertNull($_SESSION['__v:logged_in']);
    $_REQUEST['name'] = "Freefall";
    $_REQUEST['genre'] = "Bad";
    $tag = $this->callbackTag('<v:users:login required="name,genre" path="/artists" redirect="/loggedin" />');
    _vae_users_callback_login($tag);
    $this->assertNull($_SESSION['__v:logged_in']);
    $this->assertErrors('Login information incorrect.');
    $this->assertRedirect("/page");
  }
  
  function testVaeUsersCallbackLoginBadLoginCustomError() {
    $this->assertNull($_SESSION['__v:logged_in']);
    $_REQUEST['name'] = "Freefall";
    $_REQUEST['genre'] = "Bad";
    $tag = $this->callbackTag('<v:users:login required="name,genre" path="/artists" invalid="MYCUSTOM" redirect="/loggedin" />');
    _vae_users_callback_login($tag);
    $this->assertNull($_SESSION['__v:logged_in']);
    $this->assertErrors('MYCUSTOM');
    $this->assertRedirect("/page");
  }
  
  function testVaeUsersCallbackLogout() {
    $_SESSION['__v:logged_in'] = 13421;
    $tag = $this->callbackTag('<v:users:logout redirect="/index" />');
    _vae_users_callback_logout($tag);
    $this->assertNull($_SESSION['__v:logged_in']);
    $this->assertRedirect("/index");
  }
  
  function testVaeUsersCallbackLogoutNoRediret() {
    $_SESSION['__v:logged_in'] = 13421;
    $tag = $this->callbackTag('<v:users:logout />');
    _vae_users_callback_logout($tag);
    $this->assertNull($_SESSION['__v:logged_in']);
    $this->assertRedirect("/page");
  }
  
  function testVaeUsersCallbackRegister() {
    $this->mockRest(55555);
    $this->assertNull($_SESSION['__v:logged_in']);
    $tag = $this->callbackTag('<v:users:register path="/artists" redirect="/loggedin" />');
    _vae_users_callback_register($tag);
    $this->assertEqual($_SESSION['__v:logged_in'], array('path' => "/artists", 'id' => 55555));
    $this->assertFlash("has been created");
    $this->assertRedirect("/loggedin");
  }
  
  function testVaeUsersCallbackRegisterRestError() {
    $this->mockRestError();
    $this->assertNull($_SESSION['__v:logged_in']);
    $tag = $this->callbackTag('<v:users:register path="/artists" redirect="/loggedin" />');
    _vae_users_callback_register($tag);
    $this->assertNull($_SESSION['__v:logged_in']);
    $this->assertRedirect("/page");
  }
  
  function testVaeUsersCurrentUser() {
    $this->assertNull(_vae_users_current_user());
    $_SESSION['__v:logged_in']['id'] = 13421;
    $ret = _vae_users_current_user();
    $this->assertEqual(_vae_fetch("name", $ret), "Freefall");
    $this->assertSessionDep('__v:logged_in');
  }
  
  function testVaeUsersFind() {
    $_REQUEST['name'] = "Freefall";
    $_REQUEST['genre'] = "Rock";
    $tag = array('attrs' => array('required' => 'name,genre', 'path' => '/artists'));
    $ret = _vae_users_find($tag);
    $this->assertEqual(_vae_fetch(13421)->data, $ret->data);
  }
  
  function testVaeUsersFindNotFound() {
    $_REQUEST['name'] = "Freefall";
    $_REQUEST['genre'] = "RockBad";
    $tag = array('attrs' => array('required' => 'name,genre', 'path' => '/artists'));
    $this->assertNull(_vae_users_find($tag));
  }
  
}

?>

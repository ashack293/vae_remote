<?php

require_once(dirname(__FILE__) . "/../lib/callback.php");

class CallbackTest extends VaeUnitTestCase {
  
  function testVaeCallbackCreateErrors() {
    $tag = $this->callbackTag('<v:create path="/artists"><v:text_field path="name" required="true" /></v:create>');
    _vae_render_create($tag['attrs'], $tag, null, $tag['callback'], new Context());
    _vae_callback_create($tag);
    $this->assertErrors("Name can't be blank");
    $this->assertRedirect("/page");
  }
  
  function testVaeCallbackCreate() {
    $_REQUEST['name'] = "Freefall";
    $tag = $this->callbackTag('<v:create path="/artists" redirect="/index"><v:text_field path="name" required="true" /></v:create>');
    _vae_render_create($tag['attrs'], $tag, null, $tag['callback'], new Context());
    _vae_callback_create($tag);
    $this->assertNoErrors();
    $this->assertRedirect("/index");
  }
  
  function testVaeCallbackCreateFormmail() {
    $_REQUEST['YourName'] = "Kevin";
    $_REQUEST['YourMessage'] = "Message";
    $_REQUEST['Irrelevant'] = "whether she has a boyfriend";
    $tag = $this->callbackTag('<v:create path="/artists" formmail="test@actionverb.com"><v:text_field name="YourName" /><v:text_field name="YourMessage" /></v:create>');
    _vae_render_create($tag['attrs'], $tag, null, $tag['callback'], new Context());
    _vae_callback_create($tag);
    $this->assertEqual($_SESSION['__v:formmail']['recent'], array('YourName' => "Kevin", 'YourMessage' => "Message"));
    $this->assertMail();
    $this->assertNoErrors();
    $this->assertRedirect("/page");
  }
  
  function testVaeCallbackCreateRestError() {
    $_REQUEST['name'] = "Freefall";
    $this->mockRestError();
    $tag = $this->callbackTag('<v:create path="/artists" redirect="/index"><v:text_field path="name" required="true" /></v:create>');
    _vae_render_create($tag['attrs'], $tag, null, $tag['callback'], new Context());
    _vae_callback_create($tag);
    $this->assertRestError();
    $this->assertRedirect("/page");
  }
  
  function testVaeCallbackFile() {
    global $_VAE;
    $tag = $this->callbackTag('<v:file path="13421/pdf" />');
    $tag['callback'] = array('filename' => 'NalaCat', 'src' => 'sample-nala.jpg');
    $this->assertEqual(_vae_callback_file($tag), "__STREAM__");
    $this->assertEqual($_VAE['stream'], $_VAE['config']['data_path'] . "sample-nala.jpg");
  }
  
  function testVaeCallbackFormmailNoData() {
    $tag = $this->callbackTag('<v:formmail to="test@actionverb.com"><v:text_field name="YourName" /><v:text_field name="YourMessage" /></v:formmail>');
    _vae_callback_formmail($tag);
    $this->assertNoMail();
    $this->assertRedirect("/page");
  }
  
  function testVaeCallbackFormmailAllData() {
    $_REQUEST['YourName'] = "Kevin";
    $_REQUEST['YourMessage'] = "Message";
    $_REQUEST['Irrelevant'] = "whether she has a boyfriend";
    $tag = $this->callbackTag('<v:formmail to="test@actionverb.com"><v:text_field name="YourName" /><v:text_field name="YourMessage" /></v:formmail>');
    _vae_callback_formmail($tag);
    $this->assertEqual($_SESSION['__v:formmail']['recent'], array('YourName' => "Kevin", 'YourMessage' => "Message"));
    $this->assertMail();
    $this->assertRedirect("/page");
  }
  
  function testVaeCallbackFormmailErrors() {
    $tag = $this->callbackTag('<v:formmail to="test@actionverb.com" redirect="/finished"><v:text_field name="YourName" required="name" /><v:text_field name="YourMessage" /></v:formmail>');
    _vae_callback_formmail($tag);
    $this->assertNoMail();
    $this->assertErrors("YourName must contain a first and last name");
    $this->assertRedirect("/page");
  }
  
  function testVaeCallbackFormmailCustomRedirect() {
    $_REQUEST['YourMessage'] = "Message";
    $tag = $this->callbackTag('<v:formmail to="test@actionverb.com" redirect="/different_page"><v:text_field name="YourMessage" /></v:formmail>');
    _vae_callback_formmail($tag);
    $this->assertMail();
    $this->assertRedirect("/different_page");
  }
  
  function testVaeCallbackNewsletterEmptyEmail() {
    $tag = $this->callbackTag('<v:newsletter code="abcdef123"></v:newsletter>');
    _vae_callback_newsletter($tag);
    $this->assertNoRest();
    $this->assertErrors("You did not enter an E-Mail address.");
    $this->assertRedirect("/page");
  }
  
  function testVaeCallbackNewsletterGenericError() {
    $_REQUEST['e_mail_address'] = "kevin@actionverb.com";
    $tag = $this->callbackTag('<v:newsletter code="abcdef123"></v:newsletter>');
    _vae_callback_newsletter($tag);
    $this->assertRest();
    $this->assertErrors("There was an error in creating the subscription.");
    $this->assertRedirect("/page");
  }
  
  function testVaeCallbackNewsletterUserAlreadySubscribed() {
    $_REQUEST['e_mail_address'] = "kevin@actionverb.com";
    $this->mockRest("That E-Mail Address already on this list!");
    $tag = $this->callbackTag('<v:newsletter code="abcdef123"></v:newsletter>');
    _vae_callback_newsletter($tag);
    $this->assertRest();
    $this->assertErrors("You are already subscribed!");
    $this->assertRedirect("/page");
  }
  
  function testVaeCallbackNewsletterUserSuccess() {
    $_REQUEST['e_mail_address'] = "kevin@actionverb.com";
    $this->mockRest("Welcome to the list");
    $tag = $this->callbackTag('<v:newsletter code="abcdef123"></v:newsletter>');
    _vae_callback_newsletter($tag);
    $this->assertRest();
    $this->assertNoErrors();
    $this->assertRedirect("/page");
  }
  
  function testVaeCallbackNewsletterCustomRedirect() {
    $_REQUEST['e_mail_address'] = "kevin@actionverb.com";
    $this->mockRest("Welcome to the list");
    $tag = $this->callbackTag('<v:newsletter redirect="/index" code="abcdef123"></v:newsletter>');
    _vae_callback_newsletter($tag);
    $this->assertRest();
    $this->assertNoErrors();
    $this->assertRedirect("/index");
  }
  
  function testVaeCallbackUpdateMissingRequired() {
    $tag = $this->callbackTag('<v:update path="/13421"><v:text_field path="name" required="true" /></v:update>');
    _vae_callback_update($tag);
    $this->assertErrors("Name can't be blank");
    $this->assertRedirect("/page");
  }
  
  function testVaeCallbackUpdate() {
    $_REQUEST['name'] = "Freefall2";
    $tag = $this->callbackTag('<v:update redirect="/index" path="/13421"><v:text_field path="name" required="true" /></v:update>');
    _vae_callback_update($tag);
    $this->assertFlash("Saved.");
    $this->assertRedirect("/index");
  }
  
  function testVaeCallbackUpdateRestError() {
    $_REQUEST['name'] = "Freefall2";
    $this->mockRestError();
    $tag = $this->callbackTag('<v:update redirect="/index" path="/13421"><v:text_field path="name" required="true" /></v:update>');
    _vae_callback_update($tag);
    $this->assertRestError();
    $this->assertRedirect("/page");
  }
  
  function testVaeCallbackZip() {
    $out = _vae_callback_zip(array('callback' => array('filename' => 'myzip.zip', 'files' => array(array('src' => 'sample-nala.jpg')))));
    $this->assertNotNull($out);
  }
  
}

?>

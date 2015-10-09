<?php

class GeneralTest extends VaeUnitTestCase {

  function testVaeAbsoluteDataUrl() {
    global $_VAE;
    $this->assertEqual(_vae_absolute_data_url(), "http://btg.vaesite.net/__data/");
    $this->assertEqual(_vae_absolute_data_url("cow"), "http://btg.vaesite.net/__data/cow");
    $this->assertEqual(_vae_absolute_data_url("cow/"), "http://btg.vaesite.net/__data/cow/");
    unset($_VAE['config']['data_url']);
    $_REQUEST['__vae_ssl_router'] = "1";
    $this->assertEqual(_vae_absolute_data_url(), "https://btg.vaesite.com");
  }

  function testVaeAkismet() {
    $this->assertTrue(_vae_akismet(array('akismet' => "12345")));
  }

  function testVaeAppendJs() {
    $this->assertEqual(_vae_append_js("alert('kevin')    ", "\r$('#test').slideDown();\n\n"), "alert('kevin'); $('#test').slideDown();");
  }

  function testVaeAssetHtml() {
    $this->assertEqual("<script type=\"text/javascript\" src=\"src.js\"></script>\n", _vae_asset_html("js", "src.js"));
    $this->assertEqual("<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"src.css\" />\n", _vae_asset_html("screen", "src.css"));
    $this->assertEqual("<link rel=\"stylesheet\" type=\"text/css\" media=\"print\" href=\"src.css\" />\n", _vae_asset_html("print", "src.css"));
  }

  function testVaeAttrs() {
    $this->assertEqual(_vae_attrs(array("href" => "http://google.com/", "bad" => "kevin"), "a"), ' href="http://google.com/"');
    $this->assertEqual(_vae_attrs(array("href" => "http://google.com/", "ajax" => "kevin"), "a"), ' href="http://google.com/"');
    $this->assertEqual(_vae_attrs(array( "ajax" => "kevin"), "a"), '');
  }

  function testVaeCallbackRedirect() {
    $this->assertNoRedirect();
    $url = "http://google.com/";
    _vae_callback_redirect($url);
    $this->assertRedirect($url);
  }

  function testVaeClearLogin() {
    $_SESSION['__v:logged_in'] = true;
    _vae_clear_login();
    $this->assertFalse($_SESSION['__v:logged_in']);
  }

  function testVaeCombineArrayKeys() {
    $out = _vae_combine_array_keys(array("name" => "Kevin", "address" => "1375 Broadway", "phone" => "2563376464"), array("name", "address"));
    $this->assertEqual($out, "Kevin, 1375 Broadway");
  }

  function testVaeConfigurePhp() {
    global $_VAE;
    _vae_configure_php();
    $this->assertFalse($_VAE['skip_pdf']);
    $this->assertFalse($_VAE['from_proxy']);
  }

  function testVaeConfigurePhpSkipPdf() {
    global $_VAE;
    $_REQUEST['__skip_pdf'] = true;
    _vae_configure_php();
    $this->assertTrue($_VAE['skip_pdf']);
  }

  function testVaeConfigurePhpProxy() {
    global $_VAE;
    $_REQUEST['__proxy'] = "testproxy";
    $_SESSION['a'] = 'b';
    $request_old = $_REQUEST;
    $post_old = $_POST;
    $session_old = $_SESSION;
    _vae_configure_php();
    $this->assertEqual($_POST, $post_old);
    $this->assertEqual($_REQUEST, $request_old);
    $this->assertEqual($_SESSION, $session_old);
    $this->assertTrue($_VAE['from_proxy']);
  }

  function testVaeConfigurePhpProxyRequestData() {
    global $_VAE;
    $_REQUEST['__proxy'] = "testproxy";
    $_REQUEST['__get_request_data'] = true;
    $test_post = array("a" => "b");
    $test_request = array("c" => "d");
    $_SESSION['e'] = 'f';
    $test_session = $_SESSION;
    _vae_long_term_cache_set("_proxy_post_" . $_REQUEST['__proxy'], serialize($test_post), 1);
    _vae_long_term_cache_set("_proxy_request_" . $_REQUEST['__proxy'], serialize($test_request), 1);
    _vae_configure_php();
    $this->assertEqual($_POST, $test_post);
    $this->assertEqual($_REQUEST, $test_request);
    $this->assertEqual($_SESSION, $test_session);
  }

  function testVaeConfigurePhpRouter() {
    global $_VAE;
    $_REQUEST['__router'] = "123";
    _vae_configure_php();
  }

  function testVaeDebug() {
    global $_VAE;
    _vae_debug("test_message");
    $this->assertEqual($_VAE['debug'], "test_message\n");
    _vae_debug("test2");
    $this->assertEqual($_VAE['debug'], "test_message\ntest2\n");
  }

  function testVaeDependencyAdd() {
    global $_VAE;
    $name = "test_dep";
    $value = md5($name);
    _vae_dependency_add($name, $value);
    $this->assertDep($name, $value);
  }

  function testVaeDie() {
    _vae_die();
    $this->pass();
  }

  function testVaeEle() {
    $this->assertEqual("5", _vae_ele(array("atlanta" => "5", "boston" => "10"), "atlanta"));
    $this->assertEqual("5", _vae_ele(array("albequerque" => "15", "atlanta" => "5", "boston" => "10"), "atlanta"));
    $this->assertEqual(4, _vae_ele(array(1, 2, array(3, 4, 5)), 2, 1));
  }

  function testVaeEscapeForJs() {
    $this->assertEqual("\\'Kevin\\'", _vae_escape_for_js('"Kevin"'));
    $this->assertEqual("Kevin", _vae_escape_for_js('Kevin'));
    $this->assertEqual("'Kevin'", _vae_escape_for_js("'Kevin'"));
    $this->assertEqual("'Kevin&<'", _vae_escape_for_js("'Kevin&<'"));
  }

  function testVaeError() {
    $this->expectException();
    try {
      _vae_error("message", "debugging", "filename");
      $this->fail("expected VaeException");
    } catch (VaeException $e) {
      $this->assertEqual($e->getMessage(), "message");
      $this->assertEqual($e->filename, "filename");
      $this->assertEqual($e->debugging_info, "debugging");
      $this->assertNotNull($e->backtrace);
    }
  }

  function testVaeExceptionHandler() {
    _vae_exception_handler(new VaeException());
  }

  function testVaeFetchMultiple() {
    $ctxt = _vae_fetch("13421");
    $this->assertEqual(_vae_fetch_multiple("name,genre", $ctxt), "Freefall - Rock");
    $this->assertEqual(_vae_fetch_multiple("name,nothing,genre", $ctxt), "Freefall - Rock");
    $this->assertEqual(_vae_fetch_multiple("name,image", $ctxt), "Freefall - <img src=\"http://btg.vaesite.net/__data/Array\" />");
    $ctxt = _vae_fetch("14188");
    $this->assertEqual(_vae_fetch_multiple("title,body", $ctxt), "SEcond post - <br>\n   No matter how determined a woman is to keep the spark alive, other\n  things come into play (depression, work stress, motherhood, unseen\n  episodes of <i>Project Runway</i>). The good news: Help is on the way.\n  The better news: It's not coming from me (who has more questions than\n  answers this month). It's from Leah Millheiser, MD, who runs the Female\n  Sexual Medicine Program at Stanford University.<br> <br> Millheiser\n  says that unlike in men, where the issue of performance is mainly\n  related to blood flow to the penis, in women many factors—physical and\n  psychological—can affect sexual desire and enjoyment. And although we\n  gain confidence to ask for what we need as we age, our bodies change.\n  During perimenopause, libido can drop and orgasms can become less\n  intense or harder to achieve. Millheiser says you and your partner\n  might need to revise routines that worked in the past to learn what\n  works now.");
  }

  function testVaeFile() {
    $this->assertEqual(_vae_file("123-file", false, "file/123"), "");
  }

  function testVaeFindSource() {
    $this->assertFalse(_vae_find_source("bad"));
    $this->assertFalse(_vae_find_source("test1.html"));
    $this->assertEqual(_vae_find_source("test1.haml"), "test1.haml");
    $this->assertEqual(_vae_find_source("test1"), "test1.haml");
    $this->assertEqual(_vae_find_source("test1.php"), "test1.php");
    $this->assertEqual(_vae_find_source("test2"), "test2.html");
    $this->assertEqual(_vae_find_source("test8"), "test8.haml.php");
  }

  function testVaeFlash() {
    _vae_flash("test");
    $this->assertEqual($_SESSION['__v:flash_new']['messages'], array(array('msg' => "test", 'type' => 'msg', 'which' => '')));
  }

  function testVaeFlashChangeTypeAndWhich() {
    _vae_flash("test", "cow", "which");
    $this->assertEqual($_SESSION['__v:flash_new']['messages'], array(array('msg' => "test", 'type' => 'cow', 'which' => 'which')));
    _vae_flash("test");
    $this->assertEqual($_SESSION['__v:flash_new']['messages'], array(array('msg' => "test", 'type' => 'cow', 'which' => 'which')));
  }

  function testVaeFlashAreErrors() {
    _vae_flash("normal");
    $_SESSION['__v:flash'] = $_SESSION['__v:flash_new'];
    $this->assertFalse(_vae_flash_are_errors());
    _vae_flash("error", 'err');
    $_SESSION['__v:flash'] = $_SESSION['__v:flash_new'];
    $this->assertTrue(_vae_flash_are_errors());
  }

  function testVaeFlashErrors() {
    $this->assertFalse(_vae_flash_errors(array()));
    $this->assertTrue(_vae_flash_errors(array("cow")));
    $_SESSION['__v:flash'] = $_SESSION['__v:flash_new'];
    $this->assertTrue(_vae_flash_are_errors());
  }

  function testVaeFormPrepare() {
    $render_context = new Context();
    $tag = array();
    $this->assertEqual(array('_vae_form_prepared' => true), _vae_form_prepare($a, $tag, null, $render_context));
    $this->assertEqual(array('name' => 'gradelevel', '_vae_form_prepared' => true), _vae_form_prepare(array('name' => 'gradelevel'), $tag, null, $render_context));
    $this->assertEqual(array('name' => 'gradelevel', 'default' => 'cow', 'value' => 'cow', '_vae_form_prepared' => true), _vae_form_prepare(array('name' => 'gradelevel', 'default' => 'cow'), $tag, null, $render_context));
    $this->assertEqual(array('name' => 'gradelevel', 'value' => '', 'path' => 'gradelevel', 'id' => 'gradelevel', '_vae_form_prepared' => true), _vae_form_prepare(array('path' => 'gradelevel'), $tag, null, $render_context));
    $this->assertEqual(array('name' => 'gradelevel_name', 'value' => '', 'path' => 'gradelevel', 'id' => 'gradelevel_name', '_vae_form_prepared' => true), _vae_form_prepare(array('path' => 'gradelevel', 'name' => 'gradelevel_name'), $tag, null, $render_context));
    $this->assertEqual(array('name' => 'gradelevel', 'value' => '', 'path' => 'gradelevel', 'id' => 'gradelevel_id', '_vae_form_prepared' => true), _vae_form_prepare(array('path' => 'gradelevel', 'id' => 'gradelevel_id'), $tag, null, $render_context));
    $this->assertEqual(array('class' => 'myclass required', 'required' => 'true', 'name' => 'gradelevel', 'value' => '', 'path' => 'gradelevel', 'id' => 'gradelevel', '_vae_form_prepared' => true), _vae_form_prepare(array('class' => 'myclass', 'required' => 'true', 'path' => 'gradelevel'), $tag, null, $render_context));
    $this->assertEqual(array('class' => 'myclass required digits', 'required' => 'digits', 'name' => 'gradelevel', 'value' => '', 'path' => 'gradelevel', 'id' => 'gradelevel', '_vae_form_prepared' => true), _vae_form_prepare(array('class' => 'myclass', 'required' => 'digits', 'path' => 'gradelevel'), $tag, null, $render_context));
    $this->assertEqual(array('name' => 'gradelevel', 'value' => '', 'path' => 'gradelevel', 'id' => 'gradelevel', '_vae_form_prepared' => true), _vae_form_prepare(array('path' => 'gradelevel'), $tag, null, $render_context));
    $context = _vae_fetch(13421);
    $this->assertEqual(array('name' => 'gradelevel[13421]', 'value' => '', 'path' => 'gradelevel', 'id' => 'gradelevel_13421', '_vae_form_prepared' => true), _vae_form_prepare(array('path' => 'gradelevel'), $tag, $context, $render_context));
    $render_context->set_in_place('form_context', $context);
    $this->assertEqual(array('name' => 'gradelevel', 'value' => '', 'path' => 'gradelevel', 'id' => 'gradelevel', '_vae_form_prepared' => true), _vae_form_prepare(array('path' => 'gradelevel'), $tag, $context, $render_context));
    $_SESSION['__v:flash_new']['post']['gradelevel'] = "6";
    $this->assertEqual(array('name' => 'gradelevel', '_vae_form_prepared' => true), _vae_form_prepare(array('name' => 'gradelevel'), $tag, null, $render_context));
    _vae_flash_errors(array("test error"));
    $_SESSION['__v:flash'] = $_SESSION['__v:flash_new'];
    $this->assertTrue(_vae_flash_are_errors());
    $this->assertEqual(array('name' => 'gradelevel', 'value' => 6, '_vae_form_prepared' => true), _vae_form_prepare(array('name' => 'gradelevel'), $tag, null, $render_context));
    $this->assertEqual(array('name' => 'gradelevel', 'value' => 6, 'path' => 'gradelevel', 'id' => 'gradelevel', '_vae_form_prepared' => true), _vae_form_prepare(array('path' => 'gradelevel'), $tag, null, $render_context));
    $this->assertEqual(array('name' => 'confirm_gradelevel', 'value' => false, 'path' => 'confirm_gradelevel', 'id' => 'confirm_gradelevel', '_vae_form_prepared' => true), _vae_form_prepare(array('path' => 'confirm_gradelevel'), $tag, null, $render_context));
    $this->assertEqual(array('name' => 'name', 'value' => "Freefall", 'path' => 'name', 'id' => 'name', '_vae_form_prepared' => true), _vae_form_prepare(array('path' => 'name'), $tag, $context, $render_context));
    $render_context->set_in_place("form_create_mode", true);
    $this->assertEqual(array('name' => 'name', 'path' => 'name', 'id' => 'name', '_vae_form_prepared' => true), _vae_form_prepare(array('path' => 'name'), $tag, $context, $render_context));
  }

  function testVaeFormatForRss() {
    $this->assertEqual(_vae_format_for_rss("Kevin\rBombino\n<>"), "Kevin Bombino &lt;&gt;");
  }

  function testVaeGdHandle() {
    $this->assertIsA(_vae_gd_handle("sample-nala.jpg"), "Resource");
  }

  function testVaeGetElse() {
    $context = _vae_fetch(13421);
    $render_context = new Context();
    $tag = array('tags' => array(array('type' => 'if')));
    $this->assertEqual(_vae_get_else($tag, $context, $render_context, "ElseNotFound"), "ElseNotFound");
    $tag = array('tags' => array(array('type' => 'if'), array('type' => 'else', 'tags' => array(array('type' => 'text', 'attrs' => array('path' => 'name'))))));
    $this->assertEqual(_vae_get_else($tag, $context, $render_context, "ElseNotFound"), "Freefall");
  }

  function testVaeGlobalId() {
    global $_VAE;
    $this->assertPattern('/_/', _vae_global_id());
    $this->assertEqual(_vae_global_id(7), $_VAE['globalid'] . "_7");
  }

  function testVaeH() {
    $this->assertEqual(_vae_h("<hr />"), "&lt;hr /&gt;");
    $this->assertEqual(_vae_h("<hr />&lt;"), "&lt;hr /&gt;&amp;lt;");
  }
  function testVaeHandleOb() {
    global $_VAE;
    $_SESSION['__v:flash'] = array('stuff' => 'things');
    _vae_handleob("<html>test</html>");
    $this->assertNoRedirect();
    $this->assertNull($_SESSION['__v:flash']);
    $_VAE['run_hooks'] = array("content:updated" => 13421);
    $_VAE['session_cookies'] = array('cook1' => 'monster', 'cook2' => 'monster2');
    $this->assertNull($_SESSION['__v:flash_new']);
    $this->assertNull($_SESSION['cook1']);
    _vae_handleob("<html>test</html>");
    $this->assertNoRedirect();
    $this->assertNull($_SESSION['__v:flash_new']);
    $this->assertEqual($_SESSION['cook1'], "monster");
    $this->assertEqual($_SESSION['cook2'], "monster2");
  }

  function testVaeHandleObDebug() {
    $_REQUEST['__debug'] = true;
    _vae_debug("test");
    $this->expectException();
    $this->assertPattern('/Debugging Traces Available/', _vae_handleob("<html>test</html>"));
  }

  function testVaeHandleObFinal() {
    global $_VAE;
    $_VAE['final'] = "finalCountdown!";
    $this->assertEqual("finalCountdown!", _vae_handleob("<html>test</html>"));
  }

  function testVaeHandleObFlash() {
    global $_VAE;
    _vae_flash("test_message");
    $this->assertNotNull($_SESSION['__v:flash_new']);
    _vae_handleob("<html>test</html>");
    $this->assertRedirect("/page");
    $this->assertNull($_SESSION['__v:flash_new']);
    $this->assertEqual($_SESSION['__v:flash'], array('redirected' => 1, 'messages' => array(array('msg' => "test_message", "which" => "", "type" => "msg"))));
  }

  function testVaeHandleObFlashXhrErr() {
    global $_VAE;
    _vae_flash("TESTERR", 'err');
    $_REQUEST['__xhr'] = "1";
    $this->assertTrue(_vae_is_xhr());
    $this->assertNoRedirect();
    $this->assertEqual(_vae_handleob("<html>test</html>"), "__err=TESTERR");
  }
  function testVaeHandleObMergeSessionData() {
    $_SESSION['moo'] = "SPOTS";
    $this->assertEqual("<html>SPOTS</html>", _vae_handleob("<html><v:session_dump key='moo' /></html>"));
  }

  function testVaeHandleObRedirect() {
    _vae_render_redirect("/cow");
    $this->assertEqual("Redirecting to /cow", _vae_handleob("<html>test</html>"));
    $this->assertRedirect("/cow");
    $this->assertNull($_SESSION['__v:flash_new']);
    $this->assertEqual($_SESSION['__v:flash'], array('redirected' => 1));
  }

  function testVaeHandleObRedirectDebug() {
    _vae_render_redirect("/redir");
    $_REQUEST['__debug'] = "1";
    $this->assertEqual("Redirecting to /redir?__debug=1", _vae_handleob("<html>test</html>"));
  }

  function testVaeHandleObRedirectForwardOldFlashMessages() {
    _vae_render_redirect("/cow");
    $_SESSION['__v:flash']['messages'] = array(array('msg' => "test_message", "which" => "", "type" => "msg"));
    $_SESSION['__v:flash']['redirected'] = 1;
    $this->assertEqual("Redirecting to /cow", _vae_handleob("<html>test</html>"));
    $this->assertRedirect("/cow");
    $this->assertNull($_SESSION['__v:flash_new']);
    $this->assertEqual($_SESSION['__v:flash'], array('redirected' => 1, 'messages' => array(array('msg' => "test_message", "which" => "", "type" => "msg"))));
  }

  function testVaeHandleObRedirectPost() {
    $_POST['kevin'] = "test";
    _vae_render_redirect("/cow");
    _vae_handleob("<html>test</html>");
    $this->assertRedirect("/cow");
    $this->assertNull($_SESSION['__v:flash_new']);
    $this->assertEqual($_SESSION['__v:flash'], array('redirected' => 1, 'post' => $_POST));
  }

  function testVaeHandleObRedirectPostTrashed() {
    $_POST['kevin'] = "test";
    _vae_render_redirect("/cow", true);
    _vae_handleob("<html>test</html>");
    $this->assertRedirect("/cow");
    $this->assertNull($_SESSION['__v:flash_new']);
    $this->assertEqual($_SESSION['__v:flash'], array('redirected' => 1));
  }

  function testVaeHandleObRedirectXhr() {
    _vae_render_redirect("/xmlredir");
    $_REQUEST['__xhr'] = "1";
    $this->assertTrue(_vae_is_xhr());
    $this->assertEqual("Redirecting to /xmlredir?__xhr=1", _vae_handleob("<html>test</html>"));
  }

  function testVaeHandleObRedirectXssProtection() {
    _vae_render_redirect("/cow?<script>");
    $this->assertEqual("Redirecting to /", _vae_handleob("<html>test</html>"));
  }

  function testVaeHandleObStream() {
    global $_VAE;
    $_VAE['stream'] = dirname(__FILE__) . "/data/testfile.txt";
    $this->assertEqual("value123", _vae_handleob("__STREAM__"));
  }

  function testVaeHandleObTicks() {
    $_REQUEST['__time'] = true;
    vae_tick("test");
    vae_tick("test");
    $this->assertPattern('/<h2>Vae Timer<\/h2>/', _vae_handleob("<html>test</html>"));
  }

  function testVaeHandleObSslRedirections() {
    global $_VAE;
    $this->assertFalse($_VAE['ssl_required']);
    $_SERVER['HTTPS'] = true;
    $_SESSION['__v:pre_ssl_host'] = "btgrecords.com";
    _vae_handleob("<html>test</html>");
    $this->assertRedirect("http://btgrecords.com/page");
  }

  function testVaeHandleObSslRedirectionsSslTrue() {
    global $_VAE;
    $_VAE['ssl_required'] = true;
    $_SERVER['HTTPS'] = true;
    _vae_handleob("<html>test</html>");
    $this->assertNoRedirect();
  }

  function testVaeHandleObSslRedirectionsRouter() {
    global $_VAE;
    $this->assertFalse($_VAE['ssl_required']);
    $_REQUEST['__vae_ssl_router'] = true;
    $_SESSION['__v:pre_ssl_host'] = "btgrecords.com";
    _vae_handleob("<html>test</html>");
    $this->assertRedirect("http://btgrecords.com/page");
  }

  function testVaeHandleObSslRedirectionsLocal() {
    global $_VAE;
    $this->assertFalse($_VAE['ssl_required']);
    $_REQUEST['__vae_local'] = true;
    $_SERVER['HTTPS'] = true;
    _vae_handleob("<html>test</html>");
    $this->assertNoRedirect();
  }

  function testVaeHtml2Rgb() {
    $this->assertEqual(array(255, 255, 255), _vae_html2rgb("#ffffff"));
    $this->assertEqual(array(255, 0, 0), _vae_html2rgb("#ff0000"));
    $this->assertEqual(array(255, 0, 0), _vae_html2rgb("#f00"));
    $this->assertEqual(array(255, 255, 255), _vae_html2rgb("ffffff"));
    $this->assertEqual(array(0, 0, 0), _vae_html2rgb(""));
  }

  function testVaeHtmlArea() {
    $this->assertEqual("All Html has \nbeen\nstrippedout", _vae_htmlarea("<html>All <p><strong>Html</strong> has </p>been<br />stripped<script type='text/javascript'>out</script></html>", array('nohtml' => true)));
    $this->assertEqual(_vae_htmlarea("www.google.com", array()), '<a href="http://google.com" target="_blank">google.com</a>');
    $this->assertEqual(_vae_htmlarea("http://www.google.com", array()), '<a href="http://www.google.com" target="_blank">http://www.google.com</a>');
    $this->assertEqual(_vae_htmlarea("<a href=\"http://www.google.com\">http://www.google.com</a>", array('links_to_new_window' => 'all')), '<a target="_blank" href="http://www.google.com">http://www.google.com</a>');
    $this->assertEqual(_vae_htmlarea("<a href=\"http://www.google.com\">http://www.google.com</a>", array('links_to_new_window' => 'external')), '<a target="_blank" href="http://www.google.com">http://www.google.com</a>');
    $this->assertEqual(_vae_htmlarea("<a href=\"/cow.html\">Cow</a>", array('links_to_new_window' => 'all')), '<a target="_blank" href="/cow.html">Cow</a>');
    $this->assertEqual(_vae_htmlarea("<a href=\"/cow.html\">Cow</a>", array('links_to_new_window' => 'external')), '<a href="/cow.html">Cow</a>');
    $this->assertEqual("Section dividers removed", _vae_htmlarea("Section dividers<hr /> removed", array()));
    $this->assertEqual("Section 3Section 4", _vae_htmlarea("Section 1<hr />Section 2<hr />Section 3<hr />Section 4", array('section' => '2+')));
    $this->assertEqual("Section 2", _vae_htmlarea("Section 1<hr />Section 2<hr />Section 3<hr />Section 4", array('section' => '1')));
    $this->assertPattern('/audioplayer/', _vae_htmlarea("<img src='/VAE_HOSTED_AUDIO/123' />", array(), false));
    $this->assertPattern('/foo=man/', _vae_htmlarea("<img src='/VAE_HOSTED_AUDIO/123' />", array('audio_player_vars' => "foo=man&bar=woman"), false));
    $this->assertEqual("", _vae_htmlarea("<img src='/VAE_HOSTED_AUDIO/123' />", array(), true));
    $this->assertPattern('/\/player/', _vae_htmlarea("<img src='/VAE_HOSTED_VIDEO/123' />", array(), false));
    $this->assertEqual("", _vae_htmlarea("<img src='/VAE_HOSTED_VIDEO/123' />", array(), true));
    $this->assertEqual("<img src=\"http://btg.vaesite.net/__data/Array\" />", _vae_htmlarea("<img src='/VAE_HOSTED_IMAGE/123' />", array(), false));
  }

  function testVaeHumanize() {
    $this->assertEqual("Kevin Bombino", _vae_humanize("kevin_bombino"));
  }

  function testVaeInOb() {
    $this->assertTrue(_vae_in_ob());
  }

  function testVaeInjectAssets() {
    $base_html = "<html><head></head><body></body></html>";
    $this->assertEqual($base_html, _vae_inject_assets($base_html));
    _vae_needs_jquery();
    $out = _vae_inject_assets($base_html);
    $this->assertPattern('/<script(.*)jquery.js(.*)<\/script>/', $out);
    $this->assertEqual('<html><head><script type="text/javascript" src="/__assets/jquery.js"></script></head><body></body></html>', $out);
    $base_html = "<html><body></body></html>";
    $out = _vae_inject_assets($base_html);
    $this->assertPattern('/<script(.*)jquery.js(.*)<\/script>/', $out);
    $this->assertEqual('<script type="text/javascript" src="/__assets/jquery.js"></script><html><body></body></html>', $out);
    $preexisting_jquery_html = "<html><head><script type='text/javascript' src='/mylib/jquery.min.js'></script></head><body></body></html>";
    $this->assertEqual($preexisting_jquery_html, _vae_inject_assets($preexisting_jquery_html));
    _vae_needs_jquery('ui');
    $out = _vae_inject_assets($base_html);
    $this->assertPattern('/<script(.*)jquery.js(.*)<\/script>/', $out);
    $this->assertPattern('/<script(.*)jquery.ui.js(.*)<\/script>/', $out);
  }

  function testVaeInjectAssetsCssAssets() {
    global $_VAE;
    $base_html = "<html><head><_VAE_ASSET_screen1></head><body></body></html>";
    $_VAE['assets'] = array('screen' => array('assets/testscreen1.css'));
    $_VAE['asset_inject_points'] = array('screen' => 1);
    $_VAE['asset_types'] = array('screen' => 'screen');
    $out = _vae_inject_assets($base_html);
    $this->assertTrue(preg_match('/<link rel="stylesheet" type="text\/css" media="screen" href="http:\/\/btg.vaesite.net\/__data\/([0-9a-f]*).css" \/>/', $out, $matches));
    $this->assertPattern('/body {\nbackground:url\(\/__cache\/a([0-9]*)\/assets\/images\/cow.jpg\)\n}/', _vae_read_file($matches[1] . ".css"));
    $this->assertDep("assets/testscreen1.css", "bb146644dd28815d5b78fc2bd1262472");
  }

  function testVaeInjectAssetsJsAssets() {
    global $_VAE;
    $this->mockRest("\nalert('test');alert('cow');");
    $base_html = "<html><head><_VAE_ASSET_js1></head><body></body></html>";
    $_VAE['assets'] = array('js' => array('assets/test1.js'));
    $_VAE['asset_inject_points'] = array('js' => 1);
    $_VAE['asset_types'] = array('js' => 'js');
    $out = _vae_inject_assets($base_html);
    $this->assertTrue(preg_match('/<html><head><script type="text\/javascript" src="http:\/\/btg.vaesite.net\/__data\/([0-9a-f]*).js"><\/script>/', $out, $matches));
    $this->assertEqual(_vae_read_file($matches[1] . ".js"), "\nalert('test');alert('cow');");
    $this->assertDep("assets/test1.js", "8c840dbd9d7d72890b0527243e92512a");
  }

  function testVaeInjectAssetsSassAssets() {
    global $_VAE;
    $base_html = "<html><head><_VAE_ASSET_screen1></head><body></body></html>";
    $_VAE['assets'] = array('screen' => array('assets/test1.sass'));
    $_VAE['asset_inject_points'] = array('screen' => 1);
    $_VAE['asset_types'] = array('screen' => 'screen');
    $out = _vae_inject_assets($base_html);
    $this->assertTrue(preg_match('/<link rel="stylesheet" type="text\/css" media="screen" href="http:\/\/btg.vaesite.net\/__data\/([0-9a-f]*).css" \/>/', $out, $matches));
    $this->assertPattern('/background:url\(\/__cache\/a([0-9]*)\/assets\/images\/cow.jpg\)\n}/', _vae_read_file($matches[1] . ".css"));
    $this->assertDep("assets/test1.sass", "01e55d4c56cd71e8f77cd8c8d9133268");
  }

  function testVaeInjectAssetsJavascripts() {
    $base_html = "<html><head></head><body></body></html>";
    $this->assertEqual($base_html, _vae_inject_assets($base_html));
    _vae_needs_javascript("prototype");
    $out = _vae_inject_assets($base_html);
    $this->assertPattern('/<script(.*)prototype.js(.*)<\/script>/', $out);
  }

  function testVaeInjectAssetsOnDomReady() {
    _vae_on_dom_ready("alert('cow');");
    $out = _vae_inject_assets("<html><head></head></html>");
    $this->assertEqual("<html><head><script type=\"text/javascript\" src=\"/__assets/jquery.js\"></script><script type='text/javascript'>jQuery(function() { alert('cow'); });</script></head></html>", $out);
  }

  function testVaeInjectAssetsOnDomReadyXhr() {
    $_REQUEST['__xhr'] = true;
    _vae_on_dom_ready("alert('cow');");
    $out = _vae_inject_assets("qq");
    $this->assertEqual("qq<script type='text/javascript'>alert('cow');</script>", $out);
  }

  function testVaeInjectAssetsCssCallback() {
    // Tested as part of _vae_inject_assets()
  }

  function testVaeInterpretVaeml() {
    global $_VAE;
    $_REQUEST['id'] = 13421;
    $this->assertEqual("<html>", _vae_interpret_vaeml("<html>"));
    $this->assertEqual("Freefall", _vae_interpret_vaeml("<v:text path='name' />"));
    $this->assertEqual("Freefall", _vae_interpret_vaeml("<v:text path='name' />"));
    _vae_needs_jquery('ui');
    $this->assertPattern('/<script(.*)jquery.js(.*)<\/script>(.*)Freefall/', _vae_interpret_vaeml("<html><head></head><v:text path='name' />"));
  }

  function testVaeInterpretVaemlExtraTests() {
    $this->assertEqual(_vae_interpret_vaeml("HEY HEY"), "HEY HEY");
    $this->assertEqual(_vae_interpret_vaeml('<v:text path="artists/albums/songs[duration=\'5:07\']/name" />'), "One More Time");
    $this->assertEqual(_vae_interpret_vaeml('<v:collection path="artists">a</v:collection>'), "aaaa");
    $this->assertEqual(_vae_interpret_vaeml('<v:collection path="13423/songs">a</v:collection>'), "aaa");
  }

  function testVaeIsXhr() {
    $this->assertFalse(_vae_is_xhr());
    $_REQUEST['__xhr'] = "1";
    $this->assertTrue(_vae_is_xhr());
  }

  function testVaeIsXhrServer() {
    $this->assertFalse(_vae_is_xhr());
    $_SERVER['HTTP_X_REQUESTED_WITH'] = "XML";
    $this->assertTrue(_vae_is_xhr());
  }

  function testVaeJsEsc() {
    $this->assertEqual("alert(&#39;kevin&#39;);", _vae_jsesc("  alert('kevin');  \n  "));
    $this->assertEqual("alert(\\\"kevin\\n\\\");", _vae_jsesc("  alert(\"kevin\n\");  \n  "));
  }

  function helperVaeLocalAuthorize($status = "GOOD") {
    $_REQUEST['__vae_local'] = "cow";
    $memcache_base_key = "__vae_local" . $_SERVER['DOCUMENT_ROOT'] . $_REQUEST['__vae_local'];
    _vae_long_term_cache_set($memcache_base_key . "auth", $status);
    _vae_long_term_cache_set($memcache_base_key . "f/__vae.php", '<?php $_VAE["VaeLocalPhpTest"] = 42; ?>');
    return $memcache_base_key . "f";
  }

  function testVaeLocal() {
    global $_VAE;
    $_SERVER['SCRIPT_NAME'] = "/test1";
    $_REQUEST['__vae_local_files'] = array('/test1.html' => '<?php $_VAE["VaeLocalHtmlTest"] = 41; ?>');
    $key = $this->helperVaeLocalAuthorize();
    _vae_local();
    $this->assertEqual($_VAE["VaeLocalPhpTest"], 42);
    $this->assertEqual($_VAE["VaeLocalHtmlTest"], 41);
    $this->assertEqual($_VAE['local'], $key);
    $this->assertEqual($_VAE['filename'], "/test1.html");
    $this->assertNotNull($_VAE['cache_key']);
  }

  function testVaeLocalFailedToAuthorizeFromMemcache() {
    $this->helperVaeLocalAuthorize("BAD");
    $this->expectException();
    try {
      _vae_local();
      $this->fail("expected VaeException");
    } catch (VaeException $e) {
      $this->pass();
    }
  }
  function testVaeLocalAuthenticateGood() {
    global $_VAE;
    $_REQUEST['__local_username'] = "kevin";
    $_REQUEST['__local_password'] = "pass";
    $_REQUEST['__local_version'] = $_VAE['local_newest_version'];
    $this->mockRest("GOOD");
    $this->assertEqual(_vae_local_authenticate("key"), "GOOD");
  }

  function testVaeLocalAuthenticateBad() {
    $_REQUEST['__local_username'] = "kevin";
    $_REQUEST['__local_password'] = "pass";
    $this->mockRest("BAD");
    $this->assertEqual(_vae_local_authenticate("key"), "BAD");
  }

  function testVaeLocalAuthenticateOld() {
    global $_VAE;
    $_REQUEST['__local_username'] = "kevin";
    $_REQUEST['__local_password'] = "pass";
    $_REQUEST['__local_version'] = "0.1.4";
    $this->mockRest("GOOD");
    $this->assertPattern('/MSG/', _vae_local_authenticate("key"));
  }

  function testVaeLocalExec() {
    global $noop;
    _vae_local_exec('<?php $noop = true; ?>');
    $this->pass();
  }

  function testVaeLocalNeeds() {
    _vae_local_needs("/notfound1");
    $this->assertNoFinal();
    $_REQUEST['__vae_local'] = true;
    _vae_local_needs("/notfound1");
    $this->assertFinal("__vae_local_needs=/notfound1");
  }

  function testVaeMakeFilename() {
    $this->assertEqual("kevin-bombino-1-is-superhero-cow-emancipation-proclamat.pdf", _vae_make_filename("pdf", "Kevin Bombino ++1 Is Superhero\n\n\n Cow   Emancipation Proclamation Devastation RI0T!!"));
    $this->assertEqual("sample-nala.1.jpg", _vae_make_filename("jpg", "SAMPLE NALA"));
    $this->assertPattern('/^([0-9a-f]*).pdf/', _vae_make_filename("pdf"));
  }

  function testVaeMergeDataFromTags() {
    $tag = $this->callbackTag('<v:formmail to="test@actionverb.com"><v:text_field name="Your Name" /><v:if path="cow"><v:text_field name="YourMessage" /></v:if><v:date_select name="Dob" /></v:formmail>');
    $data = array();
    $errors = array();
    $_REQUEST['Your_Name'] = "Kevin";
    $_REQUEST['YourMessage'] = "Hey";
    $_REQUEST['Dob_month'] = "10";
    $_REQUEST['Dob_day'] = "7";
    $_REQUEST['Dob_year'] = "1986";
    _vae_merge_data_from_tags($tag, $data, $errors);
    $this->assertEqual($data, array('Your Name' => "Kevin", "Dob" => "1986-10-07"));
    $this->assertEqual($errors, array());
  }

  function testVaeMergeDataFromTagsErrors() {
    $tag = $this->callbackTag('<v:formmail to="test@actionverb.com"><v:text_field name="Your Name" required="true" /><v:if path="cow"><v:text_field name="YourMessage" /></v:if></v:formmail>');
    $data = array();
    $errors = array();
    _vae_merge_data_from_tags($tag, $data, $errors);
    $this->assertPatternInArray('/Your Name can\'t be blank/', $errors);
  }

  function testVaeMergeDataFromTagsErrorsCreditCard() {
    $_REQUEST['cc'] = "4111111111111112";
    $tag = $this->callbackTag('<v:formmail to="test@actionverb.com"><v:text_field name="cc" required="creditcard" /></v:formmail>');
    $data = array();
    $errors = array();
    _vae_merge_data_from_tags($tag, $data, $errors);
    $this->assertPatternInArray('/Cc must be a valid credit card number/', $errors);
    $_REQUEST['cc'] = "4111111111111111";
    $errors = array();
    _vae_merge_data_from_tags($tag, $data, $errors);
    $this->assertEqual($errors, array());
  }

  function testVaeMergeDataFromTagsErrorsDate() {
    $_REQUEST['date'] = "not a valid date";
    $tag = $this->callbackTag('<v:formmail to="test@actionverb.com"><v:text_field name="date" required="date" /></v:formmail>');
    $data = array();
    $errors = array();
    _vae_merge_data_from_tags($tag, $data, $errors);
    $this->assertPatternInArray('/Date must be a valid date/', $errors);
    $_REQUEST['date'] = "july 4 1976";
    $errors = array();
    _vae_merge_data_from_tags($tag, $data, $errors);
    $this->assertEqual($errors, array());
  }

  function testVaeMergeDataFromTagsErrorsDigits() {
    $_REQUEST['d'] = "cow";
    $tag = $this->callbackTag('<v:formmail to="test@actionverb.com"><v:text_field name="d" required="digits" /></v:formmail>');
    $data = array();
    $errors = array();
    _vae_merge_data_from_tags($tag, $data, $errors);
    $this->assertPatternInArray('/D must only contain numeric digits/', $errors);
    $_REQUEST['d'] = "456786768";
    $errors = array();
    _vae_merge_data_from_tags($tag, $data, $errors);
    $this->assertEqual($errors, array());
  }

  function testVaeMergeDataFromTagsErrorsEmail() {
    $_REQUEST['email'] = "cow";
    $tag = $this->callbackTag('<v:formmail to="test@actionverb.com"><v:text_field name="email" required="email" /></v:formmail>');
    $data = array();
    $errors = array();
    _vae_merge_data_from_tags($tag, $data, $errors);
    $this->assertPatternInArray('/Email must be a valid E-Mail/', $errors);
    $_REQUEST['email'] = "kevin@great.com";
    $errors = array();
    _vae_merge_data_from_tags($tag, $data, $errors);
    $this->assertEqual($errors, array());
  }

  function testVaeMergeDataFromTagsErrorsName() {
    $_REQUEST['billing_name'] = "Kevin";
    $tag = $this->callbackTag('<v:formmail to="test@actionverb.com"><v:text_field name="billing_name" required="name" /></v:formmail>');
    $data = array();
    $errors = array();
    _vae_merge_data_from_tags($tag, $data, $errors);
    $this->assertPatternInArray('/Billing Name must contain a first/', $errors);
    $_REQUEST['billing_name'] = "Kevin Bombino";
    $errors = array();
    _vae_merge_data_from_tags($tag, $data, $errors);
    $this->assertEqual($errors, array());
  }

  function testVaeMergeDataFromTagsErrorsNumber() {
    $_REQUEST['d'] = "cow";
    $tag = $this->callbackTag('<v:formmail to="test@actionverb.com"><v:text_field name="d" required="number" /></v:formmail>');
    $data = array();
    $errors = array();
    _vae_merge_data_from_tags($tag, $data, $errors);
    $this->assertPatternInArray('/D must be a valid number/', $errors);
    $_REQUEST['d'] = "1456.23";
    $errors = array();
    _vae_merge_data_from_tags($tag, $data, $errors);
    $this->assertEqual($errors, array());
  }

  function testVaeMergeDataFromTagsErrorsUrl() {
    $_REQUEST['url'] = "http//bad.com";
    $tag = $this->callbackTag('<v:formmail to="test@actionverb.com"><v:text_field name="url" required="url" /></v:formmail>');
    $data = array();
    $errors = array();
    _vae_merge_data_from_tags($tag, $data, $errors);
    $this->assertPatternInArray('/Url must be a valid URL/', $errors);
    $_REQUEST['url'] = "http://great.com";
    $errors = array();
    _vae_merge_data_from_tags($tag, $data, $errors);
    $this->assertEqual($errors, array());
  }

  function testVaeMergeDataFromTagsErrorsConfirmation() {
    $_REQUEST['name'] = "kevin";
    $_REQUEST['confirm_name'] = "kevinn";
    $tag = $this->callbackTag('<v:formmail to="test@actionverb.com"><v:text_field name="name" /><v:text_field name="confirm_name" /></v:formmail>');
    $data = array();
    $errors = array();
    _vae_merge_data_from_tags($tag, $data, $errors);
    $this->assertPatternInArray('/Name doesn\'t match confirmation/', $errors);
  }

  function testVaeMergeSessionData() {
    $_SESSION['variable'] = "COW";
    $this->assertEqual("testCOWeqw", _vae_merge_session_data("test<__VAE_SESSION_DUMP=variable>eqw"));
  }

  function testVaeMinifyJs() {
    $this->assertEqual("alert('kevin'); alert('moo');", _vae_minify_js("   \n\r\n  alert('kevin');\n\n\n\r alert('moo');    \n\n\t"));
  }

  function testVaeMultipartMail() {
    _vae_multipart_mail("kevin@bombino.org", "kevin@actionverb.com", "Test Subject", "text", "html");
    $this->assertMail(1);
  }

  function testVaeNaturalTime() {
    $this->assertEqual(_vae_natural_time(time()-30), "less than a minute ago");
    $this->assertEqual(_vae_natural_time(time()-60), "about a minute ago");
    $this->assertEqual(_vae_natural_time(time()-180), "3 minutes ago");
    $this->assertEqual(_vae_natural_time(time()-7200), "2 hours ago");
    $this->assertEqual(_vae_natural_time(time()-86400), "1 day ago");
    $this->assertEqual(_vae_natural_time(time()-86400*3), "3 days ago");
  }

  function testVaeNeedsJavascript() {
    global $_VAE;
    $this->assertNull($_VAE['javascripts']);
    _vae_needs_javascript();
    $this->assertEqual($_VAE['javascripts'], array());
    _vae_needs_javascript("prototype", "effects");
    $this->assertEqual($_VAE['javascripts'], array("prototype" => true, "effects" => true));
  }

  function testVaeNeedsJquery() {
    global $_VAE;
    $this->assertNull($_VAE['javascripts']);
    _vae_needs_jquery();
    $this->assertEqual($_VAE['javascripts'], array("jquery" => true));
    _vae_needs_jquery("ui");
    $this->assertEqual($_VAE['javascripts'], array("jquery" => true, "jquery.ui" => true));
  }

  function testVaeOnDomReady() {
    global $_VAE;
    $this->assertNull($_VAE['javascripts']);
    _vae_on_dom_ready("alert('hey');");
    $this->assertEqual($_VAE['javascripts'], array("jquery" => true));
    $this->assertEqual($_VAE['on_dom_ready'], array("alert('hey');"));
    _vae_on_dom_ready("alert('hey');");
    $this->assertEqual($_VAE['javascripts'], array("jquery" => true));
    $this->assertEqual($_VAE['on_dom_ready'], array("alert('hey');", "alert('hey');"));
  }

  function testVaeOneline() {
    $this->assertEqual("Freefall", _vae_oneline("name", _vae_fetch(13421)));
    $this->assertEqual("Freefall", _vae_oneline("name,123,456", _vae_fetch(13421)));
    $this->assertEqual("http://btg.vaesite.net/__data/Array", _vae_oneline("image", _vae_fetch(13421)));
    $this->assertEqual("13423,13424", _vae_oneline("JOIN(albums)", _vae_fetch(13421)));
    $_REQUEST['kevin'] = "moocow";
    $this->assertEqual("moocow", _vae_oneline("PARAM(kevin)", _vae_fetch(13421)));
    $_REQUEST['kevin'] = "///";
    $this->assertEqual("%2F%2F%2F", _vae_oneline("PARAM(kevin)", _vae_fetch(13421), "href"));
    $_REQUEST['kevin'] = "\"'";
    $this->assertEqual("&quot;&#039;", _vae_oneline("PARAM(kevin)", _vae_fetch(13421), "path"));
  }

  function testVaeOnelineGet() {
    $this->assertEqual("Kevin", _vae_oneline_get("  Kevin\n", "TextItem", false, array()));
    $this->assertEqual(13421, _vae_oneline_get(_vae_fetch(13421), null, false, array()));
    $this->assertEqual("August 21, 2008", _vae_oneline_get(_vae_fetch("14186/date"), null, false, array()));
    $this->assertEqual("12345", _vae_oneline_get("12345", false, array()));
    $this->assertEqual("12345", _vae_oneline_get("12345", false, array()));
    $this->assertEqual("12345", _vae_oneline_get("12345", false, array()));
  }

  function testVaeOnelineSize() {
    global $_VAE;
    $this->assertEqual(_vae_oneline_size("sample-nala.jpg"), $_VAE['config']['data_url'] . "sample-nala.jpg");
    $this->assertEqual(_vae_oneline_size("sample-nala.jpg", false), $_VAE['config']['data_url'] . "sample-nala.jpg");
    $this->assertEqual(_vae_oneline_size("sample-nala.jpg", true), 52741);
  }

  function testVaeOnelineUrl() {
    $this->assertEqual("", _vae_oneline_url("artists", null));
    $this->assertEqual("", _vae_oneline_url("", _vae_fetch(13421)));
    $this->assertEqual("/artist/kevin-bombino", _vae_oneline_url("", _vae_fetch(13432)));
    $this->assertEqual("/artist/kevin-bombino", _vae_oneline_url("13432", null));
  }

  function testVaeParsePath() {
    _vae_parse_path();
    $this->assertEqual("", $_REQUEST['path']);
    $this->assertNull($_REQUEST['id']);
    $this->assertNull($_REQUEST['awesome']);
    $_SERVER['PATH_INFO'] = "/12345-kevin-is-awesome-456";
    _vae_parse_path();
    $this->assertEqual($_REQUEST['path'], "12345-kevin-is-awesome-456");
    $this->assertEqual($_REQUEST['id'], "12345");
    $this->assertEqual($_REQUEST['awesome'], "456");
    $this->assertNull($_REQUEST['kevin']);
  }

  function testVaePhp() {
    global $testVaePhp;
    $testVaePhp = "4";
    $this->assertEqual("1", _vae_php("=(2-1);", null));
    $this->assertEqual("1", _vae_php("return (2-1);", null));
    $this->assertEqual("4", _vae_php('=$testVaePhp;', null));
    $this->assertEqual("4", _vae_php('=$testVaePhp', null));
    $context = _vae_fetch(13421);
    $this->assertEqual(13421, _vae_php('=$id', $context));
    $this->assertEqual("Freefall", _vae_php('=$context["name"]', $context));
  }

  function testVaePlaceholder() {
    global $_VAE;
    $this->assertEqual(_vae_placeholder("id"), "1234");
    $this->assertEqual(_vae_placeholder("shipment_company"), "UPS");
    $this->assertEqual(_vae_placeholder("shipment_tracking_number"), "1Z8A5E940342201962");
    $this->assertEqual(_vae_placeholder("something_else"), "(something_else)");
    $_VAE['from_proxy'] = true;
    $this->assertEqual(_vae_placeholder("shipment_company"), "%SHIPMENT_COMPANY%");
  }

  function testVaeQs() {
    $this->assertEqual(_vae_qs(""), "");
    $this->assertEqual(_vae_qs("a=b"), "?a=b");
    $this->assertEqual(_vae_qs("&&&a=b&&&"), "?a=b");
    $this->assertEqual(_vae_qs("a=b&c=d"), "?a=b&c=d");
    $_GET['e'] = "f";
    $_GET['__vae_local'] = "test";
    $_GET['__vae_ssl_router'] = "test";
    $this->assertEqual(_vae_qs("a=b"), "?e=f&a=b");
    $this->assertEqual(_vae_qs("e=g"), "?e=g");
    $this->assertEqual(_vae_qs("a=b", false), "?a=b");
    $this->assertEqual(_vae_qs("", true, "e=q"), "?e=f&e=q");
  }

  function testVaeReadFile() {
    $this->assertEqual("value123", _vae_read_file("testfile.txt"));
    $this->assertEqual("", _vae_read_file("testfile2.txt"));
  }

  function testVaeRegisterHook() {
    global $_VAE;
    $this->assertNull($_VAE['hook']['diva']);
    _vae_register_hook("diva", array('callback' => "beyonce1"));
    $this->assertEqual($_VAE['hook']['diva'], array(array('callback' => "beyonce1")));
    _vae_register_hook("diva", array('callback' => "beyonce2"));
    $this->assertEqual($_VAE['hook']['diva'], array(array('callback' => "beyonce1"), array('callback' => "beyonce2")));
  }

  function testVaeRegisterTag() {
    global $_VAE;
    $this->assertNull($_VAE['tags']['cowtag']);
    $opts = array("handler" => "_vae_render_cowtag");
    _vae_register_tag("cowtag", $opts);
    $this->assertEqual($_VAE['tags']['cowtag'], $opts);
    $this->assertNull($_VAE['callbacks']['cowtag']);
    _vae_register_tag("cowtag", array("handler" => "_vae_render_cowtag", "callback" => "_vae_callback_cowtag", "filename" => "cowtag.php"));
    $this->assertEqual($_VAE['tags']['cowtag']['html'], "form");
    $this->assertNotNull($_VAE['callbacks']['cowtag']);
    $this->assertEqual($_VAE['callbacks']['cowtag']['filename'], "cowtag.php");
    $this->assertNull($_VAE['form_items']['cowtag']);
    $opts = array("handler" => "_vae_render_cowtag", "html" => "input");
    _vae_register_tag("cowtag", $opts);
    $this->assertEqual($_VAE['tags']['cowtag'], $opts);
    $this->assertNotNull($_VAE['form_items']['cowtag']);
  }

  function testVaeRemote() {
    $this->expectException();
    try {
      _vae_remote();
    } catch (VaeException $e) {
      $this->pass();
    }
  }

  function testVaeRemoveFile() {
    global $_VAE;
    $file = $_VAE['config']['data_path'] . "testdelete.txt";
    $this->assertFalse(file_exists($file));
    file_put_contents($file, "stuff");
    $this->assertTrue(file_exists($file));
    _vae_remove_file("testdelete.txt");
    $this->assertFalse(file_exists($file));
  }

  function testVaeRenderBacktrace() {
    $this->assertPattern('/<pre>/', _vae_render_backtrace(array()));
  }

  function testVaeRenderError() {
    $this->assertPattern('/<h2>/', _vae_render_error(new VaeException("test")));
  }

  function testVaeRenderFinal() {
    _vae_render_final("TESTSTUFF");
    $this->assertFinal("TESTSTUFF");
  }

  function testVaeRenderMessage() {
    $this->assertPattern('/<html>/', _vae_render_message("a", "b"));
  }

  function testVaeRenderTimer() {
    $_REQUEST['__time'] = true;
    vae_tick("test");
    vae_tick("test");
    $this->assertPattern('/<h2>Vae Timer<\/h2>/', _vae_render_timer());
  }

  function testVaeReportError() {
    $this->assertNotNull(_vae_report_error("subj", "msg"));
    $this->assertMail(1);
  }

  function testVaeRequestParam() {
    $this->assertNull(_vae_request_param("cow"));
    $_SESSION['__v:flash']['post']['cow'] = "moo2";
    $this->assertNull(_vae_request_param("cow"));
    $_REQUEST['cow'] = "moo";
    $this->assertEqual("moo", _vae_request_param("cow"));
    $this->assertEqual("moo2", _vae_request_param("cow", true));
  }

  function testVaeRequireSsl() {
    global $_VAE;
    $this->assertNull($_VAE['ssl_required']);
    $this->assertEqual("", _vae_require_ssl());
    $this->assertEqual($_SESSION['__v:pre_ssl_host'], "btg.vaesite.com");
    $this->assertRedirect("https://btg.vaesite.com/page");
    $this->assertNotNull($_VAE['ssl_required']);
  }

  function testVaeRequireSslDifferentDomain() {
    global $_VAE;
    $_SERVER['HTTP_HOST'] = "btgrecords.com";
    $this->assertNull($_VAE['ssl_required']);
    $this->assertEqual("", _vae_require_ssl());
    $this->assertEqual($_SESSION['__v:pre_ssl_host'], "btgrecords.com");
    $this->assertRedirect("https://btg.vaesite.com/page?__router=" . session_id());
    $this->assertNotNull($_VAE['ssl_required']);
  }

  function testVaeRequireSslHttps() {
    global $_VAE;
    $this->assertNull($_VAE['ssl_required']);
    $_SERVER['HTTPS'] = true;
    $this->assertFalse(_vae_require_ssl());
    $this->assertNotNull($_VAE['ssl_required']);
  }

  function testVaeRequireSslRouter() {
    global $_VAE;
    $this->assertNull($_VAE['ssl_required']);
    $_REQUEST['__vae_ssl_router'] = true;
    $this->assertFalse(_vae_require_ssl());
    $this->assertNotNull($_VAE['ssl_required']);
  }

  function testVaeRequireSslLocal() {
    global $_VAE;
    $this->assertNull($_VAE['ssl_required']);
    $_REQUEST['__vae_local'] = true;
    $this->assertFalse(_vae_require_ssl());
    $this->assertNotNull($_VAE['ssl_required']);
  }

  function testVaeRoundSignificantDigits() {
    $this->assertEqual(_vae_round_significant_digits(54321, -1), 50000);
    $this->assertEqual(_vae_round_significant_digits(54321, 1), 50000);
    $this->assertEqual(_vae_round_significant_digits(54321, 3), 54300);
    $this->assertEqual(_vae_round_significant_digits(54321, 5), 54321);
    $this->assertEqual(_vae_round_significant_digits(0.07654, 3), 0.0765);
  }

  function testVaeRunHooks() {
    global $_VAE;
    vae_register_hook("test1", "testVaeRunHooksTest1");
    $this->assertTrue(_vae_run_hooks("test1"));
    $this->assertEqual($_VAE['hooktest1'], 1);
    vae_register_hook("test2", "testVaeRunHooksTest2");
    $this->assertFalse(_vae_run_hooks("test2"));
    $this->assertEqual($_VAE['hooktest2'], 1);
    vae_register_hook("test2", "testVaeRunHooksTest1");
    $this->assertFalse(_vae_run_hooks("test2"));
    $this->assertEqual($_VAE['hooktest1'], 1);
    $this->assertEqual($_VAE['hooktest2'], 2);
  }

  function testVaeScriptTag() {
    $this->assertEqual("<script type='text/javascript'>code</script>", _vae_script_tag("code"));
  }

  function testVaeSessionDepsAdd() {
    global $_VAE;
    _vae_session_deps_add("test_session_dep");
    $this->assertSessionDep("test_session_dep");
    $this->assertNull($_VAE['cant_cache']);
    $_SESSION["logged_in"] = 4;
    _vae_session_deps_add("logged_in");
    $this->assertEqual($_VAE['cant_cache'], "logged_in - unknown");
  }

  function testVaeSetCacheKey() {
    global $_VAE;
    $found = array();
    for ($i = 0; $i < 60; $i++) {
      if (($i % 4) < 2) $_SERVER['PATH_INFO'] = rand();
      elseif (($i % 8) < 4) $_SERVER['QUERY_STRING'] = rand();
      else $_POST = array('key' => rand());
      _vae_set_cache_key();
      $found[] = $_VAE['cache_key'];
    }
    $this->assertNotNull($_VAE['cache_key']);
    $this->assertEqual(count($found), count(array_unique($found)));
  }

  function testVaeSessionCookie() {
    global $_VAE;
    $this->assertNull($_VAE['session_cookies']);
    _vae_session_cookie("cow", "value");
    $this->assertEqual($_VAE['session_cookies']["cow"], "value");
  }

  function testVaeSetDefaultConfig() {
    global $_VAE, $BACKLOTCONFIG;
    $BACKLOTCONFIG = array('test' => 'value');
    _vae_set_default_config();
    $this->assertNotNull($_VAE['config']['asset_url']);
    $this->assertNotNull($_VAE['config']['data_path']);
    $this->assertNotNull($_VAE['config']['data_url']);
    $this->assertNotNull($_VAE['global_cache_key']);
    $this->assertEqual($_VAE['config']['test'], "value");
  }

  function testVaeSetInitialContextEmpty() {
    global $_VAE;
    _vae_set_initial_context();
    $this->assertNull($_VAE['context']);
  }

  function testVaeSetInitialContextGetParam() {
    global $_VAE;
    $_GET['geese'] = 13423;
    _vae_set_initial_context();
    $this->assertEqual($_VAE['context']->id, 13423);
  }

  function testVaeSetInitialContextIdInRequest() {
    global $_VAE;
    $_REQUEST['id'] = 13423;
    _vae_set_initial_context();
    $this->assertEqual($_VAE['context']->id, 13423);
  }

  function testVaeSetInitialContextIdAsContext() {
    global $_VAE;
    $_VAE['context'] = 13423;
    _vae_set_initial_context();
    $this->assertEqual($_VAE['context']->id, 13423);
  }

  function testVaeSetLogin() {
    $this->mockRest("601 Authorized. user_id=123");
    _vae_set_login();
    $this->assertEqual($_SESSION['__v:user_id'], 123);
  }

  function testVaeSrc() {
    $this->assertEqual(_vae_src("test1"), array("/test1.haml", file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/test1.haml")));
    $this->assertDep("test1.haml");
    $this->assertNotDep("/test1.php");
    $this->assertEqual(_vae_src("/test1"), array("/test1.haml", file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/test1.haml")));
    $this->assertEqual(_vae_src("/test1.haml"), array("/test1.haml", file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/test1.haml")));
    $this->assertEqual(_vae_src("test2"), array("/test2.html", file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/test2.html")));
    $this->assertDep("test2.html");
    $this->assertEqual(_vae_src("test3"), array("/test3.sass", file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/test3.sass")));
    $this->assertEqual(_vae_src("test4"), array("/test4.xml", file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/test4.xml")));
    $this->assertEqual(_vae_src("test5"), array("/test5.rss", file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/test5.rss")));
    $this->assertEqual(_vae_src("test6"), array("/test6.pdf.haml", file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/test6.pdf.haml")));
    $this->assertEqual(_vae_src("test7"), array("/test7.pdf.haml.php", file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/test7.pdf.haml.php")));
    $this->assertEqual(_vae_src("test8"), array("/test8.haml.php", file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/test8.haml.php")));
    $this->assertEqual(_vae_src("test9"), array("/test9.pdf.html", file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/test9.pdf.html")));
    $this->setLocal();
    $this->assertEqual(_vae_src("notfound1"), "");
    $this->assertFinal("__vae_local_needs=/notfound1");
    _vae_long_term_cache_set("local/found1.html", "<html>a file</html>");
    $this->assertEqual(_vae_src("/found1"), array("/found1.html", "<html>a file</html>"));
    $this->clearLocal();
  }

  function testVaeStoreFile() {
    $this->assertNotNull(_vae_store_file("TESTIDEN2" . md5(rand()), "some data", "txt", md5(rand()) . "testfile.txt"));
  }

  function testVaeStringifyArray() {
    _vae_stringify_array(array('k' => (float)5.5));
    $this->pass();
  }

  function testVaeTick() {
    global $_VAE;
    $_REQUEST['__time'] = true;
    $old_tick = $_VAE['tick'];
    $old_ticks = $_VAE['ticks'];
    _vae_tick("test");
    $this->assertNotEqual($old_tick, $_VAE['tick']);
    $this->assertNotEqual($old_ticks, $_VAE['ticks']);
    unset($_REQUEST['__time']);
    unset($_VAE['tick']);
    unset($_VAE['ticks']);
  }

  function testVaeUpdateSettingsFeed() {
    $this->mockRest("<?php");
    _vae_update_settings_feed();
    $this->pass();
  }

  function testVaeUrlize() {
    $this->assertEqual(_vae_urlize("www.google.com"), '<a href="http://google.com" target="_blank">google.com</a>');
    $this->assertEqual(_vae_urlize("http://www.google.com"), '<a href="http://www.google.com" target="_blank">http://www.google.com</a>');
    $this->assertEqual(_vae_urlize("http://google.com"), '<a href="http://google.com" target="_blank">http://google.com</a>');
    $this->assertEqual(_vae_urlize("http://google.com/some/arbitrary/path"), '<a href="http://google.com/some/arbitrary/path" target="_blank">http://google.com/some/arbitrary/path</a>');
    $this->assertEqual(_vae_urlize("http://google.com/some?qs=1"), '<a href="http://google.com/some?qs=1" target="_blank">http://google.com/some?qs=1</a>');
  }

  function testVaeValidCreditcard() {
    $this->assertTrue(_vae_valid_creditcard("378282246310005"));
    $this->assertTrue(_vae_valid_creditcard("5610591081018250"));
    $this->assertTrue(_vae_valid_creditcard("4024007152592936"));
    $this->assertTrue(_vae_valid_creditcard("4539871803120902"));
    $this->assertTrue(_vae_valid_creditcard("4916213331055594"));
    $this->assertTrue(_vae_valid_creditcard("4485257205577259"));
    $this->assertTrue(_vae_valid_creditcard("4929005280381970"));
    $this->assertTrue(_vae_valid_creditcard("4532081024741919"));
    $this->assertTrue(_vae_valid_creditcard("4485526942542812"));
    $this->assertTrue(_vae_valid_creditcard("4556626025915929"));
    $this->assertTrue(_vae_valid_creditcard("4556078778963864"));
    $this->assertTrue(_vae_valid_creditcard("4539464866771926"));
    $this->assertTrue(_vae_valid_creditcard("5336972116097381"));
    $this->assertTrue(_vae_valid_creditcard("5261140752119211"));
    $this->assertTrue(_vae_valid_creditcard("5162971093452899"));
    $this->assertTrue(_vae_valid_creditcard("5189910057968481"));
    $this->assertTrue(_vae_valid_creditcard("5402238682490080"));
    $this->assertTrue(_vae_valid_creditcard("5597208936374302"));
    $this->assertTrue(_vae_valid_creditcard("5451667522689321"));
    $this->assertTrue(_vae_valid_creditcard("5233582233764211"));
    $this->assertTrue(_vae_valid_creditcard("5235944749499146"));
    $this->assertTrue(_vae_valid_creditcard("5414482892978676"));
    $this->assertTrue(_vae_valid_creditcard("6011111111111117"));
    $this->assertTrue(_vae_valid_creditcard("4222222222222"));
    $this->assertFalse(_vae_valid_creditcard("4485257205577251"));
    $this->assertFalse(_vae_valid_creditcard("4929005280381971"));
    $this->assertFalse(_vae_valid_creditcard("4532081024741911"));
    $this->assertFalse(_vae_valid_creditcard("4485526942542811"));
    $this->assertFalse(_vae_valid_creditcard("4556626025915921"));
    $this->assertFalse(_vae_valid_creditcard("4556078778963861"));
    $this->assertFalse(_vae_valid_creditcard("4539464866771921"));
    $this->assertFalse(_vae_valid_creditcard("453946486677192"));
    $this->assertFalse(_vae_valid_creditcard(""));
  }

  function testVaeValidDate() {
    $this->assertTrue(_vae_valid_date("now"));
    $this->assertTrue(_vae_valid_date("September 9 2000"));
    $this->assertTrue(_vae_valid_date("9/9/2000"));
    $this->assertTrue(_vae_valid_date("10 September 2000"));
    $this->assertTrue(_vae_valid_date("+1 day"));
    $this->assertTrue(_vae_valid_date("+1 week"));
    $this->assertTrue(_vae_valid_date("+1 week 2 days 4 hours 2 seconds"));
    $this->assertTrue(_vae_valid_date("next Thursday"));
    $this->assertTrue(_vae_valid_date("last Monday"));
    $this->assertFalse(_vae_valid_date("9/9/9999999"));
    $this->assertFalse(_vae_valid_date("no"));
    $this->assertFalse(_vae_valid_date("123456789"));
    $this->assertFalse(_vae_valid_date("7 junio 2009"));
  }

  function testVaeValidDigits() {
    $this->assertTrue(_vae_valid_digits(""));
    $this->assertTrue(_vae_valid_digits("0"));
    $this->assertTrue(_vae_valid_digits("1"));
    $this->assertTrue(_vae_valid_digits("101010"));
    $this->assertTrue(_vae_valid_digits("12345678"));
    $this->assertTrue(_vae_valid_digits("72631498716"));
    $this->assertFalse(_vae_valid_digits("0.0"));
    $this->assertFalse(_vae_valid_digits("1,456"));
    $this->assertFalse(_vae_valid_digits("kevin"));
    $this->assertFalse(_vae_valid_digits("0 12 27"));
    $this->assertFalse(_vae_valid_digits("0-10-20"));
    $this->assertFalse(_vae_valid_digits("#3"));
  }

  function testVaeValidEmail() {
    $this->assertTrue(_vae_valid_email("kevin@bombino.org"));
    $this->assertTrue(_vae_valid_email("kevin@bombino.fr"));
    $this->assertTrue(_vae_valid_email("kevin.bombino@bombino.org"));
    $this->assertTrue(_vae_valid_email("kevin@bombino.org.fr"));
    $this->assertTrue(_vae_valid_email("kevin_bombino@aol.com.net"));
    $this->assertTrue(_vae_valid_email("kevin@bombino.aero"));
    $this->assertTrue(_vae_valid_email("Zeallylongcrazyshit@bbdo.kz"));
    $this->assertFalse(_vae_valid_email("kevin@@bombino.org"));
    $this->assertFalse(_vae_valid_email("kevin@bombino@bombino.org"));
    $this->assertFalse(_vae_valid_email("kevin#bombino.org"));
    $this->assertFalse(_vae_valid_email("kevinbombino.org"));
  }

  function testVaeValidUrl() {
    $this->assertTrue(_vae_valid_url("http://google.com/"));
    $this->assertTrue(_vae_valid_url("ftp://usernam@someurl.com"));
    $this->assertTrue(_vae_valid_url("http://hh-1hallo.msn.blabla.com:80800/test/test/test.aspx?dd=dd&id=dki"));
    $this->assertTrue(_vae_valid_url("http://twitter.com/test"));
    $this->assertTrue(_vae_valid_url("http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"));
    $this->assertTrue(_vae_valid_url("telnet://example.org:8888"));
    $this->assertTrue(_vae_valid_url("http://www.google.com/search?q=good+url+regex&rls=com.microsoft:*&ie=UTF-8&oe=UTF-8&startIndex=&startPage=1"));
    $this->assertTrue(_vae_valid_url("ftp://joe:password@ftp.filetransferprotocal.com"));
    $this->assertTrue(_vae_valid_url("https://some-url.com?query=&name=joe?filter=*.*#some_anchor"));
    $this->assertFalse(_vae_valid_url(""));
    $this->assertFalse(_vae_valid_url("cow"));
    $this->assertFalse(_vae_valid_url("12345"));
    $this->assertFalse(_vae_valid_url("http    .org	/TR/xhtml1/DTD/xhtml1-transitional.dtd"));
    $this->assertFalse(_vae_valid_url("http://hh-1hallo. msn.blablabla.com:80800/test/test.aspx?dd=dd&id=dki"));
    $this->assertFalse(_vae_valid_url("google.com"));
    $this->assertFalse(_vae_valid_url("example.org"));
    $this->assertFalse(_vae_valid_url("www.example.org"));
    $this->assertFalse(_vae_valid_url("http:/example.org"));
    $this->assertFalse(_vae_valid_url("http//example.org"));
  }

  function testVaeWriteFile() {
    _vae_write_file("test.xml", "test data");
    $this->pass();
  }

}

/*** Helpers ***/

function testVaeRunHooksTest1() {
  global $_VAE;
  $_VAE['hooktest1']++;
  return true;
}

function testVaeRunHooksTest2() {
  global $_VAE;
  $_VAE['hooktest2']++;
  return false;
}

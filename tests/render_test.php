<?php

class RenderTest extends VaeUnitTestCase {
  
  function setUp() {
    $this->tag = array();
    $this->callback = array();
    $this->render_context = new Context();
    parent::setUp();
  }
  
  function helperRunRenderTestsFromTestFilesCallback($fn) {
    global $_VAE;
    eval($fn[1]);
  }
  
  function testRunRenderTestsFromTestFiles() {
    global $_VAE;
    $dir = dirname(__FILE__) . "/render_tests/";
    if ($handle = opendir($dir)) {
      while (false !== ($file = readdir($handle))) {
        if (strstr($file, ".test")) {
          $test_file = file_get_contents($dir . $file) . "\n";
          $tests = explode("\n=\n", $test_file);
          $i = 0;
          foreach ($tests as $test) {
            $i++;
            $testlines = explode("\n", $test);
            if ($test[0] == "E") {
              $expected_exception = substr(array_shift($testlines), 1);
              $test = implode("\n", $testlines);
            }
            if ($test[0] == "D") {
              $dump = true;
              $test = implode("\n", $testlines);
            }
            $sep = explode("\n>\n", $test . "\n");
            $input = preg_replace_callback('/<\?php(.*)\?>/', array($this, "helperRunRenderTestsFromTestFilesCallback"), $sep[0]);
            if ($expected_exception) $this->expectException();
            try {
              list($parse_tree, $render_context) = _vae_parse_vaeml($input, $file . "." . $i, null, new Context());
              $out = _vae_render_tags($parse_tree, $_VAE['context'], $render_context);
              $out = preg_replace('/\n\n*/', "\n", trim($out));
              if ($expected_exception) $this->fail("Expecting exception [$expected_exception]");
            } catch (VaeException $e) {
              $out = "";
              $msg = strip_tags($e->getMessage());
              if ($expected_exception && strstr($msg, $expected_exception)) $this->pass();
              else $this->fail("Got unexpected exception [" . $msg . "]");
            }
            if ($sep[1]) {
              $output = preg_replace_callback('/<\?php(.*)\?>/', array($this, "helperRunRenderTestsFromTestFilesCallback"), $sep[1]);
              $output = preg_replace('/\n\n*/', "\n", trim($output));
              if (substr($output, 0, 1) == "/" && substr($output, -1, 1) == "/") {
                $this->assertPattern($output, $out, "Expecting Pattern [$output] in Render Test File [$file]");
              } else {
                $this->assertEqual($output, $out, "Expecting [$out] to be [$output] in Render Test File [$file]");
              }
            } elseif ($dump) {
              echo $out . "\n";
            } elseif (!$expected_exception) {
              $this->pass();
            }
            $this->tearDown();
            $this->setUp();
            unset($dump);
            unset($expected_exception);
          }
        }
      }
      closedir($handle);
    }
  }

  function testVaeRender() {
    global $_VAE;
    $tag = array('innerhtml' => 'inside');
    $this->assertEqual(_vae_render($tag, null, $this->render_context), 'inside');
    $tag = array('innerhtml' => '<v=13421/name>');
    $this->assertEqual(_vae_render($tag, null, $this->render_context), 'Freefall');
    $tag = array('type' => 'text', 'attrs' => array('path' => "13421/name"));
    $this->assertEqual(_vae_render($tag, null, $this->render_context), 'Freefall');
    $tag = array('type' => 'text', 'attrs' => array('text' => "<v=13421/name>"));
    $this->assertEqual(_vae_render($tag, null, $this->render_context), 'Freefall');
  }

  function testVaeRenderCallback() {
    $tag = array('type' => 'rock', 'tags' => array(array('type' => "text_field", 'attrs' => array("name" => "mp3"))));
    $out = _vae_render_callback("rock", array('path' => "/artists/name"), $tag, null, $this->callback, $this->render_context);
    $this->assertEqual($out, '<form action="/page?__v:rock=a4eb1ae1924960e2dce7fa5b57957df8" method="post"><input name="mp3" type="text" /></form>');
  }

  function testVaeRenderCallbackLink() {
    $tag = array('type' => 'rock', 'tags' => array(array('innerhtml' => 'linkcaption')));
    $out = _vae_render_callback_link("rock", array('path' => "/artists/name"), $tag, null, $this->callback, $this->render_context);
    $this->assertEqual($out, '<a href="/page?__v:rock=a4eb1ae1924960e2dce7fa5b57957df8">linkcaption</a>');
  }

  function testVaeRenderCreate() {
    _vae_render_create(array('path' => "/artists"), $this->tag, null, $this->callback, $this->render_context);
    $this->assertEqual($this->callback['structure_id'], 1269);
    $this->assertEqual($this->callback['row_id'], 0);
  }

  function testVaeRenderDivider() {
    $this->assertEqual("", _vae_render_divider(array(), $this->tag, null, $this->callback, $this->render_context));
  }

  function testVaeRenderFile() {
    $tag = array('type' => "file", 'attrs' => array("path" => "13425/mp3", "filename" => "EP"));
    _vae_render_file($tag['attrs'], $tag, null, $this->callback, $this->render_context);
    $this->assertEqual($this->callback['filename'], "EP");
    $this->assertEqual($this->callback['src'][0], '28616-file');
    $this->assertEqual($this->callback['src'][1]->id, _vae_fetch("13425/mp3")->id);
    $this->assertEqual($this->callback['src'][2], 'file/28616');
  }

  function testVaeRenderFileUrl() {
    $out = _vae_render_fileurl(array('path' => "/artists/name"), $this->tag, null, $this->callback, $this->render_context);
    $this->assertEqual("Freefall", $out);
  }

  function testVaeRenderFlashInside() {
    _vae_flash("test");
    $_SESSION['__v:flash'] = $_SESSION['__v:flash_new'];
    $this->assertEqual('<div class="flash msg">test</div>', _vae_render_flash_inside("", new Context()));
    $this->assertEqual('', _vae_render_flash_inside('header', new Context()));
    unset($_SESSION['__v:flash_new']);
    _vae_flash("test", 'msg', 'header');
    $_SESSION['__v:flash'] = $_SESSION['__v:flash_new'];
    $this->assertEqual('<div class="flash msg">test</div>', _vae_render_flash_inside('header', new Context()));
  }

  function testVaeRenderRedirect() {
    global $_VAE;
    _vae_render_redirect("/plow");
    $this->assertRedirect("/plow");
    $this->assertFalse($_VAE['trash_post_data']);
  }

  function testVaeRenderRedirectTrashPostData() {
    global $_VAE;
    _vae_render_redirect("/plow", true);
    $this->assertRedirect("/plow");
    $this->assertTrue($_VAE['trash_post_data']);
  }
  
  function testVaeRenderRedirectSslSamePage() {
    $_SESSION['__v:pre_ssl_host'] = "actionverb.com";
    _vae_render_redirect("/page");
    $this->assertRedirect("/page");
  }
  
  function testVaeRenderRedirectSsl() {
    $_SESSION['__v:pre_ssl_host'] = "actionverb.com";
    _vae_render_redirect("/plow");
    $this->assertRedirect("http://actionverb.com/plow");
  }
  
  function testVaeRenderRedirectSslNoLeadingSlash() {
    $_SESSION['__v:pre_ssl_host'] = "actionverb.com";
    _vae_render_redirect("plow");
    $this->assertRedirect("http://actionverb.com/plow");
  }

  function testVaeRenderRedirectRouter() {
    _vae_render_redirect("http://btgrecords.com/plow");
    $this->assertRedirect("http://btgrecords.com/plow?__router=" . session_id());
  }

  function testVaeRenderRedirectRouter2() {
    _vae_render_redirect("http://www.btgrecords.com/plow");
    $this->assertRedirect("http://www.btgrecords.com/plow?__router=" . session_id());
  }

  function testVaeRenderRedirectRouter3() {
    _vae_render_redirect("http://www.sonyrecords.com/plow");
    $this->assertRedirect("http://www.sonyrecords.com/plow");
  }

  function testVaeRenderTags() {
    $tag = $this->callbackTag("<v:section path='13421'>in<v:else>out</v:else></v:section>");
    $this->assertEqual("in", _vae_render_tags($tag));
    $this->assertEqual("out", _vae_render_tags($tag, null, null, false));
    $tag = $this->callbackTag("<v:section path='13421'><v=13421/name></v:section>");
    $this->assertEqual("Freefall", _vae_render_tags($tag));
  }

  function testVaeRenderUpdate() {
    _vae_render_update(array('path' => "13421"), $this->tag, null, $this->callback, $this->render_context);
    $this->assertEqual($this->callback['row_id'], 13421);
  }

  function testVaeRenderZip() {
    $tag = array('tags' => array(array('type' => "file", 'attrs' => array("path" => "13425/mp3"))));
    _vae_render_zip(array( "filename" => "Songs"), $tag, null, $this->callback, $this->render_context);
    $this->assertEqual($this->callback['filename'], "Songs");
    $this->assertEqual($this->callback['files'], array(array('src' => array('28616-file', '28616', 'file/28616', '', false), 'filename' => NULL)));
  }

}

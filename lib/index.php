<?php

// PATCH
if (isset($_VAE)) $_VERB = $_VAE;

$_VERB['version'] = 100;

function _verb_should_load() {
  if (file_exists($_SERVER['DOCUMENT_ROOT'] . "/__noverb.php")) return false;
  if (preg_match('/^\/piwik/', $_SERVER['REQUEST_URI'])) return false;
  return true;
}

if (_verb_should_load()) {
  
  /* Store start times */
  $_VERB['start_tick'] = microtime(true);
  if ($_REQUEST['__time']) {
    $_VERB['tick'] = microtime(true);
    $_VERB['ticks'] = array();
  }
  
  /* Phpinfo */
  if ($_REQUEST['__phpinfo']) {
    phpinfo();
    die();
  }
  
  /* Connect to memcached */
  $_VERB['memcached'] = @memcache_pconnect('localhost', 11211);
  
  //$_VERB['verbdbd_port'] = 9092;
  
  /* Bring in the rest of Verb */
  require_once(dirname(__FILE__) . "/general.php");
  require_once(dirname(__FILE__) . "/verb_exception.php");
  _verb_configure_php();
  require_once(dirname(__FILE__) . "/callback.php");
  require_once(dirname(__FILE__) . "/constants.php");
  require_once(dirname(__FILE__) . "/context.php");
  require_once(dirname(__FILE__) . "/func.php");
  require_once(dirname(__FILE__) . "/pages.php");
  require_once(dirname(__FILE__) . "/parse.php");
  require_once(dirname(__FILE__) . "/phpapi.php");
  require_once(dirname(__FILE__) . "/render.php");
  require_once(dirname(__FILE__) . "/rest.php");
  require_once(dirname(__FILE__) . "/store.php");
  require_once(dirname(__FILE__) . "/verbdata.php");
  require_once("/www/verb_thrift/current/php/client.php");
  
  /* Initialize */
  _verb_set_default_config();
  unset($_SESSION['__v:flash_new']);
  if (file_exists($_SERVER['DOCUMENT_ROOT']."/__verb.php") && !$_REQUEST['__verb_local']) require_once($_SERVER['DOCUMENT_ROOT']."/__verb.php");
  
  /* Perform remote actions */
  if ($_REQUEST['clear_login']) _verb_clear_login();
  if ($_REQUEST['set_login']) _verb_set_login();
  if ($_REQUEST['secret_key']) _verb_remote();
  _verb_tick("Verb Startup", true);
  
  /* Dispatch request */
  if ($_REQUEST['__status']) {
    require_once(dirname(__FILE__) . "/status.php");
    _verb_status();
  }
  if ($_REQUEST['__test']) {
    require_once(dirname(__FILE__) . "/test.php");
    _verb_test();
  }
  if ($_REQUEST['__build_constants']) {
    require_once(dirname(__FILE__) . "/constants_build.php");
  }
  
  _verb_load_settings();
  if ($_REQUEST['__v:store_payment_method_ipn']) _verb_store_ipn();  
  _verb_page_check_domain();
  if ($_REQUEST['__page'] || (strstr($_SERVER['SCRIPT_FILENAME'], "lib/pages.php") && strstr($_SERVER['SCRIPT_FILENAME'], "verb"))) _verb_page();
  _verb_page_check_redirects();
  _verb_parse_path();
  if ($_REQUEST['__verb_local']) _verb_local();
    
  if (substr($_SERVER['SCRIPT_FILENAME'], -5) == ".sass") {
    require_once(dirname(__FILE__) . "/haml.php");
    ob_start('_verb_sass_ob');
  } elseif (strstr($_SERVER['SCRIPT_FILENAME'], ".pdf") && !isset($_VERB['skip_pdf'])) {
    require_once(dirname(__FILE__) . "/pdf.php");
    _verb_pdf();  
  } elseif (!$_ENV['TEST']) {  
    /* Normal Request */
    if (!isset($_VERB['filename'])) $_VERB['filename'] = str_replace($_SERVER['DOCUMENT_ROOT'], "", $_SERVER['SCRIPT_FILENAME']);
    _verb_set_cache_key();
    _verb_start_ob();
  }
  
}

?>
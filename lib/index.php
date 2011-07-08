<?php

if ($_VERB) {
  foreach ($_VERB as $k => $v) {
    $_VAE[$k] = $v;
  }
}

$_VAE['version'] = 100;

function _vae_should_load() {
  if (file_exists($_SERVER['DOCUMENT_ROOT'] . "/__novae.php")) return false;
  if (file_exists($_SERVER['DOCUMENT_ROOT'] . "/__noverb.php")) return false;
  if (preg_match('/^\/piwik/', $_SERVER['REQUEST_URI'])) return false;
  return true;
}

if (_vae_should_load()) {
  
  /* Store start times */
  $_VAE['start_tick'] = microtime(true);
  if ($_REQUEST['__time']) {
    $_VAE['tick'] = microtime(true);
    $_VAE['ticks'] = array();
  }
  
  /* Phpinfo */
  if ($_REQUEST['__phpinfo']) {
    phpinfo();
    die();
  }
  
  /* Connect to memcached */
  $_VAE['memcached'] = @memcache_pconnect('localhost', 11211);
  
  //$_VAE['vaedbd_port'] = 9092;
  
  /* Bring in the rest of Vae */
  require_once(dirname(__FILE__) . "/general.php");
  require_once(dirname(__FILE__) . "/vae_exception.php");
  _vae_configure_php();
  require_once(dirname(__FILE__) . "/callback.php");
  require_once(dirname(__FILE__) . "/compat.php");
  require_once(dirname(__FILE__) . "/constants.php");
  require_once(dirname(__FILE__) . "/context.php");
  require_once(dirname(__FILE__) . "/func.php");
  require_once(dirname(__FILE__) . "/pages.php");
  require_once(dirname(__FILE__) . "/parse.php");
  require_once(dirname(__FILE__) . "/phpapi.php");
  require_once(dirname(__FILE__) . "/render.php");
  require_once(dirname(__FILE__) . "/rest.php");
  require_once(dirname(__FILE__) . "/store.php");
  require_once(dirname(__FILE__) . "/thrift.php");
  require_once(dirname(__FILE__) . "/vaedata.php");
  
  /* Initialize */
  _vae_set_default_config();
  unset($_SESSION['__v:flash_new']);
  
  /* Perform remote actions */
  if ($_REQUEST['clear_login']) _vae_clear_login();
  if ($_REQUEST['set_login']) _vae_set_login();
  if ($_REQUEST['secret_key']) _vae_remote();
  _vae_tick("Vae Startup", true);
  
  /* Dispatch request */
  if ($_REQUEST['__status']) {
    require_once(dirname(__FILE__) . "/status.php");
    _vae_status();
  }
  if ($_REQUEST['__test']) {
    require_once(dirname(__FILE__) . "/test.php");
    _vae_test();
  }
  if ($_REQUEST['__session']) {
    session_start();
    var_dump($_SESSION);
    die();
  }
  if ($_REQUEST['__build_constants']) {
    require_once(dirname(__FILE__) . "/constants_build.php");
  }
  
  _vae_load_settings();  
  if ($_REQUEST['__v:store_payment_method_ipn']) _vae_store_ipn();  
  if (file_exists($_SERVER['DOCUMENT_ROOT']."/__vae.php") && !$_REQUEST['__vae_local'] && !$_REQUEST['__verb_local']) require_once($_SERVER['DOCUMENT_ROOT']."/__vae.php");
  if (file_exists($_SERVER['DOCUMENT_ROOT']."/__verb.php") && !$_REQUEST['__vae_local'] && !$_REQUEST['__verb_local']) require_once($_SERVER['DOCUMENT_ROOT']."/__verb.php");  
  
  _vae_page_check_domain();
  if ($_REQUEST['__page'] || (strstr($_SERVER['SCRIPT_FILENAME'], "lib/pages.php") && strstr($_SERVER['SCRIPT_FILENAME'], "vae"))) _vae_page();
  _vae_page_check_redirects();
  _vae_parse_path();
  if ($_REQUEST['__vae_local'] || $_REQUEST['__verb_local']) _vae_local();

  if (substr($_SERVER['SCRIPT_FILENAME'], -5) == ".sass") {
    require_once(dirname(__FILE__) . "/haml.php");
    ob_start('_vae_sass_ob');
  } elseif (strstr($_SERVER['SCRIPT_FILENAME'], ".pdf") && !isset($_VAE['skip_pdf'])) {
    require_once(dirname(__FILE__) . "/pdf.php");
    _vae_pdf();  
  } elseif (!$_ENV['TEST']) {  
    /* Normal Request */
    if (!isset($_VAE['filename'])) $_VAE['filename'] = str_replace($_SERVER['DOCUMENT_ROOT'], "", $_SERVER['SCRIPT_FILENAME']);
    _vae_set_cache_key();
    _vae_start_ob();
  }
  
}

?>

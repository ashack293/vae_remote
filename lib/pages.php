<?php

function _vae_page() {
  global $_VAE;
  if (!strlen($_REQUEST['__page'])) {
    $client = _vae_thrift();
    $client->fixDocRoot($_SERVER['DOCUMENT_ROOT']);
  }
  $a = explode(".", $_REQUEST['__page']);
  _vae_page_find($a[0]);
  _vae_page_check_redirects();
  if ($_REQUEST['__vae_local'] || $_REQUEST['__verb_local']) return _vae_local("/" . $a[0]);
  if ($a[0] == "admin" || $a[0] == "admin/") {
    @Header("Location: https://" . $_VAE['settings']['subdomain'] . ".vaeplatform.com/");
    _vae_die();
  }
  if (vae_staging() && $_REQUEST['__page'] == "robots.txt") {
    _vae_robots_txt();
  }
  _vae_page_404("Could not match URL.");
}

function _vae_page_404($message = "") {
  if (_vae_run_hooks("404", $_REQUEST['__page'])) {
    @header("HTTP/1.1 404 File Not Found");
    @header("Status: 404 File Not Found");
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . "/error_pages/not_found.html")) {
      echo @file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/error_pages/not_found.html");
    } else {
      if (!$_ENV['TEST']) echo "<h1>Not Found</h1><p>The URL you requested does not exist on this website!</p>";
    }
    if (strlen($message) && !$_ENV['TEST']) echo "<!-- " . $message . " -->";
  }
  _vae_die();
}

function _vae_page_check_domain() {
  global $_VAE;
  foreach (array($_SERVER['HTTP_HOST'], str_replace("www.", "", $_SERVER['HTTP_HOST'])) as $try) {
    if (isset($_VAE['settings']['domains'][$try])) {
      $d = $_VAE['settings']['domains'][$try];
      if ($d['home']) {
        if (substr($_SERVER['REQUEST_URI'], 0, strlen($d['home'])) != $d['home']) {
          if ((substr($d['home'], 0, 7) == "http://") || (substr($d['home'], 0, 8) == "https://")) {
            if (substr($d['home'], -1, 1) == "/") $d['home'] = substr($d['home'], 0, strlen($d['home'])-1);
            $nd = str_replace(array("http://", "https://"), "", $d['home']);
            if (isset($_VAE['settings']['domains'][$nd]) || isset($_VAE['settings']['domains'][str_replace("www.", "", $nd)])) {
              $d['home'] .= $_SERVER['REQUEST_URI'];
            }
          }
          @Header("Location: " . $d['home'], true, 301);
          _vae_die();
        }
      }
    }
  }
}

function _vae_page_check_redirects() {  
  global $_VAE;
  $e = explode("?", $_SERVER['REQUEST_URI'], 2);
  $page = substr($_SERVER['REQUEST_URI'], 1);
  $page_without_query_string = substr($e[0], 1);
  $http = "http://" . $_SERVER['HTTP_HOST'] . "/";
  foreach (array($page, $page_without_query_string, $http . $page, $http . $page_without_query_string) as $try) {
    if (isset($_VAE['settings']['redirects'][$try])) {
      $new_url = $_VAE['settings']['redirects'][$try];
      foreach (explode("&", $e[1]) as $param) {
        list($k, $v) = explode("=", $param, 2);
        if (!strstr($try, $k . "=") && strstr($param, "=")) {
          $qs .= "&" . $param;
        }
      }
      if (strlen($qs)) $new_url .= (strstr($new_url, "?") ? $qs : "?" . substr($qs, 1));
      _vae_page_redirect_to($new_url);
    }
  }
}

function _vae_page_find($page) {
  global $_VAE;
  if (!preg_match("/^([-\\/a-zA-Z0-9_.]+)$/", $page)) return false;
  $page = preg_replace_callback("/\/locale\/([a-z]*)/", "_vae_page_locale_callback", $page);
  $page = preg_replace_callback("/\/([a-z0-9]*_)?page\/([0-9]*|all)/", "_vae_page_page_number_callback", $page);
  $_SERVER['PHP_SELF'] = "/" . $page;
  $_REQUEST['path'] = (isset($_SERVER['PATH_INFO']) ? preg_replace("/\/locale\/([a-z]*)/", "", preg_replace("/\/([a-z0-9]*_)?page\/([0-9]*)/", "", substr($_SERVER['PATH_INFO'], 1))) : "");
  if (!strlen($page)) return false;
  $cached = memcache_get($_VAE['memcached'], $_VAE['global_cache_key'] . "path2" . $page);
  if (is_array($cached)) {
    if ($cached['id']) {
      $_REQUEST['id'] = $cached['id'];
      _vae_page_run($page, $cached['template'], $cached['id'], true);
    }
  } else {
    if (!_vae_prod() && !file_exists($_VAE['config']['data_path'] . "feed.xml") && !isset($_VAE['config']['content_subdomain'])) return false;
    while (strstr($page, "//")) $page = str_replace("//", "/", $page);
    if (substr($page, 0, 1) == "/") $page = substr($page, 1);
    $split = explode("/", $page);
    for ($i = count($split); $i > 0; $i--) {
      if ($split[$i-1] == "") continue;
      $p = implode("/", array_slice($split, 0, $i));
      if ($context = _vae_fetch("@permalink/" . $p)) {
        $_REQUEST['id'] = $context->id();
        $other_page_to_render = implode("/", array_slice($split, $i));
        _vae_page_run($page, (strlen($other_page_to_render) ? $other_page_to_render : $context->structure()->permalink), $context);
        return;
      } 
    }
    memcache_set($_VAE['memcached'], $_VAE['global_cache_key'] . "path2" . $page, array());
  }
  return false;
}

function _vae_page_locale_callback($matches) {
  $_REQUEST['locale'] = $matches[1];
  $_GET['locale'] = $matches[1];
  return "";
}

function _vae_page_page_number_callback($matches) {
  $_REQUEST[$matches[1] . 'page'] = $matches[2];
  return "";
}

function _vae_page_redirect_to($url) {
  if (substr($url, 0, 5) != "http:" && substr($url, 0, 6) != "https:") $url = "/" . $url;
  @Header("Location: " . $url, true, 301);
  _vae_die();
}

function _vae_page_run($page, $template, $context, $from_cache = false) {
  global $_VAE;
  $_SERVER['PHP_SELF'] = "/" . $page;
  $_VAE['context'] = $context;
  if ($_REQUEST['__vae_local'] || $_REQUEST['__verb_local']) return _vae_local($template);
  list($filename, $vaeml) = _vae_src($template);
  if (!strlen($vaeml)) return _vae_page_404("Could not find Permalink HTML page.  We were looking for $template or $template.html or $template.haml or $template.php.");
  if ($from_cache == false) memcache_set($_VAE['memcached'], $_VAE['global_cache_key'] . "path2" . $page, array('id' => $_REQUEST['id'], 'template' => (string)$template));
  $_VAE['filename'] = $filename;
  _vae_set_cache_key();
  if ($_ENV['TEST']) return $filename;
  ob_start("_vae_handleob");
  require_once($_SERVER['DOCUMENT_ROOT'] . "/" . $filename);
  _vae_die();
}

function _vae_robots_txt() {
  Header("Content-type: text/plain");
  echo "User-agent: *\r\nDisallow: /";
  _vae_die();
}

?>
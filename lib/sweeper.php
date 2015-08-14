<?php

$dir = dirname(__FILE__);
require($dir . "/general.php");
require($dir . "/vaedata.php");

if (!strlen($argv[1])) die("No subdomain provided");
if (!is_numeric($argv[2])) die("No fsnum provided");

$fsnum = $argv[2];

if (!file_exists("/mnt/vae-fs-$fsnum/vhosts/" . $argv[1] . ".verb")) die("Bad subdomain provided.");
$_VAE['settings']['subdomain'] = $argv[1];
$_VAE['config']['data_path'] = "/mnt/vae-fs-$fsnum/vhosts/" . $argv[1] . ".verb/data/";

function _vae_sweep_data_dir() {
  global $_VAE;
  foreach (_vae_long_term_cache_sweeper_info() as $k => $v) {
    $save[$v] = true;
    $filename = $_VAE['config']['data_path'].$v;
    if (!file_exists($filename)) {
      echo "File Missing $v (key: $k)\n";
      _vae_long_term_cache_delete($k);
    } else {
      echo "Found file $v (key: $k)\n";
    }
  }
  $dh = opendir($_VAE['config']['data_path']);
  while (($file = readdir($dh)) !== false) {
    if (in_array($file, array(".", "..", "feed.xml", "settings.php", "uploads"))) continue;
    if (isset($save[$file])) continue;
    $filename = $_VAE['config']['data_path'] . $file;
    $fileage = time() - filemtime($filename);
    if ($fileage < 3*86400) continue;
    echo "deleting $file\n";
    unlink($filename);
  }
  touch($_VAE['config']['data_path'] . "/feed.xml"); // invalidate caches
  echo "done\n";
  flush();
}

_vae_sweep_data_dir();

?>

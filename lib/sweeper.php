<?php

$dir = "/app/vae-remote/deploy/current/lib";
require($dir . "/general.php");

if (!strlen($argv[1])) die("No subdomain provided");
var_dump($argv);
if (!is_numeric($argv[2])) die("No fsnum provided");

$fsnum = $argv[2];

if (!file_exists("/mnt/vae-fs-$fsnum/vhosts/" . $argv[1] . ".verb")) die("Bad subdomain provided.");
$_VAE['settings']['subdomain'] = $argv[1];
$_VAE['config']['data_path'] = "/mnt/vae-fs-$fsnum/vhosts/" . $argv[1] . ".verb/data/";
_vae_sweep_data_dir();

?>

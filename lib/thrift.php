<?php

$paths = array('/app/vaedb/deploy/current/php/vendor', dirname(__FILE__) . "/../tests/dependencies/vae_thrift/php/vendor", dirname(__FILE__) . "/../../vae_thrift/php/vendor");
foreach ($paths as $path) {
  if (file_exists($path)) {
    $GLOBALS['THRIFT_ROOT'] = $path;
    break;
  }
}
require_once $THRIFT_ROOT . '/Thrift/ClassLoader/ThriftClassLoader.php';
$loader = new Thrift\ClassLoader\ThriftClassLoader();
$loader->registerNamespace('Thrift', $THRIFT_ROOT);
$loader->registerDefinition('Thrift', $THRIFT_ROOT . '/packages');
$loader->register();

require_once $GLOBALS['THRIFT_ROOT'].'/../../gen-php/Thrift/VaeDb.php';
require_once $GLOBALS['THRIFT_ROOT'].'/../../gen-php/Thrift/VaeRubyd.php';
require_once $GLOBALS['THRIFT_ROOT'].'/../../gen-php/Thrift/Types.php';

use Thrift\Transport\TPhpStream;
use Thrift\Protocol\TBinaryProtocol;

function _vae_thrift($port = 9090) {
  global $_VAE;
  if ($_VAE['vaerubyd']) return $_VAE['vaerubyd'];
  $_VAE['vaerubyd'] = _vae_thrift_open("VaeRubydClient", $port);
  return $_VAE['vaerubyd'];
};

function _vae_dbd($port = 9091) {
  global $_VAE;
  if ($_VAE['vaedbd_port']) $port = $_VAE['vaedbd_port'];
  if ($_VAE['vaedbd']) return $_VAE['vaedbd'];
  $_VAE['vaedbd'] = _vae_thrift_open("VaeDbClient", $port);
  return $_VAE['vaedbd'];
};

function _vae_thrift_open($client_class, $port) {
  global $_VAE;

  $backends = $_VAE['vaedbd_backends'];
  if (!$backends) {
    throw new VaeException("", "No VaeDb backends configured");
  }

  $i = 0;
  $shift = hexdec(substr(md5($_VAE['settings']['subdomain']), 0, 15));
  while ($i < 4) {
    $_VAE['thrift_host'] = $backends[($shift + $i) % count($backends)];
    try {
      _vae_tick("Using " . $_VAE['thrift_host'] . " as VaeDB backend.");
      $socket = new Thrift\Transport\TSocket($_VAE['thrift_host'], $port);
      $socket->setRecvTimeout(30000);
      $transport = new Thrift\Transport\TBufferedTransport($socket, 1024, 1024);
      $protocol = new Thrift\Protocol\TBinaryProtocol($transport);
      if ($client_class == "VaeDbClient") {
        $client = new Thrift\VaeDbClient($protocol);
      } else {
        $client = new Thrift\VaeRubydClient($protocol);
      }
      $transport->open();
      return $client;
    } catch (Thrift\Exception\TException $tx) {
    }

    if (!$backends) { $backends = $_VAE['vaedbd_backends']; }
    usleep(10000);
    $i++;
  }

  throw new VaeException("", $tx->getMessage());
}

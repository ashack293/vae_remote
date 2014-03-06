<?php

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

function _vae_vaedb_backends($subdomain) {
  global $_VAE;
  if (array_key_exists($subdomain, $_VAE['vaedbd_overrides']))
      return $_VAE['vaedbd_overrides'][$subdomain];
  return $_VAE['vaedbd_backends'];
};

function _vae_thrift_open($client_class, $port) {
  global $_VAE;

  $GLOBALS['THRIFT_ROOT'] = '/app/vaedb/deploy/current/php/vendor/thrift';
  require_once $GLOBALS['THRIFT_ROOT'].'/Thrift.php';
  require_once $GLOBALS['THRIFT_ROOT'].'/protocol/TBinaryProtocol.php';
  require_once $GLOBALS['THRIFT_ROOT'].'/transport/TSocket.php';
  require_once $GLOBALS['THRIFT_ROOT'].'/transport/TBufferedTransport.php';
  require_once $GLOBALS['THRIFT_ROOT'].'/../../../gen-php/vae/VaeDb.php';
  require_once $GLOBALS['THRIFT_ROOT'].'/../../../gen-php/vae/VaeRubyd.php';
  require_once $GLOBALS['THRIFT_ROOT'].'/../../../gen-php/vae/vae_types.php';

  $subdomain = $_VAE['settings']['subdomain'];
  if(!($backends = _vae_vaedb_backends($subdomain))) {
    throw new VaeException("", "No VaeDb backends configured");
  }
  
  $i = 0;
  $shift = hexdec(substr(md5($subdomain),0,15));
  while ($i < 4) {
    $_VAE['thrift_host'] = $backends[($shift + $i) % count($backends)];
    try {
      _vae_tick("Using " . $_VAE['thrift_host'] . " as VaeDB backend.");
      $socket = new TSocket($_VAE['thrift_host'], $port);
      $socket->setRecvTimeout(30000);
      $transport = new TBufferedTransport($socket, 1024, 1024);
      $protocol = new TBinaryProtocol($transport);
      $client = new $client_class($protocol);
      $transport->open();
      return $client;
    } catch (TException $tx) {
    }

    if(!$backends) { $backends = _vae_vaedb_backends($subdomain); }
    sleep(1);
    $i++;
  }

  throw new VaeException("", $tx->getMessage());
}

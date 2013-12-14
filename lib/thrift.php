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

function _vae_vaedb_backends() {
  global $_VAE;

  $vaedb_backends = array();
  foreach($_VAE['vaedbd_backend_tiers'] as $tier) {
    shuffle($tier);
    $vaedb_backends = array_merge($vaedb_backends, $tier);
  }

  return $vaedb_backends;
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

  if(!($backends = _vae_vaedb_backends())) {
    throw new VaeException("", "No VaeDb backends configured");
  }
  
  $i = 0;
  while ($i < 4) {
    $_VAE['thrift_host'] = array_shift($backends);
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
        _vae_tick(' TException: '.$tx->why.' -- '.$tx->getMessage());
    }

    if(!$backends) { $backends = _vae_vaedb_backends(); }
    sleep(1);
    $i++;
  }

  throw new VaeException("", $tx->getMessage());
}

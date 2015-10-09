<?php

class VaeContext implements ArrayAccess, Countable {

  private $___context;
  private $___createInfo = array();
  private $___data = array();
  private $___dataSource = null;
  private $___formId = null;
  private $___locals = array();
  private $___mainContext = null;
  private $___query;
  private $___queries = array();
  private $___singleData;
  private $___structure = null;

  public function __construct($context = null, &$query = null, $singleData = null, $dataSource = "vaedb") {
    $this->___context = $context;
    $this->___query = $query;
    $this->___singleData = $singleData;
    if ($context && get_class($context) == "Thrift\\VaeDbContext" && $context->dataMap) {
      $this->___addData($context->dataMap);
      if ($context->dataSource) $dataSource = $context->dataSource;
    }
    $this->___dataSource = $dataSource;
  }

  public function ___addCreateInfo($query, $createInfo) {
    $this->___createInfo[$query] = $createInfo;
  }

  public function ___addData($data, $formId = null, $clone = false) {
    if (count($data)) {
      foreach ($data as $k => $v) {
        $this->___data[$k] = $v;
      }
    }
    if ($formId) $this->___formId = $formId;
    if ($clone) {
      VaeQuery::___openClient();
      $this->___context = new Thrift\VaeDbContext(array('id' => $data['id']));
    }
  }

  public function ___addQuery($query) {
    $q = $query->___getQuery();
    if (!isset($this->___queries[$q])) $this->___queries[$q] = array();
    $this->___queries[$q][] = $query;
  }

  public function ___addStructure($structure) {
    $this->___structure = $structure;
  }

  public function __call($name, $arguments) {
    return $this->get($name, array_shift($arguments));
  }

  private function ___findQueryFromCache($query, $options, $useDataCache = true) {
    if ($useDataCache && !count($options) && isset($this->___data[$query])) {
      $v = $this->___data[$query];
      if ($v != "(collection)") return new self($this, $query, $v, $this->___dataSource);
    }
    if (isset($this->___queries[$query])) {
      foreach ($this->___queries[$query] as $q) {
        if ($q->___getOptions() == $options) return $q;
      }
    }
    return false;
  }

  private function ___proxyObject() {
    if (strlen($this->___singleData) && $this->___dataSource == "vaedb") {
      return $this->___context->get($this->___query, null, false);
    }
    return false;
  }

  public function __get($name) {
    return $this->get($name);
  }

  public function __invoke($name) {
    return $this->get($name);
  }

  public function __isset($name) {
    return $this->offsetExists($name);
  }

  public function __set($name, $value) {
    $this->offsetSet($name, $value);
  }

  public function __toString() {
    if (strlen($this->___singleData)) return (string)$this->___singleData;
    if ($this->___context && (get_class($this->___context) != "Thrift\\VaeDbContext")) return "";
    if (strlen($this->___context->data)) return $this->___context->data;
    return "";
  }

  public function __unset($name) {
    $this->offsetUnset($name);
  }

  public function collection() {
    return false;
  }

  public function count() {
    return 1;
  }

  public function data() {
    if ($this->___data) return $this->___data;
    if (strlen($this->___singleData) || !$this->___query) return false;
    $this->___query->___retrieveData();
    return $this->___data;
  }

  public function debug() {
    return $this->data();
  }

  public function forCreating($query = null) {
    if ($ret = $this->___createInfo[$query]) return $ret;
    if (is_object($this->___query)) {
      $this->___query->___retrieveCreateInfo($query);
    } else {
      VaeQuery::___createInfo($query, $this);
    }
    return $this->___createInfo[$query];
  }

  public function found() {
    return $this;
  }

  public function formId() {
    if (is_numeric($this->___formId)) return $this->___formId;
    return $this->id();
  }

  public function get($query = null, $options = null, $useDataCache = true) {
    $raiseErrors = true;
    if ($query == "collection") return $this->collection();
    if ($query == "count") return $this->count();
    if ($query == "data") return $this->data();
    if ($query == "debug") return $this->debug();
    if ($query == "found") return $this->found();
    if ($query == "formId") return $this->formId();
    if ($query == "id") return $this->id();
    if ($query == "parent") return $this->parent();
    if ($query == "permalink") return $this->permalink();
    if ($query == "permalinkOrId") return $this->permalinkOrId();
    if ($query == "structure") return $this->structure();
    if ($query == "totalMatches") return $this->totalMatches();
    if ($query == "type") return $this->type();
    if (substr($query, 0, 3) == "___") throw new VaeException("", "get() called on an internal property");
    if (substr($query, 0, 1) == "@") {
      $query = substr($query, 1);
      $raiseErrors = false;
    }
    if (isset($this->___locals[$query])) return $this->___locals[$query];
    list($path, $query) = _vaeql_query($query, $this, $options, $raiseErrors);
    unset($options['assume_numbers']);
    if (!$path) return $query;
    if ($ret = $this->___findQueryFromCache($query, $options, $useDataCache)) return $ret;
    if ($this->___dataSource == "sql") {
      return VaeQuery::___factory($query, $options);
    } elseif (is_object($this->___query)) {
      $this->___query->___nestedQuery($query, $options, $raiseErrors);
    } elseif ($this->___context && $this->___context->id) {
      if (!preg_match("/^[0-9]/", $query) && substr($query, 0, 1) != "/") $query = $this->___context->id . "/" . $query;
      return VaeQuery::___factory($query, $options, true);
    } elseif (preg_match("/^[0-9]/", $query)) {
      return VaeQuery::___factory($query, $options, true);
    }
    return $this->___findQueryFromCache($query, $options, $useDataCache);
  }

  public function id() {
    return (($id = $this->___context->id) == 0 ? null : $id);
  }

  public function offsetExists($name) {
    $g = $this->get($name);
    return ($g ? (is_object($g) ? $g->found() : true) : false);
  }

  public function offsetGet($name) {
    return $this->get($name);
  }

  public function offsetSet($name, $value) {
    $this->___locals[$name] = $value;
  }

  public function offsetUnset($name) {
    unset($this->___locals[$name]);
  }

  public function parent() {
    return $this->get("..");
  }

  public function permalink($leadingSlash = true) {
    $p = $this->___context->permalink;
    if (strlen($p)) return (($leadingSlash) ? "/" : "") . $p;
    if (is_numeric($this->___formId)) return vae($this->___context->id)->permalink($leadingSlash);
    return "";
  }

  public function permalinkOrId() {
    if (strlen($p = $this->permalink())) return $p;
    return $this->id();
  }

  public function structure() {
    if (strlen($this->___singleData)) {
      if ($p = $this->___proxyObject()) return $p->structure();
      return false;
    }
    if ($this->___structure) return $this->___structure;
    if (!$this->___query) return false;
    $this->___query->___retrieveStructures();
    return $this->___structure;
  }

  public function totalMatches() {
    return 1;
  }

  public function type() {
    if (strlen($this->___singleData)) {
      if ($p = $this->___proxyObject()) return $p->type();
      return "TextItem";
    }
    return $this->___context->type;
  }

  public static function ___fromArray($array, $formId = null, $clone = false) {
    $context = new self();
    $context->___addData($array, $formId, $clone);
    return $context;
  }

}

class VaeQuery implements Iterator, ArrayAccess, Countable {

  private $___contexts = array();
  private $___first;
  private $___next = null;
  private $___options;
  private $___query;
  private $___raiseErrors = true;
  private $___responseId = null;
  private $___totalMatches = null;

  private static $client = null;
  public static $sessionId = null;
  public static $generation = null;

  private function __construct($query = null, $options = null, $responseId = null, $first = null, $raiseErrors = true) {
    if ($first == null) $first = $this;
    $this->___first = $first;
    $this->___options = $options;
    $this->___query = $query;
    $this->___raiseErrors = $raiseErrors;
    $this->___responseId = $responseId;
    $this->___sanitize_options();
  }

  public function ___addContext($context) {
    $this->___contexts[] = new VaeContext($context, $this);
  }

  public function ___addContextFromArray($array, $formId = null, $clone = false) {
    $context = new VaeContext();
    $context->___addData($array, $formId, $clone);
    $this->___contexts[] = $context;
  }

  public function ___addTotalMatches($total) {
    $this->___totalMatches = $total;
  }

  public function __call($name, $arguments) {
    if ($this->___kicker()) return "";
    return $this->current()->get($name, array_shift($arguments));
  }

  private function ___executeQuery($responseId, $query, $options, $raiseErrors = false) {
    global $_VAE;
    try {
      if (substr($query, 0, 1) == "~") {
        self::___openClient();
        $q = new VaeSqlQuery($responseId, substr($query, 1), $options);
        return $q->toVaeDbResponse();
      }
      for ($i = 0; $i < 10; $i++) {
        $start = microtime(true);
        $result = null;
        try {
          if (!self::$sessionId) self::___openSession();
          if ($options == null) $options = array();
          $result = self::$client->get(self::$sessionId, $responseId, $query, $options);
        } catch (TSocketException $e) {
          self::___resetClient();
        }
        if ($result) {
          return $result;
        }
      }
      throw new VaeException("", "Could not connect to VaeDBd to get()");
    } catch (Thrift\VaeDbInternalError $ie) {
      if ($raiseErrors) throw new VaeException("", "VaeDB (" . $_VAE['thrift_host'] . ") Internal Error: " . $ie->getMessage());
      return false;
    } catch (Thrift\VaeDbQueryError $qe) {
      if ($raiseErrors) throw new VaeException("VaeQL Query Error: " . $qe->getMessage());
      return false;
    }
  }

  public function ___first() {
    return $this->___first;
  }

  public function __get($name) {
    return $this->get($name);
  }

  public function ___getOptions() {
    return $this->___options;
  }

  public function ___getQuery() {
    return $this->___query;
  }

  public function __invoke($name) {
    if ($this->___kicker()) return "";
    return $this->current()->get($name);
  }

  public function __isset($name) {
    return $this->offsetExists($name);
  }

  public function ___kicker() {
    $this->___run();
    if (!$this->___contexts) return true;
    if (!$this->valid()) $this->rewind();
    return false;
  }

  public function ___nestedQuery($query = null, $options = null, $raiseErrors = false) {
    if ($this->___responseId < 0) return;
    $response = $this->___executeQuery($this->___responseId, $query, $options, $raiseErrors);
    if (!$response) return false;
    $iter = new VaeQueryIterator($this);
    $firstDataQuery = $prevDataQuery = null;
    foreach ($response->contexts as $queryContext) {
      if ($queryContext->totalItems) {
        $dataQuery = new VaeQuery($query, $options, $response->id, $firstDataQuery);
        if ($firstDataQuery == null) $firstDataQuery = $dataQuery;
        if ($prevDataQuery) $prevDataQuery->___setNext($dataQuery);
        $prevDataQuery = $dataQuery;
        $dataQuery->___addTotalMatches($queryContext->totalItems);
        foreach ($queryContext->contexts as $responseContext) {
          $dataQuery->___addContext($responseContext);
        }
      } else {
        $dataQuery = new VaeQuery($query, $options, $response->id, null);
      }
      $dataContext = $iter->current();
      if ($dataContext) {
        $dataContext->___addQuery($dataQuery);
      }
      $iter->next();
    }
    //if ($prevDataQuery && $prevDataQuery->collection()) $prevDataQuery->___retrieveData(); // TODO: determine if this is a speedup
  }

  public function ___newContextIterator() {
    return new ArrayIterator($this->___contexts);
  }

  public function ___next() {
    return $this->___next;
  }

  public function ___retrieveCreateInfo($query, $contextForResponse = null) {
    if (!self::$sessionId) self::___openSession();
    try {
      $response = self::$client->createInfo(self::$sessionId, $this->___responseId, $query);
    } catch (Thrift\VaeDbQueryError $qe) {
      throw new VaeException("VaeQL Query Error: " . $qe->getMessage());
    }
    $iter = new VaeQueryIterator($this);
    foreach ($response->contexts as $createInfo) {
      if ($contextForResponse) {
        $dataContext = $contextForResponse;
      } else {
        $dataContext = $iter->current();
      }
      $dataContext->___addCreateInfo($query, $createInfo);
      $iter->next();
    }
  }

  public function ___retrieveData() {
    global $_VAE;
    $response = null;
    for ($i = 0; $i < 5; $i++) {
      try {
        $response = self::$client->data(self::$sessionId, $this->___responseId);
      } catch (TSocketException $e) {
        self::___resetClient();
      }
    }
    if (!$response) {
      throw new VaeException("", "Could not connect to VaeDBd to data()");
    }
    $iter = new VaeQueryIterator($this);
    foreach ($response->contexts as $data) {
      $dataContext = $iter->current();
      if ($dataContext) {
        $dataContext->___addData($data->data);
      } else {
        throw new VaeException("", "vaedb returned more contexts than I was expecting?!");
      }
      $iter->next();
    }
  }

  public function ___retrieveStructures() {
    $response = self::$client->structure(self::$sessionId, $this->___responseId);
    $iter = new VaeQueryIterator($this);
    foreach ($response->contexts as $structure) {
      $dataContext = $iter->current();
      $dataContext->___addStructure($structure);
      $iter->next();
    }
  }

  private function ___run() {
    if ($this->___responseId) return;
    $response = $this->___executeQuery(0, $this->___query, $this->___options, $this->___raiseErrors);
    if ($response) {
      $this->___responseId = $response->id;
      foreach ($response->contexts as $queryContext) {
        $this->___addTotalMatches($queryContext->totalItems);
        foreach ($queryContext->contexts as $context) {
          $this->___addContext($context);
        }
      }
      //if ($this->collection()) $this->___retrieveData(); // TODO: determine if this is a speedup
    }
  }

  public function __set($name, $value) {
    $this->offsetSet($name, $value);
  }

  public function ___setNext($next) {
    $this->___next = $next;
  }

  public function __toString() {
    if ($this->___kicker()) return "";
    $c = $this->current();
    return ($c ? $c->__toString() : "");
  }

  public function __unset($name) {
    $this->offsetUnset($name);
  }

  public function collection() {
    $t = $this->type();
    return ($t == "Collection" || $t == "NestedCollection");
  }

  public function count() {
    $this->___kicker();
    return count($this->___contexts);
  }

  public function current() {
    $this->___run();
    return current($this->___contexts);
  }

  public function data() {
    $c = $this->current();
    return ($c ? $c->data() : array());
  }

  public function debug() {
    return $this->data();
  }

  public function forCreating($query = null) {
    $c = $this->current();
    return ($c ? $c->forCreating($query) : false);
  }

  public function found() {
    return ($this->totalMatches() > 0 ? $this : false);
  }

  public function get($query, $options = null) {
    if ($this->___kicker()) return "";
    if ($query == "collection") return $this->collection();
    if ($query == "count") return $this->count();
    if ($query == "found") return $this->found();
    if ($query == "totalMatches") return $this->totalMatches();
    return $this->current()->get($query, $options);
  }

  public function key() {
    $this->___run();
    $c = $this->current();
    return ($c ? $c->id() : 0);
  }

  public function next() {
    $this->___run();
    return next($this->___contexts);
  }

  public function offsetExists($name) {
    $g = $this->get($name);
    return ($g ? $g->found() : false);
  }

  public function offsetGet($name) {
    return $this->get($name);
  }

  public function offsetSet($name, $value) {
    $this->current()->offsetSet($name, $value);
  }

  public function offsetUnset($name) {
    $this->current()->offsetUnset($name);
  }

  public function permalink($leadingSlash = true) {
    $c = $this->current();
    return ($c ? $c->permalink($leadingSlash) : false);
  }

  public function permalinkOrId() {
    $c = $this->current();
    return ($c ? $c->permalinkOrId() : false);
  }

  public function rewind() {
    reset($this->___contexts);
  }

  // Just sanitize the paginate option to start with, and we'll return
  // to sanitize other options after discussing with Kevin what is safe
  // and unsafe to cast to a string.  If it turns out that it's always
  // safe and desired to cast all options to strings, then we'll do
  // that. --MHB
  private function ___sanitize_options() {
    if ($this->___options != null) {
      if (isset($this->___options["paginate"]))
        $this->___options["paginate"] = (string) $this->___options["paginate"];
    }
  }

  public function structure() {
    $c = $this->current();
    return ($c ? $c->structure() : false);
  }

  public function toArray() {
    $array = array();
    foreach ($this as $r) {
      $array[] = $r;
    }
    return $array;
  }

  public function totalMatches() {
    $this->___kicker();
    return $this->___totalMatches;
  }

  public function type() {
    $c = $this->current();
    return ($c ? $c->type() : "");
  }

  public function valid() {
    return ($this->current() !== false);
  }

  public static function ___createInfo($query, $contextForResponse) {
    $q = new self();
    $q->___retrieveCreateInfo($query, $contextForResponse);
  }

  public static function ___factory($query = null, $options = null, $already_transformed = false) {
    if ($query == null) {
      return new VaeContext();
    } else {
      if (substr($query, 0, 1) == "@") {
        $query = substr($query, 1);
        $raiseErrors = false;
      } else {
        $raiseErrors = true;
      }
      if (!$already_transformed) {
        list($path, $query) = _vaeql_query($query, $this, $options, $raiseErrors);
        if (!$path) return $query;
      }
      return new self($query, $options, null, null, $raiseErrors);
    }
  }

  public static function ___fromArray($array, $clone = false) {
    $q = new self(null, null, -1);
    if (is_array($array) && count($array)) {
      foreach ($array as $id => $r) {
        $q->___addContextFromArray($r, $id, $clone);
      }
    }
    return $q;
  }

  private static function ___getSubdomain() {
    global $_VAE;
    if ($_VAE['config']['content_subdomain']) {
      return $_VAE['config']['content_subdomain'];
    } elseif (preg_match('/\/([a-z0-9]*)\.verb\//', $_SERVER['DOCUMENT_ROOT'], $matches)) {
      return $matches[1];
    } elseif (function_exists('_vae_test_xml_path')) {
      return _vae_test_xml_path();
    } else {
      return $_VAE['settings']['subdomain'];
    }
  }

  public static function ___openClient() {
    if (!self::$client) self::$client = _vae_dbd();
  }

  public static function ___openSession() {
    global $_VAE;
    self::___openClient();
    for ($i = 0; $i < 5; $i++) {
      try {
        $ret = self::$client->openSession2(self::___getSubdomain(), $_VAE['config']['secret_key'], vae_staging(), mt_rand());
        self::$sessionId = $ret->session_id;
        self::$generation = $ret->generation;
        return;
      } catch (TSocketException $e) {
        self::___resetClient();
      }
    }
    throw new VaeException("", "Could not connect to VaeDBd to openSession");
  }

  public static function ___shortTermCacheGet($key, $flags) {
    if (!self::$sessionId) self::___openSession();
    $ret = self::$client->shortTermCacheGet(self::$sessionId, _vae_safe_key($key), $flags);
    return unserialize($ret);
  }

  public static function ___shortTermCacheSet($key, $value, $flags, $expires) {
    if (!self::$sessionId) self::___openSession();
    return self::$client->shortTermCacheSet(self::$sessionId, _vae_safe_key($key), serialize($value), $flags, $expires);
  }

  public static function ___shortTermCacheDelete($key) {
    if (!self::$sessionId) self::___openSession();
    return self::$client->shortTermCacheDelete(self::$sessionId, _vae_safe_key($key));
  }

  public static function ___longTermCacheGet($key, $renew, $useShortTermCache) {
    if (!self::$sessionId) self::___openSession();
    return self::$client->longTermCacheGet(self::$sessionId, _vae_safe_key($key), $renew, $useShortTermCache);
  }

  public static function ___longTermCacheSet($key, $value, $expireInterval, $isFilename) {
    if (!self::$sessionId) self::___openSession();
    return self::$client->longTermCacheSet(self::$sessionId, _vae_safe_key($key), $value, $expireInterval, $isFilename);
  }

  public static function ___longTermCacheDelete($key) {
    if (!self::$sessionId) self::___openSession();
    return self::$client->longTermCacheDelete(self::$sessionId, _vae_safe_key($key));
  }

  public static function ___longTermCacheEmpty() {
    if (!self::$sessionId) self::___openSession();
    return self::$client->longTermCacheEmpty(self::$sessionId);
  }

  public static function ___longTermCacheSweeperInfo() {
    if (!self::$sessionId) self::___openSession();
    return self::$client->longTermCacheSweeperInfo(self::$sessionId)->data;
  }

  public static function ___sessionCacheGet($key) {
    if (!self::$sessionId) self::___openSession();
    return self::$client->sessionCacheGet(self::$sessionId, _vae_safe_key($key));
  }

  public static function ___sessionCacheSet($key, $value) {
    if (!self::$sessionId) self::___openSession();
    return self::$client->sessionCacheSet(self::$sessionId, _vae_safe_key($key), $value);
  }

  public static function ___sessionCacheDelete($key) {
    if (!self::$sessionId) self::___openSession();
    return self::$client->sessionCacheDelete(self::$sessionId, _vae_safe_key($key));
  }

  public static function ___sitewideLock() {
    if (!self::$sessionId) self::___openSession();
    return self::$client->sitewideLock(self::$sessionId);
  }

  public static function ___sitewideUnlock() {
    if (!self::$sessionId) self::___openSession();
    return self::$client->sitewideUnlock(self::$sessionId);
  }

  public static function ___resetClient() {
    global $_VAE;
    sleep(2);
    unset($_VAE['vaedbd']);
    self::$client = null;
    self::___openClient();
  }

  public static function ___resetSite() {
    global $_VAE;
    self::___openClient();
    self::$sessionId = self::$client->resetSite(self::___getSubdomain(), $_VAE['config']['secret_key']);
    self::___resetClient();
  }

}

class VaeQueryIterator implements Iterator {

  private $obj;

  public function __construct($obj) {
    $this->obj = $obj->___first();
    $this->setIterator();
  }

  public function current() {
    return $this->iter->current();
  }

  public function key() {
    return $this->iter->key();
  }

  public function next() {
    $next = $this->iter->next();
    while (!$this->iter->valid() && ($nextObj = $this->obj->___next())) {
      $this->obj = $nextObj;
      $this->setIterator();
      $next = $this->iter->current();
    }
    return $next;
  }

  public function rewind() {
    $this->obj = $this->obj->___first();
    $this->setIterator();
  }

  private function setIterator() {
    $this->iter = $this->obj->___newContextIterator();
  }

  public function valid() {
    return $this->iter->valid();
  }

}

class VaeSqlQuery {

  private static $connection = null;
  private static $connectionInfo = null;
  private static $responseId = null;
  private static $responses = null;

  private $columns = null;
  private $limited = false;
  private $mysqlResult = null;
  private $query = null;
  private $table = null;

  public function __construct($responseId, $query, $options) {
    $this->response = new VaeDbResponse();
    self::$responseId++;
    $this->response->id = self::$responseId;
    $context = new VaeDbResponseForContext();
    $this->connect();
    $this->buildQuery($query, $options);
    $this->executeQuery();
    $context->totalItems = $this->totalResults();
    $context->contexts = array();
    while ($row = mysql_fetch_assoc($this->mysqlResult)) {
      $ctxt = new VaeDbContext();
      if ($row['id']) $ctxt->id = $row['id'];
      $ctxt->dataSource = "sql";
      $ctxt->dataMap = $row;
      $context->contexts[] = $ctxt;
    }
    $this->response->contexts = array();
    if (false && $responseId) {
      if (!isset(self::$responses[$responseId])) {
        throw new Thrift\VaeDbInternalError("Invalid responseId");
      }
      $responseNumber = count(self::$responses[$responseId]->contexts);
    } else {
      $responseNumber = 1;
    }
    for ($i = 0; $i < $responseNumber; $i++) {
      $this->response->contexts[] = $context;
    }
    self::$responses[self::$responseId] = $this->response;
  }

  private function buildQuery($query, $options) {
    if (substr($query, 0, 7) == "SELECT ") {
      $this->table = false;
    } else {
      while (preg_match('/\[([^\]]*)\]/', $query, $matches)) {
        $query = str_replace($matches[0], "", $query);
        $where .= (strlen($where) ? " AND " : "") . $matches[1];
      }
      $this->table = $query;
      $query = "SELECT * FROM " . $query;
    }
    if ($options['order']) {
      $options['order'] = str_replace("REVERSE(", "DESC(", $options['order']);
      if ($options['order'] == "DESC()") {
        $order = "id DESC";
      } else {
        foreach (explode(",", $options['order']) as $o) {
          if (strlen($order)) $order .= ", ";
          if (preg_match('/DESC\(([^)]*)\)/i', $o, $regs)) {
            $o = $regs[1];
            $order .= $o . " DESC";
          } else {
            $order .= $o . ((strstr($o, " ASC") || strstr($o, " DESC")) ? "" : " ASC");
          }
        }
      }
      $order = " ORDER BY " . $order;
      if (strstr($query, " ORDER BY ")) {
        throw new VaeException("Cannot use <span class='c'>order</span> option if your SQL query manually specifies <span class='c'>ORDER BY</span>");
      }
    }
    if ($options['groups']) {
      throw new VaeException("Cannot use <span class='c'>groups</span> option in SQL queries.");
    }
    if (stristr($options['page'], "last")) {
      throw new VaeException("Cannot use <span class='c'>page=\"last()\"</span> option in SQL queries.");
    }
    if ($options['paginate']) $options['limit'] = $options['paginate'];
    if (stristr($options['page'], "all")) unset($options['limit']);
    if ($options['limit'] || $options['skip']) {
      if (!isset($options['skip'])) $options['skip'] = 0;
      if (!isset($options['limit'])) $options['limit'] = 99999999;
      if ($options['page'] && $options['limit']) {
        $options['skip'] += ($options['page'] - 1) * $options['limit'];
      }
      $limit = " LIMIT " . $options['skip'] . "," . $options['limit'];
      $query = str_replace("SELECT ", "SELECT SQL_CALC_FOUND_ROWS ", $query);
      $this->limited = true;
      if (strstr($query, " LIMIT ")) {
        throw new VaeException("Cannot use <span class='c'>group</span>/<span class='c'>limit</span>/<span class='c'>paginate</span>/<span class='c'>skip</span> options if your SQL query manually specifies <span class='c'>LIMIT</span>");
      }
    }
    if ($options['filter']) {
      if (!$this->table) {
        throw new VaeException("Cannot use <span class='c'>filter</span> option if you manually specified a SQL query.");
      }
      foreach ($this->getColumns() as $column) {
        if ($colList) $colList .= " OR ";
        $colList .= $column . " LIKE '%" . mysql_escape_string($options['filter']) . "%'";
      }
      $where .= (strlen($where) ? " AND " : "") . "(" . $colList . ")";
    }
    if (strlen($where)) $where = " WHERE " . $where;
    $this->query = $query . $where . $order . $limit;
  }

  private function connect() {
    if (self::$connection == null) {
      $ci = self::$connectionInfo;
      if (!$ci[0] || !$ci[1] || !$ci[2] || !$ci[3]) {
        throw new VaeException('SQL Query requested but you did not provide SQL credentials by calling <span class="c">vae_sql_credentials($username, $password)</span>');
      }
      if ((self::$connection = mysql_connect($ci[0], $ci[2], $ci[3])) === false) {
        throw new VaeException("Could not connect to database using username: " . $c[2]);
      }
      if (!mysql_select_db($ci[1], self::$connection)) {
        throw new VaeException("Could not open database: " . $c[1]);
      }
    }
  }

  private function executeQuery() {
    $this->mysqlResult = mysql_query($this->query, self::$connection) or $this->mysqlError();
  }

  private function getColumns() {
    if ($this->columns) return $this->columns;
    $this->columns = array();
    $res = mysql_query("SHOW FIELDS FROM " . $this->table, self::$connection) or $this->mysqlError();
    while ($row = mysql_fetch_row($res)) {
      $this->columns[] = $row[0];
    }
    return $this->columns;
  }

  private function mysqlError() {
    throw new VaeException("MySQL Error: " . mysql_error(self::$connection) . ".  Attempted query was: <span class='c'>" . $this->query . "</span>.");
  }

  private function totalResults() {
    if ($this->limited) {
      $res = mysql_query("SELECT FOUND_ROWS();", self::$connection) or $this->mysqlError();
      while ($row = mysql_fetch_row($res)) {
        return $row[0];
      }
    } else {
      return mysql_num_rows($this->mysqlResult);
    }
  }

  public function toVaeDbResponse() {
    return $this->response;
  }

  public static function setConnection($host, $db, $username, $password) {
    self::$connectionInfo = array($host, $db, $username, $password);
  }

}

/*** Public API ***/

function vae($query = null, $options = null, $context = "__") {
  global $_VAE;
  if ($context == "__") {
    _vae_set_initial_context();
    $context = $_VAE['context'];
  }
  if ($context) return $context->get($query, $options);
  return VaeQuery::___factory($query, $options);
}

function vae_context($array = null) {
  global $_VAE;
  if ($array == null) return $_VAE['vaeql_context'];
  return VaeQuery::___fromArray($array);
}

function vae_find($query = null, $options = null, $context = null) {
  return vae($query, $options, $context);
}

function vae_sql_credentials($username, $password) {
  VaeSqlQuery::setConnection("localhost", $username, $username, $password);
}


/**** Internal API ****/

function _vae_fetch($query = null, $context = null, $options = null) {
  if ($context) {
    $c = $context->get($query, $options);
    if (is_object($c)) return $c->found();
    return $c;
  }
  $ret = VaeQuery::___factory($query, $options);
  if (is_object($ret)) return $ret->found();
  return $ret;
}

function _vae_fetch_for_creating($query, $context = null) {
  if (is_object($context)) return $context->forCreating($query);
  $obj = VaeQuery::___factory();
  if (is_object($obj)) return $obj->forCreating($query);
  return false;
}

function _vae_fetch_without_errors($query = null, $context = null, $options = null) {
  if (substr($query, 0, 1) != "@") $query = "@" . $query;
  return _vae_fetch($query, $context, $options);
}

function _vae_reset_site() {
  return VaeQuery::___resetSite();
}

function _vae_array_to_xml($array, $clone = false) {
  return VaeQuery::___fromArray($array, $clone);
}

function _vae_to_xml($array, $clone = false, $formId = null) {
  return VaeContext::___fromArray($array, $formId, $clone);
}


/**** VaeQL API ****/

function _vaeql_function($function, $args) {
  if (function_exists($function)) {
    $result = call_user_func_array($function, $args);
  } elseif (function_exists("vae_" . $function)) {
    $result = call_user_func_array("vae_" . $function, $args);
  } elseif (function_exists("vae_" . $function)) {
    $result = call_user_func_array("vae_" . $function, $args);
  } else {
    return "[FUNCTION NOT FOUND: $function]";
  }
  if ($result === false) return "0";
  return (string)$result;
}

function _vaeql_path($path) {
  global $_VAE;
  $ret = (string)_vae_fetch($path, $_VAE['vaeql_context']);
  return $ret;
}

function _vaeql_query($query, $context = null, $options = null, $raiseErrors = true) {
  global $_VAE;
  if (is_null($options)) $options = array();
  $_VAE['vaeql_context'] = $context;
  $query = preg_replace('/\[([A-Za-z0-9_]*)=DATE\(([^]]*)\)\]/i', "[\\1:DATERANGE(\\2)]", $query);
  if (!function_exists('_vaeql_query_internal')) {
    return array(1, $query);
  }
  $ret = _vaeql_query_internal($query);
  if (is_array($ret)) {
    list($path, $query) = $ret;
  } elseif ($ret < -99) {
    throw new VaeException("", "VaeQL Internal Error.  Error Code: " . $ret);
  } elseif ((substr($query, 0, 1) != "@") && $raiseErrors) {
    throw new VaeException("We could not parse VaeQL Query: <span class='code'>" . $query . "</span>");
  } else {
    $path = 0;
    $query = "";
  }
  if (isset($options['assume_numbers']) && is_numeric($query) && $path) {
    $path = 0;
  }
  return array($path, $query);
}

function _vaeql_range_function($function, $args) {
  if (function_exists($function)) {
    $result = call_user_func_array($function, $args);
  } elseif (function_exists("vae_" . $function)) {
    $result = call_user_func_array("vae_" . $function, $args);
  } elseif (function_exists("vae_" . $function)) {
    $result = call_user_func_array("vae_" . $function, $args);
  } else {
    return "[FUNCTION NOT FOUND: $function]";
  }
  if (!is_array($result)) $result = array($result);
  return $result;
}

function _vaeql_variable($name) {
  return $_REQUEST[$name];
}


/*** Cache APIs ***/

function _vae_safe_key($key) {
  return substr($key, 0, 240);
}

function _vae_short_term_cache_get($key, $flags = 0) {
  return VaeQuery::___shortTermCacheGet($key, $flags);
}

function _vae_short_term_cache_set($key, $value, $flags = 0, $expires = 0) {
  return VaeQuery::___shortTermCacheSet($key, $value, $flags, $expires);
}

function _vae_short_term_cache_delete($key) {
  return VaeQuery::___shortTermCacheDelete($key);
}

function _vae_long_term_cache_get($iden, $renew_expiry = null) {
  return VaeQuery::___longTermCacheGet($iden, $renew_expiry, !$_ENV['TEST'] && !$_REQUEST['__debugcache']);
}

function _vae_long_term_cache_set($key, $value, $expire_interval = null, $is_filename = 0) {
  if ($expire_interval == null) $expire_interval = 90;
  return VaeQuery::___longTermCacheSet($key, $value, $expire_interval, $is_filename);
}

function _vae_long_term_cache_delete($iden) {
  return VaeQuery::___longTermCacheDelete($iden);
}

function _vae_long_term_cache_empty() {
  return VaeQuery::___longTermCacheEmpty();
}

function _vae_long_term_cache_sweeper_info() {
  return VaeQuery::___longTermCacheSweeperInfo();
}

function _vae_long_term_cache_exists($iden) {
  if (_vae_long_term_cache_get($iden)) {
    return true;
  }
  return false;
}

function _vae_session_handler_open($s, $n) {
  return true;
}

function _vae_session_handler_read($id) {
  $data = VaeQuery::___sessionCacheGet($id);
  if (strlen($data)) {
    return base64_decode($data);
  }
  return "";
}

function _vae_session_handler_write($id, $data) {
  if (!$data) return _vae_session_handler_destroy($id);
  $data = base64_encode($data);
  if (strlen($data) > 1048576) return false;
  return VaeQuery::___sessionCacheSet($id, $data);
}

function _vae_session_handler_close() {
  return true;
}

function _vae_session_handler_destroy($id) {
  VaeQuery::___sessionCacheDelete($id);
  return true;
}

function _vae_session_handler_gc($expire) {
  return true;
}

function _vae_sitewide_lock() {
  if ($_REQUEST['__debug']) return true;
  for ($i = 0; $i < 50; $i++) {
    if (VaeQuery::___sitewideLock() == 1) return;
    usleep(1000000);
  }
  _vae_error("", "Couldn't obtain Sitewide lock to download files from Vae.");
}

function _vae_sitewide_unlock() {
  return VaeQuery::___sitewideUnlock();
}

?>

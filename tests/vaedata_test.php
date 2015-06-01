<?php

class VaeDataTest extends VaeUnitTestCase {

  function testVae() {
    $songs = vae("13421/albums/13423/songs");
    $this->assertEqual($songs->id(), 13425);
    $this->assertEqual($songs->name, "Last Chance");
    $this->assertNotNull(vae());
    $this->assertEqual(vae("artists", array('limit' => 1))->count, 1);
    $this->assertEqual(vae("name", null, vae("artists")), "Freefall");
    $this->assertEqual(vae("artists", array('filter' => "reefall"))->id, 13421);
    $this->assertTrue(vae("artists")->collection);
    $this->assertTrue(vae("artists")->collection());
    $this->assertEqual(vae("artists/name"), "Freefall");
    $this->assertEqual(vae("artists")->name, "Freefall");
    $this->assertEqual(vae("artists")->name(), "Freefall");
    $this->assertEqual(vae("artists")->name(array('meaningless_option' => true)), "Freefall");
    $this->assertFalse(vae("artists/name")->collection);
    $this->assertFalse(vae("artists/name")->collection());
    $this->assertTrue(vae("artists/name")->found);
    $this->assertTrue(vae("artists/name")->found());
    $this->assertFalse(vae("artists/nombre")->found);
    $this->assertFalse(vae("artists/nombre")->found());
    $this->assertEqual(vae("artists")->count, 4);
    $this->assertEqual(vae("artists")->count(), 4);
    $this->assertEqual(vae("artists")->current()->count, 1);
    $this->assertEqual(vae("artists")->current()->count(), 1);
    $this->assertFalse(vae("artists")->current()->collection);
    $this->assertFalse(vae("artists")->current()->collection());
    $this->assertIsA(vae("artists")->current()->found, "VaeContext");
    $this->assertIsA(vae("artists")->current()->found(), "VaeContext");
    $this->assertEqual(vae("artists")->current()->id, 13421);
    $this->assertEqual(vae("artists")->current()->id(), 13421);
    $this->assertEqual(vae("artists/albums")->current()->parent->id, 13421);
    $this->assertEqual(vae("artists/albums")->current()->parent()->id(), 13421);
    $this->assertEqual(vae("13432")->current()->permalink, "/artist/kevin-bombino");
    $this->assertEqual(vae("13432")->current()->permalink(), "/artist/kevin-bombino");
    $this->assertEqual(vae("13432")->current()->permalink(false), "artist/kevin-bombino");
    $this->assertEqual(vae("13432")->current()->permalinkOrId, "/artist/kevin-bombino");
    $this->assertEqual(vae("13432")->current()->permalinkOrId(), "/artist/kevin-bombino");
    $this->assertEqual(vae("13421")->current()->permalinkOrId, 13421);
    $this->assertEqual(vae("13421")->current()->permalinkOrId(), 13421);
    $this->assertEqual(vae("artists")->current()->structure->id, 1269);
    $this->assertEqual(vae("artists")->current()->structure()->id, 1269);
    $this->assertEqual(vae("artists")->current()->totalMatches, 1);
    $this->assertEqual(vae("artists")->current()->totalMatches(), 1);
    $this->assertEqual(vae("artists")->current()->type, "Collection");
    $this->assertEqual(vae("artists")->current()->type(), "Collection");
    $this->assertEqual(vae("13421")->count, 1);
    $this->assertEqual(vae("13421")->count(), 1);
    $this->assertIsA(vae("artists")->found, "VaeQuery");
    $this->assertIsA(vae("artists")->found(), "VaeQuery");
    $this->assertEqual(vae("13421")->id, 13421);
    $this->assertEqual(vae("13421")->id(), 13421);
    $this->assertEqual(vae("artists/albums")->parent->id, 13421);
    $this->assertEqual(vae("artists/albums")->parent()->id(), 13421);
    $this->assertEqual(vae("13432")->permalink, "/artist/kevin-bombino");
    $this->assertEqual(vae("13432")->permalink(), "/artist/kevin-bombino");
    $this->assertEqual(vae("13432")->permalink(false), "artist/kevin-bombino");
    $this->assertEqual(vae("artists", array('limit' => 1))->totalMatches, 4);
    $this->assertEqual(vae("artists", array('limit' => 1))->totalMatches(), 4);
    $this->assertEqual(vae("artists")->type, "Collection");
    $this->assertEqual(vae("artists")->type(), "Collection");
    $this->assertEqual(vae("artists")->structure->type, "Collection");
    $this->assertEqual(vae("artists")->structure()->type, "Collection");
    $artists = vae("artists");
    $this->assertEqual($artists["name"], "Freefall");
    $this->assertTrue(isset($artists["name"]));
    $this->assertFalse(isset($artists["nombre"]));
    $artists['nombre'] = "Pasquale";
    $this->assertEqual("Pasquale", $artists['nombre']);
    unset($artists['nombre']);
    $this->assertEqual("", $artists['nombre']);
    unset($artists['name']);
    $this->assertEqual($artists["name"], "Freefall");
    $data = array (
      'albums' => '(collection)',
      'bio' => 'A powerpop/indie/rock band from Cambridge, Massachusetts, United States which began on the 28th of December, 2004.  With Sam Lissner on lead vocals and guitar, Kevin Bombino also on lead vocals and guitar, Ben Feltner on bass and vocals and Neil Hartmann on drums.',
      'category' => '(collection)',
      'feature_on_homepage' => '1',
      'genre' => 'Rock',
      'image' => '28610',
      'items' => '(collection)',
      'name' => 'Freefall',
      'yield' => 'TEST_NEWSLETTER_YIELD',
    );
    $this->assertEqual(vae("13421")->data, $data);
    $this->assertEqual(vae("13421")->data(), $data);
    $this->assertEqual(vae("13421")->debug, $data);
    $this->assertEqual(vae("13421")->debug(), $data);
    $this->assertEqual(vae("13421")->current()->data, $data);
    $this->assertEqual(vae("13421")->current()->data(), $data);
    $this->assertEqual(vae("13421")->current()->debug, $data);
    $this->assertEqual(vae("13421")->current()->debug(), $data);
    $freefall = vae("13421")->current();
    $this->assertFalse(isset($freefall['cow']));
    $freefall['cow'] = "moo";
    $this->assertTrue(isset($freefall['cow']));
    $this->assertEqual($freefall->cow, "moo");
    $this->assertEqual($freefall['cow'], "moo");
    unset($freefall['cow']);
    $this->assertEqual($freefall->cow, "");
    $this->assertEqual(vae(13421)->forCreating("albums")->structure_id, 1271);
    $this->assertEqual(vae(13421)->forCreating("albums")->row_id, 13421);
    try {
      count(vae(13421)->___cow);
      $this->fail();
    } catch (VaeException $e) {
      $this->pass();
    }
    try {
      count(vae(13421)->current()->___cow);
      $this->fail();
    } catch (VaeException $e) {
      $this->pass();
    }
    $this->assertEqual(vae("artists", array('filter' => 'Rock'))->totalMatches, 2);
    $this->assertEqual(vae("artists", array('filter' => 'Rock', 'paginate' => 1, 'page' => 1))->name, "Freefall");
    $this->assertEqual(vae("artists", array('filter' => 'Rock', 'paginate' => 1, 'page' => 2))->name, "Kevin Bombino");
    $this->assertEqual(vae("blog_posts[date=DATE('2008-08')]")->count, 2);
    $this->assertEqual(vae("blog_posts[date=DATE('2008-07')]")->count, 0);
    $this->assertEqual(vae("blog_posts[date=DATE('2008-09')]")->count, 0);
    $this->assertEqual(vae("blog_posts[date=DATE('2008-08')]")->count, 2);
    $this->assertEqual(vae("blog_posts[date=DATE('2008-08-21')]")->count, 2);
    $this->assertEqual(vae("blog_posts[date=DATE('2008-08-22')]")->count, 0);
    $this->assertEqual(vae("blog_posts[date=DATE('2008-08-20')]")->count, 0);
    $_REQUEST['d'] = "2008";
    $this->assertEqual(vae('blog_posts[date=DATE($d)]')->count, 2);
    $this->assertEqual(vae('blog_posts[date=DATE(\'$d\')]')->count, 0);
    $this->assertEqual(vae('permalink/artist/kevin-bombino')->id, 13432);
    $this->assertEqual(vae('permalink/artist/kevin-bombino')->name, "Kevin Bombino");
    $this->assertEqual(vae('permalink/artist/kevin-bombino/name'), "Kevin Bombino");
    $this->assertEqual(vae('permalink/artist/kevin-bombino/albums/name'), "Unreleased Songs");
  }
    
  function testVaeFetch() {
    $this->assertEqual(_vae_fetch("13427")->name, "One More Time");
    $this->assertEqual(_vae_fetch("13427/name"), "One More Time");
    $this->assertEqual(_vae_fetch("13427")->name, "One More Time");
    $this->assertEqual(_vae_fetch("artists/albums/13424/name"), "EP");
    $this->assertEqual(_vae_fetch("artists/category")->id, 11);
    $this->assertEqual(_vae_fetch("11/recommended")->id, 11);
    $this->assertEqual(_vae_fetch("14/recommended")->id, 11);
    $this->assertEqual(_vae_fetch("categories[recommended=14]")->id, 15);
    $this->assertEqual(_vae_fetch("categories[recommended='14']")->id, 15);
    $this->assertEqual(_vae_fetch("14/categories")->id, 15);
    $res = _vae_fetch("11/categories");
    $this->assertEqual($res->id, 11);
    $res->next();
    $this->assertEqual($res->id, 14);
    $res->next();
    $this->assertEqual($res->id, 15);
    $this->assertEqual(_vae_fetch("designers/categories/artists[name='Freefall']/name"), "Freefall");
    $this->assertEqual(_vae_fetch("@designers/13/categories/name"), false);
    $this->assertEqual(_vae_fetch("artists[name='Kevin Bombino']/name"), "Kevin Bombino");
    $this->assertEqual(_vae_fetch("artists[name='Kevin Bombino']/name"), "Kevin Bombino");
    $this->assertEqual(_vae_fetch("@artists[name='Kevin Bombinp']/name"), false);
    $_REQUEST['min_price'] = "3.00";  
    $this->assertEqual(_vae_fetch('artists/albums[price>$min_price]/name'), "EP");
    $this->assertEqual(_vae_fetch("name", _vae_fetch("artists")), "Freefall");
    $this->assertEqual(_vae_fetch("../name", _vae_fetch(13423)), "Freefall");
    $res = _vae_fetch("15/recommended");
    $this->assertEqual($res->id, 11);
    $res->next();
    $this->assertEqual($res->id, 14);
    $res = _vae_fetch("/artists/name");
    $this->assertEqual($res, "Freefall");
    $res->next();
    $this->assertEqual($res, "Kevin Bombino");
    $res->next();
    $this->assertEqual($res, "Pratt Avenue");
    $res->next();
    $this->assertEqual($res, "Matt Blake");
    $this->assertEqual(count(_vae_fetch("artists[name='Freefall']/albums/songs")), 7);
    $this->assertEqual(count(_vae_fetch("/artists[name='Freefall']/albums/songs")), 7);
    $this->assertEqual(count(_vae_fetch("artists[name='Freefall']/albums/songs[1]")), 2);
    $this->assertEqual(count(_vae_fetch("artists[name='Freefall']/albums/songs[3]")), 2);
    $this->assertEqual(_vae_fetch("@artists[name='Freefall']/albums/songs[0]"), false);
    $this->assertEqual(_vae_fetch("@songs[duration='5:07']"), false);
    $this->assertEqual(_vae_fetch("@/songs[duration='5:07']"), false);
    $this->assertEqual(_vae_fetch("13427")->name, "One More Time");
    $this->assertEqual(_vae_fetch("123"), NULL);
    $this->assertEqual(_vae_fetch("13423")->name, "Road Trip EP");
    $this->assertEqual(_vae_fetch("13423")->name, "Road Trip EP"); // test cache
    $this->assertEqual(_vae_fetch(13421)->structure->name, "Artists");
    $context = _vae_fetch(13432);
    $this->assertEqual($context->permalink(false), "artist/kevin-bombino");
    $this->assertEqual($context->permalink, "/artist/kevin-bombino");
    $this->assertEqual($context->permalink(true), "/artist/kevin-bombino");
    $context = _vae_fetch(13421);
    $this->assertFalse($context->permalink());
    $this->assertEqual(13423, _vae_fetch("artists/albums")->id);
    $this->assertEqual(_vae_fetch("artists/albums/name"), "Road Trip EP");
    $this->assertEqual(_vae_fetch("albums/name", _vae_fetch(13421)), "Road Trip EP");
    $this->assertEqual(_vae_fetch("artists/albums/songs/name"), "Last Chance");
    $this->assertEqual(_vae_fetch("artists/category/name"), "Category 1");
    $this->assertEqual(_vae_fetch("category/name", _vae_fetch(13421)), "Category 1");
    $this->assertEqual(_vae_fetch("artists/category/designer/name"), "Designer 1");
    $this->assertEqual(_vae_fetch("designers/categories/name"), "Category 1");
    $name = _vae_fetch("artists[name='Freefall']/albums/songs[1]")->name;
    $this->assertEqual((string)$name, (string)_vae_fetch("artists[name='Freefall']/albums/songs")->name);
    $this->assertEqual(vae("13421/albums", array('order' => 'year'))->name, "EP");
  }
  
  function testVaeFetchForCreating() {
    $c = _vae_fetch_for_creating("artists");
    $this->assertEqual(array($c->structure_id, $c->row_id), array(1269, null));
    $c = _vae_fetch_for_creating("/artists");
    $this->assertEqual(array($c->structure_id, $c->row_id), array(1269, null));
    try {
      $c = _vae_fetch_for_creating("13421");
      $this->fail();
    } catch (VaeException $e) {
      $this->pass();
    }
    $ctxt = _vae_fetch("/artists");
    _vae_fetch_for_creating("/artists", $ctxt);
    $this->assertEqual(array($c->structure_id, $c->row_id), array(1269, null));
    try {
      _vae_fetch_for_creating("artists", $ctxt);
      $this->fail();
    } catch (VaeException $e) {
      $this->pass();
    }
    $c = _vae_fetch_for_creating("items", $ctxt);
    $this->assertEqual(array($c->structure_id, $c->row_id), array(1285, 13421));
    $c = _vae_fetch_for_creating("13432/items", $ctxt);
    $this->assertEqual(array($c->structure_id, $c->row_id), array(1285, 13432));
    $c = _vae_fetch_for_creating("/13432/items", $ctxt);
    $this->assertEqual(array($c->structure_id, $c->row_id), array(1285, 13432));
    $c = _vae_fetch_for_creating("/artists/13432/items", $ctxt);
    $this->assertEqual(array($c->structure_id, $c->row_id), array(1285, 13432));
    $c = _vae_fetch_for_creating("/artists/13432/items");
    $this->assertEqual(array($c->structure_id, $c->row_id), array(1285, 13432));
  }
  
  function testVaeFind() {
    $this->assertEqual(vae_find("13421/name"), "Freefall");
    $this->assertEqual(vae_find("13423/songs")->data, array ('name' => 'Last Chance',    'duration' => '3:45',    'mp3' => '28616',    'price' => '0.99',    'feature_on_homepage' => '1',  ));
  }
  
  function testVaeResetSite() {
    _vae_reset_site();
  }
  
  function textVaeArrayToXml() {
    $data = array(201 => array("kevin" => "awesome"), 202 => array("kevin" => "baller"));
    $res = _vae_array_to_xml($data);
    $this->assertEqual($res->kevin, "awesome");
    $this->assertEqual($res->current()->kevin, "awesome");
    $this->assertEqual($res->current()->formId, 201);
    $res->next();
    $this->assertEqual($res->current()->kevin, "baller");
  }
  
  function testVaeToXml() {
    $data = array("kevin" => "awesome");
    $res = _vae_to_xml($data);
    $this->assertEqual($res->kevin, "awesome");
  }
  
  function testVaeQl() {
    $this->assertEqual(_vaeql_query("now()"), array(0, vae_now()));
    $this->assertEqual(_vaeql_query("artists[category='']"), array(1, "artists[category='']"));
    $_REQUEST['variable'] = "Sean";
    $this->assertEqual(_vaeql_query("artists[category=\$variable]"), array(1, "artists[category='Sean']"));
    $this->assertEqual(_vaeql_query("artists[category=\$nonvariable]"), array(1, "artists"));
    $this->assertEqual(_vaeql_query("artists[category=path()]"), array(1, "artists[category='']"));
    $this->assertEqual(_vaeql_query("artists[category=7]"), array(1, "artists[category='7']"));
    $this->assertEqual(_vaeql_query("artists[category=freefall]"), array(1, "artists[category=freefall]"));
    $this->assertEqual(vae("1+2"), 3);
    $this->assertEqual(vae("1+2**3"), 7);
    $this->assertEqual(vae("1+2+3"), 6);
    $this->assertEqual(vae("(1+2)**3"), 9);
    $this->assertEqual(vae('$nonexist'), "");
    $this->assertEqual(vae('(7**11)+14'), '91');
    $this->assertEqual(vae('(((5+6)//3**5+(6//7)))'), '19.1904');
    $this->assertEqual(_vaeql_query("artists"), array(1, "artists"));
    $this->assertEqual(vae('(1 ? "yes" : "no")'), "yes");
    $this->assertEqual(vae('(0 ? "yes" : "no")'), "no");
    $this->assertEqual(vae('((5>7) ? "yes" : "no")'), "no");
    $this->assertEqual(vae('(5>7 ? "yes" : "no")'), "0");
    $this->assertEqual(vae('((5>7<1) ? "yes" : "no")'), "yes");
    $this->assertEqual(vae('"string"'), "string");
    $this->assertEqual(vae('6>7'), "0");
    $this->assertEqual(vae('6>7'), false);
    $this->assertEqual(vae('6<7-2'), false);
    $this->assertEqual(vae('6<7+2'), true);
  }

  function testShortTermCacheGetAndSet() {
    $this->assertEqual(_vae_short_term_cache_get("badkey"), "");
    _vae_short_term_cache_set("st1", "v1");
    $this->assertEqual(_vae_short_term_cache_get("st1"), "v1");
    _vae_short_term_cache_set("st1", "v2");
    $this->assertEqual(_vae_short_term_cache_get("st1"), "v2");
  }

  function testShortTermCacheDelete() {
    _vae_short_term_cache_set("st1", "v2", 600, 1);
    _vae_short_term_cache_set("st2", "v2", 600, 1);
    $this->assertEqual(_vae_short_term_cache_get("st1"), "v2");
    _vae_short_term_cache_delete("st1");
    $this->assertEqual(_vae_short_term_cache_get("st1"), "");
    $this->assertEqual(_vae_short_term_cache_get("st2"), "v2");
  }
    
  function testLongTermCacheGetAndSet() {
    $this->assertEqual(_vae_long_term_cache_get("badkey"), "");
    _vae_long_term_cache_set("t1", "v1", 600, 0);
    $this->assertEqual(_vae_long_term_cache_get("t1", false), "v1");
    _vae_long_term_cache_set("t1", "v2", 600, 0);
    $this->assertEqual(_vae_long_term_cache_get("t1", false), "v2");
    $this->assertEqual(_vae_long_term_cache_get("t1", true), "v2");
  }

  function testLongTermCacheDelete() {
    _vae_long_term_cache_set("t1", "v2", 600, 1);
    _vae_long_term_cache_set("t2", "v2", 600, 1);
    $this->assertEqual(_vae_long_term_cache_get("t1"), "v2");
    _vae_long_term_cache_delete("t1");
    $this->assertEqual(_vae_long_term_cache_get("t1"), "");
    $this->assertEqual(_vae_long_term_cache_get("t2"), "v2");
  }

  function testLongTermCacheEmpty() {
    _vae_long_term_cache_set("t1", "v2", 600, 1);
    _vae_long_term_cache_set("t2", "v2", 600, 1);
    $this->assertEqual(_vae_long_term_cache_get("t1"), "v2");
    _vae_long_term_cache_empty();
    $this->assertEqual(_vae_long_term_cache_get("t1"), "");
    $this->assertEqual(_vae_long_term_cache_get("t2"), "");
  }

  function testLongTermCacheSweeperInfo() {
    $this->assertNotNull(_vae_store_file("TESTIDEN2", "testfile.txt", "txt"));
    $this->assertEqual(array_keys(_vae_long_term_cache_sweeper_info()), array("TESTIDEN2"));
  }
    
}

?>

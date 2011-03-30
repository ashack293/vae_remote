<?php

class ParseTest extends VaeUnitTestCase {
  
  function testVaeFindFragment() {
    $tag = $this->tag("<v:template filename='kevin.html'><v:fragment for='moo' marker='wrong'></v:fragment><v:fragment for='cow' marker='right'></v:fragment></v:template>");
    $fragment = _vae_find_fragment($tag, "cow");
    $this->assertEqual($fragment['attrs']['marker'], "right");
  }
  
  function testVaeFindFragmentInvalidNesting() {
    $tag = $this->tag("<v:template filename='kevin.html'><v:if path='condition'><v:fragment for='moo' marker='wrong'></v:fragment><v:fragment for='cow' marker='right'></v:fragment></v:if></v:template>");
    $this->assertFalse(_vae_find_fragment($tag, "cow"));
  }
  
  function testVaeMergeYield() {
    $inner_tags = $this->tag("<v:template filename='kevin.html'><v:text path='kevin' /></v:template>");
    list($parse_tree, $context) = _vae_parse_vaeml("<html><body>Some stuff<v:yield /></body></html>");
    $merged = _vae_merge_yield($parse_tree, $inner_tags, new Context());
    $correct_parse_tree = array (  'tags' =>   array (    0 =>     array (      'innerhtml' => '<html><body>Some stuff',    ),    1 =>     array (      'type' => 'text',      'tags' =>       array (      ),      'filename' => NULL,      'attrs' =>       array (        'path' => 'kevin',      ),    ),    2 =>     array (      'type' => 'yield',      'tags' =>       array (      ),      'filename' => NULL,      'attrs' =>       array (      ),    ),    3 =>     array (      'innerhtml' => '</body></html>',    ),  ),);
    $this->assertEqual($correct_parse_tree, $parse_tree);
  }
  
  function testVaeMergeYieldFlashTag() {
    $inner_tags = $this->tag("<v:template filename='kevin.html'><v:text path='kevin' /></v:template>");
    list($parse_tree, $context) = _vae_parse_vaeml("<html><body>Some stuff<v:flash /><v:yield /></body></html>");
    $merged = _vae_merge_yield($parse_tree, $inner_tags, new Context());
    $this->assertTrue($context->get("has_flash_tag"));
  }
  
  function testVaeMergeYieldNested() {
    $inner_tags = $this->tag("<v:template filename='kevin.html'><v:text path='kevin' /></v:template>");
    list($parse_tree, $context) = _vae_parse_vaeml("<html><body><v:collection path='artist'>Some stuff<v:yield /></v:collection></body></html>");
    $merged = _vae_merge_yield($parse_tree, $inner_tags, new Context());
    $correct_parse_tree = array (  'tags' =>   array (    0 =>     array (      'innerhtml' => '<html><body>',    ),    1 =>     array (      'type' => 'collection',      'tags' =>       array (        0 =>         array (          'innerhtml' => 'Some stuff',        ),        1 =>         array (          'type' => 'text',          'tags' =>           array (          ),          'filename' => NULL,          'attrs' =>           array (            'path' => 'kevin',          ),        ),        2 =>         array (          'type' => 'yield',          'tags' =>           array (          ),          'filename' => NULL,          'attrs' =>           array (          ),        ),      ),      'filename' => NULL,      'attrs' =>       array (        'path' => 'artist',      ),    ),    2 =>     array (      'innerhtml' => '</body></html>',    ),  ),);
    $this->assertEqual($correct_parse_tree, $parse_tree);
  }
  
  function testVaeMergeYieldFragments() {
    $inner_tags = $this->tag("<v:template filename='kevin.html'><v:fragment for='header'>THE HEADER</v:fragment><v:text path='kevin' /></v:template>");
    list($parse_tree, $context) = _vae_parse_vaeml("<html><head><v:yield for='header' /></head><body>Some stuff<v:yield /></body></html>");
    $merged = _vae_merge_yield($parse_tree, $inner_tags, new Context());
    $correct_parse_tree = array (  'tags' =>   array (    0 =>     array (      'innerhtml' => '<html><head>',    ),    1 =>     array (      'innerhtml' => 'THE HEADER',    ),    2 =>     array (      'innerhtml' => '</head><body>Some stuff',    ),    3 =>     array (      'type' => 'fragment',      'tags' =>       array (        0 =>         array (          'innerhtml' => 'THE HEADER',        ),      ),      'filename' => NULL,      'attrs' =>       array (        'for' => 'header',      ),    ),    4 =>     array (      'type' => 'text',      'tags' =>       array (      ),      'filename' => NULL,      'attrs' =>       array (        'path' => 'kevin',      ),    ),    5 =>     array (      'type' => 'yield',      'tags' =>       array (      ),      'filename' => NULL,      'attrs' =>       array (      ),    ),    6 =>     array (      'innerhtml' => '</body></html>',    ),  ),);
    $this->assertEqual($correct_parse_tree, $parse_tree);
  }
  
  function testVaeMergeYieldMissingFragments() {
    $inner_tags = $this->tag("<v:template filename='kevin.html'><v:text path='kevin' /></v:template>");
    list($parse_tree, $context) = _vae_parse_vaeml("<html><head><v:yield for='header' /></head><body>Some stuff<v:yield /></body></html>");
    $merged = _vae_merge_yield($parse_tree, $inner_tags, new Context());
    $correct_parse_tree = array (  'tags' =>   array (    0 =>     array (      'innerhtml' => '<html><head>',    ),    1 =>     array (      'type' => 'yield',      'tags' =>       array (      ),      'filename' => NULL,      'attrs' =>       array (        'for' => 'header',      ),    ),    2 =>     array (      'innerhtml' => '</head><body>Some stuff',    ),    3 =>     array (      'type' => 'text',      'tags' =>       array (      ),      'filename' => NULL,      'attrs' =>       array (        'path' => 'kevin',      ),    ),    4 =>     array (      'type' => 'yield',      'tags' =>       array (      ),      'filename' => NULL,      'attrs' =>       array (      ),    ),    5 =>     array (      'innerhtml' => '</body></html>',    ),  ),);
    $this->assertEqual($correct_parse_tree, $parse_tree);
  }
  
  function testVaeParseVaeml() {
    $inner_tags = $this->tag("<v:template filename='kevin.html'><v:text path='kevin' /></v:template>");
    list($parse_tree, $context) = _vae_parse_vaeml("<html><body>Some stuff<v:yield /></body></html>", "test.html", $inner_tags);
    $correct_parse_tree = array (  'tags' =>   array (    0 =>     array (      'innerhtml' => '<html><body>Some stuff',    ),    1 =>     array (      'type' => 'text',      'tags' =>       array (      ),      'filename' => NULL,      'attrs' =>       array (        'path' => 'kevin',      ),    ),    2 =>     array (      'type' => 'yield',      'tags' =>       array (      ),      'filename' => 'test.html',      'attrs' =>       array (      ),    ),    3 =>     array (      'innerhtml' => '</body></html>',    ),  ),);
    $this->assertEqual($correct_parse_tree, $parse_tree);
    list($parse_tree, $context) = _vae_parse_vaeml("<html><body>Some stuff<v:yield /></body></html>", "test.html", $inner_tags);
    $this->assertEqual($correct_parse_tree, $parse_tree);
  }
  
  function testVaeParseVaemlHaml() {
    list($parse_tree, $context) = _vae_parse_vaeml("%html\n  %body\n    %v:text(path='kevin')", "test.haml");
    $correct_parse_tree = array (  'tags' =>   array (    0 =>     array (      'innerhtml' => "<html>\n  <body>\n    ",    ),    1 =>     array (      'type' => 'text',      'tags' =>       array (      ),      'filename' => 'test.haml',      'attrs' =>       array (        'path' => 'kevin',      ),    ),    2 =>     array (      'innerhtml' => "\n  </body>\n</html>\n",    ),  ),);
    $this->assertEqual($correct_parse_tree, $parse_tree);
    list($parse_tree, $context) = _vae_parse_vaeml("%html\n  %body\n    %v:text(path='kevin')", "test.haml.php");
    $correct_parse_tree = array (  'tags' =>   array (    0 =>     array (      'innerhtml' => "<html>\n  <body>\n    ",    ),    1 =>     array (      'type' => 'text',      'tags' =>       array (      ),      'filename' => 'test.haml.php',      'attrs' =>       array (        'path' => 'kevin',      ),    ),    2 =>     array (      'innerhtml' => "\n  </body>\n</html>\n",    ),  ),);
    $this->assertEqual($correct_parse_tree, $parse_tree);
  }
  
  function testVaeParseVaemlErrors() {
    try {
      $this->assertFalse(_vae_parse_vaeml("<v:template>"));
    } catch (VaeException $e) {
      $this->pass();
    }
  }
  
  function testVaeParseVaemlMissingRequiredAttribute() {
    try {
      $this->assertFalse(_vae_parse_vaeml("<v:template></v:template>"));
    } catch (VaeException $e) {
      $this->pass();
    }
    _vae_parse_vaeml("<v:template filename='kevin.html'></v:template>");
  }
  
  function testVaeParseVaemlInvalidVaemlTag() {
    _vae_parse_vaeml("<v:badness></v:badness>");
    $this->pass();
  }
  
  function testVaeParserMaskErrors() {
    global $_VAE;
    _vae_parser_mask_errors(123, "DOMDocument::loadXML() [<a href='domdocument.loadxml'>domdocument.loadxml</a>]: THISISERR");
    $this->assertEqual($_VAE['parser_errors'], "<li>THISISERR</li>");
    _vae_parser_mask_errors(123, " in tag vaeml line 1 in Entity2NDERR and vaeml in Entity in Entity");
    $this->assertEqual($_VAE['parser_errors'], "<li>THISISERR</li><li>2NDERR</li>");
  }
  
  function testVaeMLParser() {
    // Tested as part of _vae_parse_vaeml()
  }
  
}

?>
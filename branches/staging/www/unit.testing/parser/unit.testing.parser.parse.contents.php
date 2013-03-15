<?php

$parser = new parser("http://## FILL BASE URL ##/user/shal");

// $parser = new parser("http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd");
$parser->weave_sitemap();

// $xml_dtd_parser = new XML_DTD_Parser();
// $xml_dtd_parser->parse(dirname(__FILE__).CHARACTER_SLASH."dtd/xhtml1-transitional.dtd");
// $tree = $xml_dtd_parser->parse($parser->weave_sitemap(), false);
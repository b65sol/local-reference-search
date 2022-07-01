<?php
include 'src/build-index.php';
include 'vendor/wikia/simplehtmldom/simple_html_dom.php';
if($argc < 2) {
  die("Usage: {$argv[0]} <html-file> [selector]\nAdd HTML file to reference search index.\nOptionally include a SimpleHTML DOM selector to narrow to relevent part.\nFile must be relative to script.\n");
}
chdir(dirname(__FILE__));
if(!is_file($argv[1])) {
  echo "$argv[1] not found\n";
}
$selector = 'body';
if(!empty($argv[2])) {
  $selector = $argv[2];
}

if(filesize($argv[1]) < 50) {
  echo "$argv[1] is too small\n\n";
  exit(2);
}

$html = file_get_html($argv[1]);
$scripts = $html->find("script");
foreach($scripts as $script) {
  $script->outertext = '';
}

//$stopwords = array_filter(array_map('trim', file('stopwords.txt')));
$sel = $html->find($selector);
$text = '';
foreach($sel as $selected) {
  $text .= html_entity_decode(strtolower($selected->plaintext), ENT_COMPAT, 'UTF-8'). ' ';
}
$text = trim($text);
if(strlen($text) < 20) {
  echo "No content selected, skipping.\n\n";
  exit(1);
}

$titleelem = $html->find('title');
foreach($titleelem as $titleel) {
  $title = html_entity_decode($titleel->plaintext, ENT_COMPAT, 'UTF-8');
}

//var_dump($title);
//var_dump($text);
$text = str_replace('_', '', $text);

$fullpath = realpath($argv[1]);
//var_dump($fullpath);
$relpath = str_replace(dirname(__FILE__).'/', '', $fullpath);
//var_dump($relpath);

echo "$title\n$relpath\n\n";

$ind = new Index("refstore.sqlite");
$ind->upsert_document($relpath, $text.' '.strtolower($title));
$ind->set_meta($relpath, 'title', $title);

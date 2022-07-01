<?php

class PersonalSearch {
  public static function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
  }

  public static function base64url_decode($data) {
    return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
  }

  /**
   * @param String $url Remote URL.
   * @param String $index_content Index content.
   * @param String $title Title
   * @param Index $index Search index to insert into. If null, returns document instead.
   */
  public static function add_external_file_to_index($url, $index_content, $title, $index = null) {
    $html = str_get_html($index_content);
    $scripts = $html->find("script");
    foreach($scripts as $script) {
      $script->outertext = '';
    }

    //$stopwords = array_filter(array_map('trim', file('stopwords.txt')));
    $sel = [$html];
    $text = '';
    foreach($sel as $selected) {
      $text .= html_entity_decode(strtolower($selected->plaintext), ENT_QUOTES, 'UTF-8'). ' ';
    }
    $text = trim($text);
    if(strlen($text) < 20) {
      throw new Exception("Insufficient copy, not adding to index.\n");
    }

    $text = str_replace('_', '', $text);
    if($index == null) {
      return array(
        'title' => $title,
        'textbody' => $text,
      );
    } else {
      $index->upsert_document($url, $text.' '.strtolower($title));
      $index->set_meta($url, 'title', $title);
    }
  }


  /**
   * @param String $filepath  Should be relative to indexroot.
   * @param Selector $selector SimpleHTMLDom selector to collect content from.
   * @param Index $index Search index to insert into. If null, returns document instead.
   */
  public static function add_html_file_to_index($filepath, $selector, $index = null) {
    $relpath = $filepath;
    $html = file_get_html($filepath);
    $scripts = $html->find("script");
    foreach($scripts as $script) {
      $script->outertext = '';
    }

    //$stopwords = array_filter(array_map('trim', file('stopwords.txt')));
    $sel = $html->find($selector);
    $text = '';
    foreach($sel as $selected) {
      $text .= html_entity_decode(strtolower($selected->plaintext), ENT_QUOTES, 'UTF-8'). ' ';
    }
    $text = trim($text);
    if(strlen($text) < 20) {
      throw new Exception("Insufficient copy, not adding to index.\n");
    }

    $titleelem = $html->find('title');
    foreach($titleelem as $titleel) {
      $title = html_entity_decode($titleel->plaintext, ENT_QUOTES, 'UTF-8');
    }

    //var_dump($title);
    //var_dump($text);
    $text = str_replace('_', '', $text);
    if($index == null) {
      return array(
        'title' => $title,
        'textbody' => $text,
      );
    } else {
      $index->upsert_document($relpath, $text.' '.strtolower($title));
      $index->set_meta($relpath, 'title', $title);
    }
  }

  public static function manual_document_wrap($title, $copy) {
    $content = '<!DOCTYPE html>';
    $content .= '<html><head><title>'.htmlspecialchars($title, ENT_QUOTES, 'UTF-8').'</title>';
    $content .= '<meta charset="utf-8"><meta name="viewport" content="width=device-width">';
    $content .= '<style type="text/css">html,body{background: black; color: #aea; font-family: Helvetica, Arial, sans-serif; font-size: 15px; line-height: 110%; }
    pre {white-space: pre; font-family: Lucida Console, Courier New, monospace;} </style>';
    $content .= '</head><body>';
    $content .= '<h1>'.htmlspecialchars($title, ENT_QUOTES, 'UTF-8').'</h1>';
    $content .= '<div id="content"><!--123BEGIN-->'.$copy.'<!--123END--></div>';
    $content .= '</body></html>';
    return $content;
  }
}

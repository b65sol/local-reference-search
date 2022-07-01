<?php
include 'src/build-index.php';
include 'vendor/wikia/simplehtmldom/simple_html_dom.php';
require 'src/personal-search.php';

$retrieve = '';
if(!empty($_REQUEST['retrieve'])) {
  $retrieve = preg_replace('/[^a-z0-9A-Z_-]/', '_', $_REQUEST['retrieve']);
}
$ind = new Index("refstore.sqlite");
echo '<!DOCTYPE html>';
echo '<html>';
?>
<style type="text/css">
body,html {
  background-color: black;
  color: #afa;
  font-family: helvetica, arial, sans-serif;
  font-size: 15px;
  line-height: 110%;
}

a {
  color: #afa;
  font-weight: bold;
}

input[type="text"], textarea {
  background: #333;
  color: #afa;
}
</style>
<meta name="viewport" content="width=device-width">
<meta charset="utf-8">
<?php
if(!empty($retrieve)) {
  if(!is_dir('manual')) {
    mkdir('manual');
  }
  $filepath = 'manual/'.$retrieve.'.html';
  $message = '';
  if(!empty($_POST['content'])) {
    $html = PersonalSearch::manual_document_wrap($_POST['title'], $_POST['content']);
    file_put_contents($filepath, $html);
    try {
      PersonalSearch::add_html_file_to_index($filepath, '#content', $ind);
      $message = 'Added to Index';
    } catch (Exception $e) {
      $message = 'Could not add to index: '.$e->getMessage();
    }
  }
  $content = '';
  $title  = '';
  if(is_file($filepath)) {
    $html = file_get_html($filepath);

    $htmlcont = file_get_contents($filepath);
    preg_match('/<!--123BEGIN-->(.*)<!--123END-->/s', $htmlcont, $match);
    if(!empty($match[1])) {
      $content = $match[1];
    } else {
      foreach($html->find('#content') as $contentnode) {
        $content .= $contentnode->innertext;
      }
    }

    $title = '';
    foreach($html->find('title') as $titlenode) {
      $title = $titlenode->plaintext;
    }
  }
  ?>
  <title>Document Edit: <?php echo $retrieve; ?></title>
  </head>
  <body>
    <p><a href="add-manual.php">Return to Manual Index</a></p>
    <?php if(!empty($message)): ?>
      <p><?php echo htmlspecialchars($message, ENT_COMPAT, 'UTF-8'); ?></p>
    <?php endif; ?>
    <h1>Document Edit: <?php echo $retrieve; ?></h1>
    <form action="" method="post">
      <input type="hidden" name="retrieve" value="<?php echo $retrieve; ?>">
      <label>Title: <br>
      <input type="text" name="title" style="width: 70%" value="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>"><br>
      <label>HTML Content: <br>
        <textarea name="content" style="width: 90%; height: 60vh;"><?php echo htmlspecialchars($content, ENT_QUOTES, 'UTF-8'); ?></textarea>
      </label><br>
      <input type="submit" value="Create/Modify" style="width: 25%;">
    </form>

    <h2>Existing Manual Copy Documents</h2>
    <?php foreach(glob('manual/*.html') as $manualfile) : ?>
      <p><a href="?retrieve=<?php echo rawurlencode(preg_replace(';\.html$;i', '', basename($manualfile))); ?>">
        <?php echo htmlspecialchars(preg_replace('/^manual\//', '', $manualfile), ENT_QUOTES, 'UTF-8'); ?>
      </a></p>
    <?php endforeach; ?>

  </body>
  <?php
} else {
  ?>
  <title>Document Select</title>
  </head>
  <body>
    <p><a href="index.php">Back to Search</a></p>
    <h1>Document Select</h1>
    <form action="" method="get">
      <input type="text" name="retrieve" style="width: 70%">
      <input type="submit" value="Create/Modify" style="width: 25%;">
    </form>

    <?php foreach(glob('manual/*.html') as $manualfile) : ?>
      <p><a href="?retrieve=<?php echo rawurlencode(preg_replace(';\.html$;i', '', basename($manualfile))); ?>">
        <?php echo htmlspecialchars(preg_replace('/^manual\//', '', $manualfile), ENT_QUOTES, 'UTF-8'); ?>
      </a></p>
    <?php endforeach; ?>

  </body>
  <?php
}
echo '</html>';

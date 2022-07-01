<?php
include 'src/build-index.php';
include 'vendor/wikia/simplehtmldom/simple_html_dom.php';
require 'src/personal-search.php';

$retrieve = '';
if(!empty($_REQUEST['retrieve'])) {
  $retrieve = PersonalSearch::base64url_encode($_REQUEST['retrieve']);
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
  if(!is_dir('external')) {
    mkdir('external');
  }
  $filepath = 'external/'.$retrieve.'.html';
  $message = '';
  if(!empty($_POST['content'])) {
    $html = PersonalSearch::manual_document_wrap($_POST['title'], $_POST['content']);
    file_put_contents($filepath, $html);
    try {
      PersonalSearch::add_external_file_to_index($_REQUEST['retrieve'], $_POST['content'], $_POST['title'], $ind);
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
  <title>External Document Edit: <?php echo $retrieve; ?></title>
  </head>
  <body>
    <p><a href="add-manual.php">Return to Manual Index</a></p>
    <p><a href="add-external.php">Return to External Store Index</a></p>
    <?php if(!empty($message)): ?>
      <p><?php echo htmlspecialchars($message, ENT_COMPAT, 'UTF-8'); ?></p>
    <?php endif; ?>
    <h1>Document Edit: <?php echo htmlspecialchars($_REQUEST['retrieve'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <form action="" method="post">
      <input type="hidden" name="retrieve" value="<?php echo htmlspecialchars($_REQUEST['retrieve'], ENT_QUOTES, 'UTF-8'); ?>">
      <label>Title: <br>
      <input type="text" name="title" style="width: 70%" value="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>"><br></label>
      <label>Index Content: <br>
        <textarea name="content" style="width: 90%; height: 60vh;"><?php echo htmlspecialchars($content, ENT_QUOTES, 'UTF-8'); ?></textarea>
      </label><br>
      <label>
        Description: <br>
        <textarea name="description" style="width: 90%; height: 10vh;"><?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?></textarea>
      </label>
      <input type="submit" value="Create/Modify" style="width: 25%;">
    </form>

    <h2>Existing External Documents</h2>
    <?php foreach(glob('external/*.html') as $manualfile) : ?>
      <p><a href="?retrieve=<?php echo rawurlencode(PersonalSearch::base64url_decode(preg_replace(';\.html$;i', '', basename($manualfile)))); ?>">
        <?php echo htmlspecialchars(PersonalSearch::base64url_decode(preg_replace(';\.html$;i', '', basename($manualfile)), ENT_QUOTES, 'UTF-8')); ?>
      </a></p>
    <?php endforeach; ?>

  </body>
  <?php
} else {
  ?>
  <title>External Document Select</title>
  </head>
  <body>
    <p><a href="index.php">Back to Search</a></p>
    <h1>External Document Select</h1>
    <form action="" method="get">
      <input type="text" name="retrieve" style="width: 70%">
      <input type="submit" value="Create/Modify" style="width: 25%;">
    </form>

    <?php foreach(glob('external/*.html') as $manualfile) : ?>
      <p><a href="?retrieve=<?php echo rawurlencode(PersonalSearch::base64url_decode(preg_replace(';\.html$;i', '', basename($manualfile)))); ?>">
        <?php echo htmlspecialchars(PersonalSearch::base64url_decode(preg_replace(';\.html$;i', '', basename($manualfile)), ENT_QUOTES, 'UTF-8')); ?>
      </a></p>
    <?php endforeach; ?>

  </body>
  <?php
}
echo '</html>';

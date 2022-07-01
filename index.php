<?php
$search = !empty($_GET['s']) ? $_GET['s'] : '';
?><!DOCTYPE html>
<html style="background-color: #111; color: #e1e1e1;">
  <head><title>Documentation and Notes Search<?php if(!empty($search)) { echo ' :: '.htmlspecialchars($search, ENT_COMPAT, 'UTF-8'); } ?></title>
  <style type="text/css">
    .result-div {
      padding-top: 5px;
      padding-bottom: 10px;
    }

    .result-div a {
      color: #0b0;
    }

    a {
      color: #afa;
      font-weight: bold;
    }
  </style>
  </head>
  <body style="padding: 10px;">
    <p><a href="add-manual.php">Add Manual Page</a></p>
    <h4>Search Documentation and Notes:</h4>
   <form action="" method="get"><input type="text" name="s" style="background: #333; color: #c1f1c1; width:60%; border: 1px dotted #0f0;" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"><input type="submit" value="Search"></form>
   <?php if(!empty($search)):
     include 'src/build-index.php';
     echo '<h3>Results:</h3>';
     $ind = new Index("refstore.sqlite");
     $r = $ind->search(str_replace('_', '', strtolower($search)));

     foreach($r as $extid) :
       $title = $ind->get_meta($extid, 'title')[0];
       ?>
       <div class="result-div">
         <a href="<?php echo $extpath = htmlspecialchars($extid, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($title, ENT_COMPAT, 'UTF-8'); ?></a>
         <br>
         <span style="font-size: 90%;"><?php echo $extpath; ?></span>
       </div>
   <?php endforeach; endif;?>
  </body>
</html>

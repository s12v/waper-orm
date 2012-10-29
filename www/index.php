<?php
require(dirname(__FILE__).'/../app/autoload.php');
require(dirname(__FILE__).'/../app/config.php');

ob_start();

// action
$images = Model_Image_Table::getInstance()->findAll(
  array('id:<' => 4546456),
  array('userId', 'file'),
  array('rowCount' => true, 'limit' => '10', 'order' => new Waper_DB_SqlExpression("RAND()"))
);
if ($images) {
  $rowCount = Waper_DB::getInstance()->getTotalRowsCount();
  $users = $images->getUser(array("name"));
  $comments = $images->getComments(array("userId", "imageId", "text"), array('order' => array('id' => 'ASC')));
}


// html
echo "<pre>";
if ($images) {
  echo "Total images: $rowCount, selected ".count($images)."<br/>";
  foreach ($images as $image) {
    echo "id: ".$image->id."<br/>";
    echo "userId: ".$image->userId."<br/>";
    echo "file: ".$image->file."<br/>";
    echo "user.name: ".$image->getUser()->name."<br/>";
    echo "comments:<br/>";
    if ($image->hasComments()) {
      foreach ($image->getComments() as $comment) {
        echo "\tid: ".$comment->id."<br/>";
        echo "\tuserId: ".$comment->userId."<br/>";
        echo "\ttext: ".$comment->text."<br/>";
      }
    } else {
      echo "No comments<br/>";
    }
    echo "--------------<br/>";
  }
} else {
  echo "no images";
}
echo "</pre>";

// Show SQL
echo '<div style="font-family: monospace; border: 1px solid #ccc; padding: 5px; background-color: #f6f6f6;">';
foreach (Waper_Timer::getStat() as $call) {
  if (!$call['error']) {
    echo $call['label']."<br/>";
  }
}
echo "</div>";

$body = ob_get_clean();

?>

<!doctype html>
<html>
  <head>
    <title>demo</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <script type="text/javascript"></script>
     
  </head>
  <body>

    <ol>    
      <li>Select all images by some criteria, ordered by RAND(), limit 10</li>
      <li>Find total rows count</li>
      <li>Select corresponding users</li>
      <li>Select corresponding comments, ordered by id</li>
    </ol>
    
    <?=$body ?>
    
   
  </body>
</html>

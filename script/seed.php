<?php
throw new Exception("Don't execute me");

require(dirname(__FILE__).'/../app/autoload.php');
require(dirname(__FILE__).'/../app/config.php');

$user = new Model_User_Row();
$user->name = "User #1";
$user->save();

$user = new Model_User_Row();
$user->name = "User #2";
$user->save();

$user = new Model_User_Row();
$user->name = "User #3";
$user->save();

for ($i = 1; $i <= 10; $i++) {
  $image = new Model_Image_Row();
  $image->file = "file_1_".$i.".jpg";
  $image->desc = "User 1, file ".sprintf("%02d", $i);
  $image->userId = 1;
  $image->save();
}

for ($i = 1; $i <= 10; $i++) {
  $image = new Model_Image_Row();
  $image->file = "file_2_".$i.".jpg";
  $image->desc = "User 2, file ".sprintf("%02d", $i);
  $image->userId = 2;
  $image->save();
}

for ($i = 1; $i <= 10; $i++) {
  $image = new Model_Image_Row();
  $image->file = "file_3_".$i.".jpg";
  $image->desc = "User 3, file ".sprintf("%02d", $i);
  $image->userId = 3;
  $image->save();
}

for ($i = 1; $i <= 100; $i++) {
  $comment = new Model_Comment_Row();
  $comment->userId = mt_rand(1,3);
  $comment->imageId = mt_rand(1,30);
  $comment->text = "Comment # ".$i." userId: ".$comment->userId.", imageId: ".$comment->imageId;
  $comment->save();
}


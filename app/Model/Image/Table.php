<?php
/**
 * @author Sergey Novikov <mail@snov.me>
 */
class Model_Image_Table extends Waper_DB_Table_Abstract
{
  protected $_scheme = array(
    'id' => array(
      'table' => 'image',
      'field' => 'id',
      'type'  => self::TYPE_INT,
    ),
    'userId' => array(
      'table' => 'image',
      'field' => 'userId',
      'type'  => self::TYPE_INT,
    ),
    'file' => array(
      'table' => 'image_data',
      'field' => 'file',
      'type'  => self::TYPE_STRING,
    ),
    'desc' => array(
      'table' => 'image_data',
      'field' => 'desc',
      'type'  => self::TYPE_STRING,
    ),
  );

  protected $_tables = array(
    'image' => 'id',
    'image_data' => 'id'
  );

  protected $_relations = array(
    'User' => array(self::RELATION_HAS_ONE, 'User', 'userId'),
    'Comments' => array(self::RELATION_HAS_MANY, 'Comment', 'imageId'),
  );

}


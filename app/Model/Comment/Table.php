<?php
/**
 *
 * @author Sergey Novikov <mail@snov.me>
 */
class Model_Comment_Table extends Waper_DB_Table_Abstract
{
  protected $_scheme = array(
    'id' => array(
      'table' => 'comment',
      'field' => 'id',
      'type'  => self::TYPE_INT,
    ),
    'userId' => array(
      'table' => 'comment',
      'field' => 'userId',
      'type'  => self::TYPE_INT,
    ),
    'imageId' => array(
      'table' => 'comment',
      'field' => 'imageId',
      'type'  => self::TYPE_INT,
    ),
    'text' => array(
      'table' => 'comment_data',
      'field' => 'text',
      'type'  => self::TYPE_STRING,
    ),
  );

  protected $_tables = array(
    'comment' => 'id',
    'comment_data' => 'id'
  );

  protected $_relations = array(
    'User' => array(self::RELATION_HAS_ONE, 'User', 'userId'),
    'Image' => array(self::RELATION_HAS_ONE, 'Image', 'imageId'),
  );

}


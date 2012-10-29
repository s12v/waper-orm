<?php
/**
 *
 * @author Sergey Novikov <mail@snov.me>
 */
class Model_User_Table extends Waper_DB_Table_Abstract
{
  protected $_scheme = array(
    'id' => array(
      'table' => 'user',
      'field' => 'id',
      'type'  => self::TYPE_INT,
    ),
    'name' => array(
      'table' => 'user_data',
      'field' => 'name',
      'type'  => self::TYPE_STRING,
    ),
  );

  protected $_tables = array(
    'user' => 'id',
    'user_data' => 'id'
  );

  protected $_relations = array(
    'Comments' => array(self::RELATION_HAS_MANY, 'Comment', 'userId'),
    'Images' => array(self::RELATION_HAS_MANY, 'Image', 'userId'),
  );

}


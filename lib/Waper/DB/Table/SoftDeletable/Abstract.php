<?php
/**
 * @package waper
 * @author Sergey Novikov <mail@snov.me>
 */
abstract class Waper_DB_Table_SoftDeletable_Abstract extends Waper_DB_Table_Abstract
{
  /**
   * Количество строк
   * @param array $conditions
   * @param array $options
   * @return int|null
   */
  public function count(Array $conditions=array(), Array $options=array())
  {
    if (isset($options['all'])) {
      unset($options['all']);
      return parent::count($conditions, $options);
    } else {
      return parent::count(array_merge($conditions, array('deleted' => 0)), $options);
    }
  }
  
}


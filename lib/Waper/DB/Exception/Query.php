<?php
/**
 * @package waper
 * @author Sergey Novikov <mail@snov.me>
 */
class Waper_DB_Exception_Query extends Waper_Exception
{
  protected $_query = '';
  
  public function setQuery($query)
  {
    $this->_query = $query;
  }

  public function getQuery()
  {
    return $this->_query;
  }
}

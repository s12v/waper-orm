<?php
/**
 * @package waper
 * @author Sergey Novikov <mail@snov.me>
 */
class Waper_DB_SqlExpression
{
  protected $_expression = null;

  public function __construct($expression)
  {
    $this->_expression = $expression;
  }

  public function __toString()
  {
    return (string)$this->_expression;
  }
}

<?php
/**
 * Ошибка загрузки требуемых данных
 *
 * @package Waper
 * @author Sergey Novikov <mail@snov.me>
 */
class Waper_DB_Exception_Require extends Waper_Exception
{
  public function __construct($message='Не загружены необходимые данные')
  {
    parent::__construct($message);
  }
}

<?php
/**
 * @package waper
 * @author Sergey Novikov <mail@snov.me>
 */
interface Waper_DB_Row_SoftDeletable_Interface
{
  public function isDeleted();
  public function delete($soft=true);
  public function restore();
}

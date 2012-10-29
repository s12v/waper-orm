<?php
/**
 * @package waper
 * @author Sergey Novikov <mail@snov.me>
 */
interface Waper_DB_RowSet_SoftDeletable_Interface
{
  public function delete($soft=true);
}

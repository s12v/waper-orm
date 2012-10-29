<?php
/**
 * @package waper
 * @author Sergey Novikov <mail@snov.me>
 */
interface Waper_DB_RowSet_Interface
{
  public function getRowClass();
  public function getRowSetClass();
  public function getTableClass();

  public function getIds();
  public function getById($id);

  public function load(Array $what, Array $fields=array(), Array $options=array());
  public function loadByIds(Array $ids, Array $fields=array(), Array $options=array());
  public function loadFields(Array $fields, Array $options=array());

  public function push(Waper_DB_Row_Interface $item);

  public function getColumn($name);
  public function getUniqueColumn($name);
  public function setColumn($name, $value);

  public function save();
  public function delete();

//  public function addRelated($label, $object);
  public function unsetRelated($label=null);
}

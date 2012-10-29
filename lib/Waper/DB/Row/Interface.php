<?php
/**
 * @package waper
 * @author Sergey Novikov <mail@snov.me>
 */
interface Waper_DB_Row_Interface
{
  public function getRowClass();
  public function getRowSetClass();
  public function getTableClass();

  public function isLoaded();
  public function getId();

  public function setReadOnly($field=null);
  public function setReadWrite($field=null);

  public function loadById($id, Array $fields=array(), Array $options=array());
  public function load(Array $what, Array $fields=array(), Array $options=array());

  public function setRow(Array $data);
  
  public function save();
  public function delete();

  public function addRelated($label, & $object);
  public function unsetRelated($label=null);

}

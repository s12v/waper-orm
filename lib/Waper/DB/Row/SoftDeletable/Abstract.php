<?php
/**
 * @package waper
 * @author Sergey Novikov <mail@snov.me>
 */
abstract class Waper_DB_Row_SoftDeletable_Abstract
extends Waper_DB_Row_Abstract
implements Waper_DB_Row_SoftDeletable_Interface
{
  /**
   * Загрузка по ID не удаленного объекта
   * @param int $id
   * @param array $fields
   * @param array $options Для загрузки всех объектов, в том числе удаленных нужно задать опцию 'all'
   */
  public function loadById($id, Array $fields=array(), Array $options=array())
  {
    if (isset($options['all'])) {
      unset($options['all']);
      parent::loadById($id, $fields, $options);
    } else {
      parent::load(array($this->_table->getPrimary() => $id, 'deleted' => 0), $fields, $options);
    }
  }

  /**
   * Загрузка по ID не удаленного объекта
   * @param array $conditions
   * @param array $fields
   * @param array $options Для загрузки всех объектов, в том числе удаленных нужно задать опцию 'all'
   */
  public function load(Array $conditions, Array $fields=array(), Array $options=array())
  {
    if (isset($options['all'])) {
      unset($options['all']);
      if (isset($conditions['deleted'])) {
        unset($conditions['deleted']);
      }
    } elseif (!isset($conditions['deleted'])) {
      $conditions['deleted'] = 0;
    }

    parent::load($conditions, $fields, $options);
  }

  /**
   * @param bool|int $soft По умолчанию false, т.к. может вызываться из родительского delete()
   */
  public function beforeDelete($soft=false) { }

  /**
   * @param bool|int $soft По умолчанию false, т.к. может вызываться из родительского delete()
   */
  public function afterDelete($soft=false) { }

  /**
   * "Мягкое" удаление
   * @param bool|int $soft Значение для deleted или self::HARD_DELETE для обычного удаления
   */
  public function delete($soft=true)
  {
    if ($soft) {
      $this->need();
      $this->_table->getDB()->transaction();
      try {
        $this->beforeDelete($soft);
        $this->deleted = (int)$soft;
        $this->save();
        $this->afterDelete($soft);
      } catch (Exception $e) {
        $this->_table->getDB()->rollback();
        throw $e;
      }
      $this->_table->getDB()->commit();
    } else {
      $this->deleteCompletely();
    }
  }

  /**
   * Полное удаление
   */
  public function deleteCompletely()
  {
    parent::delete();
  }

  /**
   * Вызывается перед восстановлением
   */
  public function beforeRestore() { }

  /**
   * Вызывается после восстановления
   */
  public function afterRestore() { }

  /**
   * Восстановление после мягкого удаления
   */
  public function restore()
  {
    $this->need();
    $this->_table->getDB()->transaction();
    try {
      $this->_beforeRestore();
      $this->deleted = 0;
      $this->save();
      $this->_afterRestore();
    } catch (Exception $e) {
      $this->_table->getDB()->rollback();
      throw $e;
    }
    $this->_table->getDB()->commit();
  }

  /**
   *
   * @return bool
   */
  public function isDeleted()
  {
    return (bool)$this->deleted;
  }

}

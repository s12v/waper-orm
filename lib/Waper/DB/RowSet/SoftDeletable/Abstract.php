<?php
/**
 * @package waper
 * @author Sergey Novikov <mail@snov.me>
 */
abstract class Waper_DB_RowSet_SoftDeletable_Abstract
extends Waper_DB_RowSet_Abstract
implements Waper_DB_RowSet_SoftDeletable_Interface
{
  /**
   * Загрузка элементов по primary id
   * @param array $ids
   * @param array $fields
   * @param array $options Для загрузки всех объектов, в том числе удаленных нужно задать опцию 'all'
   */
  public function loadByIds(Array $ids, Array $fields=array(), Array $options=array())
  {
    if (isset($options['all'])) {
      unset($options['all']);
      parent::loadByIds($ids, $fields, $options);
    } else {
      parent::load(array($this->_table->getPrimary() => $ids, 'deleted' => 0), $fields, $options);
    }
  }

  /**
   * Загрузка не удаленных объектов
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
   * @param bool|int $soft
   */
  public function beforeDelete($soft=false)
  {
    foreach ($this as $item) {
      $item->beforeDelete($soft);
    }
  }

  /**
   * @param bool|int $soft
   */
  public function afterDelete($soft=false)
  {
    foreach ($this as $item) {
      $item->afterDelete($soft);
    }
  }

  /**
   * Мягкое удаление
   * @param bool|int $soft
   */
  public function delete($soft=true)
  {
    if ($soft) {
      if ($this->count()) {
        $this->_table->getDB()->transaction();
        try {
          $this->beforeDelete($soft);
          $this->saveColumn('deleted', (int)$soft);
          $this->afterDelete($soft);
        } catch (Exception $e) {
          $this->_table->getDB()->rollback();
          throw $e;
        }
        $this->_table->getDB()->commit();
      }
    } else {
      // Обычное удаление
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
   */
  public function beforeRestore()
  {
    foreach ($this as $item) {
      $item->beforeRestore();
    }
  }

  /**
   */
  public function afterRestore()
  {
    foreach ($this as $item) {
      $item->afterRestore();
    }
  }

  /**
   * Восстановление
   */
  public function restore()
  {
    $this->need();
    $this->_table->getDB()->transaction();
    try {
      $this->beforeRestore();
      $this->saveColumn('deleted', 0);
      $this->afterRestore();
    } catch (Exception $e) {
      $this->_table->getDB()->rollback();
      throw $e;
    }
    $this->_table->getDB()->commit();
  }

}

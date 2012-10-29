<?php
/**
 * @package waper
 * @author Sergey Novikov <mail@snov.me>
 */
abstract class Waper_DB_RowSet_Abstract
implements ArrayAccess, Countable, Iterator, Waper_DB_RowSet_Interface
{
  /**
   * @var Waper_DB_Table_Abstract
   */
  protected $_table;

  /**
   * Основной массив со строками
   * @var array
   */
  protected $_items = array();

  /**
   * Внутренний указатель массива
   * @var int
   */
  protected $_pointer = 0;

  /**
   * Количество элементов
   * @var int
   */
  protected $_count = 0;

  /**
   * Связанные объекты
   * @var array
   */
  protected $_related = array();

  /**
   * Массив для быстрого поиска объектов по ID
   * @var array
   */
  protected $_ids = array();

  public function __construct()
  {
    $this->_table = call_user_func( array($this->getTableClass(), 'getInstance') );
  }

  /**
   * Функция для вызова в цепочке
   * @param int|null $type реакция на требование, 404 или обычная ошибка
   * @return Waper_DB_RowSet_Abstract
   */
  public function need($type=null)
  {
    if (!count($this->_items)) {
      if ((int)$type == 404) {
        throw new Waper_Exception_404();
      } else {
        throw new Waper_DB_Exception_Require();
      }
    } else {
      return $this;
    }
  }

  /**
   * @param mixed $id
   * @return integer
   */
  protected function _getOffsetById($id)
  {
    if (isset($this->_ids[$id]) && isset($this->_items[$this->_ids[$id]])) {
      return $this->_ids[$id];
    } else {
      return null;
    }
  }

  /**
   * Поиск объекта по ID. В случае дублирования ID будет возвращен последний добавленный объект
   * @param mixed $id
   * @return Waper_DB_Row_Interface|null
   */
  public function getById($id)
  {
    if (isset($this->_ids[$id]) && isset($this->_items[$this->_ids[$id]])) {
      return $this->_items[$this->_ids[$id]];
    } else {
      return null;
    }
  }

  /**
   * Возвращает массив ID объектов
   * @return array 
   */
  public function getIds()
  {
    return array_keys($this->_ids);
  }

  /**
   * Массив экранированных ID объектов, для SQL
   * @return array
   */
  protected function _getEscapedIds()
  {
    return $this->_table->escapeIds($this->getIds());
  }

  /**
   * unserialize колонки
   * @param string $name
   */
  public function unserializeColumn($name)
  {
    foreach ($this->_items as $item) {
      $id = $item->getId();
      $value = $item->$name;
      $unserializedName = $name.'Unserialized';
      if ($value && $plain = unserialize($value)) {
        $item->$unserializedName = $plain;
      } else {
        $item->$unserializedName = null;
      }
    }
  }

  /**
   * Массив всех значений заданного поля
   * @param string $name
   * @return array
   */
  public function getColumn($name)
  {
    $values = array();
    foreach ($this->_items as $item) {
      if ($item->isLoaded()) {
        $values[$item->getId()] = $item->$name;
      }
    }

    return $values;
  }

  /**
   * Массив уникальных значений заданного поля
   * Полезно, например, для сбора всех ID
   * @param string $name
   * @return array
   */
  public function getUniqueColumn($name)
  {
    $values = array();
    foreach ($this->_items as $item) {
      if ($item->isLoaded()) {
        $value = $item->$name;
        if (!isset($values[$value])) {
          $values[$value] = $item->getId();
        }
      }
    }
    return array_flip($values);
  }

  /**
   * Поиск первого совпадения по критериям вида array(
   *  'field1' => 'value1',
   *  'field2' => 'value2',
   * );
   *
   * @param array $conditions 
   * @return null|Waper_DB_Row_Abstract
   */
  public function findOne(Array $conditions)
  {
    $matchingItem = null;
    foreach ($this->_items as $item) {
      $match = true;
      foreach ($conditions as $field => $value) {
        if ($item->$field != $value) {
          $match = false;
          break;
        }
      }
      if ($match) {
        $matchingItem = $item;
        break;
      }
    }

    return $matchingItem;
  }

  /**
   * Поиск всех совпадений по критериям вида array(
   *  'field1' => 'value1',
   *  'field2' => 'value2',
   * );
   *
   * @param array $conditions
   * @return null|Waper_DB_RowSet_Abstract
   */
  public function findAll(Array $conditions)
  {
    $matchingSet = null;
    foreach ($this->_items as $item) {
      $match = true;
      foreach ($conditions as $field => $value) {
        if ($item->$field != $value) {
          $match = false;
          break;
        }
      }
      if ($match) {
        if (null === $matchingSet) {
          $class = $this->getRowSetClass();
          $matchingSet = new $class();
        }
        $matchingSet[] = $item;
      }
    }

    return $matchingSet;
  }

  /**
   * Установка поля для всех элементов
   * @param string $name
   * @param mixed $value
   */
  public function setColumn($name, $value)
  {
    foreach ($this->_items as $item) {
      $item->$name = $value;
    }
  }

  /**
   * Единственный способ добавить новый элемент во внутренний массив
   * @param DB_Row_Interface $item
   */
  public function push(Waper_DB_Row_Interface $item)
  {
    if (!$item->isLoaded()) {
      throw new Waper_DB_RowSet_Exception("Запрещено добавление незагруженных элементов");
    }

    // Получаем новое смещение и увеличиваем размер массива
    $key = $this->_count++;

    // Добавляем в массив поиска по ID
    $this->_ids[$item->getId()] = $key;

    // добавляем объект в массив
    $this->_items[$key] = $item;
  }

  /**
   * Добавление набора объектов
   * @param DB_RowSet_Interface $items
   */
  public function merge(Waper_DB_RowSet_Interface $items)
  {
    $ids = $this->getIds();
    foreach ($items as $item) {
      if ($item->isLoaded() && !in_array($item->getIds(), $ids)) {
        $this->push($item);
      }
    }
  }

  /**
   * @return string
   */
  public function getRowClass()
  {
    static $class = null;
    if (null === $class) {
      $class = preg_replace('/RowSet$/i', 'Row', get_class($this));
    }
    return $class;
  }

  /**
   * @return string
   */
  public function getRowSetClass()
  {
    static $class = null;
    if (null === $class) {
      $class = get_class($this);
    }
    return $class;
  }

  /**
   * @return string
   */
  public function getTableClass()
  {
    static $class = null;
    if (null === $class) {
      $class = preg_replace('/RowSet$/i', 'Table', get_class($this));
    }
    return $class;
  }

  /**
   * @return DB_Table_Abstract
   */
  public function getTable()
  {
    return $this->_table;
  }

  /**
   * Сохранение объектов
   */
  public function save()
  {
    foreach ($this->_items as $item) {
      $item->save();
    }
  }

  /**
   * Установка и сохранение колонки
   * @param string $field
   * @param mixed $value
   */
  public function saveColumn($field, $value)
  {
    if ($this->count()) {
      $this->setColumn($field, $value);
      $table = $this->_table->getTableForField($field);
      $sql = "UPDATE `{$table}`
              SET `".$this->_table->getExternalName($field)."` = '".$this->_table->escape($field, $value)."'
              WHERE `".$this->_table->getPrimaryForTable($table)."` IN (".implode(', ', $this->_getEscapedIds()).")";
      $this->_table->getDB()->query($sql);
    }
  }

  /**
   * Вызывается после удаления
   */
  public function afterDelete()
  {
    foreach ($this as $item) {
      $item->afterDelete();
    }
  }

  /**
   * Вызывается перед удалением
   * Требуется для удаления файлов, изменений в связанных таблицах и т.д.
   */
  public function beforeDelete()
  {
    foreach ($this as $item) {
      $item->beforeDelete();
    }
  }

  /**
   * Удаление
   */
  public function delete()
  {
    if ($this->count()) {
      $ids = array();
      foreach ($this->_items as $item) {
        if ($item->isLoaded()) {
          $ids[] = $this->_table->escape($this->_table->getPrimary(), $item->getId());
        }
      }

      if (count($ids)) {
        $queries = array();
        foreach ($this->_table->getTables() as $table) {
          $queries[] = "DELETE FROM `{$table}`
                        WHERE `".$this->_table->getPrimaryForTable($table)."` IN (".implode(',', $ids).")";
        }

        // Удаляем все в одной транзакции
        $this->_table->getDB()->transaction();
        try {
          $this->beforeDelete();
          foreach ($queries as $sql) {
            $this->_table->getDB()->query($sql);
          }
          $this->afterDelete();
        } catch (Exception $e) {
          $this->_table->getDB()->rollback();
          throw $e;
        }
        $this->_table->getDB()->commit();
      }
    }
  }

  /**
   * Загрузка из строк, полученных из БД с помощью fetchMany
   * @param array $rows
   */
  public function setRows(Array $rows)
  {
    foreach ($rows as $row) {
      $className = $this->getRowClass();
      $item = new $className;
      $item->setRow($row);
      $this->push($item);
    }
  }

  /**
   * Загрузка элементов по primary id
   * @param array $ids
   * @param array $fields
   * @param array $options
   * @return Waper_DB_RowSet_Abstract
   */
  public function loadByIds(Array $ids, Array $fields=array(), Array $options=array())
  {
    if (!count($ids)) {
      return;
    }

    // Готовим массив ID
    $dbIds = array();
    foreach ($ids as $id) {
      $dbIds[] = "'".$this->_table->escape($this->_table->getPrimary(), $id)."'";
    }

    // Разбиваем массив полей на 2: выборка из основной таблицы и из других
    // первый массив готовим к отправке в БД, т.е. меняем имена полей и добавляем обратные кавычки
    // второй массив - без изменений, он будет нужен для передачи в loadFields()
    $fetchFromMainTable = array();
    $fetchFromOtherTables = array();
    if (null !== $fields) {
      foreach ($fields as $name) {
        if ($this->_table->getTableForField($name) == $this->_table->getMainTable()) {
          $fetchFromMainTable[] = '`'.$this->_table->getExternalName($name).'`';
        } else {
          $fetchFromOtherTables[] = $name;
        }
      }
    }

    // Выборка из основной таблицы
    $sql = "SELECT `".$this->_table->getPrimaryForTable($this->_table->getMainTable())."` ".
            (count($fetchFromMainTable) ? ', '.implode(', ', $fetchFromMainTable) : '') ."\n".
            "FROM `".$this->_table->getMainTable()."`\n".
            (isset($options['join']) ? $options['join'] : '')."\n".
            "WHERE `".$this->_table->getPrimaryForTable($this->_table->getMainTable())."` IN (".implode(', ', $dbIds).")";
    $rows = $this->_table->fetchMany($sql, $options);
    foreach ($rows as $row) {
      $rowClass = $this->getRowClass();
      $item = new $rowClass;
      $item->setRow($row);
      $this->push($item);
    }

    // Выборка из других таблиц
    if (count($fetchFromOtherTables)) {
      $this->loadFields($fetchFromOtherTables, $options);
    }

    if (isset($options['require']) && $options['require']) {
      $this->need($options['require']);
    }

    return $this;
  }

  /**
   * Поиск и загрузка объектов по заданным полям
   * @param array $conditions array('field1' => $value, 'field2' => array($value1, $value2))
   * @param array $fields
   * @param array $options
   * @return Waper_DB_RowSet_Abstract
   */
  public function load(Array $conditions, Array $fields=array(), Array $options=array())
  {
    // Обработка условий, получаем список SQL-условий и список необходимых таблиц
    $parsedConditions = $this->_table->getParsedConditions($conditions);

    $whereExpressions = $parsedConditions['expressions'];
    $whereTables = $parsedConditions['tables'];

    // Разбиваем массив полей на 2: выборка из таблиц поиска и из других таблиц
    // первый массив готовим к отправке в БД, т.е. меняем имена полей и добавляем обратные кавычки
    // второй массив - без изменений, он будет нужен для передачи в loadFields()
    $fetchFromWhereTables = array();
    $fetchFromOtherTables = array();
    if (count($fields)) {
      foreach ($fields as $name) {
        if (in_array($this->_table->getTableForField($name), $whereTables)) {
          $fetchFromWhereTables[] = '`'.$this->_table->getExternalName($name).'`';
        } else {
          $fetchFromOtherTables[] = $name;
        }
      }
    }

    // составляем условие соответствия по ID (Table1Id = Table2Id AND Table1Id = Table3Id ...)
    $idExpresions = array();
    $mainPrimary = $this->_table->getPrimaryForTable($this->_table->getMainTable());
    foreach ($whereTables as $table) {
      if ($table != $this->_table->getMainTable()) {
        $idExpresions[] = "`{$mainPrimary}` = `".$this->_table->getPrimaryForTable($table)."`";
      }
    }

    // Выборка из таблиц поиска
    $sql = "SELECT `".$this->_table->getPrimaryForTable($this->_table->getMainTable())."`".
            (count($fetchFromWhereTables) ? ', '.implode(', ', $fetchFromWhereTables) : ' '). "\n".
            "FROM ";

    // Готовим экранированные имена таблиц и добавляем JOIN
    $prev = null;
    foreach ($whereTables as $table) {
      $sql .= ($prev ? ', ' : '')."`{$table}`";
      if (isset($options['join']) && $table == $this->_table->getMainTable()) {
        $sql .= ' '.$options['join']."\n";
      }
      $prev = true;
    }

    // Добавляем условия
    if (count($whereExpressions) || count($idExpresions)) {
      $sql .= "\nWHERE ";

      if (count($whereExpressions)) {
        $sql .= '('.implode(' AND ', $whereExpressions).')';
      }

      if (count($whereExpressions) && count($idExpresions)) {
        $sql .= ' AND ';
      }

      if (count($idExpresions)) {
        $sql .= '('.implode(' AND ', $idExpresions).')';
      }
    }

    $rows = $this->_table->fetchMany($sql, $options);
    foreach ($rows as $row) {
      $item = $this->_table->createRow();
      $item->setRow($row);
      $this->push($item);
    }

    // Выборка из других таблиц
    if (count($fetchFromOtherTables)) {
      $this->loadFields($fetchFromOtherTables, $options);
    }

    if (isset($options['require']) && $options['require']) {
      $this->need($options['require']);
    }

    return $this;
  }

  /**
   * Загрузка дополнительных полей для объектов
   * @param array $fields
   * @param array $options
   */
  public function loadFields(Array $fields, Array $options=array())
  {
    if(!count($this) || !count($fields)) {
      return;
    }
    if (isset($options['rowCount'])) {
      unset($options['rowCount']);
    }
    if (isset($options['limit'])) {
      unset($options['limit']);
    }
    if (isset($options['order'])) {
      unset($options['order']);
    }

    // разбиваем поля по таблицам
    $fetchFromTable = array();
    foreach ($fields as $name) {
      $table = $this->_table->getTableForField($name);
      if (!$table) {
        throw new Waper_Exception('Не найдена таблица для поля '.$name);
      }
      $fetchFromTable[$table][] = '`'.$this->_table->getExternalName($name).'`';
    }

    // Загружаем по таблицам
    foreach ($fetchFromTable as $table => $preload) {
      if (count($preload)) {
        $ids = array();
        array_unshift($preload, "`".$this->_table->getPrimaryForTable($table)."`");
        $sql = "SELECT ".implode(', ', $preload)."
                FROM `{$table}`
                WHERE `".$this->_table->getPrimaryForTable($table)."` IN (".implode(', ', $this->_getEscapedIds()).")";
        $rows = $this->_table->fetchMany($sql, $options);
        foreach ($rows as $row) {
          $id = $row[$this->_table->getPrimaryForTable($table)];
          $ids[] = $id;
          $this->getById($id)->setRow($row);
        }
      }
    }
  }

  /**
   * @return string
   */
  public function dump()
  {
    $str = $this->getRowSetClass()." (".$this->count()." items)\n";
    foreach ($this->_items as $item) {
      $str .= $item->dump();
      $str .= "\n";
    }
    $str .= "-----------------------\n";

    return $str;
  }

  /**
   * Имплементация ArrayAccess
   * @param int $key
   * @return boolean
   */
  public function offsetExists($key)
  {
    return isset($this->_items[(int)$key]);
  }

  /**
   * Имплементация ArrayAccess
   * @param int $key
   * @return Row_Abstract
   */
  public function offsetGet($key)
  {
    $key = (int)$key;
    return isset($this->_items[$key]) ? $this->_items[$key] : null;
  }

  /**
   * Разрешен только способ $set[] = $item. $set[123] = $item - запрещен
   *
   * Имплементация ArrayAccess
   * @param int $key
   * @param DB_Row_Interface $item
   */
  public function offsetSet($key, $item)
  {
    if (null !== $key) {
      throw new Waper_DB_RowSet_Exception('Добавление с заданным offset запрещено');
    }
    $this->push($item);
  }

  /**
   * Имплементация ArrayAccess
   * @param int $key
   */
  function offsetUnset($key)
  {
    throw new Waper_DB_RowSet_Exception('Запрещено');
//    $key = (int)$key;
//    if ( ($id = $this->_items[$key]->getId()) ) {
//      unset($this->_ids[$id]);
//    }
//    unset($this->_items[$key]);
  }

  /**
   * Имплементация Coutable
   * @return int
   */
  public function count()
  {
    return $this->_count;
  }

  /**
   * Имплементация Iterator
   * @return Waper_DB_Row_Interface
   * @see Iterator
   */
  public function current()
  {
    return isset($this->_items[$this->_pointer]) ? $this->_items[$this->_pointer] : null;
  }

  /**
   * Имплементация Iterator
   * @return int
   */
  public function key()
  {
    return $this->_pointer;
  }

  /**
   * Имплементация Iterator
   */
  public function next()
  {
    $this->_pointer++;
  }

  /**
   * Имплементация Iterator
   */
  public function rewind()
  {
    $this->_pointer = 0;
  }

  /**
   * Имплементация Iterator
   * @return boolean
   */
  public function valid()
  {
    return ($this->_pointer >= 0) && ($this->_pointer < $this->_count);
  }

  /**
   * Возвращает первый элемент
   * @return Waper_DB_Row_Interface
   */
  public function first()
  {
    return isset($this->_items[0]) ? $this->_items[0] : null;
  }

  /**
   *
   * @return DB_Row_Absract|null
   */
  public function rand()
  {
    if ($this->count()) {
      $keys = array();
      foreach ($this as $key => $value) {
        $keys[] = $key;
      }

      return $this->_items[array_rand($keys)];
    } else {
      return null;
    }
  }

  /**
   * Загрузка связанных данных из других таблиц
   *
   * @param string $label
   * @param array $fields
   * @param array $options
   * @param array $conditions
   * @return mixed
   */
  protected function _loadRelated($label, Array $fields, Array $options, Array $conditions)
  {
    if ( !($relationData = $this->_table->getRelation($label)) ) {
      throw new Waper_DB_RowSet_Exception("Не существует отношение {$label}, проверьте ".$this->getTableClass());
    }
    if (!$this->count()) {
      return null;
    }

    if (Waper_DB_Table_Abstract::RELATION_HAS_ONE == $relationData[0]) {
      $className = 'Model_'.$relationData[1].'_RowSet';
      $objects = new $className();
      if (count($conditions) || isset($relationData[3])) {
        // Указаны условия - объединяем
        $conditions = array_merge(array($objects->getTable()->getPrimary() => $this->getUniqueColumn($relationData[2])), $conditions);
        if (isset($relationData[3])) {
          $conditions = array_merge($relationData[3], $conditions);
        }
        $objects->load($conditions, $fields, $options);
      } else {
        $objects->loadByIds($this->getUniqueColumn($relationData[2]), $fields, $options);
      }

      // Привязка к каждому объекту
      foreach ($this->_items as $item) {
        if (($object = $objects->getById($item->$relationData[2]))) {
          $item->addRelated($label, $object);
        }
      }

      if ($objects->count()) {
        return $objects;
      } else {
        return null;
      }
    } elseif (Waper_DB_Table_Abstract::RELATION_HAS_MANY == $relationData[0]) {
      $className = 'Model_'.$relationData[1].'_RowSet';
      $objects = new $className();
      $conditions = array_merge(array($relationData[2] => $this->getIds()), $conditions);
      if (isset($relationData[3])) {
        $conditions = array_merge($relationData[3], $conditions);
      }
      $objects->load($conditions, $fields, $options);

      // Привязка к каждому объекту
      foreach ($objects as $object) {
        $this->getById($object->$relationData[2])->addRelated($label, $object);
      }

      if ($objects->count()) {
        return $objects;
      } else {
        return null;
      }
    } elseif (Waper_DB_Table_Abstract::RELATION_MANY_MANY == $relationData[0]) {
      $className = 'Model_'.$relationData[1].'_RowSet';
      $objects = new $className();
      if ($this->count()) {
        if (count($relationData) > 3) {
          // Расширенное описание
          $type='extended';
          $middleClassName = 'Model_'.$relationData[2].'_RowSet';
          $ownId = $relationData[3];
          $foreignId = $relationData[4];
          isset($relationData[5]) ? $middleConditions = $relationData[5] : $middleConditions = array();
          $middle = new $middleClassName;
          $middle->load(
            array_merge($middleConditions, array($ownId => $this->getIds())),
            array($ownId, $foreignId),
            $options
          );
          $ids = $middle->getUniqueColumn($foreignId);
        } elseif (preg_match('/(\w+)\((\w+),\s*(\w+)\)/', $relationData[2], $matches)) {
          // Простое описание
          $type='simple';
          $table = $matches[1];
          $ownId = $matches[2];
          $foreignId = $matches[3];

          $sql = "SELECT `{$ownId}` as `ownId`, `{$foreignId}` as `foreignId`
                  FROM `{$table}`
                  WHERE `{$ownId}` IN (".implode(', ', $this->_getEscapedIds()).")";
          $rows = $this->_table->fetchMany($sql, $options);
          $ids = array();
          foreach ($rows as $row) {
            $ids[] = $row['foreignId'];
          }
        } else {
          throw new Waper_Exception("Неправильно задано соотношение {$label}");
        }

        // Загрузка данных
        if (count($ids)) {
          if (count($conditions)) {
            // Указаны условия - объединяем
            $objects->load(
              array_merge($conditions, array($object->getTable()->getPrimary() => $ids)),
              $fields,
              $options
            );
          } else {
            $objects->loadByIds($ids, $fields, $options);
          }

          // Привязка к каждому объекту
          if ($type == 'extended') {
            foreach ($middle as $m) {
              if ( ($own = $this->getById($m->$ownId)) && ($foreign = $objects->getById($m->$foreignId)) ) {
                $own->addRelated($label, $foreign);
              }
            }
          } elseif ($type == 'standart') {
            foreach ($rows as $row) {
              if ( ($own = $this->getById($row['ownId'])) && ($foreign = $objects->getById($row['foreignId'])) ) {
                $own->addRelated($label, $foreign);
              }
            }
          }
        }
      }

      if ($objects->count()) {
        return $objects;
      } else {
        return null;
      }
    }
  }

  /**
   * Связанные данных из других таблиц
   *
   * @param string $label
   * @param array $fields
   * @param array $options
   * @param array $conditions
   * @return mixed
   */
  protected function _getRelated($label, Array $fields=array(), Array $options=array(), Array $conditions=array())
  {
    if (!isset($this->_related[$label])) {
      $this->_related[$label] = $this->_loadRelated($label, $fields, $options, $conditions);
    }

    return $this->_related[$label];
  }

  /**
   * Есть привязанные объекты
   * @param string $label
   * @return bool
   */
  public function hasRelated($label)
  {
    if (!isset($this->_related[$label])) {
      return false;
    } else {
      return (null !== $this->_related[$label]);
    }
  }

  /**
   * @param string $label
   * @param DB_Row_Interface|DB_RowSet_Interface|null $object
   */
//  public function addRelated($label, $object)
//  {
//    if ($object instanceof Waper_DB_Row_Interface) {
//      if (!isset($this->_related[$label])) {
//        $className = $object->getRowSetClass();
//        $this->_related[$label] = new $className;
//      }
//      $this->_related[$label]->push($object);
//    } elseif ($object instanceof Waper_DB_RowSet_Interface) {
//      if (!isset($this->_related[$label])) {
//        $className = $object->getRowSetClass();
//        $this->_related[$label] = new $className;
//      }
//      $this->_related[$label]->merge($object);
//    } elseif (null === $object) {
//      $this->_related[$label]->null;
//    } else {
//      throw new Waper_Exception('Неправильный тип объекта ('.get_class($object).')');
//    }
//  }

  /**
   * Очистка всех связанных данные
   */
  public function unsetRelated($label=null)
  {
    if (null === $label) {
      $this->_related = array();
    } elseif (isset($this->_related[$label])) {
      $this->_related[$label] = null;
    }

    foreach ($this->_items as $item) {
      $item->unsetRelated($label);
    }
  }

  /**
   * Геттер для связанных данных
   *
   * @param string $name
   * @param array $arguments
   */
  public function __call($name, $arguments)
  {
    if (preg_match('/^get/', $name)) {
      $label = preg_replace('/^get/', '', $name);
      if (isset ($arguments[2])) {
        return $this->_getRelated($label, $arguments[0], $arguments[1], $arguments[2]);
      } elseif (isset ($arguments[1])) {
        return $this->_getRelated($label, $arguments[0], $arguments[1]);
      } elseif (isset($arguments[0])) {
        return $this->_getRelated($label, $arguments[0]);
      } else {
        return $this->_getRelated($label);
      }
    } elseif (preg_match('/^has/', $name)) {
      $label = preg_replace('/^has/', '', $name);
      return $this->hasRelated($label);
    } else {
      throw new Waper_Exception('Неизвестный метод '.$name);
    }
  }

}

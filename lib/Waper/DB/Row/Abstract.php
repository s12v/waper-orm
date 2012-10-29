<?php
/**
 * @package waper
 * @author Sergey Novikov <mail@snov.me>
 */
abstract class Waper_DB_Row_Abstract implements Waper_DB_Row_Interface
{
  /**
   * Данные (поле => значение)
   * @var array
   */
  protected $_data = array();

  /**
   * Дополнительные данные, которые не являются полями
   * @var array
   */
  protected $_additionalData = array();

  /**
   * Связанные данные
   * @var array 
   */
  protected $_related = array();

  /**
   * Измененные поля
   * @var array
   */
  protected $_modifiedFields = array();

  /**
   * Поля только для чтения, name => true
   * @var array
   */
  protected $_readOnlyFields = array();

  /**
   * Изменение запрещено
   * @var boolean
   */
  protected $_readOnly = false;

  /**
   * @var Waper_DB_Table_Abstract
   */
  protected $_table = null;

  /**
   * Объект загружен
   * @var boolean
   */
  protected $_isLoaded = false;

  public function __construct()
  {
    $this->_table = call_user_func(array($this->getTableClass(), 'getInstance'));
  }

  /**
   * 
   */
  protected function _requireLoaded()
  {
    if (!$this->isLoaded()) {
      throw new Waper_DB_Exception_Require("Объект не загружен");
    }
  }

  /**
   * Функция для вызова в цепочке.
   * @param int|null $type Тип ошибки при неудаче
   * @return Waper_DB_Row_Abstract
   */
  public function need($type=null)
  {
    if (!$this->isLoaded()) {
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
   * Экранированное значение первичного ключа
   * @return string
   */
  protected function _getEscapedId()
  {
    $this->_requireLoaded();

    return $this->_table->escape($this->_table->getPrimary(), $this->_data[$this->_table->getPrimary()]);
  }

  /**
   * Загрузка одного поля
   * @param string $name
   * @return mixed
   */
  protected function _fetchField($name)
  {
    $this->_requireLoaded();
    
    $dbName = $this->_table->getExternalName($name);
    $table = $this->_table->getTableForField($name);
    if (null === $table) {
      throw new Waper_Exception("Не указана таблица для поля $name");
    }

    $sql = "SELECT `{$dbName}`
              FROM `{$table}`
             WHERE `".$this->_table->getPrimaryForTable($table)."` = '".$this->_getEscapedId()."'";
    if ( ($row = $this->_table->getDB()->fetch($sql)) ) {
      // Приведение к типу
      $type = $this->_table->getType($name);
      $value = $row[$dbName];
      if ($type == Waper_DB_Table_Abstract::TYPE_SERIALIZED) {
        $this->_data[$name] = unserialize($value);
      } else {
        $this->_data[$name] = $this->_castType($type, $value);
      }
      return $this->_data[$name];
    } elseif ( null !== ($default = $this->_table->getDefaultValue($name)) ) {
      $this->_data[$name] = $default;
      return $this->_data[$name];
    } else {
      throw new Waper_Exception("Не удалось загрузить поле {$name} ({$dbName})");
    }
  }

  /**
   * Установка загруженности
   * @param $loaded
   */
  public function setLoaded($loaded=true)
  {
    if ($loaded) {
      if (isset($this->_data[$this->_table->getPrimary()])) {
        $this->setReadOnly($this->_table->getPrimary());
        $this->_isLoaded = true;
      }
    } else {
      $this->setReadWrite($this->_table->getPrimary());
      $this->_isLoaded = false;
    }
  }

  /**
   * Загружен объект или нет
   * @return bool
   */
  public function isLoaded()
  {
    return $this->_isLoaded;
  }

  /**
   * ID объекта
   * @return mixed 
   */
  public function getId()
  {
    return $this->_isLoaded ? $this->_data[$this->_table->getPrimary()] : null;
  }

  /**
   * Установить R/O для поля или объекта (если поле не указано)
   * @param string|null $field
   */
  public function setReadOnly($field=null)
  {
    if (null === $field) {
      $this->_readOnly = true;
    } else {
      $this->_readOnlyFields[$field] = true;
    }
  }

  /**
   * Установить R/W для поля или объекта (если поле не указано)
   * @param string|null $field
   */
  public function setReadWrite($field=null)
  {
    if (null === $field) {
      $this->_readOnly = false;
    } elseif (isset($this->_readOnlyFields[$field])) {
      unset($this->_readOnlyFields[$field]);
    }
  }

  /**
   * @return string
   */
  public function getRowClass()
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
  public function getRowSetClass()
  {
    static $class = null;
    if (null === $class) {
      $class = preg_replace('/Row$/i', 'RowSet', get_class($this));
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
      $class = preg_replace('/Row$/i', 'Table', get_class($this));
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
   * Изменилось ли поле
   * @param string|null $field конкретное поле или null для любого поля
   * @return boolean
   */
  public function isChanged($field=null)
  {
    if (null === $field) {
      return (bool)count($this->_modifiedFields);
    } else {
      return isset($this->_modifiedFields[$field]);
    }
  }

  /**
   * @return string
   */
  public function dump()
  {
    $str = $this->getRowClass()."\n";

    $str .= "fields:\n";
    foreach ($this->_data as $field => $value) {
      $str .= "\t{$field}: {$value}\n";
    }

    if (count($this->_additionalData)) {
      $str .= "additional data:\n";
      foreach ($this->_additionalData as $field => $value) {
        if ($value instanceof Waper_DB_Row_Abstract) {
          $str .= "\t{$field}: ".$value->dump()."\n";
        } else {
          $str .= "\t{$field}: {$value}\n";
        }
      }
    }

    if (count($this->_related)) {
      $str .= count($this->_related)." related items:\n";
      foreach ($this->_related as $item) {
        $str .= $item->dump();
      }
    }

    return $str;
  }

  /**
   * unserialize определенного поля
   * результат в $this->fieldUnserialized
   * @param string $name
   */
  public function unserializeField($name)
  {
    $unserializedName = $name.'Unserialized';
    if ($this->$name && $plain = unserialize($this->$name)) {
      $this->$unserializedName = $plain;
    } else {
      $this->$unserializedName = null;
    }
  }

  /**
   * Получение внутреннего значения в соответствии с типом
   * @param integer $type
   * @param mixed $value
   * @return mixed
   */
  protected function _castType($type, $value)
  {
    if ($value instanceof Waper_DB_SqlExpression) {
      return $value;
    } elseif (Waper_DB_Table_Abstract::TYPE_SERIALIZED == $type) {
      return $value;
    } elseif (Waper_DB_Table_Abstract::TYPE_INT == $type) {
      return (int)$value;
    } elseif (Waper_DB_Table_Abstract::TYPE_STRING == $type ||
              Waper_DB_Table_Abstract::TYPE_DATE == $type) {
      return (string)$value;
    } elseif (Waper_DB_Table_Abstract::TYPE_FLOAT == $type) {
      return (float)$value;
    } else {
      throw new Waper_Exception('Неправильный тип');
    }
  }

  /**
   * Установка данных ассоциативным массивом, полученным из базы ($db->fetch())
   * @param array $row
   */
  public function setRow(Array $row)
  {
    foreach ($row as $name => $value) {
      // Приведение к типу
      if ( ($internalName = $this->_table->getInternalName($name)) ) {
        $type = $this->_table->getType($internalName);
        if ($type == Waper_DB_Table_Abstract::TYPE_SERIALIZED) {
          $this->_data[$internalName] = unserialize($value);
        } else {
          $this->_data[$internalName] = $this->_castType($type, $value);
        }
      } else {
        $this->$name = $value;
      }
    }

    $this->setLoaded();
  }

  /**
   * Загрузка по ID, с указанием полей
   * @param integer|string $id
   * @param array|null $fields
   * @param array $options
   * @return Waper_DB_Row_Abstract
   */
  public function loadById($id, Array $fields=array(), Array $options=array())
  {
    // Поля по таблицам
    $fieldsByTable = array();

    // Primary нужно загружать в любом случае
    $fieldsByTable[$this->_table->getMainTable()][] = '`'.$this->_table->getPrimaryForTable($this->_table->getMainTable()).'`';

    // Разбиваем поля по таблицам
    if (count($fields)) {
      foreach ($fields as $name) {
        $table = $this->_table->getTableForField($name);
        if (!$table) {
          throw new Waper_Exception('Не найдена таблица для поля '. $name);
        }
        $fieldsByTable[$table][] = '`'.$this->_table->getExternalName($name).'`';
      }
    }

    // Выбираем из основной таблицы, первичный ключ и preload для нее
    $sql = "SELECT ".implode(', ', $fieldsByTable[$this->_table->getMainTable()])."
            FROM `".$this->_table->getMainTable()."`
            WHERE `".$this->_table->getPrimaryForTable($this->_table->getMainTable())."` =
                  '".$this->_table->escape($this->_table->getPrimary(), $id)."'";
    if ( ($row = $this->_table->getDB()->fetch($sql, $options)) ) {
      $this->setRow($row);

      // Из основной таблицы уже загрузили
      unset($fieldsByTable[$this->_table->getMainTable()]);

      // Загрузка из дополнительных таблиц
      foreach($fieldsByTable as $table => $fields)
      {
        if (count($fields)) {
          $sql = "SELECT ".implode(', ', $fields)."
                  FROM `{$table}`
                  WHERE `".$this->_table->getPrimaryForTable($table)."` = '".$this->_getEscapedId()."'";
          if ( ($row = $this->_table->getDB()->fetch($sql, $options)) ) {
            $this->setRow($row);
          }
        }
      }
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
   * @return Waper_DB_Row_Abstract
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
    if (null !== $fields) {
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

    // Готовим экранированные имена таблиц
    $whereTablesDB = array();
    foreach ($whereTables as $table) {
      $whereTablesDB[] = "`{$table}`";
    }

    // Выборка из таблиц поиска
    $sql = "SELECT `".$this->_table->getPrimaryForTable($this->_table->getMainTable())."`
            ". (count($fetchFromWhereTables) ? ', '.implode(', ', $fetchFromWhereTables) : '') ."
            FROM ".implode(',', $whereTablesDB)."";

    // Добавляем условия
    if (count($whereExpressions) || count($idExpresions)) {
      $sql .= ' WHERE ';

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

    // В любом случае нужен только один результат
    $options['limit'] = 1;

    if ( ($row = $this->_table->fetch($sql, $options)) ) {
      $this->setRow($row);
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
   * @return null
   */
  public function loadFields(Array $fields, Array $options=array())
  {
    if(!count($fields)) return;
    if(!$this->isLoaded()) return;

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
        $sql = "SELECT ".implode(', ', $preload)."
                FROM `{$table}`
                WHERE `".$this->_table->getPrimaryForTable($table)."` = '".$this->_getEscapedId()."'";
        if ( ($row = $this->_table->getDB()->fetch($sql, $options)) ) {
          $this->setRow($row);
        }
      }
    }
  }

  /**
   * Сохранение
   */
  public function save()
  {
    // Сохраняем, если элемент изменен или новый
    if ($this->isChanged() || !$this->_isLoaded) {
      $this->beforeSave();

      $fieldsByTable = array();
      foreach ($this->_modifiedFields as $name => $null) {
        if ($this->_data[$name] instanceof Waper_DB_SqlExpression) {
          // Задано SQL-выражение
          $fieldsByTable[$this->_table->getTableForField($name)][] =
            "`".$this->_table->getExternalName($name)."` = ".$this->_data[$name];
        } else {
          // Обычные данные (число/строка)
          $fieldsByTable[$this->_table->getTableForField($name)][] =
            "`".$this->_table->getExternalName($name)."` = '".$this->_table->escape($name, $this->_data[$name])."'";
        }
      }

      if ($this->isLoaded()) {
        // объект был загружен, update
        if (count($fieldsByTable) > 1) {
          $transaction = true;
          $this->_table->getDB()->transaction();
        } else {
          $transaction = false;
        }

        try {
          foreach ($fieldsByTable as $table => $fields) {
            $sql = "UPDATE `{$table}`
                    SET ".implode(', ', $fields)."
                    WHERE `".$this->_table->getPrimaryForTable($table)."` = '".$this->_getEscapedId()."'";
            $this->_table->getDB()->query($sql);
          }
        } catch (Exception $e) {
          if ($transaction) {
            $this->_table->getDB()->rollback();
          }
          throw $e;
        }

        if ($transaction) {
          $this->_table->getDB()->commit();
        }
      } else {
        // новый объект, insert
        if (!isset($fieldsByTable[$this->_table->getMainTable()])) {
          // нет полей для основной таблицы, но вставить туда нужно обязательно
          $fieldsByTable[$this->_table->getMainTable()] = array();
          $data = ' VALUES ()';
        } else {
          $data = ' SET '.implode(', ', $fieldsByTable[$this->_table->getMainTable()]);
        }

        if (count($fieldsByTable) > 1) {
          $transaction = true;
          $this->_table->getDB()->transaction();
        } else {
          $transaction = false;
        }

        try {
          // Сохраняем в основную таблицу, чтобы получить значение primary
          $sql = "INSERT INTO `".$this->_table->getMainTable()."`".$data;
          $result = $this->_table->getDB()->query($sql);

          if (!$result) {
            throw new Waper_Exception("Ошибка сохранения: ".$this->_table->getDB()->getError());
          }

          if ( isset($this->_modifiedFields[$this->_table->getPrimary()]) ) {
            // Primary был указан напрямую
            $this->_isLoaded = true;
          } else {
            // Primary не был указан, получаем из базы
            $this->_data[$this->_table->getPrimary()] = $this->_table->getDB()->getInsertedId();
            $this->_isLoaded = true;
          }

          if ($this->_isLoaded) {
            // Данные для основной таблицы больше не нужны
            unset($fieldsByTable[$this->_table->getMainTable()]);

            // Теперь можно сохранять во все оставшиеся таблицы
            $tables = array_diff($this->_table->getTables(), array($this->_table->getMainTable()));
            foreach ($tables as $table) {
              // добавляем primary
              $fields[] = "`".$this->_table->getPrimaryForTable($table)."` = '".$this->_getEscapedId()."'";

              if (isset($fieldsByTable[$table])) {
                $fields = array_merge($fieldsByTable[$table], $fields);
              }

              // сохраняем
              $sql = "INSERT INTO `{$table}`
                      SET ".implode(', ', $fields);
              $this->_table->getDB()->query($sql);
            }
          } else {
            // Ошибка базы?
            throw new Waper_Exception('Объект должен быть загружен');
          }
        } catch (Exception $e) {
          if ($transaction) {
            $this->_table->getDB()->rollback();
          }
          throw $e;
        }

        if ($transaction) {
          $this->_table->getDB()->commit();
        }
      }

      // Сбрасываем измененные поля
      $this->_modifiedFields = array();
    }
  }

  /**
   * Вызывается при удалении.
   * Служит для удаление файлов, обновления индексов и т.п.
   */
  public function beforeDelete() { }

  /**
   * Вызывается при удалении.
   * Служит для удаление файлов, обновления индексов и т.п.
   */
  public function afterDelete()
  {
    $this->_modifiedFields = array();
  }

  /**
   * Вызывается перед сохранением
   */
  public function beforeSave() { }

  /**
   * Удаление
   */
  public function delete()
  {
    $this->_requireLoaded();

    $this->_table->getDB()->transaction();
    try {
      $this->beforeDelete();
      foreach ($this->_table->getTables() as $table) {
        $sql = "DELETE FROM `{$table}`
                WHERE "."`".$this->_table->getPrimaryForTable($table)."` = '".$this->_getEscapedId()."'";
        $this->_table->getDB()->query($sql);
      }
      $this->afterDelete();
    } catch (Exception $e) {
      if ($transaction) {
        $this->_table->getDB()->rollback();
      }
      throw $e;
    }
    $this->_table->getDB()->commit();
  }

  /**
   * @param string $name
   * @return bool
   */
  public function __isset($name)
  {
    return isset($this->_data[$name]) || isset($this->_additionalData[$name]);
  }

  /**
   * @param string $name
   * @return mixed
   */
  public function __get($name)
  {
    if (isset($this->_data[$name])) {
      return $this->_data[$name];
    } elseif (isset($this->_additionalData[$name])) {
      return $this->_additionalData[$name];
    } elseif ($this->isLoaded()) {
      return $this->_fetchField($name);
    } else {
      return null;
    }
  }

  /**
   * @param string $name
   * @param mixed $value
   */
  public function __set($name, $value)
  {
    if ($this->_readOnly) {
      throw new Waper_Exception("Объект защищен от записи");
    }

    if (isset($this->_readOnlyFields[$name])) {
      throw new Waper_Exception("Поле {$name} защищено от записи");
    }

    if ($this->_table->issetField($name)) {
      $newValue = $this->_castType($this->_table->getType($name), $value);
      if (!isset($this->_data[$name]) || $this->_data[$name] !== $newValue) {
        $this->_modifiedFields[$name] = true;
      }
      $this->_data[$name] = $newValue;
    } else {
      // можно добавлять поля, не описанные в схеме, но они не будут сохранены с помощью save()
      $this->_additionalData[$name] = $value;
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
    $this->_requireLoaded();

    if ( !($relationData = $this->_table->getRelation($label)) ) {
      throw new Waper_Exception("Не существует отношение {$label}, проверьте ".$this->getTableClass());
    }

    if (Waper_DB_Table_Abstract::RELATION_HAS_ONE == $relationData[0]) {
      $className = 'Model_'.$relationData[1].'_Row';
      $object = new $className();

      if (count($conditions) || isset($relationData[3])) {
        // Указаны условия - объединяем
        $conditions = array_merge(array($object->getTable()->getPrimary() => $this->$relationData[2]), $conditions);
        if (isset($relationData[3])) {
          $conditions = array_merge($conditions, $this->$relationData[3]);
        }
        $object->load($conditions, $fields, $options);
      } else {
        $object->loadById($this->$relationData[2], $fields, $options);
      }

      if ($object->isLoaded()) {
        return $object;
      } else {
        return null;
      }
    } elseif (Waper_DB_Table_Abstract::RELATION_HAS_MANY == $relationData[0]) {
      $className = 'Model_'.$relationData[1].'_RowSet';
      $object = new $className();
      $conditions = array_merge(array($relationData[2] => $this->getId()), $conditions);
      if (isset($relationData[3])) {
        $conditions = array_merge($relationData[3], $conditions);
      }
      $object->load($conditions, $fields, $options);
      
      if ($object->count()) {
        return $object;
      } else {
        return null;
      }
    } elseif (Waper_DB_Table_Abstract::RELATION_MANY_MANY == $relationData[0]) {
      $className = 'Model_'.$relationData[1].'_RowSet';
      $object = new $className();

      if (count($relationData) > 3) {
        // Расширенное описание
        $middleClassName = 'Model_'.$relationData[2].'_RowSet';
        $ownId = $relationData[3];
        $foreignId = $relationData[4];
        isset($relationData[5]) ? $middleConditions = $relationData[5] : $middleConditions = array();
        $middle = new $middleClassName;
        $middle->load(
          array_merge($middleConditions, array($ownId => $this->getId())),
          array($ownId, $foreignId),
          $options
        );
        $ids = $middle->getUniqueColumn($foreignId);
      } elseif (preg_match('/(\w+)\((\w+),\s*(\w+)\)/', $relationData[2], $matches)) {
        // Простое описание
        $table = $matches[1];
        $ownId = $matches[2];
        $foreignId = $matches[3];

        $sql = "SELECT `{$foreignId}` as `id`
                FROM `{$table}`
                WHERE `{$ownId}` = '".$this->_getEscapedId()."'";
        $rows = $this->_table->fetchMany($sql, $options);
        $ids = array();
        foreach ($rows as $row) {
          $ids[] = $row['id'];
        }
      } else {
        throw new Waper_Exception("Неправильно задано отношение {$label}");
      }

      if (count($ids)) {
        if (count($conditions)) {
          // Указаны условия - объединяем
          $object->load(
            array_merge($conditions, array($object->getTable()->getPrimary() => $ids)),
            $fields,
            $options
          );
        } else {
          $object->loadByIds($ids, $fields, $options);
        }
      }

      if ($object->count()) {
        return $object;
      } else {
        return null;
      }
    }
  }

  /**
   * Есть привязанные объекты, УЖЕ ЗАГРУЖЕННЫЕ с помощью _getRelated
   *
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
   * @param string $label
   * @param mixed $value
   */
  public function setRelated($label, $value)
  {
    if (null === $value || $value instanceof Waper_DB_Row_Interface || $value instanceof Waper_DB_RowSet_Interface) {
      $this->_related[$label] = $value;
    } else {
      throw new Waper_Exception('Неправильный тип объекта');
    }
  }

  /**
   * @param string $label
   * @param DB_Row_Interface|DB_RowSet_Interface|null $object
   */
  public function addRelated($label, & $object)
  {
    if ($object instanceof Waper_DB_Row_Interface) {
      if ( !($relationData = $this->_table->getRelation($label)) ) {
        throw new Waper_Exception("Не существует отношение {$label}, проверьте ".$this->getTableClass());
      }

      if (Waper_DB_Table_Abstract::RELATION_HAS_ONE == $relationData[0]) {
        $this->_related[$label] = $object;
      } else {
        if (!isset($this->_related[$label]) || null === $this->_related[$label]) {
          $className = $object->getRowSetClass();
          $this->_related[$label] = new $className;
        }
        $this->_related[$label]->push($object);
      }
    } elseif ($object instanceof Waper_DB_RowSet_Interface) {
      if ( !($relationData = $this->_table->getRelation($label)) ) {
        throw new Waper_Exception("Не существует отношение {$label}, проверьте ".$this->getTableClass());
      }

      if (Waper_DB_Table_Abstract::RELATION_HAS_ONE == $relationData[0]) {
        throw new Waper_Exception("Нельзя добавить множество при RELATION_HAS_ONE");
      }

      if (!isset($this->_related[$label]) || null === $this->_related[$label]) {
        $className = $object->getRowSetClass();
        $this->_related[$label] = new $className;
      }
      $this->_related[$label]->merge($object);
    } else {
      throw new Waper_Exception('Неправильный тип объекта ('.get_class($object).')');
    }
  }

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

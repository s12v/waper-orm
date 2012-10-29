<?php
/**
 * @package waper
 * @author Sergey Novikov <mail@snov.me>
 */
abstract class Waper_DB_Table_Abstract
{
  const TYPE_INT = 1;
  const TYPE_FLOAT = 2;
  const TYPE_STRING = 3;
  const TYPE_BLOB = 4;
  const TYPE_DATE = 5;
  const TYPE_SERIALIZED = 6;

  const OPERATOR_EQ = 1;
  const OPERATOR_NEQ = 2;
  const OPERATOR_GEQ = 3;
  const OPERATOR_LEQ = 4;
  const OPERATOR_GT = 5;
  const OPERATOR_LT = 6;

  const RELATION_HAS_ONE = 1;
  const RELATION_HAS_MANY = 2;
  const RELATION_MANY_MANY = 3;

  /**
   * @var Array className => instance 
   */
  protected static $_instances = array();
  
  /**
   * Синглетон для всех дочерних классов
   * @return Waper_DB_Table_Abstract 
   */
  public static function getInstance() {
    $className = get_called_class();
    if (!isset(self::$_instances[$className])) {
      self::$_instances[$className] = new static();
    }
    return self::$_instances[$className];
  }
  
  /**
   * Схема данных, пример:
   * scheme['id'] = array(
   *  'table' => 'Table',
   *  'field' => 'id',
   *  'type'  => self::TYPE_INT,
   *  'default' => 123
   * )
   * @var array
   */
  protected $_scheme = array();

  /**
   * Таблицы
   * table name => primary id
   * 
   * @var array
   */
  protected $_tables = array();

  /**
   * Связи с другими таблицами.
   *
   * Структура: метка => array(описание)
   * Метка - это произвольная уникальная строка
   *
   * RELATION_HAS_ONE
   *
   *  Table1 -> Table2
   *  Table1.key = Table2.id
   *  array(self::RELATION_HAS_ONE, 'Table2', 'key', $conditions=array())
   *  $conditions - дополнительные условия для поиска (например, по типу)
   *
   * RELATION_HAS_MANY
   *
   *  Table1 <- Table2
   *  Table1.id = Table2.key
   *  array(self::RELATION_HAS_MANY, 'Table2', 'key', $conditions=array())
   *  $conditions - дополнительные условия для поиска
   *
   * RELATION_MANY_MANY
   *
   *  Table1 <- Middle -> Table2
   *  Table1.id = Middle.key1, Middle.key2 = Table2.key
   *
   *  Первый вариант описания:
   *
   *  array(self::RELATION_MANY_MANY, 'Table2', 'Middle', 'key1', 'key2', $conditions=array())
   *  $conditions - дополнительные условия для поиска в Middle
   *
   *  Второй (упрощенный) вариант, не требует наличия классов Model_Middle_*
   * 
   *  key1, key2 - как в базе данных (SQL)
   *  array(self::RELATION_MANY_MANY, 'Table2', 'Middle(key1,key2)')
   *
   * @var array
   */
  protected $_relations = array();

  /**
   * Ключ
   * @var string
   */
  protected $_primary = 'id';

  /**
   * Трансформация в имена базы данных field => dbField
   * @var array
   */
  protected $_transform = array();

  /**
   * Обратная трансформация dbField => field
   * Заполняется автоматически
   * @var array
   */
  protected $_reverseTransform = array();

  /**
   * @var Waper_DB
   */
  protected $_db;

  protected function __construct()
  {
    $this->_doSelfCheck();
    $this->_db = Waper_DB::getInstance();
    $this->_prepareTransforms();
  }

  /**
   * @return string
   */
  public function getRowClass()
  {
    return preg_replace('/Table$/i', 'Row', get_class($this));
  }

  /**
   * @return string
   */
  public function getRowSetClass()
  {
    return preg_replace('/Table$/i', 'RowSet', get_class($this));
  }

  /**
   * @return string
   */
  public function getTableClass()
  {
    return get_class($this);
  }

  /**
   * Проверка конфигурации
   */
  protected function _doSelfCheck()
  {
    $this->_verifyScheme();
    $this->_requireField($this->_primary);
  }

  /**
   * Все поля для данного объекта (описанные в $_scheme)
   * @return array
   */
  public function getAllFields()
  {
    return array_keys($this->_scheme);
  }

  /**
   * Проверка схемы
   */
  protected function _verifyScheme()
  {
    foreach ($this->_scheme as $name => $params) {
      if (!isset($params['table'])) {
        throw new Waper_Exception("Некорректно запонен массив \$_scheme, отсутствует необходимое поле 'table' для {$name}");
      }
      if (!isset($params['field'])) {
        throw new Waper_Exception("Некорректно запонен массив \$_scheme, отсутствует необходимое поле 'field' для {$name}");
      }
      if (!isset($params['type'])) {
        throw new Waper_Exception("Некорректно запонен массив \$_scheme, отсутствует необходимое поле 'type' для {$name}");
      }
    }
  }

  /**
   * Заполнение массива обратной трансформации
   */
  private function _prepareTransforms()
  {
    foreach ($this->_scheme as $name => $params) {
      $this->_transform[$name] = $params['field'];
      $this->_reverseTransform[$params['field']] = $name;
    }
  }

  /**
   * Требование наличия поля в описании класса
   * @param string $name
   */
  protected function _requireField($name)
  {
    if (!isset($this->_scheme[$name])) {
      throw new Waper_Exception("Незарегистрированное поле {$name}, класс ".$this->getRowClass());
    }
  }

  /**
   * Требование наличия таблицы в описании класса
   * @param string $table
   */
  protected function _requireTable($table)
  {
    if (!isset($this->_tables[$table])) {
      throw new Waper_Exception("Незарегистрированная таблица {$table}, класс ".$this->getTableClass());
    }
  }

  /**
   * @return DB
   */
  public function getDB()
  {
    return $this->_db;
  }

  /**
   * Экранирование поля в зависимости от типа.
   *
   * @param string $name
   * @param mixed $value
   * @return string
   */
  public function escape($name, $value)
  {
    static $cache = array();
    if (isset($cache[$name])) {
      $type = $cache[$name];
    } else {
      $cache[$name] = $type = $this->getType($name);
    }

    if (self::TYPE_INT == $type) {
      return (int)$value;
    } elseif (self::TYPE_SERIALIZED == $type) {
      return $this->_db->escape(serialize($value));
    } else {
      return $this->_db->escape((string)$value);
    }
  }

  /**
   *
   * @param array $ids
   * @return <type>
   */
  public function escapeIds(Array $ids)
  {
    $escaped = array();
    foreach ($ids as $id) {
      $escaped[] = "'".$this->escape($this->getPrimary(), $id)."'";
    }

    return $escaped;
  }

  /**
   * Прямая трансформация name => dbName
   * @param string $name
   * @return string
   */
  public function getExternalName($name)
  {
    return isset($this->_transform[$name]) ?  $this->_transform[$name] : $name;
  }

  /**
   * Обратная трансформация dbName => name
   * @param string $name
   * @return string
   */
  public function getInternalName($name)
  {
    if (isset($this->_reverseTransform[$name])) {
      return $this->_reverseTransform[$name];
    } elseif (in_array($name, $this->_tables)) {
      return $this->_primary;
    } else {
      return null;
    }
  }

  /**
   * Тип для поля
   * @param string $name
   * @return int
   */
  public function getType($name)
  {
    return isset($this->_scheme[$name]['type']) ? $this->_scheme[$name]['type'] : self::TYPE_STRING;
  }

  /**
   * Возможен ли NULL
   * @param string $name
   * @return integer
   */
//  public function getNull($name)
//  {
//    $this->_requireField($name);
//    return isset($this->_scheme[$name]['null']) ? $this->_scheme[$name]['null'] : false;
//  }

  /**
   * Значение по умолчанию для поля
   * @param string $name
   * @return string
   */
  public function getDefaultValue($name)
  {
    if (isset($this->_scheme[$name]['default'])) {
      return $this->_scheme[$name]['default'];
    } else {
      return null;
    }
  }

  /**
   * ID
   * @return string
   */
  public function getPrimary()
  {
    return $this->_primary;
  }

  /**
   * @param string $name
   * @return boolean
   */
  public function issetField($name)
  {
    return isset($this->_scheme[$name]);
  }

  /**
   * ID для таблицы
   * @param string $table
   * @return string
   */
  public function getPrimaryForTable($table)
  {
    $this->_requireTable($table);
    return $this->_tables[$table];
  }

  /**
   * Основная таблица
   * @return string
   */
  public function getMainTable()
  {
    return $this->_scheme[$this->_primary]['table'];
  }

  /**
   * Таблица для поля
   * @param string $name
   * @return string
   */
  public function getTableForField($name)
  {
    if (isset($this->_scheme[$name]['table'])) {
      return $this->_scheme[$name]['table'];
    } else {
      return null;
    }
  }

  /**
   * Все таблицы
   * @return array
   */
  public function getTables()
  {
    return array_keys($this->_tables);
  }

  /**
   * @return array
   */
  public function getRelation($key)
  {
    if (isset($this->_relations[$key])) {
      return $this->_relations[$key];
    } else {
      return null;
    }
  }

  /**
   * @return array 
   */
  public function getRelations()
  {
    return $this->_relations;
  }

  /**
   * @return Waper_DB_Row_Abstract
   */
  public function createRow()
  {
    $className = $this->getRowClass();
    return new $className;
  }

  /**
   * @return Waper_DB_RowSet_Abstract
   */
  public function createRowSet()
  {
    $className = $this->getRowSetClass();
    return new $className;
  }

  /**
   * Поиск и загрузка объектов по заданным полям
   * @param array $conditions array('field1' => $value, 'field2' => array($value1, $value2))
   * @param array $fields
   * @param array $options
   * @return Waper_DB_Row_Abstract
   */
  public function findOne(Array $conditions=array(), Array $fields=array(), Array $options=array())
  {
    $item = $this->createRow();
    $item->load($conditions, $fields, $options);
    if ($item->isLoaded()) {
      return $item;
    } else {
      return null;
    }
  }

  /**
   * @param array $conditions
   * @param array $options
   * @return Waper_DB_RowSet_Abstract
   */
  public function findAll(Array $conditions=array(), Array $fields=array(), Array $options=array())
  {
    $items = $this->createRowSet();
    $items->load($conditions, $fields, $options);
    if ($items->count()) {
      return $items;
    } else {
      return null;
    }
  }

  /**
   * @param array $conditions
   * @param array $options
   * @return Waper_DB_Row_Abstract
   */
  public function findById($id, Array $fields=array(), Array $options=array())
  {
    $className = $this->getRowClass();
    $item = new $className();
    $item->loadById($id, $fields, $options);
    if ($item->isLoaded()) {
      return $item;
    } else {
      return null;
    }
  }

  /**
   * @param array $conditions
   * @param array $options
   * @return Waper_DB_RowSet_Abstract
   */
  public function findByIds(Array $ids, Array $fields=array(), Array $options=array())
  {
    $className = $this->getRowSetClass();
    $items = new $className();
    $items->loadByIds($ids, $fields, $options);
    if ($items->count()) {
      return $items;
    } else {
      return null;
    }
  }

  /**
   * @param string $sql
   * @param array $options
   * @return Waper_DB_RowSet_Abstract|null
   */
  public function query($sql, Array $options=array())
  {
    $className = $this->getRowSetClass();
    $items = new $className();
    $rows = $this->_db->fetchMany($sql, $options);
    $items->setRows($rows);
    if ($items->count()) {
      return $items;
    } else {
      return null;
    }
  }

  /**
   * Оператор для SQL-запроса
   *
   * $field:<operator> => $value
   * Операторы: != или <>, >, <, >=, <=
   *
   * @todo убрать старую запись
   * @param string $field
   * @return int
   */
  protected function _getOperator(& $field)
  {
    $suffix = strrchr($field, ':');
    if (false !== $suffix) {
      if ($suffix == ':!=' || $suffix == ':<>') {
        $operator = self::OPERATOR_NEQ;
      } elseif ($suffix == ':>=') {
        $operator = self::OPERATOR_GEQ;
      } elseif ($suffix == ':<=') {
        $operator = self::OPERATOR_LEQ;
      } elseif ($suffix == ':>') {
        $operator = self::OPERATOR_GT;
      } elseif ($suffix == ':<') {
        $operator = self::OPERATOR_LT;
      } else {
        $operator = self::OPERATOR_EQ;
      }

      $field = str_replace($suffix, '', $field);
    } else {
      // старая запись
      if ($field[0] == '!') {
        $operator = self::OPERATOR_NEQ;
        $field = str_replace('!', '', $field);
      } elseif ($field[0] == '>' && $field[1] == '=') {
        $operator = self::OPERATOR_GEQ;
        $field = str_replace('>=', '', $field);
      } elseif ($field[0] == '<' && $field[1] == '=') {
        $operator = self::OPERATOR_LEQ;
        $field = str_replace('<=', '', $field);
      } elseif ($field[0] == '>') {
        $operator = self::OPERATOR_GT;
        $field = str_replace('>', '', $field);
      } elseif ($field[0] == '<') {
        $operator = self::OPERATOR_LT;
        $field = str_replace('<', '', $field);
      } else {
        $operator = self::OPERATOR_EQ;
      }
    }

    return $operator;
  }

  /**
   * Массив условий, готовый к добавлению в SQL-запрос
   *
   * @param array $conditions
   * @return array
   */
  public function getParsedConditions(Array $conditions)
  {
    $tables = array();
    $expressions = array();
    foreach ($conditions as $field => $value) {
      // Оператор для SQL-запроса
      $operator = $this->_getOperator($field);

      // Массив таблиц, участвующих в поиске
      $table = $this->getTableForField($field);
      if ($table && !in_array($table, $tables)) {
        $tables[] = $table;
      }

      // Массив условий
      $sql = "`".$this->getExternalName($field)."`";
      if (is_array($value)) {
        // Field IN (...)
        if (count($value)) {
          $escapedValues = array();
          foreach ($value as $v) {
            if ($v instanceof Waper_DB_SqlExpression) {
              // прямая передача выражения
              $escapedValues[] = ' '.ltrim($v);
            } else {
              $escapedValues[] = "'".$this->escape($field, $v)."'";
            }
          }

          if (self::OPERATOR_NEQ == $operator) {
            $sql .= ' NOT IN ('.implode(', ', $escapedValues).')';
          } elseif (self::OPERATOR_EQ == $operator) {
            $sql .= ' IN ('.implode(', ', $escapedValues).')';
          } else {
            throw new Waper_Exception('Оператор не поддерживается для массива');
          }
          $expressions[] = $sql;
        } else {
          // не обрабатывается, просто поле не будет участвовать в поиске
        }
      } else {
        // Field = '...'
        if ($value instanceof Waper_DB_SqlExpression) {
          // прямая передача выражения
          $sql .= ' '.ltrim($value);
        } else {
          // обычный параметр (строка или число)
          if (self::OPERATOR_EQ == $operator) {
            $sql .= ' = ';
          } elseif (self::OPERATOR_NEQ == $operator) {
            $sql .= ' <> ';
          } elseif (self::OPERATOR_GEQ == $operator) {
            $sql .= ' >= ';
          } elseif (self::OPERATOR_LEQ == $operator) {
            $sql .= ' <= ';
          } elseif (self::OPERATOR_GT == $operator) {
            $sql .= ' > ';
          } elseif (self::OPERATOR_LT == $operator) {
            $sql .= ' < ';
          } else {
            throw new Waper_Exception("Неизвестный оператор {$operator}");
          }

          $sql .= "'".$this->escape($field, $value)."'";
        }
        $expressions[] = $sql;
      }
    }

    // Основная таблица обязательно должна участвовать в выборке
    if (!in_array($this->getMainTable(), $tables)) {
      $tables[] = $this->getMainTable();
    }

    return array(
      'expressions' => $expressions,
      'tables' => $tables,
    );
  }

  /**
   * Количество строк
   * @param array $conditions
   * @param array $options
   * @return int|null
   */
  public function count(Array $conditions=array(), Array $options=array())
  {
    // Обработка условий, получаем список SQL-условий и список необходимых таблиц
    $parsedConditions = $this->getParsedConditions($conditions);
    $whereExpressions = $parsedConditions['expressions'];
    $whereTables = $parsedConditions['tables'];

    // Составляем условие соответствия по ID (Table1Id = Table2Id AND Table1Id = Table3Id ...)
    $idExpresions = array();
    $mainPrimary = $this->getPrimaryForTable($this->getMainTable());
    foreach ($whereTables as $table) {
      if ($table != $this->getMainTable()) {
        $idExpresions[] = "`{$mainPrimary}` = `".$this->getPrimaryForTable($table)."`";
      }
    }

    // Готовим экранированные имена таблиц
    $whereTablesDB = array();
    foreach ($whereTables as $table) {
      $whereTablesDB[] = "`{$table}`";
    }

    // Выборка из таблиц поиска
    $sql = "SELECT COUNT(*) as `value`
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

    if ( ($row = $this->_db->fetch($sql, $options)) ) {
      return (int)$row['value'];
    } else {
      return null;
    }
  }

  /**
   * Расширение для DB::fetch()
   *
   * @param string $sql
   * @param array $options
   * @return array
   */
  public function fetch($sql, Array $options=array())
  {
    if (isset($options['order'])) {
      $order = $options['order'];
      if (is_array($order)) {
        $external = array();
        foreach ($order as $field => $value) {
          if ( ($dbName = $this->getExternalName($field)) ) {
            $external[$dbName] = $value;
          } else {
            throw new Waper_Exception('Незарегистрированное поле в сортировке, класс '.$this->getTableClass());
          }
        }
        $options['order'] = $external;
      }
    }

    return $this->_db->fetch($sql, $options);
  }

  /**
   * Расширение для DB::fetchMany()
   *
   * @param string $sql
   * @param array $options
   * @return array
   */
  public function fetchMany($sql, Array $options=array())
  {
    if (isset($options['order'])) {
      $order = $options['order'];
      if (is_array($order)) {
        $external = array();
        foreach ($order as $field => $value) {
          if ( ($dbName = $this->getExternalName($field)) ) {
            $external[$dbName] = $value;
          } else {
            throw new Waper_Exception('Незарегистрированное поле в сортировке, класс '.$this->getTableClass());
          }
        }
        $options['order'] = $external;
      }
    }

    return $this->_db->fetchMany($sql, $options);
  }

}


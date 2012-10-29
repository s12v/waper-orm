<?php
/**
 * Работа с БД
 * 
 * @package waper
 * @author Sergey Novikov <mail@snov.me>
 * @todo Заменить прямую работу с Xcache на Zend_Cache_*
 */
class Waper_DB
{
  private $_config;
  private $_connected = false;

  /**
   * @var mysqli
   */
  protected $_mysqli = null;

  /**
   * @var boolean
   */
	protected $_debug = false;

  /**
   * @var boolean|integer 
   */
  protected $_rowCount = null;

  /**
   * @var string
   */
  protected $_cacheSalt = 'waper_db_cache_';

  /**
   * Счетчик вложенных транзакций
   * @var int
   */
  protected $_transactionCount = 0;

  /**
   * Кеш включен
   * @var boolean 
   */
  protected $_cacheEnabled = true;

	protected static $_instances = array();
  
  /**
   * @param string $config
   * @return Waper_DB
   */
	public static function getInstance($config='main')
	{
		if (!isset(self::$_instances[$config]))	{
			self::$_instances[$config] = new self($config);
		}

		return self::$_instances[$config];
	}

  /**
   * @param string $config
   */
  protected function __construct($config)
  {
    $this->_config = Waper_Config::$DATABASES[$config];

    if (Waper_Env::isDebug()) {
      $this->_debug = true;
    }
  }

  /**
   * @param bool $debug
   */
  public function setDebug($debug=true)
  {
    $this->_debug = (bool)$debug;
  }

  /**
   */
  protected function _reconnect()
  {
    $this->_connected = false;
    $this->connect();
  }

  /**
   * Соединение с базой данных
   */
	public function connect()
	{
		if (!$this->_connected)	{
			$this->_startTimer("SQL-connect");

      $maxAttempts = 4;
			$try_counter = 0;
			while ( ($try_counter++ < $maxAttempts) && !($this->_mysqli instanceof mysqli)) {
				$this->_mysqli = new mysqli($this->_config['host'], $this->_config['username'], $this->_config['password'], $this->_config['dbname'], $this->_config['port']);
			}

			$this->_stopTimer("SQL-connect");

			if (mysqli_connect_errno())	{
        throw new Waper_DB_Exception_Connection("Ошибка при установке соединения с MySQL");
			}

			if (!$this->_mysqli->set_charset($this->_config['charset'])) {
        throw new Waper_DB_Exception_Connection("Ошибка при установке кодировки");
			}

			$this->_connected = true;
		}
	}

  /**
   * @return mysqli 
   */
  public function getConnection()
  {
		if (!$this->_connected)	{
      $this->connect();
    }

    return $this->_mysqli;
  }

  /**
   * @param string $query
   * @return string
   */
  protected function _getCacheKey($query)
  {
    return sha1($this->_cacheSalt.$query);
  }

  /**
   * @param string $query
   * @param array $options
   * @return array
   */
  protected function _getFromCache($query)
  {
    $this->_startTimer('[CACHE] '.$query);
    $value = xcache_get($this->_getCacheKey($query));
    $this->_stopTimer('[CACHE] '.$query);
    
    return $value;
  }

  /**
   * @param string $query
   * @param array $data
   * @param integer $ttl
   */
  protected function _putToCache($query, $data, $ttl)
  {
    xcache_set($this->_getCacheKey($query), $data, $ttl);
  }

  /**
   * Применение опций к строке запроса
   *
   * @param string $sql
   * @param array $options
   * @return string
   */
  protected function _buildQuery($sql, Array & $options)
  {
    if (count($options)) {
      if (isset($options['order'])) {
        $orders = array();
        if (is_array($options['order'])) {
          foreach ($options['order'] as $field => $type) {
            $orders[] = '`'.$field.'` '.$type;
          }
        } elseif ($options['order'] instanceof Waper_DB_SqlExpression) {
          $orders[] = $options['order'];
        }
        if (count($orders)) {
          $sql .= ' ORDER BY '.implode(', ', $orders);
        }
        unset($options['order']);
      }
      if (isset($options['limit'])) {
        if (is_array($options['limit'])) {
          $sql .= ' LIMIT '.(int)$options['limit'][0].(isset($options['limit'][1]) ? ', '.(int)$options['limit'][1] : '');
        } else {
          $sql .= ' LIMIT '.(int)$options['limit'];
        }
        unset($options['limit']);
      }
      if (isset($options['rowCount']) && $options['rowCount']) {
        $this->_resetRowCount();
        $sql = preg_replace('/^(\s*)select/i', 'SELECT SQL_CALC_FOUND_ROWS', $sql);
      }
    }

    return $sql;
  }

  /**
   * @param string $sql
   * @param array $options
   * @return MySQLi_Result
   */
	public function query($sql, Array $options=array())
	{
    if (!$this->_connected) {
      $this->connect();
    }

    $errorCount = 0;
    $this->_startTimer($sql);
    for (;;) {
      try {
        if (false === ($result = $this->_mysqli->query($sql))) {
          if (!isset($options['ignore'])) {
            // Обработка ошибки
            $queryException = new Waper_DB_Exception_Query($this->_mysqli->error, $this->_mysqli->errno);
            $queryException->setQuery($sql);
            throw $queryException;
          }
        }
        // Это была успешная попытка
        break;
      } catch (Waper_DB_Exception_Query $e) {
        if ($e->getCode() == 2006 && $errorCount < 3) {
          // Обработка "MySQL server has gone away"
          $errorCount++;
          usleep($errorCount*300000);
          $this->_reconnect();
        } else {
          $e->setQuery($sql);
          throw $e;
        }
      }
    }
    $this->_stopTimer($sql);

    if (isset($options['rowCount']) && $options['rowCount']) {
      $this->_rowCount = null;
      $this->getTotalRowsCount();
    }

		return $result;
	}

  /**
   * Выборка одной строки в виде ассоциативного массива
   *
   * @param string $sql
   * @param array $options
   * @return array|boolean
   */
  public function fetch($sql, Array $options=array())
  {
    $sql = $this->_buildQuery($sql, $options);
    if ( !isset($options['ttl']) || !$options['ttl'] || !$this->_cacheEnabled )  {
      $result = $this->query($sql, $options);
      $data = $result->fetch_assoc();

      if (null === $data) {
        return false;
      } else {
        return $data;
      }
    } else {
      return $this->_fetchUsingCache($sql, $options);
    }
  }

  /**
   * @param string $sql
   * @param array $options
   * @return array|boolean
   */
  protected function _fetchUsingCache($sql, Array $options=array())
  {
    $ttl = $options['ttl'];
    unset($options['ttl']);

    if (null !== ($data = $this->_getFromCache($sql, $options)) )  {
      if ( ($data['time'] + $ttl) > time() ) {
        return $data['value'];
      }
    }

    $data = array(
      'time' => time(),
      'value' => $this->fetch($sql, $options));
    $this->_putToCache($sql, $data, $ttl);

    return $data['value'];
  }

  /**
   * Выборка нескольких строк
   *
   * @param string $sql
   * @param array $options
   * @return array
   */
  public function fetchMany($sql, Array $options=array())
  {
    $sql = $this->_buildQuery($sql, $options);
    if ( !isset($options['ttl']) || !$options['ttl'] || !$this->_cacheEnabled ) {
      $data = array();
      $result = $this->query($sql, $options);
      while ($row = $result->fetch_assoc()) {
        $data[] = $row;
      }

      return $data;
    } else {
      return $this->_fetchManyUsingCache($sql, $options);
    }
  }

  /**
   * @param string $sql
   * @param array $options
   * @return array
   */
  protected function _fetchManyUsingCache($sql, Array $options=array())
  {
    $ttl = $options['ttl'];
    unset($options['ttl']);

    if (null !== ($data = $this->_getFromCache($sql, $options)) ) {
      if ( ($data['time'] + $ttl) > time() ) {
        // Восстанавливаем общее число строк
        if (isset($data['rowCount'])) {
          $this->_rowCount = $data['rowCount'];
        }

        return $data['value'];
      }
    }

    // Значение не найдено, получаем из базы
    $data = array(
      'time' => time(),
      'value' => $this->fetchMany($sql, $options));

    // Сохраняем общее число строк
		if (stristr($sql, 'SQL_CALC_FOUND_ROWS')) {
      $data['rowCount'] = $this->getTotalRowsCount();
    }

    $this->_putToCache($sql, $data, $ttl);
    return $data['value'];
  }

  /**
   * Новая транзакция запускается только один раз
   * При запросе вложенных транзакций только увеличивается счетчик
   * 
   * Это работает, исходя из условия что после старта транзакции
   * ОБЯЗАТЕЛЬНО происходит commit() либо rollback()
   *
   * Пример:
   * 
   * $db->transaction();
   * try {
   *   // ...
   * } catch (Exception $e) {
   *   $db->rollback();
   *   throw $e;
   * }
   * $db->commit();
   */
  public function transaction()
  {
    if (!$this->_transactionCount) {
      $this->query("SET AUTOCOMMIT=0");
      $this->query("START TRANSACTION");
    }
    $this->_transactionCount++;
  }

  /**
   * Коммит происходит только один раз
   * Для вложенных транзакций ничего не делаем, только уменьшаем счетчик
   */
  public function commit()
  {
    if ($this->_transactionCount == 1) {
      $this->query("COMMIT");
      $this->query("SET AUTOCOMMIT=1");
    }

    if (!$this->_transactionCount) {
      throw new Waper_Exception("commit() без транзакции");
    }

    $this->_transactionCount--;
  }

  /**
   * Аналогично commit(), происходит только один раз
   * Для вложенных транзакций ничего не делаем, только уменьшаем счетчик
   */
  public function rollback()
  {
    if ($this->_transactionCount == 1) {
      $this->query("ROLLBACK");
      $this->query("SET AUTOCOMMIT=1");
    }

    if (!$this->_transactionCount) {
      throw new Waper_Exception("rollback() без транзакции");
    }

    $this->_transactionCount--;
  }

  /**
   * В процессе ли транзакция
   * 
   * @return bool 
   */
  public function isTransaction()
  {
    return ($this->_transactionCount > 0);
  }

	/**
   * id последней добавленной строки
   * @return integer id
   */
	public function getInsertedId()
	{
		return $this->_mysqli->insert_id;
	}

  /**
   * affected_rows
   * @return integer
   */
  public function getAffectedRowsCount()
  {
    return (int)$this->_mysqli->affected_rows;
  }

  /**
   * Возвращает число строк для последнего запроса с использованием SQL_CALC_FOUND_ROWS
   * @return integer|boolean
   */
  public function getTotalRowsCount()
  {
		if (null === $this->_rowCount) {
      if (false !== ($count = $this->fetch("SELECT FOUND_ROWS() as value"))) {
        $this->_rowCount = $count['value'];
      }
		}

    return (int)$this->_rowCount;
  }

  /**
   * @return string 
   */
  public function getError()
  {
    return $this->_mysql->error;
  }

  /**
   * Сброс сохраненного числа строк
   */
  protected function _resetRowCount()
  {
    $this->_rowCount = null;
  }

  /**
   * Экранирование строки
   * @param string $str
   * @return string 
   */
	public function escape($str)
	{
	  if (!$this->_connected) {
  	  $this->connect();
    }

    return $this->_mysqli->real_escape_string($str);
	}

  /**
   * @param string $label
   */
  protected function _startTimer($label)
  {
    if ($this->_debug) {
      Waper_Timer::start($label);
    }
  }

  /**
   * @param string $label
   */
  protected function _stopTimer($label)
  {
    if ($this->_debug) {
      Waper_Timer::stop($label);
    }
  }

}

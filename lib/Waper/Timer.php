<?php
/**
 * @package waper
 * @author Sergey Novikov <mail@snov.me>
 */
class Waper_Timer
{
  protected static $_data = array();
  protected static $_stack = array();

  public static function start($label)
  {
    if (!isset(self::$_data[$label])) {
      self::$_data[$label] = array();
    }

    if (!isset(self::$_stack[$label])) {
      self::$_stack[$label] = array();
    }

    self::$_data[$label][] = array(microtime(true), null);
    self::$_stack[$label][] = count(self::$_data[$label]) - 1;
  }

  public static function stop($label)
  {
    if (!isset(self::$_data[$label])) {
      self::$_data[$label] = array();
      self::$_data[$label][] = array(null, null);
    }

    if (!isset(self::$_stack[$label])) {
      self::$_stack[$label] = array();
    }

    if (count(self::$_stack[$label])) {
      $index = array_pop(self::$_stack[$label]);
      self::$_data[$label][$index][1] = microtime(true);
    }
  }

  /**
   * Время с начала выполнения скрипта
   * return float
   */
  public static function getExecutionTime()
  {
    if (function_exists("xdebug_time_index")) {
      return xdebug_time_index();
    } else {
      return null;
    }
  }

  /**
   * Общее время выполнения всех записей
   * @return float
   */
  public static function getRecordsTime()
  {
    $total = 0;
    foreach (self::$_data as $runs) {
      foreach ($runs as $run) {
        if (null !== $run[0] && null !== $run[1]) {
          $total += ($run[1] - $run[0]);
        }
      }
    }

    return $total;
  }

  /**
   * Число всех записей
   * @return integer
   */
  public static function getRecordsCount()
  {
    $count = 0;
    foreach (self::$_data as $runs) {
      foreach ($runs as $run) {
        if (null !== $run[0] && null !== $run[1]) {
          $count++;
        }
      }
    }

    return $count;
  }

  /**
   * Преобразует внутренние данные в одноуровневый массив
   * @return array
   */
  public static function getStat()
  {
    $data = array();

    foreach (self::$_data as $label => $runs) {
      $row = array(
        'label' => $label,
        'count' => 0,
        'max' => 0,
        'avg' => 0,
        'total' => 0,
        'error' => false
      );

      foreach ($runs as $stat) {
        if (null === $stat[0] || null === $stat[1]) {
          $row['error'] = true;
        }

        if ($row['error']) {
          break;
        }

        $time = $stat[1] - $stat[0];

        $row['count'] += 1;
        $row['total'] += $time;

        if ($time > $row['max']) {
          $row['max'] = $time;
        }
      }

      if (!$row['error'] && $row['count']) {
        $row['avg'] = $row['total'] / $row['count'];
      }

      $data[$label] = $row;
    }

    return $data;
  }

  public static function getRecordsForLabel($label)
  {
    return self::$_data[$label];
  }

}

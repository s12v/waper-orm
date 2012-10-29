<?php
/**
 * Определяет окружение: разработка/дебаг/нормальное(рабочее)
 * 
 * @package waper
 * @author Sergey Novikov <mail@snov.me>
 */
class Waper_Env
{
  const LEVEL_PRODUCTION = 0;
  const LEVEL_DEBUG = 1;
  const LEVEL_DEVEL = 2;

  protected static $_level = null;
  protected static $_userId = 0;

  /**
   */
  protected static function _init()
  {
    if (null == self::$_level) {
      if (Waper_Config::$ENV['dev']) {
        self::$_level = self::LEVEL_DEVEL;
      } elseif(Waper_Config::$ENV['debug'] && 1 == self::$_userId || 113047 == self::$_userId) {
        self::$_level = self::LEVEL_DEBUG;
      } else {
        self::$_level = self::LEVEL_PRODUCTION;
      }
    }
  }

  /**
   * Установка ID пользователя
   * @param int $userId
   */
  public static function setUserId($userId)
  {
    self::$_userId = (int)$userId;
  }

  /**
   * Отладка включена (может быть на рабочей версии)
   * @return bool
   */
  public static function isDebug()
  {
    if (null === self::$_level) {
      self::_init();
    }

    return (self::LEVEL_DEBUG == self::$_level || self::LEVEL_DEVEL == self::$_level);
  }

  /**
   * Локальная версия разработчика
   * @return bool
   */
  public static function isDevel()
  {
    if (null === self::$_level) {
      self::_init();
    }

    return (self::LEVEL_DEVEL == self::$_level);
  }

  /**
   * Рабочая версия
   * @return bool
   */
  public static function isProd()
  {
    if (null === self::$_level) {
      self::_init();
    }

    return (self::LEVEL_PRODUCTION == self::$_level);
  }

}


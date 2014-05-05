<?php
/**
 * Class following Singleton pattern for specific extension configuration
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 5 May 2014
 */
class CRM_Utils_ApiConfig {
  /*
   * singleton pattern
   */
  static private $_singleton = NULL;
  /*
   * location type for default (in First known as 'thuis'
   */
  public $locationThuisId = 0;
  public $locationThuis = NULL;
  /**
   * Constructor function
   */
  function __construct() {
    $this->locationThuisId = 1;
    $this->locationThuis = 'Thuis';
  }
  /**
   * Function to return singleton object
   * 
   * @return object $_singleton
   * @access public
   * @static
   */
  public static function &singleton() {
    if (self::$_singleton === NULL) {
      self::$_singleton = new CRM_Utils_ApiConfig();
    }
    return self::$_singleton;
  }
}

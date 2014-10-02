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
  public $locationVgeAdres = NULL;
  public $locationVgeAdresId = 0;
  /*
   * custom group id's for huurovereenkomst and koopovereenkomst
   */
  public $customGroupHhldHovId = 0;
  public $customFieldVgeAdresHhld = 0;
  public $customGroupOrgHovId = 0;
  public $customFieldVgeAdresOrg = 0;
  public $customGroupKovId = 0;
  public $customFieldVgeAdresKov = 0;
  /**
   * Constructor function
   */
  function __construct() {
    $this->locationThuisId = 1;
    $this->locationThuis = 'Thuis';
    $this->locationVgeAdres = 'VGEadres';
    try {
      $this->locationVgeAdresId = civicrm_api3('LocationType', 'Getvalue', array('name' => $this->locationVgeAdres, 'return' => 'id'));
    } catch (CiviCRM_API3_Exception $ex) {
      $this->locationVgeAdresId = 0;
    }
    
    $this->customGroupHhldHovId = $this->getCustomGroupId('Huurovereenkomst (huishouden)');
    $this->customFieldVgeAdresHhld =$this->getCustomField('VGE_adres_First', $this->customGroupHhldHovId);
    
    $this->customGroupOrgHovId = $this->getCustomGroupId('Huurovereenkomst (organisatie)');
    $this->customFieldVgeAdresOrg = $this->getCustomField('vge_adres', $this->customGroupOrgHovId);
    
    $this->customGroupKovId = $this->getCustomGroupId('Koopovereenkomst');
    $this->customFieldVgeAdresKov = $this->getCustomField('VGE_adres_KOV', $this->customGroupKovId);
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
  /**
   * Function to get custom group id
   * @param string $customGroupName
   * @return int $customGroupId
   * @access private
   */
  private function getCustomGroupId($customGroupName) {
    if (!empty($customGroupName)) {
      try {
        $customGroupId = civicrm_api3('CustomGroup', 'Getvalue', array('name' => 
          $customGroupName, 'return' => 'id'));
      } catch (CiviCRM_API3_Exception $ex) {
        $customGroupId = 0;
      }
    } else {
      $customGroupId = 0;
    }
    return $customGroupId;
  }
  /**
   * Function to get custom field id with name and custom_group_id
   * 
   * @param string $customFieldName
   * @param int $customGroupId
   * @return int $customFieldId
   * @access private
   */
  private function getCustomField($customFieldName, $customGroupId) {
    if (!empty($customFieldName) && !empty($customGroupId)) {
      $params = array(
        'name'            => $customFieldName,
        'custom_group_id' => $customGroupId,
        'return'          => 'id');
      try {
        $customFieldId = civicrm_api3('CustomField', 'Getvalue', $params);
      } catch (CiviCRM_API3_Exception $ex) {
        $customFieldId = 0;
      }
    } else {
      $customFieldId = 0;
    }
    return $customFieldId;
  }
  
}

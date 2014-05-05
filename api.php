<?php

require_once 'api.civix.php';

/**
 * Implementation of hook_civicrm_config
 */
function api_civicrm_config(&$config) {
  _api_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function api_civicrm_xmlMenu(&$files) {
  _api_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function api_civicrm_install() {
    /*
     * check if logfile for REST calls exists, create if not
     */
    $logfileExists = CRM_Core_DAO::checkTableExists( 'dgw_restlog' );
    if ( !$logfileExists ) {
        $createLogfile =
" CREATE TABLE `dgw_restlog` (
  `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `log_timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `log_function` varchar(75) COLLATE utf8_unicode_ci NOT NULL,
  `from_ip` varchar(25) COLLATE utf8_unicode_ci DEFAULT NULL,
  `from_user` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `from_host` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `from_uri` varchar(512) COLLATE utf8_unicode_ci DEFAULT NULL,
  `log_message` text COLLATE utf8_unicode_ci,
  `request_timestamp` datetime DEFAULT NULL,
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='log REST interface calls'";
        CRM_Core_DAO::executeQuery( $createLogfile );
    } else {
        CRM_Core_DAO::executeQuery( "TRUNCATE TABLE dgw_restlog" );
    }
  return _api_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function api_civicrm_uninstall() {
  return _api_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function api_civicrm_enable() {
  return _api_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function api_civicrm_disable() {
  return _api_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function api_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _api_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function api_civicrm_managed(&$entities) {
  return _api_civix_civicrm_managed($entities);
}
/**
 * Implementation of hook_civicrm_custom
 * BOS1307269 add or update VGE address when HOV custom record is updated or
 * created
 * 
 * @author Erik Hommel <erik.hommel@civicoop.org>
 * @date 5 May 2014
 */
function api_civicrm_custom($op, $groupId, $entityId, $params) {
  /*
   * BOS1307269
   */
  if ($op == 'create' || $op == 'edit') {
    $apiConfig = CRM_Utils_ApiConfig::singleton();
    if ($groupId == $apiConfig->customGroupHhldHovId || $groupId == $apiConfig->customGroupOrgHovId 
      || $groupId == $apiConfig->customGroupKovId) {
      /*
       * retrieve vge adddress field based on custom group
       */
      switch($groupId) {
        case $apiConfig->customGroupHhldHovId:
          $vgeAddressId = $apiConfig->customFieldVgeAdresHhld;
          break;
        case $apiConfig->customGroupOrgHovId:
          $vgeAddressId = $apiConfig->customFieldVgeAdresOrg;
          break;
        case $apiConfig->customGroupKovId:
          $vgeAddressId = $apiConfig->customFieldVgeAdresKov;
          break;
      }
      foreach ($params as $param) {
        if ($param['custom_field_id'] == $vgeAddressId) {
          /*
           * create or update Vge Address
           */
          _api_civicrm_add_vge_address($param['value'], $entityId);
        }
      }
    }
  }
}
/**
 * Internal function to create a Vge Address for entity
 * 
 * @param string $address
 * @param int $contactId
 */
function _api_civicrm_add_vge_address($address, $contactId) {
  $apiConfig = CRM_Utils_ApiConfig::singleton();
  $addressParts = CRM_Utils_DgwApiUtils::parseVgeAddress($address);
  $addressSelect = "SELECT * FROM civicrm_address WHERE contact_id = %1 AND location_type_id = %2";
  $addressParams = array(
    1 => array($contactId, 'Integer'),
    2 => array($apiConfig->locationVgeAdresId, 'Integer'));
  $dao = CRM_Core_DAO::executeQuery($addressSelect, $addressParams);
  if ($dao->fetch()) {
    $sqlAction = 'UPDATE civicrm_address SET ';
    $sqlWhere = ' WHERE id = '.$dao->id;
  } else {
    $sqlAction = 'INSERT INTO civicrm_address SET ';
    $sqlWhere = '';
  }
  if (!is_array($addressParts)) {
    $sqlFields = 'street_address = %1';
    $sqlValues = array(1 => array($addressParts, 'String'));
  } else {
    $sqlFields[] = 'street_name = %1';
    $sqlValues[1] = array($addressParts['street_name'], 'String');
    $sqlFields[] = 'street_number = %2';
    $sqlValues[2] = array($addressParts['street_number'], 'Integer');
    $sqlFields[] = 'postal_code = %3';
    $sqlValues[3] = array($addressParts['postal_code'], 'String');
    $sqlFields[] = 'city = %4';
    $sqlValues[4] = array($addressParts['city'], 'String');
    $sqlFields[] = 'location_type_id = %5';
    $sqlValues[5] = array($apiConfig->locationVgeAdresId, 'Integer');
    $maxIndex = 6;
    if (empty($sqlWhere)) {
      $sqlFields[] = 'contact_id = %6';
      $sqlValues[6] = array($contactId, 'Integer');
      $maxIndex = 7;
    }
    $streetAddressParams = array('street_name' => $addressParts['street_name'], 'street_number' => $addressParts['street_number']);
    if (isset($addressParts['street_unit'])) {
      $sqlFields[] = 'street_unit = %'.$maxIndex;
      $sqlValues[$maxIndex] = array($addressParts['street_unit'], 'String');
      $streetAddressParams['street_unit'] = $addressParts['street_unit'];
      $maxIndex++;
    }
    $formattedAddress = CRM_Utils_DgwUtils::glueStreetAddressNl($streetAddressParams);
    if (!civicrm_error($formattedAddress)) {
      $sqlFields[] = 'street_address = %'.$maxIndex;
      $sqlValues[$maxIndex] = array($formattedAddress['parsed_street_address'], 'String');
    }
  }
  $sqlAddress = $sqlAction.implode(', ', $sqlFields).$sqlWhere;
  CRM_Core_Error::debug("sqlAddress", $sqlAddress);
  CRM_Core_Error::debug("sqlValues", $sqlValues);
  CRM_Core_DAO::executeQuery($sqlAddress, $sqlValues);
  exit();
}

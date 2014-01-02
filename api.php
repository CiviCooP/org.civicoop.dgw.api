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

<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

require_once '../civicrm.config.php';
require_once 'CRM/Core/Config.php';
$config = CRM_Core_Config::singleton();

/**
 * Parse the pathinfo
 */
$q = "";
if (isset($_SERVER['PATH_INFO']) && strlen($_SERVER['PATH_INFO'])) {
	$q = $_SERVER['PATH_INFO'];
} elseif (isset($_GET['q']) && strlen($_GET['q'])) {
	$q = $_GET['q'];
	unset($_GET['q']);
}

if ($q == "civicrm/login") {
	$_GET['q'] = "civicrm/login";
}

$k = explode("&", $q);
for($i=1; $i < count($k); $i++) {
	$a = explode("=", $k[$i]);
	if (count($a) == 2) {
		$_GET[$a[0]] = $a[1];
	}
}

/**
 * Rename action parameter
 */
if (isset($_GET['action'])) {
	$_GET['dgwaction'] = $_GET['action'];
	unset($_GET['action']);
}
/**
 * Rename entity parameter
 */
if (isset($_GET['entity'])) {
	$_GET['dgwentity'] = $_GET['entity'];
	unset($_GET['entity']);
}

$q = $k[0];
$q = explode("/", $q);
$p ="";
foreach($q as $action) {
	if (strlen($p) && $p == 'dgwcontact') {
		$c = CRM_Utils_DgwApiUtils::parseEntity($action);
		$_GET['entity'] = $c['entity'];
		$_GET['action'] = $c['action'];
	}
	$p = $action;
}

unset($_REQUEST);
$_REQUEST = array();
foreach($_GET as $key => $value) {
	$_REQUEST[$key] = $value;
}
require_once 'CRM/Utils/DgwREST.php';
$rest = new CRM_Utils_DgwREST();

$rest->loadCMSBootstrap();

$return = $rest->run();
if ( isset( $_GET['json']) && $_GET['json'] ) {
    header('Content-Type: text/javascript');
} else {
    header('Content-Type: text/xml' );
}
echo $return;


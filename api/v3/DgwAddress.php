<?php

/*
+--------------------------------------------------------------------+
| CiviCRM version 4.3                                                |
+--------------------------------------------------------------------+
| Copyright CiviCoop Academic Free License v3.02013                  |
+--------------------------------------------------------------------+
| BOS1307269 Erik Hommel <erik.hommel@civicoop.org> 5 May 2014       |
| Translate location type thuis to id 1 independent of CiviCRM       |
+--------------------------------------------------------------------+
*/

/**
 * Function to update an individual address in CiviCRM
 * incoming is either address or adr_refno
 */
function civicrm_api3_dgw_address_update($inparms) {
    /*
     * set superglobal to avoid double update via post or pre hook
     */
    $GLOBALS['dgw_api'] = "nosync";
    $apiConfig = CRM_Utils_ApiConfig::singleton();
    $thuisID = $apiConfig->locationThuisId;
    $thuisType = $apiConfig->locationThuis;
    /*
     * if no address_id or adr_refno passed, error
     */
    if (!isset($inparms['address_id']) && !isset($inparms['adr_refno'])) {
        return civicrm_api3_create_error("Address_id en adr_refno ontbreken beiden");
    }
    if (isset($inparms['address_id'])) {
        $address_id = trim($inparms['address_id']);
    } else {
        $address_id = null;
    }
    if (isset($inparms['adr_refno'])) {
        $adr_refno = trim($inparms['adr_refno']);
    } else {
        $adr_refno = null;
    }
    if (empty($address_id) && empty($adr_refno)) {
        return civicrm_api3_create_error("Address_id en adr_refno ontbreken beiden");
    }
    /*
     * if start_date passed and format invalid, error
     */
    if (isset($inparms['start_date']) && !empty($inparms['start_date'])) {
        $valid_date = CRM_Utils_DgwUtils::checkDateFormat($inparms['start_date']);
        if (!$valid_date) {
            return civicrm_api3_create_error("Onjuiste formaat start_date");
        } else {
            $start_date = $inparms['start_date'];
        }
    }
    /*
     * if end_date passed and format invalid, error
     */
    if (isset($inparms['end_date']) && !empty($inparms['end_date'])) {
        $valid_date = CRM_Utils_DgwUtils::checkDateFormat($inparms['end_date']);
        if (!$valid_date) {
            return civicrm_api3_create_error("Onjuiste formaat end_date");
        } else {
            $end_date = $inparms['end_date'];
        }
    }
    /*
     * if $adr_refno is used and address_id is empty, retrieve address from synchronisation First table
     */
    if (!empty($adr_refno) && empty($address_id)) {
        $address_id = CRM_Utils_DgwApiUtils::getEntityIdFromSyncTable($adr_refno, 'address');
    }
    /*
     * if $address_id is still empty, error
     */
    if (empty($address_id)) {
        return civicrm_api3_create_error("Adres niet gevonden");
    }
    /*
     * check if address exists in CiviCRM
     */
    $checkparms = array("address_id" => $address_id, 'version' => 3);
    $res_check = civicrm_api('Address', 'getsingle', $checkparms);
    if (civicrm_error($res_check)) {
        return civicrm_api3_create_error("Adres niet gevonden");
    }
    $contactID = $res_check['contact_id'];
    /*
     * if location_type is invalid, error
     */
    $params = array();
    $params['version'] = 3;
    $params['address_id'] = $address_id;
    if (isset($inparms['location_type'])) {
        $location_type = trim($inparms['location_type']);
        /*
         * BOS1307269 if location_type = thuis
         */
        if ($location_type == $thuisType || $location_type == strtolower($thuisType)) {
          $location_type_id = $thuisID;
        } else {
          $location_type_id = CRM_Utils_DgwApiUtils::getLocationIdByName($location_type);
          if ($location_type_id == "") {
              return civicrm_api3_create_error("Location_type is ongeldig");
          }
          $params['location_type_id'] = $location_type_id;
        }
    } else {
        $location_type = "";
    }
    /*
     * if is_primary is not 0 or 1, error
     */
    if (isset($inparms['is_primary'])) {
        $is_primary = $inparms['is_primary'];
        if ($is_primary != 0 && $is_primary != 1) {
            return civicrm_api3_create_error("Is_primary is ongeldig");
        }
        $params['is_primary'] = $is_primary;
    }
    /*
     * if start_date > today and location_type is not toekomst, error
     */
    if (isset($start_date) && !empty($start_date)) {
        $start_date = date("Ymd", strtotime($start_date));
        if ($start_date > date("Ymd") && $location_type != "toekomst") {
            return civicrm_api3_create_error("Combinatie location_type en start/end_date ongeldig");
        }
        /*
         * if location_type = toekomst and start_date is not > today, error
         */
        if ($location_type == "toekomst" && $start_date <= date("Ymd")) {
            return civicrm_api3_create_error("Combinatie location_type en start/end_date ongeldig");
        }
    }
    /*
     * if end_date < today and location_type is not oud, error
     */
    if (isset($end_date) && !empty($end_date)) {
        $end_date = date("Ymd", strtotime($end_date));
        if ($end_date < date("Ymd") && $location_type != "oud") {
            return civicrm_api3_create_error("Combinatie location_type en start/end_date ongeldig");
        }
        /*
         * if location_type = oud and end_date is empty or > today, error
         */
        if ($location_type == "oud" && (empty($end_date) || $end_date > date("Ymd"))) {
            return civicrm_api3_create_error("Combinatie location_type en start/end_date ongeldig");
        }
    }
    /*
     * if postal_code passed an format invalid, error
     */
    if (isset($inparms['postal_code']) && !empty($inparms['postal_code'])) {
        $valid_postal = CRM_Utils_DgwUtils::checkPostcodeFormat(trim($inparms['postal_code']));
        if (!$valid_postal) {
            return civicrm_api3_create_error("Postcode is ongeldig");
        } else {
            $postal_code = trim($inparms['postal_code']);
        }
        $params['postal_code'] = $postal_code;
    }
    /*
     * if country_iso passed, check if country iso code exists in CiviCRM
     */
    if (isset($inparms['country_iso']) && !empty($inparms['country_iso'])) {
        $country_iso = trim($inparms['country_iso']);
        $countries = CRM_Core_PseudoConstant::countryIsoCode();
        $country_id = array_search($country_iso, $countries);
        if (!$country_id) {
            return civicrm_api3_create_error("Country_iso $country_iso komt niet voor");
        }
        $params['country_id'] = $country_id;
    }
    /*
     * if street_number passed and not numeric, error
     */
    if (isset($inparms['street_number'])) {
        $street_number = trim($inparms['street_number']);
        if (!empty($street_number) && !is_numeric($street_number)) {
            return civicrm_api3_create_error( "Huisnummer is niet numeriek");
        } elseif(!empty($street_number)) {
            $params['street_number'] = $street_number;
        }
    }
    $oudID =  CRM_Utils_DgwApiUtils::getLocationIdByName("Oud");
    if ($thuisID == "" || $oudID == "") {
        return civicrm_api3_create_error("Location types zijn niet geconfigureerd");
    }
    /*
     * all validation passed
     */
    if (isset($inparms['street_name'])) {
        $params['street_name'] = trim($inparms['street_name']);
    }
    if (isset($inparms['street_suffix'])) {
        $params['street_unit'] = trim($inparms['street_suffix']);
    }
    if (isset($inparms['city'])) {
        $params['city'] = trim($inparms['city']);
    }
    if (isset($inparms['location_type'])) {
      $params['location_type_id'] = $location_type_id;
    }
    /*
     * compute street address
     */
    $street_address = $res_check['street_name'];
    if (isset($params['street_name'])) {
        $street_address = $params['street_name'];
    }
    if (isset($params['street_number'])) {
        $street_address = $street_address." ".$params['street_number'];
    } elseif (isset($res_check['street_number'])) {
        $street_address = $street_address." ".$res_check['street_number'];
    }
    if (!empty($params['street_suffix'])) {
        $street_address = $street_address.$params['street_suffix'];
    } elseif (isset($res_check['street_unit'])) {
        $street_address = $street_address.$res_check['street_unit'];
    }
    $params['street_address'] = trim($street_address);
    /*
     * if location_type = toekomst or oud, set start and end date in add.
     * field
     */
    if ($location_type == "oud" || $location_type == "toekomst") {
        if (isset($start_date) && !empty($start_date)) {
            $datum = date("d-m-Y", strtotime($start_date));
            $sup = "(Vanaf $datum";
        }
        if (isset($end_date) && !empty($end_date)) {
            $datum = date("d-m-Y", strtotime($end_date));
            if (isset($sup) && !empty($sup)) {
                $sup = $sup." tot $datum)";
            } else {
                $sup = "(Tot $datum)";
            }
        } else {
            $sup = $sup.")";
        }
    }
    /*
     * issue 132: set supplemental address for end date if not location type oud
     */
    if ($location_type != "oud" && isset($end_date) && !empty($end_date)) {
        $datum = date("d-m-Y", strtotime($end_date));
        $sup = "(Tot $datum)";
    }
    /*
     * issue 139, Erik Hommel, 30 nov 2010
     * If current location_type_id = 1 and end_date is passed
     * as parm and not in future, make address "Oud"
     */
    if (isset($end_date) && !empty($end_date)) {
        if ($res_check['location_type_id'] == $thuisID && $end_date <= date("Ymd")) {
            $params['location_type_id'] = $oudID;
            _removeAllOudAddresses($contactID, $oudID);
        }
    }
    /*
     * update address with new values
     */
    if (isset($sup) && !empty($sup)) {
        $params['supplemental_address_1'] = $sup;
    }
    if (isset($start_date) && !empty($start_date)) {
        $params['start_date'] = $start_date;
    }
    if (isset($end_date) && !empty($end_date)) {
        $params['end_date'] = $end_date;
    }
    $res_update = civicrm_api('Address', 'Create', $params);
    if (civicrm_error($res_update)) {
        return civicrm_api3_create_error('Onbekende fout: '.$res_update['error_msg']);
    }
    /*
     * set new adr_refno in synctable if passed
     */
    if (isset($inparms['adr_refno']) && !empty($inparms['adr_refno'])) {
        $refno = trim($inparms['adr_refno']);
        $key_first_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('key_first');
        $change_date_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('change_date');
        $group = CRM_Utils_DgwApiUtils::retrieveCustomGroupByName('Synchronisatie_First_Noa');
        $fields = CRM_Utils_DgwApiUtils::retrieveCustomValuesForContactAndCustomGroupSorted($res_check['contact_id'], $group['id']);
        $fid = "";
        foreach($fields as $key => $field) {
            if ($field['entity_id'] == $address_id  && $field['entity'] == "address") {
                $fid = ":".$key;
                break;
            }
        }
        $changeDate = date('Ymd');
        $civiparms2 = array (
            'version' => 3,
            'entity_id' => $res_check['contact_id'],
            'custom_'.$key_first_field['id'].$fid => $inparms['adr_refno'],
            'custom_'.$change_date_field['id'].$fid => $changeDate,
            );
        $civicres2 = civicrm_api('CustomValue', 'Create', $civiparms2);
    }
    /*
     * issue 239: if there is only one address left, make this primary
     */
    $outparms['is_error'] = "0";
    unset($GLOBALS['dgw_api']);
    return $outparms;
}
/**
 * Function to delete an address in CiviCRM
 */
function civicrm_api3_dgw_address_delete($inparms) {
    /*
     * set superglobal to avoid double delete via post or pre hook
     */
    $GLOBALS['dgw_api'] = "nosync";
    /*
     * if no address_id or adr_refno passed, error
     */
    if (!isset($inparms['address_id']) && !isset($inparms['adr_refno'])) {
        return civicrm_api3_create_error("Address_id en adr_refno ontbreken beiden");
    }
    if (isset($inparms['address_id'])) {
        $address_id = trim($inparms['address_id']);
    } else {
        $address_id = null;
    }
    if (isset($inparms['adr_refno'])) {
        $adr_refno = trim($inparms['adr_refno']);
    } else {
        $adr_refno = null;
    }
    if (empty($address_id) && empty($adr_refno)) {
        return civicrm_api3_create_error("Address_id en adr_refno ontbreken beiden");
    }
    /*
     * if $adr_refno is used, retrieve $address_id from synchronisation First table
     */
    if (!empty($adr_refno)) {
        $address_id = CRM_Utils_DgwApiUtils::getEntityIdFromSyncTable($adr_refno, 'address');
    }
    /*
     * if $address_id is still empty, error
     */
    if (empty($address_id)) {
        return civicrm_api3_create_error("Adres niet gevonden");
    }
    /*
     * check if address exists in CiviCRM
     */
    $checkparms = array("address_id" => $address_id, 'version' => 3);
    $res_check = civicrm_api('Address', 'getsingle', $checkparms);
    if (civicrm_error($res_check)) {
        return civicrm_api3_create_error("Adres niet gevonden");
    }
    /*
     * all validation passed, delete address from table
     */
    $address = array(
        'version'   =>  3,
        'id'        =>  $address_id
        );
    $res = civicrm_api( 'Address', 'delete', $address );
    $outparms['is_error'] = "0";
    unset($GLOBALS['dgw_api']);
    return $outparms;
}
/**
 * function to create address
 * @param type $inparms
 * @return string
 */
function civicrm_api3_dgw_address_create($inparms) {
    /*
     * set superglobal to avoid double create via post or pre hook
     */
    $GLOBALS['dgw_api'] = "nosync";
    $apiConfig = CRM_Utils_ApiConfig::singleton();
    $thuisID = $apiConfig->locationThuisId;
    $thuisType = $apiConfig->locationThuis;
    /*
     * if no contact_id or persoonsnummer_first passed, error
     */
    if (!isset($inparms['contact_id']) && !isset($inparms['persoonsnummer_first'])) {
        return civicrm_api3_create_error("Contact_id en persoonsnummer_first ontbreken beiden");
    }
    if (isset($inparms['contact_id'])) {
        $contact_id = trim($inparms['contact_id']);
    } else {
        $contact_id = null;
    }
    if (isset($inparms['persoonsnummer_first'])) {
        $pers_nr = trim($inparms['persoonsnummer_first']);
    } else {
        $pers_nr = null;
    }
    if (empty($contact_id) && empty($pers_nr)) {
        return civicrm_api3_create_error("Contact_id en persoonsnummer_first ontbreken beiden");
    }
    /*
     * if no location_type passed, error
     */
    if (!isset($inparms['location_type'])) {
        return civicrm_api3_create_error("Location_type ontbreekt");
    } else {
        $location_type = strtolower(trim($inparms['location_type']));
    }
    /*
     * if no is_primary passed, default to 0
     */
    if (!isset($inparms['is_primary'])) {
        $is_primary = 0;
    } else {
        $is_primary = trim($inparms['is_primary']);
    }
    /*
     * if no street_name passed, error
     */
    if (!isset($inparms['street_name'])) {
        return civicrm_api3_create_error("Street_name ontbreekt");
    } else {
        $street_name = trim($inparms['street_name']);
    }
    /*
     * if no city passed, error
     */
    if (!isset($inparms['city'])) {
        return civicrm_api3_create_error("City ontbreekt");
    } else {
        $city = trim($inparms['city']);
    }
    /*
     * if start_date passed and format invalid, error
     */
    if (isset($inparms['start_date']) && !empty($inparms['start_date'])) {
        $valid_date = CRM_Utils_DgwUtils::checkDateFormat($inparms['start_date']);
        if (!$valid_date) {
            return civicrm_api3_create_error("Onjuiste formaat start_date");
        } else {
            $start_date = $inparms['start_date'];
        }
    }
    /*
     * if end_date passed and format invalid, error
     */
    if (isset($inparms['end_date']) && !empty($inparms['end_date'])) {
        $valid_date = CRM_Utils_DgwUtils::checkDateFormat($inparms['end_date']);
        if (!$valid_date) {
            return civicrm_api3_create_error("Onjuiste formaat end_date");
        } else {
            $end_date = $inparms['end_date'];
        }
    }
    $persoonsnummer_first_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('persoonsnummer_first');
    /*
     * if contact not in civicrm, error
     */
    if (isset($pers_nr)) {
        $checkparms = array("custom_".$persoonsnummer_first_field['id'] => $pers_nr);
    } else {
        $checkparms = array("contact_id" => $contact_id);
    }
    $checkparms['version'] = 3;
    $check_contact = civicrm_api('Contact', 'get', $checkparms);
    if (civicrm_error($check_contact)) {
        return civicrm_api3_create_error("Contact niet gevonden");
    } else {
        if (isset($check_contact['count']) && $check_contact['count'] == 0) {
            return civicrm_api3_create_error("Contact niet gevonden");
        }
        $check_contact = reset($check_contact['values']);
        $contact_id = $check_contact['contact_id'];
    }
    /*
     * if location_type is invalid, error
     * BOS1307269 incoming location_type 'thuis' is 1 else retrieve from utils
     */
    if ($location_type == strtolower($thuisType)) {
      $location_type_id = $thuisID;
    } else {
      $location_type_id = CRM_Utils_DgwApiUtils::getLocationIdByName($location_type);
      if ($location_type_id == "") {
          return civicrm_api3_create_error("Location_type is ongeldig");
      }
    }
    /*
     * if is_primary is not 0 or 1, error
     */
    if ($is_primary != 0 && $is_primary != 1) {
        return civicrm_api3_create_error("Is_primary is ongeldig");
    }
    /*
     * if start_date > today and location_type is not toekomst, error
     */
    if (isset($start_date) && !empty($start_date)) {
        $start_date = date("Ymd", strtotime($start_date));
        if ($start_date > date("Ymd") && $location_type != "toekomst") {
            return civicrm_api3_create_error("Combinatie location_type en start/end_date ongeldig");
        }
        /*
         * if location_type = toekomst and start_date is not > today, error
         */
        if ($location_type == "toekomst" && $start_date <= date("Ymd")) {
            return civicrm_api3_create_error("Combinatie location_type en start/end_date ongeldig");
        }
    }
    /*
     * if end_date < today and location_type is not oud, error
     */
    if (isset($end_date) && !empty($end_date)) {
        $end_date = date("Ymd", strtotime($end_date));
        if ($end_date < date("Ymd") && $location_type != "oud") {
            return civicrm_api3_create_error("Combinatie location_type en start/end_date ongeldig");
        }
        /*
         * if location_type = oud and end_date is empty or > today, error
         */
        if ($location_type == "oud") {
            if (empty($end_date) || $end_date > date("Ymd")) {
                return civicrm_api3_create_error("Combinatie location_type en start/end_date ongeldig");
            }
        }
    }
    /*
     * if country_iso does not exist in CiviCRM, error
     */
    if (isset($inparms['country_iso'])) {
        $country_iso = trim($inparms['country_iso']);
        $countries = CRM_Core_PseudoConstant::countryIsoCode();
        $country_id = array_search($country_iso, $countries);
        if (!$country_id) {
            return civicrm_api3_create_error("Country_iso ".$country_iso." komt niet voor");
        }
    }
    /*
     * if postcode entered and invalid format, error
     */
    if (isset($inparms['postal_code'])) {
        $postcode = trim($inparms['postal_code']);
        $valid = CRM_Utils_DgwUtils::checkPostcodeFormat($postcode);
        if (!$valid) {
            return civicrm_api3_create_error("Postcode ".$postcode." is ongeldig");
        }
    }
    /*
     * all validation passed
     */
    $oudID =  CRM_Utils_DgwApiUtils::getLocationIdByName("Oud");
    if ($thuisID == "" || $oudID == "") {
        return civicrm_api3_create_error("Location types zijn niet geconfigureerd");
    }
    /*
     * issue 132 : if new address has type Thuis, check if there is
     * already an address Thuis. If so, move the current Thuis to
     * location type Oud first
     */
    if ($location_type_id == $thuisID) {
        _replaceCurrentAddress($contact_id, $thuisID, $oudID, $start_date);
        /*
         * issue 158: if location_type = Thuis, is_primary = 1
        */
        if ($location_type_id == $thuisID) {
                $is_primary = 1;
        }
    }
    /*
     *  Add address to contact with standard civicrm api Address Create
     */
    $addressParams = array(
        "location_type_id" =>  $location_type_id,
        "is_primary"       =>  $is_primary,
        "city"             =>  $city,
        "street_address"   =>  "",
        'contact_id'       => $contact_id,
        'version'          => 3,
        );
    if (isset($street_name)) {
        $addressParams['street_name'] = $street_name;
        $addressParams['street_address'] = $street_name;
    }
    if (isset($inparms['street_number'])) {
        $addressParams['street_number'] = trim($inparms['street_number']);
        if (empty($addressParams['street_address'])) {
            $addressParams['street_address'] = trim($inparms['street_number']);
        } else {
            $addressParams['street_address'] = $addressParams['street_address']." ".trim($inparms['street_number']);
        }
    }
    if (isset($inparms['street_suffix'])) {
        $addressParams['street_unit'] = trim($inparms['street_suffix']);
        if (empty($addressParams['street_address'])) {
            $addressParams['street_address'] = trim($inparms['street_suffix']);
        } else {
            $addressParams['street_address'] = trim($addressParams['street_address'])." ".trim($inparms['street_suffix']);
        }
    }
    if (isset($postcode)) {
        $addressParams['postal_code'] = $postcode;
    }
    if (isset($country_id)) {
        $addressParams['country_id'] = $country_id;
    }
    /*
     * if location_type = toekomst or oud, set start and end date in add field
     */
    if ($location_type == "oud" || $location_type == "toekomst") {
        if (isset($start_date) && !empty($start_date)) {
            $datum = date("d-m-Y", strtotime($start_date));
            $addressParams['supplemental_address_1'] = "(Vanaf $datum";
        }
        if (isset($end_date) && !empty($end_date)) {
            $datum = date("d-m-Y", strtotime($end_date));
            if (isset($addressParams['supplemental_address_1']) && !empty($addressParams['supplemental_address_1'])) {
                $addressParams['supplemental_address_1'] = $addressParams['supplemental_address_1']." tot ".$datum.")";
            } else {
                $addressParams['supplemental_address_1'] = "(Tot ".$datum.")";
            }
        }
    }
    /*
     * BOS1411088 if location_type == toekomst and address already exists,
     * no error but no action either
     */
    $outparms = array();
    if ($location_type == 'toekomst') {
      $check_toekomst_result_address_id = _check_toekomst_result_address_id($addressParams);
      if (!empty($check_toekomst_result_address_id)) {
        $outparms['address_id'] = $check_toekomst_result_address_id;
        $outparms['is_error'] = '0';
      }
    }
    if (empty($outparms)) {
      $res_adr = civicrm_api('Address', 'create', $addressParams);
      if (civicrm_error($res_adr)) {
        return civicrm_api3_create_error("Onverwachte fout van CiviCRM, adres kon niet gemaakt worden, melding : ".$res_adr['error_message']);
      } else {
        /*
         * retrieve address_id from result array
         */
        $address_id = $res_adr['id'];
        /*
         * for synchronization with First Noa, add record to table for
         * synchronization if adr_refno passed as parameter
         */
        if (isset($inparms['adr_refno'])) {
          $action_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('action');
          $entity_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('entity');
          $entity_id_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('entity_id');
          $key_first_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('key_first');
          $change_date_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('change_date');
          $changeDate = date('Ymd');
          $civiparms5 = array (
            'version' => 3,
            'entity_id' => $contact_id,
            'custom_'.$action_field['id'] => "none",
            'custom_'.$entity_field['id'] => "address",
            'custom_'.$entity_id_field['id'] => $address_id,
            'custom_'.$key_first_field['id'] => $inparms['adr_refno'],
            'custom_'.$change_date_field['id'] => $changeDate
            );
          $civicres5 = civicrm_api('CustomValue', 'Create', $civiparms5);
        }
        /*
         * return array
         */
        $outparms['address_id'] = $address_id;
        $outparms['is_error'] = "0";
      } 
    }
    unset($GLOBALS['dgw_api']);
    return $outparms;
}
/**
 * Function to get addresss for a contact
*/
function civicrm_api3_dgw_address_get($inparms) {
    /*
     * initialize output parameter array
     */
    $outparms = array("");
    $civiparms = array (
        'version' => 3,
    );
    /*
     * if contact_id empty and address_id empty, error
     */
    if (!isset($inparms['contact_id']) && !isset($inparms['address_id'])) {
        return civicrm_api3_create_error("Geen contact_id of address_id doorgegeven
            in dgwcontact_addressget.");
    }
    if (empty($inparms['contact_id']) && empty($inparms['address_id'])) {
        return civicrm_api3_create_error("Contact_id en address_id allebei leeg in
            dgwcontact_addressget.");
    }
    /*
     * if contact_id not numeric, error
     */
    if (!empty($inparms['contact_id'])) {
        $contact_id = trim($inparms['contact_id']);
        if (!is_numeric($contact_id)) {
            return civicrm_api3_create_error( 'Contact_id '.$contact_id.' heeft
                niet numerieke waarden in dgwcontact_addressget');
        }
    }
    if (isset($contact_id)) {
        $civiparms['contact_id'] = $contact_id;
    }
    if (isset($inparms['address_id']) && !empty($inparms['address_id'])) {
        $civiparms['id'] = $inparms['address_id'];
    }
    /*
     * Use the adress api
     */
    $civires1 = civicrm_api('address', 'get', $civiparms);
    if (civicrm_error($civires1)) {
        return civicrm_api3_create_error($civires1['error_message']);
    }
    $i = 1;
    foreach ($civires1['values'] as $result) {
        /* Get location type name */
        // BOS1307269 use Thuis if location_type_id = $thuisID
        $apiConfig = CRM_Utils_ApiConfig::singleton();
        $thuisId = $apiConfig->locationThuisId;
        $thuisType = $apiConfig->locationThuis;
        if ($result['location_type_id'] == $thuisId) {
          $locationType = $thuisType;
        } else {
          $locationType = CRM_Utils_DgwApiUtils::getLocationByid($result['location_type_id']);
        }
        /*
         * params exactly in expected sequence because NCCW First does not
         * cope otherwise
         */
        if (isset($contact_id)) {
            $outparms[$i]['contact_id'] = $contact_id;
        } else {
            $outparms[$i]['contact_id'] = "";
        }
        if (isset($result['id'])) {
            $outparms[$i]['address_id'] = $result['id'];
        } else {
            $outparms[$i]['address_id'] = "";
        }
        if (isset($locationType)) {
            $outparms[$i]['location_type'] = $locationType;
        } else {
            $outparms[$i]['location_type'] = "";
        }
        if (isset($result['is_primary'])) {
            $outparms[$i]['is_primary'] = $result['is_primary'];
        } else {
            $outparms[$i]['is_primary'] = 0;
        }
        if (isset($result['street_address'])) {
            $outparms[$i]['street_address'] = $result['street_address'];
        } else {
            $outparms[$i]['street_address'] = "";
        }
        if (isset($result['street_name'])) {
            $outparms[$i]['street_name'] = $result['street_name'];
        } else {
            $outparms[$i]['street_name'] = "";
        }
        if (isset($result['street_number'])) {
            $outparms[$i]['street_number'] = $result['street_number'];
        } else {
            $outparms[$i]['street_number'] = "";
        }
        if (isset($result['street_unit'])) {
            $outparms[$i]['street_suffix'] = $result['street_unit'];
        } else {
            $outparms[$i]['street_suffix'] = "";
        }
        if (isset($result['postal_code'])) {
            $outparms[$i]['postal_code'] = $result['postal_code'];
        } else {
            $outparms[$i]['postal_code'] = "";
        }
        if (isset($result['city'])) {
            $outparms[$i]['city'] = $result['city'];
        } else {
            $outparms[$i]['city'] = "";
        }
        if (isset($result['country_id'])) {
            $outparms[$i]['country_id'] = $result['country_id'];
            if (!empty($result['country_id'])) {
                $outparms[$i]['country'] = CRM_Core_PseudoConstant::country($result['country_id']);
            } else {
                $outparms[$i]['country'] = "";
            }
        } else {
            $outparms[$i]['country_id'] = "";
            $outparms[$i]['country'] = "";
        }
        $outparms[$i]['start_date'] = date("Y-m-d");
        $outparms[$i]['end_date'] = "";   
        $i++;
    }
    $outparms[0]['record_count'] = $i - 1;
    return $outparms;
}
/**
 * Internal function to replace current address
 * @param type $contact_id
 * @param type $thuisID
 * @param type $oudID
 */
function _replaceCurrentAddress($contact_id, $thuisID, $oudID, $startDateNew) {
    $GLOBALS['dgw_api'] = "nosync";
    $civiparms2 = array (
        'version' => 3,
        'contact_id' => $contact_id,
        'location_type_id' => $thuisID
    );
    $civires2 = civicrm_api('Address', 'Get', $civiparms2);
    if (isset($civires2['values']) && is_array($civires2['values'])) {
        /*
         * remove all existing addresses with type Oud
         */
        _removeAllOudAddresses($contact_id, $oudID);
        /*
         * update current thuis address to location type oud
         */
        foreach($civires2['values'] as $aid => $address) {
            $civiparms4 = array (
                'version' => 3,
                'id' => $aid,
                'contact_id' => $contact_id,
                'location_type_id' => $oudID,
            );
            if (!empty($startDateNew)) {
                $endDate = date('d-m-Y', strtotime($startDateNew . ' -1 day'));
                $civiparms4['supplemental_address_2'] = "(tot $endDate )";
            }
            $civires4 = civicrm_api('Address', 'update', $civiparms4);
        }
    }
}
/**
 * Internal function to remove old addresses
 * @param type $contact_id
 * @param type $oudID
 */
function _removeAllOudAddresses($contact_id, $oudID) {
    $GLOBALS['dgw_api'] = "nosync";
    $civiparms3 = array (
        'version' => 3,
        'contact_id' => $contact_id,
        'location_type_id' => $oudID
    );
    $civires3 = civicrm_api('Address', 'get', $civiparms3);


    if (isset($civires3['values']) && is_array($civires3['values'])) {
        foreach($civires3['values'] as $aid => $address) {
            $civiparms4 = array (
                'version' => 3,
                'id' => $aid,
            );
            $civires4 = civicrm_api('Address', 'delete', $civiparms4);
        }
    }
}
/**
 * Function to check if toekomst address already exists
 * BOS1411088
 */
function _check_toekomst_result_address_id($address_params) {
  $address_id = 0;
  if (isset($address_params['contact_id']) && !empty($address_params['contact_id'])) {
    $check_query = _check_toekomst_build_query($address_params);
    if (!empty($check_query)) {
      $dao = CRM_Core_DAO::executeQuery($check_query['query'], $check_query['params']);
      while ($dao->fetch()) {
        $address_id = $dao->id;
      }
    }
  }
  return $address_id;
}
/**
 * Function to set query and params for check address id
 * BOS1411088
 * 
 * @param array $address_params
 * @return array $check_query
 */
function _check_toekomst_build_query($address_params) {
  $check_query = array();
  $count_param = 3;
  $check_query['params'] = array(
    1 => array($address_params['location_type_id'], 'Positive'),
    2 => array($address_params['contact_id'], 'Positive'));
  $where_fields = array(
    'street_name' => 'String', 
    'street_number' => 'Positive', 
    'street_unit' => 'String', 
    'street_address' => 'String', 
    'city' => 'String', 
    'postal_code' => 'String');
  $where_clauses = array();
  foreach ($where_fields as $where_field => $where_type) {
    if (isset($address_params[$where_field])) {
      $where_clause = $where_field.' = %'.$count_param;
      $where_clauses[] = $where_clause;
      $check_query['params'][$count_param] = array($address_params[$where_field], $where_type);
      $count_param++;
    }
  }
  if (!empty($where_clauses)) {
    $check_query['query'] = 'SELECT id FROM civicrm_address '
      . 'WHERE location_type_id = %1 AND contact_id = %2 AND '.implode(' AND ', $where_clauses);
  } else {
    $check_query = array();
  }
  return $check_query;
}
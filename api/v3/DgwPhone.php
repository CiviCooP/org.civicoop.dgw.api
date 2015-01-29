<?php

/*
+--------------------------------------------------------------------+
| CiviCRM version 4.3                                                |
+--------------------------------------------------------------------+
| Copyright CiviCoop Academic Free License v3.02013                  |
+--------------------------------------------------------------------+
| BOS1404581 return get values in expected sequence                  |
| Erik Hommel, 17 April 2014                                         |
+--------------------------------------------------------------------+
| BOS1307269 Erik Hommel <erik.hommel@civicoop.org> 5 May 2014       |
| Translate location type thuis to id 1 independent of CiviCRM       |
+--------------------------------------------------------------------+
*/

/*
 * Function to update an individual phonenumber in CiviCRM
* incoming is either phone_id or cde_refno
*/
function civicrm_api3_dgw_phone_update($inparms) {
    /*
     * set superglobal to avoid double update via post or pre hook
     */
    $GLOBALS['dgw_api'] = "nosync";
    // BOS1307269 introduce api Config class
    $apiConfig = CRM_Utils_ApiConfig::singleton();
    $thuisLocationTypeId = $apiConfig->locationThuisId;
    $thuisLocationType = $apiConfig->locationThuis;
    
    /*
     * if no phone_id or cde_refno passed, error
     */
    if (!isset($inparms['phone_id']) && !isset($inparms['cde_refno'])) {
        return civicrm_api3_create_error("Phone_id en cde_refno ontbreken beiden");
    }
    if (isset($inparms['phone_id'])) {
        $phone_id = trim($inparms['phone_id']);
    } else {
        $phone_id = null;
    }
    if (isset($inparms['cde_refno'])) {
        $cde_refno = trim($inparms['cde_refno']);
        $phone_id = CRM_Utils_DgwApiUtils::getEntityIdFromSyncTable($cde_refno, 'phone');
    } else {
        $cde_refno = null;
    }
    if (empty($phone_id) && empty($cde_refno)) {
        return civicrm_api3_create_error("Phone_id en cde_refno ontbreken beiden");
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
     * if $phone_id is still empty, error
     */
    if (empty($phone_id)) {
        return civicrm_api3_create_error("Phone niet gevonden");
    }

    /*
     * check if phone exists in CiviCRM
     */
    $checkparms = array("phone_id" => $phone_id, 'version' => 3);
    $res_check = civicrm_api('Phone', 'getsingle', $checkparms);
    if (civicrm_error($res_check)) {
        return civicrm_api3_create_error("Phone niet gevonden");
    }
    /*
     * issue 185: if not Toekomst or Oud and phone type = Werktel then
     * location should be work. If not Toekomst or Oud and phone type is
     * Contacttel then location is Thuis
     */
    if (isset($inparms['phone_type']) && strtolower($inparms['phone_type']) == "werktel") {
        if (isset($location_type)) {
            if ($location_type != "toekomst" && $location_type != "oud") {
                $location_type = "werk";
            }
        } else {
            $location_type = "werk";
        }
    }
    if (isset($inparms['phone_type']) && strtolower($inparms['phone_type'] == "contacttel")) {
        if (isset($location_type)) {
            if ($location_type != "toekomst" && $location_type != "oud") {
                $location_type = "thuis";
            }
        } else {
            $location_type = "werk";
        }
    }
    /*
     * if location_type is invalid, error
     */
    if (isset($inparms['location_type'])) {
      // BOS1307269
      if ($inparms['location_type'] == $thuisLocationType) {
        $location_type_id = $thuisLocationTypeId;
      } else {
        $location_type_id = CRM_Utils_DgwApiUtils::getLocationIdByName($inparms['location_type']);
        if ($location_type_id == "") {
                return civicrm_api3_create_error("Location_type is ongeldig");
        }
      }
    }
    /*
     * if phone_type is invalid, error
     */
    if (isset($inparms['phone_type'])) {
        $phone_type_id = false;
        $phone_types = CRM_Core_PseudoConstant::phoneType();
        foreach($phone_types as $key => $type) {
            if (strtolower($type) == strtolower($inparms['phone_type'])) {
                $phone_type_id = $key;
            }
        }
        if ($phone_type_id===false) {
            return civicrm_api3_create_error("Invalid phone type");
        }
    }
    /*
     * if is_primary is not 0 or 1, error
     */
    if (isset($inparms['is_primary'])) {
        if ($inparms['is_primary'] != 0 && $inparms['is_primary'] != 1) {
            return civicrm_api3_create_error("Is_primary is ongeldig");
        }
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
        if ( isset( $location_type ) ) {
            if ($location_type == "toekomst" && $start_date <= date("Ymd")) {
                return civicrm_api3_create_error("Combinatie location_type en start/end_date ongeldig");
            }
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
        if ( isset( $location_type ) ) {
            if ($location_type == "oud") {
                if (empty($end_date) || $end_date > date("Ymd")) {
                    return civicrm_api3_create_error("Combinatie location_type en start/end_date ongeldig");
                }
            }
        }
    }
    /*
     * issue 177 en 180: delete of phone is passed as update from First, where
     * phone is empty. Check this, and if phone is empty delete phone
     */
    if (isset($inparms['phone']) && empty($inparms['phone'])) {
        return civicrm_api('DgwPhone', 'delete', array('version' => 3, 'phone_id' => $phone_id));
    } else {
        /*
         * if location type toekomst or oud, add start and end date after phone
         */
        if (isset($location_type)) {
            if ($location_type == "toekomst") {
                if (isset($start_date) && !empty($start_date)) {
                    $datum = date("d-m-Y", strtotime($start_date));
                    $phone = $phone." (vanaf $datum)";
                }
            }
            if ($location_type == "oud") {
                if (isset($end_date) && !empty($end_date)) {
                    $datum = date("d-m-Y", strtotime($end_date));
                    $phone = $phone." (tot $datum)";
                }
            }
        }
        $params['version'] = 3;
        $params['phone_id'] = $phone_id;
        if (isset($location_type_id)) {
            $params['location_type_id'] = $location_type_id;
        }
        if (isset($phone_type_id)) {
            $params['phone_type_id'] = $phone_type_id;
        }
        if (isset($inparms['phone'])) {
            $params['phone'] = trim($inparms['phone']);
        }
        if (isset($inparms['is_primary'])) {
            $params['is_primary'] = $inparms['is_primary'];
        }
        $res_update = civicrm_api('Phone', 'Create', $params);
        if (civicrm_error($res_update)) {
            return civicrm_api3_create_error('Onbekende fout: '.$res_update['error_msg']);
        }
        /*
         * retrieve phone_id from result array
         */
        $phone_id = $res_update['id'];
        /*
         * for synchronization with First Noa, add record to table for
         * synchronization if cde_refno passed as parameter
         */
        if (isset($inparms['cde_refno'])) {
            $refno = trim($inparms['cde_refno']);
            $key_first_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('key_first');
            $change_date_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('change_date');
            $group = CRM_Utils_DgwApiUtils::retrieveCustomGroupByName('Synchronisatie_First_Noa');
            $fields = CRM_Utils_DgwApiUtils::retrieveCustomValuesForContactAndCustomGroupSorted($res_check['contact_id'], $group['id']);
            $fid = "";
            foreach($fields as $key => $field) {
                if ($field['entity_id'] == $phone_id  && $field['entity'] == "phone") {
                    $fid = ":".$key;
                    break;
                }
            }
            $changeDate = date('Ymd');
            $civiparms2 = array (
                'version' => 3,
                'entity_id' => $res_check['contact_id'],
                'custom_'.$key_first_field['id'].$fid => $inparms['cde_refno'],
                'custom_'.$change_date_field['id'].$fid => $changeDate
            );
            $civicres2 = civicrm_api('CustomValue', 'Create', $civiparms2);
        }
    }
    unset($GLOBALS['dgw_api']);
    $outparms['is_error'] = "0";
    return $outparms;
}

/*
 * Function to delete a phone number in CiviCRM
*/
function civicrm_api3_dgw_phone_delete($inparms) {
    /*
     * set superglobal to avoid double delete via post or pre hook
     */
    $GLOBALS['dgw_api'] = "nosync";
    /*
     * if no phone_id or cde_refno passed, error
     */
    if (!isset($inparms['phone_id']) && !isset($inparms['cde_refno'])) {
        return civicrm_api3_create_error("Phone_id en cde_refno ontbreken beiden");
    }
    if (isset($inparms['phone_id'])) {
        $phone_id = trim($inparms['phone_id']);
    } else {
        $phone_id = null;
    }
    if (isset($inparms['cde_refno'])) {
        $cde_refno = trim($inparms['cde_refno']);
    } else {
        $cde_refno = null;
    }
    if (empty($phone_id) && empty($cde_refno)) {
        return civicrm_api3_create_error("Phone_id en cde_refno ontbreken beiden");
    }
    /*
     * if $cde_refno is used, retrieve phone_id from synchronisation First table
     */
    if (!empty($cde_refno)) {
        $phone_id = CRM_Utils_DgwApiUtils::getEntityIdFromSyncTable($cde_refno, 'phone');
    }
    /*
     * if $phone_id is still empty, error
     */
    if (empty($phone_id)) {
        return civicrm_api3_create_error("Phone niet gevonden");
    }
    /*
     * check if phone exists in CiviCRM
     */
    $checkparms = array("phone_id" => $phone_id, 'version' => 3);
    $res_check = civicrm_api('Phone', 'getsingle', $checkparms);
    if (civicrm_error($res_check)) {
        return civicrm_api3_create_error("Phone niet gevonden");
    }
    /*
     * all validation passed, delete phone from table
     */
    $civiparms = array(
        'version'   =>  3,
        'id'        =>  $phone_id
    );
    $res = civicrm_api('Phone', 'delete', $civiparms);
    unset($GLOBALS['dgw_api']);
    $outparms['is_error'] = "0";
    return $outparms;
}

function civicrm_api3_dgw_phone_create($inparms) {
    /*
     * set superglobal to avoid double create via post or pre hook
     */
    $GLOBALS['dgw_api'] = "nosync";
    // BOS1307269 introduce api Config class
    $apiConfig = CRM_Utils_ApiConfig::singleton();
    $thuisLocationTypeId = $apiConfig->locationThuisId;
    $thuisLocationType = $apiConfig->locationThuis;
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
     * if no is_primary passed, set default to 0
     */
    if (!isset($inparms['is_primary'])) {
        $is_primary = 0;
    } else {
        $is_primary = trim($inparms['is_primary']);
    }
    /*
     * if no phone_type passed, error
     */
    if (!isset($inparms['phone_type'])) {
        return civicrm_api3_create_error("Phone_type ontbreekt");
    } else {
        $phone_type = strtolower(trim($inparms['phone_type']));
    }
    /*
     * if no phone passed, error
     */
    if (!isset($inparms['phone'])) {
        return civicrm_api3_create_error("Phone ontbreekt");
    } else {
        $phone = trim($inparms['phone']);
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
    $check_contact = civicrm_api('Contact', 'getsingle', $checkparms);
    if ( isset( $check_contact['count'] ) ) {
        if ( $check_contact['count'] == 0 ) {
            $persoonsnummer_org_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName( 'nr_in_first' );
            $checkparms = array(
                'version'                                   =>  3,
                'custom_'.$persoonsnummer_org_field['id']   =>  $pers_nr
            );
            $check_contact = civicrm_api('Contact', 'getsingle', $checkparms );
        }
    }
    if ( isset( $check_contact['contact_id'] ) ) {
        $contact_id = $check_contact['contact_id'];
    } else {
        if ( isset( $check_contact['error_message'] ) ) {
            $returnMessage = "Contact niet gevonden, foutmelding van API Contact Getsingle : ".$check_contact['error_message'];
        } else {
            $returnMessage = "Contact niet gevonden";
        }
        return civicrm_api3_create_error( $returnMessage );
    }
    /*
     * if location_type is invalid, error
     */
    // BOS1307269
    if ($location_type == $thuisLocationType || $location_type == strtolower($thuisLocationType)) {
      $location_type_id = $thuisLocationTypeId;
    } else {
      $location_type_id = CRM_Utils_DgwApiUtils::getLocationIdByName($location_type);
      if ($location_type_id == "") {
        return civicrm_api3_create_error("Location_type is ongeldig");
      }
    }
    /*
     * if phone_type is invalid, error
     */
    $phone_type_id = false;
    $phone_types = CRM_Core_PseudoConstant::phoneType();
    foreach($phone_types as $key => $type) {
        if (strtolower($type) == strtolower($phone_type)) {
            $phone_type_id = $key;
        }
    }
    if ($phone_type_id===false) {
        return civicrm_api3_create_error("Invalid phone type");
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
     * if location type toekomst or oud, add start and end date after phone
     */
    if ($location_type == "toekomst") {
        if (isset($start_date) && !empty($start_date)) {
            $datum = date("d-m-Y", strtotime($start_date));
            $phone = $phone." (vanaf $datum)";
        }
    }
    if ($location_type == "oud") {
        if (isset($end_date) && !empty($end_date)) {
            $datum = date("d-m-Y", strtotime($end_date));
            $phone = $phone." (tot $datum)";
        }
    }
    /*
     * Add phone to contact with standard civicrm function civicrm_location_add
     */
    $civiparms = array(
        "contact_id"        =>  $contact_id,
        "location_type_id"  =>  $location_type_id,
        "is_primary"        =>  $is_primary,
        "phone_type_id"     =>  $phone_type_id,
        "phone"             =>  $phone,
        "version"           =>  3);
    $res_phone = civicrm_api('Phone', 'Create', $civiparms);
    if (civicrm_error($res_phone)) {
        return civicrm_api3_create_error("Onverwachte fout van CiviCRM, phone kon niet gemaakt worden, melding : ".$res_phone['error_message']);
    } else {
        /*
         * retrieve phone_id from result array
         */
        $phone_id = $res_phone['id'];
        /*
         * for synchronization with First Noa, add record to table for
         * synchronization if cde_refno passed as parameter
         */
        if (isset($inparms['cde_refno'])) {
            $action_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('action');
            $entity_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('entity');
            $entity_id_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('entity_id');
            $key_first_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('key_first');
            $change_date_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('change_date');
            $changeDate = date('Ymd');
            $civiparms2 = array (
                'version' => 3,
                'entity_id' => $contact_id,
                'custom_'.$action_field['id'] => "none",
                'custom_'.$entity_field['id'] => "phone",
                'custom_'.$entity_id_field['id'] => $phone_id,
                'custom_'.$key_first_field['id'] => $inparms['cde_refno'],
                'custom_'.$change_date_field['id'] => $changeDate
            );
            $civicres2 = civicrm_api('CustomValue', 'Create', $civiparms2);
        }
    }
    /*
     * return array
     */
    $outparms['phone_id'] = $phone_id;
    $outparms['is_error'] = "0";
    unset($GLOBALS['dgw_api']);
    return $outparms;
}

/*
 * Function to get phones for a contact
*/
function civicrm_api3_dgw_phone_get($inparms) {
    // BOS1307269 introduce api Config class
    $apiConfig = CRM_Utils_ApiConfig::singleton();
    $thuisLocationTypeId = $apiConfig->locationThuisId;
    $thuisLocationType = $apiConfig->locationThuis;
    /*
     * initialize output parameter array
     */
    $outparms = array("");
    $civiparms = array (
        'version' => 3,
    );

    /*
     * if contact_id empty and phone_id empty, error
     */
    if (!isset($inparms['contact_id']) && !isset($inparms['phone_id'])) {
        return civicrm_api3_create_error("Geen contact_id of phone_id doorgegeven in
            dgwcontact_phoneget.");
    }
    if (empty($inparms['contact_id']) && empty($inparms['phone_id'])) {
        return civicrm_api3_create_error("Contact_id en phone_id allebei leeg in
           dgwcontact_phoneget.");
    }
    /*
     * if contact_id is used and contains non-numeric data, error
     */
    if (!empty($inparms['contact_id'])) {
        if (!is_numeric($inparms['contact_id'])) {
            return civicrm_api3_create_error("Contact_id bevat ongeldige waarde in
                dgwcontact_phoneget.");
        } else {
            $civiparms['contact_id'] = $inparms['contact_id'];
        }
    }
    /*
     * if phone_id is used and contains non-numeric data, error
     */
    if (!empty($inparms['phone_id']) && !is_numeric($inparms['phone_id'])) {
        return civicrm_api3_create_error("Phone_id bevat ongeldige waarde in
           dgwcontact_phoneget.");
    } else if (!empty($inparms['phone_id'])) {
        $civiparms['phone_id'] = $inparms['phone_id'];
        unset($civiparms['contact_id']); //phone id is use to request a specific phonenumber
    }
    /**
     * Use the phone api
     */
    $civires1 = civicrm_api('phone', 'get', $civiparms);
    if (civicrm_error($civires1)) {
        return civicrm_api3_create_error($civires1['error_message']);
    }
    $i = 1;
    foreach ($civires1['values'] as $result) {
        /* Get location type name */
        // BOS1307269
        if ($result['location_type_id'] == $thuisLocationTypeId) {
          $locationType = $thuisLocationType;
        } else {
          $locationType = CRM_Utils_DgwApiUtils::getLocationByid($result['location_type_id']);
        }
        $result['location_type'] = $locationType;
        /* Get phone type name */
        $civiparms3 = array('version' => 3, 'id' => $result['phone_type_id']);
        $civires3 = civicrm_api('OptionValue', 'getsingle', $civiparms3);
        $sequence = array('contact_id', 'phone_id', 'location_type', 'is_primary', 'phone_type', 'phone', 'start_date', 'end_date');
        if (isset($result['phone_type_id'])) {
          if (!civicrm_error($civires3)) {
            $result['phone_type'] = $civires3['label'];
          }
        }
        $result['phone_id'] = $result['id'];
        $result['start_date'] = date('Y-m-d');
        $result['end_date'] = '';
        unset($result['id']);
        $data = CRM_Utils_DgwApiUtils::setValuesInSeq($sequence, $result);
        $outparms[$i] = $data;
        $i++;
    }
    $outparms[0]['record_count'] = $i - 1;
    return $outparms;
}
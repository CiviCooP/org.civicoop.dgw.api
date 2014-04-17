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
*/

/*
 * Function to delete an e-mailadres in CiviCRM
 */
function civicrm_api3_dgw_email_delete($inparms) {
    /*
     * set superglobal to avoid double delete via post or pre hook
     */
    $GLOBALS['dgw_api'] = "nosync";
    /*
     * if no email_id or cde_refno passed, error
     */
    if (!isset($inparms['email_id']) && !isset($inparms['cde_refno'])) {
        return civicrm_api3_create_error("Email_id en cde_refno ontbreken beiden");
    }
    if (isset($inparms['email_id'])) {
        $email_id = trim($inparms['email_id']);
    } else {
        $email_id = null;
    }
    if (isset($inparms['cde_refno'])) {
        $cde_refno = trim($inparms['cde_refno']);
    } else {
        $cde_refno = null;
    }
    if (empty($email_id) && empty($cde_refno)) {
        return civicrm_api3_create_error("Email_id en cde_refno ontbreken beiden");
    }
    /*
     * if $cde_refno is used, retrieve $email_id from synchronisation First table
    */
    if (!empty($cde_refno)) {
        $email_id = CRM_Utils_DgwApiUtils::getEntityIdFromSyncTable($cde_refno, 'email');
    }
    /*
     * if $email_id is still empty, error
    */
    if (empty($email_id)) {
        return civicrm_api3_create_error("Email niet gevonden");
    }
    /*
     * check if email exists in CiviCRM
    */
    $checkparms = array("email_id" => $email_id, 'version' => 3);
    $res_check = civicrm_api('Email', 'getsingle', $checkparms);
    if (civicrm_error($res_check)) {
        return civicrm_api3_create_error("Email niet gevonden");
    }
    /*
     * all validation passed, delete email from table
     */
    $emailParams = array(
        'version'   =>  3,
        'id'        =>  $email_id
    );
    $res = civicrm_api('Email', 'delete', $emailParams );
    unset($GLOBALS['dgw_api']);
    $outparms['is_error'] = "0";
    return $outparms;
}
/*
 * Function to update an individual emailaddress in CiviCRM
 * incoming is either email_id or cde_refno
 */
function civicrm_api3_dgw_email_update($inparms) {
    /*
     * set superglobal to avoid double update via post or pre hook
     */
    $GLOBALS['dgw_api'] = "nosync";
    /*
     * if no email_id or cde_refno passed, error
     */
    if (!isset($inparms['email_id']) && !isset($inparms['cde_refno'])) {
        return civicrm_api3_create_error("Email_id en cde_refno ontbreken beiden");
    }
    if (isset($inparms['email_id'])) {
        $email_id = trim($inparms['email_id']);
    } else {
        $email_id = null;
    }
    if (isset($inparms['cde_refno'])) {
        $cde_refno = trim($inparms['cde_refno']);
    } else {
        $cde_refno = null;
    }
    if (empty($email_id) && empty($cde_refno)) {
        return civicrm_api3_create_error("Email_id en cde_refno ontbreken beiden");
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
     * if $cde_refno is used, retrieve email_id from synchronisation First table when
     * email is empty
     */
    if (!empty($cde_refno) && empty($email_id)) {
        $email_id = CRM_Utils_DgwApiUtils::getEntityIdFromSyncTable($cde_refno, 'email');
    }
    /*
     * if $email_id is still empty, error
     */
    if (empty($email_id)) {
        return civicrm_api3_create_error("Email niet gevonden");
    }
    /*
     * check if email exists in CiviCRM
     */
    $checkparms = array("email_id" => $email_id, 'version' => 3);
    $res_check = civicrm_api('Email', 'getsingle', $checkparms);
    if (civicrm_error($res_check)) {
        return civicrm_api3_create_error("Email niet gevonden");
    }
    /*
     * if location_type is invalid, error
     */
    if (isset($inparms['location_type'])) {
        $location_type_id = CRM_Utils_DgwApiUtils::getLocationIdByName($inparms['location_type']);
        if ($location_type_id == "") {
            return civicrm_api3_create_error("Location_type is ongeldig");
        } else {
            $location_type = strtolower(trim($inparms['location_type']));
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
 * all validation passed, first retrieve email to get all current values
* for total update record
*/
$params['version'] = 3;
if (isset($inparms['email'])) {
    $params['email'] = trim($inparms['email']);
}
    /*
     * issue 178: if email empty, delete email
     */
    if (isset($params['email']) && empty($params['email'])) {
        return civicrm_api('DgwEmail', 'delete', array('version' => 3, 'email_id' => $email_id));
    } else {
        /*
         * update email with new values
         */
        if (isset($location_type_id)) {
            $params['location_type_id'] = $location_type_id;
        }
        if (isset($inparms['is_primary'])) {
            $params['is_primary'] = $inparms['is_primary'];
        }
        $params['email_id'] = $email_id;
        if (isset($inparms['contact_id'])) {
            $params['contact_id'] = $inparms['contact_id'];
        } else {
            $params['contact_id'] = null;
        }
        /**
         * retrieve email if email is not set to avoid 
         * emptying email
         */
        if (!isset($params['email'])) {
            $res_get = civicrm_api('Email', 'getsingle', array('version'=>3, 'id'=>$email_id));
            if (!civicrm_error($res_get)) {
                $params['email'] = $res_get['email'];
            }
        }
        $res_update = civicrm_api('Email', 'Create', $params);
        if (civicrm_error($res_update)) {
            return civicrm_api3_create_error('Onbekende fout: '.$res_update['error_msg']);
        }
        /*
         * retrieve email_id from result array
         */
        $email_id = $res_update['id'];
        /*
         * set new cde_refno in synctable if passed
         */
        if (isset($inparms['cde_refno']) && !empty($inparms['cde_refno'])) {
            $refno = trim($inparms['cde_refno']);
            $key_first_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('key_first');
            $change_date_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('change_date');
            $group = CRM_Utils_DgwApiUtils::retrieveCustomGroupByName('Synchronisatie_First_Noa');
            $fields = CRM_Utils_DgwApiUtils::retrieveCustomValuesForContactAndCustomGroupSorted($res_check['contact_id'], $group['id']);
            $fid = "";
            foreach($fields as $key => $field) {
                if ($field['entity_id'] == $email_id  && $field['entity'] == "email") {
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
 * Function to add an e-mailaddress
 */
function civicrm_api3_dgw_email_create($inparms) {
    /*
     * set superglobal to avoid double update via post or pre hook
     */
    $GLOBALS['dgw_api'] = "nosync";
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
     * if no email passed, error
     */
    if (!isset($inparms['email'])) {
        return civicrm_api3_create_error("Email ontbreekt");
    } else {
        $email = trim($inparms['email']);
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
    $location_type_id = CRM_Utils_DgwApiUtils::getLocationIdByName($location_type);
    if ($location_type_id == "") {
        return civicrm_api3_create_error("Location_type is ongeldig");
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
     * if location type toekomst or oud, add start and end date after email
     */
    if ($location_type == "oud" || $location_type == "toekomst") {
        if (isset($start_date) && !empty($start_date)) {
            $datum = date("d-m-Y", strtotime($start_date));
            if (!isset($end_date) || empty($end_date)) {
                $email = $email." (vanaf $datum)";
            } else {
                $email = $email." (vanaf $datum";
            }
        }
        if (isset($end_date) && !empty($end_date)) {
            $datum = date("d-m-Y", strtotime($end_date));
            if (isset($start_date) && !empty($start_date)) {
                $email = $email." tot $datum";
            } else {
                $email = $email." (tot $datum";
            }
        }
        $email = $email.")";
    }
    /*
     * Add email to contact with standard civicrm function civicrm_location_add
     */
    $civiparms = array(
        "contact_id"        =>  $contact_id,
        "location_type_id"  =>  $location_type_id,
        "is_primary"        =>  $is_primary,
        "email"             =>  $email,
        "version"			=>  3);
    $res_email = civicrm_api('Email', 'create', $civiparms);
    if (civicrm_error($res_email)) {
        return civicrm_api3_create_error("Onverwachte fout van CiviCRM, email kon niet gemaakt worden, melding : ".$res_email['error_message']);
    } else {
        /*
         * retrieve email_id from result array
         */
        $email_id = $res_email['id'];
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
                'custom_'.$entity_field['id'] => "email",
                'custom_'.$entity_id_field['id'] => $email_id,
                'custom_'.$key_first_field['id'] => $inparms['cde_refno'],
                'custom_'.$change_date_field['id'] => $changeDate
            );
            $civicres2 = civicrm_api('CustomValue', 'Create', $civiparms2);
        }
        /*
         * return array
        */
        $outparms['email_id'] = $email_id;
        $outparms['is_error'] = "0";
    }
    unset($GLOBALS['dgw_api']);
    return $outparms;
}

/*
 * Function to get emails for a contact
*/
function civicrm_api3_dgw_email_get($inparms) {
    /*
     * initialize output parameter array
     */
    $outparms = array("");
    $civiparms = array (
                    'version' => 3,
    );

    /*
     * if contact_id empty and email_id empty, error
     *
     * @Todo write a spec function
     */
    if (!isset($inparms['contact_id']) && !isset($inparms['email_id'])) {
        return civicrm_api3_create_error("Geen contact_id of email_id doorgegeven in
            dgwcontact_emailget.");
    }

    if (empty($inparms['contact_id']) && empty($inparms['email_id'])) {
        return civicrm_api3_create_error("Contact_id en email_id allebei leeg in
            dgwcontact_emailget.");
    }

    /*
     * if contact_id is used and contains non-numeric data, error
     */
    if (!empty($inparms['contact_id'])) {
        if (!is_numeric($inparms['contact_id'])) {
            return civicrm_api3_create_error("Contact_id bevat ongeldige waarde in
                dgwcontact_emailget.");
        } else {
            $civiparms['contact_id'] = $inparms['contact_id'];
        }
    }
    /*
     * if email_id is used and contains non-numeric data, error
     */
    if (!empty($inparms['email_id']) && !is_numeric($inparms['email_id'])) {
        return civicrm_api3_create_error("email_id bevat ongeldige waarde in
            dgwcontact_emailget.");
    } else if (!empty($inparms['email_id'])) {
        $civiparms['email_id'] = $inparms['email_id'];
        unset($civiparms['contact_id']); //email id is use to request a specific emailnumber
    }
    /**
     * Use the email api
     */
    $civires1 = civicrm_api('email', 'get', $civiparms);
    if (civicrm_error($civires1)) {
        return civicrm_api3_create_error($civires1['error_message']);
    }
    $i = 1;
    foreach ($civires1['values'] as $result) {
        /* Get location type name */
      $sequence = array('contact_id', 'email_id', 'location_type', 'is_primary', 'email', 'start_date', 'end_date');
      if (isset($result['location_type_id'])) {
        $result['location_type'] = CRM_Utils_DgwApiUtils::getLocationByid($result['location_type_id']);
      }
      $result['email_id'] = $result['id'];
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

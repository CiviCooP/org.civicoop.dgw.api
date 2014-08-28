<?php
/*
 +--------------------------------------------------------------------+
| CiviCRM version 4.3                                                |
+--------------------------------------------------------------------+
| Copyright CiviCoop Academic Free License v3.02013                  |
+--------------------------------------------------------------------+
*/
/*
 * Function to receive error from First Noa and add this to error table in
 * CiviCRM
 */
function civicrm_api3_dgw_firstsync_error($inparms) {
    $error_group = CRM_Utils_DgwApiUtils::retrieveCustomGroupByName('Fouten_synchronisatie_First');
    if (!is_array($error_group)) {
        return civicrm_api3_create_error("CustomGroup Fouten_synchronisatie_first niet gevonden");
    }
    $error_group_id = $error_group['id'];
    /*
     * if contact_id not in parms, empty or not numeric, error
     */
    if (!isset($inparms['contact_id'])) {
        return civicrm_api3_create_error("Contact_id niet gevonden in parameters voor dgwcontact_firstsyncerror");
    } else {
        $contact_id = trim($inparms['contact_id']);
    }
    if (empty($contact_id)) {
        return civicrm_api3_create_error("Contact_id is leeg voor dgwcontact_firstsyncerror");
    }
    if (!is_numeric($contact_id)) {
        return civicrm_api3_create_error("Contact_id mag alleen numeriek zijn, doorgegeven was ".$contact_id." aan dgwcontact_firstsyncerror");
    }
    /*
     * if action not in parms, empty or not ins/upd, error
     */
    if (!isset($inparms['dgwaction'])) {
        return civicrm_api3_create_error("Action niet gevonden in parameters voor dgwcontact_firstsyncerror");
    } else {
        $action = trim(strtolower($inparms['dgwaction']));
    }
    if (empty($action)) {
        return civicrm_api3_create_error("Action is leeg voor dgwcontact_firstsyncerror");
    }
    if ($action != "ins" && $action != "upd") {
        return civicrm_api3_create_error("Action heeft ongeldigde waarde ".$action." voor dgwcontact_firstsyncerror");
    }
    /*
     * if entity not in parms, empty or invalid, error
     */
    if (!isset($inparms['dgwentity'])) {
        return civicrm_api3_create_error("Entity niet gevonden in parameters voor dgwcontact_firstsyncerror");
    } else {
        $entity = trim(strtolower($inparms['dgwentity']));
    }
    if (empty($entity)) {
        return civicrm_api3_create_error("Entity is leeg voor dgwcontact_firstsyncerror");
    }
    if ($entity != "contact" && $entity != "phone" && $entity != "address" && $entity != "email") {
        return civicrm_api3_create_error("Entity heeft ongeldigde waarde ".$entity." voor dgwcontact_firstsyncerror");
    }
    /*
     * If entity_id not in parms, empty or not numeric, error
     */
    if (!isset($inparms['entity_id'])) {
            return civicrm_api3_create_error("Entity_id niet gevonden in parameters voor dgwcontact_firstsyncerror");
    } else {
            $entity_id = trim($inparms['entity_id']);
    }
    if (empty($entity_id)) {
            return civicrm_api3_create_error("Entity_id is leeg voor dgwcontact_firstsyncerror");
    }
    if (!is_numeric($entity_id)) {
            return civicrm_api3_create_error("Entity_id mag alleen numeriek zijn, doorgegeven was ".$entity_id." aan dgwcontact_firstsyncerror");
    }
    /*
     * If error_message not in parms or empty, error
     */
    if (!isset($inparms['error_message'])) {
        return civicrm_api3_create_error("Geen foutboodschap doorgegeven in dgwcontact_firstsyncerror");
    } else {
        $errmsg = trim($inparms['error_message']);
    }
    if (empty($errmsg)) {
        return civicrm_api3_create_error( "Lege foutboodschap doorgegeven in dgwcontact_firstsyncerror");
    }
    $errdate = date("Y-m-d H:i:s");
    /*
     * Incident 09 06 11 001 : check if there is already a record with
     * the same error message for the contact. If so, update date
     */
    $datum_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('datum_synchronisatieprobleem');
    $action_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('action_err');
    $entity_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('entity_err');
    $entity_id_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('entity_id_err');
    $key_first_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('key_first_err');
    $error_msg_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('Foutboodschap');

    $custom_fields = CRM_Utils_DgwApiUtils::retrieveCustomValuesForContactAndCustomGroupSorted( $contact_id, $error_group_id);
    foreach($custom_fields as $key => $field) {
        if ($field['Foutboodschap'] == $errmsg) {
            $civiparms2 = array(
                'version' => 3,
                'entity_id' => $contact_id,
                'custom_'.$datum_field['id'].':'.$key => date('YmdHis'),
            );
            $civicres2 = civicrm_api('CustomValue', 'Create', $civiparms2);
            if (civicrm_error($civicres2)) {
                return civicrm_api3_create_error($civicres2['error_message']);
            }
            $outparms['is_error'] = "0";
            return $outparms;
        }
    }
    /*
     * Create record in first sync error table with error message
     */
    if (isset($inparms['key_first'])) {
        $key_first = trim($inparms['key_first']);
    }
    $civiparms = array(
        'version' => 3,
        'entity_id' => $contact_id,
        'custom_'.$datum_field['id'] => date('YmdHis'),
        'custom_'.$action_field['id'] => $action,
        'custom_'.$entity_field['id'] => $entity,
        'custom_'.$entity_id_field['id'] => $entity_id,
        'custom_'.$error_msg_field['id'] => $errmsg,
    );
    if (isset($key_first) && !empty($key_first)) {
        $civiparms['custom_'.$key_first_field['id']] = $key_first;
    }
    $civicres = civicrm_api('CustomValue', 'Create', $civiparms);
    if (civicrm_error($civicres)) {
        return civicrm_api3_create_error($civicres['error_message']);
    }
    $outparms['is_error'] = "0";
    return $outparms;
}

/*
 * Function to remove contact from group FirstSync
 */
function civicrm_api3_dgw_firstsync_remove($inparms) {
  $error_group = CRM_Utils_DgwApiUtils::retrieveCustomGroupByName('Fouten_synchronisatie_First');
  $group = CRM_Utils_DgwApiUtils::retrieveCustomGroupByName('Synchronisatie_First_Noa');
  if (!is_array($error_group)) {
    return civicrm_api3_create_error("CustomGroup Fouten_synchronisatie_first niet gevonden");
  }
  if (!is_array($group)) {
    return civicrm_api3_create_error("CustomGroup Synchronisatie_First_Noa niet gevonden");
  }
  $group_id = $group['id'];
  $error_group_id = $error_group['id'];
  /*
   * if contact_id empty or not numeric, error
   */
  if (!isset($inparms['contact_id'])) {
      return civicrm_api3_create_error("Geen contact_id in parms in dgwcontact_firstsyncremove");
  } else {
      $contact_id = trim($inparms['contact_id']);
  }
  if (empty($contact_id)) {
      return civicrm_api3_create_error( "Leeg contact_id voor dgwcontact_firstsyncremove" );
  }
  if (!is_numeric($contact_id)) {
      return civicrm_api3_create_error( "Contact_id '.$contact_id.' heeft niet numerieke waarden in dgwcontact_firstsyncremove");
  }
  /*
   * if action empty or not "ins", "del" or "upd", error
   */
  if (!isset($inparms['dgwaction'])) {
      return civicrm_api3_create_error("Geen action in parms in dgwcontact_firstsyncremove");
  } else {
      $action = trim(strtolower($inparms['dgwaction']));
  }
  if (empty($action)) {
      return civicrm_api3_create_error("Lege action voor dgwcontact_firstsyncremove");
  }
  if ($action != "ins" && $action != "upd" && $action != "del") {
      return civicrm_api3_create_error("Ongeldige waarde ".$action. " voor action in dgwcontact_firstsyncremove");
  }
  /*
   * if entity empty or invalid, error
   */
  if (!isset($inparms['dgwentity'])) {
      return civicrm_api3_create_error("Geen entity in parms in dgwcontact_firstsyncremove");
  } else {
      $entity = trim(strtolower($inparms['dgwentity']));
  }
  if (empty($entity)) {
      return civicrm_api3_create_error("Lege entity voor dgwcontact_firstsyncremove");
  }
  if ($entity != "contact" && $entity != "phone" && $entity != "email" && $entity != "address") {
      return civicrm_api3_create_error("Ongeldige waarde ".$entity." voor entity in dgwcontact_firstsyncremove");
  }
  /*
   * entity_id or key_first required
   */
  if (!isset($inparms['entity_id']) && !isset($inparms['key_first'])) {
      return civicrm_api3_create_error("Entity_id en key_first ontbreken in dgwcontact_firstsyncremove");
  }
  if (empty($inparms['entity_id']) && empty($inparms['key_first'])) {
      return civicrm_api3_create_error("Entity_id en key_first zijn beiden leeg in dgwcontact_firstsyncremove");
  }
  if (isset($inparms['entity_id'])) {
      $entity_id = trim($inparms['entity_id']);
      if (!is_numeric($entity_id)) {
          return civicrm_api3_create_error("Entity_id kan alleen numeriek zijn, doorgegeven was $entity_id");
      }
  } else {
      $entity_id = null;
  }
  if (isset($inparms['key_first'])) {
      $key_first = trim($inparms['key_first']);
      if (!is_numeric($key_first)) {
          return civicrm_api3_create_error("Key_first kan alleen numeriek zijn, doorgegeven was $key_first");
      }
  } else {
      $key_first = null;
  }
  $custom_fields = CRM_Utils_DgwApiUtils::retrieveCustomValuesForContactAndCustomGroupSorted( $contact_id, $group_id);
  $action_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('action');
  if (!is_array($action_field)) {
          return civicrm_api3_create_error('invalid custom field action');
  }
  if (count($custom_fields) > 0) {
    /*
     * remove entry from firstsync error table with incoming parms,
     * delete from synctable if action is 'del' and set action to none for all
     * others
     */
    if (!empty($entity_id)) {
      if ($action == "del") {
        $fields = array(
          'entity' => $entity,
          'action' => $action,
          'entity_id' => $entity_id
        );
        CRM_Utils_DgwApiUtils::removeCustomValuesRecord($group_id, $contact_id, $fields);
      } else {
        foreach($custom_fields as $id => $field) {
          if ($field['action'] != 'del' && $field['entity'] == $entity && $field['entity_id'] == $entity_id) {
            $civiparms2 = array(
              'version' => 3,
              'entity_id' => $contact_id,
              'custom_'.$action_field['id'].':'.$id => 'none',
            );
            $civires2 = civicrm_api('CustomValue', 'Create', $civiparms2);
            if (civicrm_error($civires2)) {
              return civicrm_api3_create_error($civires2['error_message']);
            }
          }
        }
      }
      $fields = array(
        'action_err' => $action,
        'entity_err' => $entity,
        'entity_id_err' => $entity_id,
      );
      CRM_Utils_DgwApiUtils::removeCustomValuesRecord($error_group_id, $contact_id, $fields);
    } else {
      if ($action == "del") {
        $fields = array(
          'entity' => $entity,
          'action' => $action,
          'key_first' => $key_first,
        );
        CRM_Utils_DgwApiUtils::removeCustomValuesRecord($group_id, $entity_id, $fields);
      } else {
        foreach($custom_fields as $id => $field) {
          if ($field['action'] != 'del' && $field['entity'] == $entity && $field['entity_id'] == $contact_id && $field['key_first'] == $key_first) {
            $civiparms2 = array(
              'version' => 3,
              'entity_id' => $contact_id,
              'custom_'.$action_field['id'].':'.$id => 'none',
            );
            $civicres2 = civicrm_api('CustomValue', 'Create', $civiparms2);
            if (civicrm_error($civires2)) {
              return civicrm_api3_create_error($civires2['error_message']);
            }
          }
        }
      }
      $fields = array(
        'action_err' => $action,
        'entity_err' => $entity,
        'key_first_err' => $key_first,
      );
      CRM_Utils_DgwApiUtils::removeCustomValuesRecord($error_group_id, $contact_id, $fields);
    }
    /*
     * if no entries left in synctable for contact with action
     * upd, action del or action ins, remove contact from group firstsync
     */
    $fields = CRM_Utils_DgwApiUtils::retrieveCustomValuesForContactAndCustomGroupSorted( $contact_id, $group_id);
    $aantal = 0;
    foreach($fields as $field) {
      if ($field['action'] == 'ins' || $field['action'] == 'upd' || $field['action'] == 'del') {
        $aantal ++;
      }
    }
    if ($aantal == 0) {
      $gid = CRM_Utils_DgwApiUtils::getGroupIdByTitle('FirstSync');
      $civiparms2 = array(
        "version"       => 3,
        "contact_id"    =>  $contact_id,
        "group_id"      =>  $gid);
      $civires2 = civicrm_api('GroupContact', 'delete', $civiparms2);
      if (civicrm_error($civires2)) {
        return civicrm_api3_create_error($civires2['error_message']);
      }
    }
    return "Firstsync remove processed correctly";
  } else {
    return "No sync records found for contact_id";
  }
}

/*
 * Function to sync with first
 */
function civicrm_api3_dgw_firstsync_get() {
    $groupTitle = CRM_Utils_DgwUtils::getDgwConfigValue('groep sync first');
    $groupParams = array(
        'version' => 3,
        'title' => $groupTitle
    );
    $apiGroup = civicrm_api('Group', 'Getsingle', $groupParams);
    if (!civicrm_error($apiGroup)) {
        if (isset($apiGroup['id'])) {
            $group_for_first_sync = $apiGroup['id'];
        } else {
            return civicrm_api3_create_error("Groep $groupTitle niet gevonden");        
        }
    } else {
        return civicrm_api3_create_error("Groep $groupTitle niet gevonden");        
    }
    /*
     * initialize output parameter array
     */
    $outparms = array("");
    $civiparms = array (
        'version' => 3,
        'group_id' => $group_for_first_sync,
        'options' => array('limit' => 99999)
    );
    /**
     * Use the GroupContact api
     */
    $civires1 = civicrm_api('GroupContact', 'get', $civiparms);
    if (civicrm_error($civires1)) {
        return civicrm_api3_create_error($civires1['error_message']);
    }
    $i = 1;
    foreach ($civires1['values'] as $contact) {
        $syncRecords = CRM_Utils_DgwApiUtils::retrieveSyncRecords($contact['contact_id']);
        foreach($syncRecords as $syncRecord) {
            $processRecord = true;
            /*
             * issue 269: do not send if key_first is empty and action is
             * not ins
             * do not send if entity = address or contact and action is
             * delete
             */
            if (empty($syncRecord['key_first']) && $syncRecord['action'] != 'ins') {
                $processRecord = false;
            }
            if ($syncRecord['action'] == 'del' && ($syncRecord['entity'] == 'contact' || $syncRecord['entity'] == 'address')) {
                $processRecord = false;
            }
            if ($syncRecord['action'] == 'none') {
                $processRecord = false;
            }
            if ($processRecord) {
                $data['contact_id'] = $contact['contact_id'];
                $data['action'] = $syncRecord['action'];
                $data['entity'] = $syncRecord['entity'];
                $data['entity_id'] = $syncRecord['entity_id'];
                $data['key_first'] = $syncRecord['key_first'];
                $pers_first = CRM_Utils_DgwApiUtils::retrievePersoonsNummerFirst($contact['contact_id']);
                $data['persoonsnummer_first'] = $pers_first;
                $outparms[$i] = $data;
                $i++;
            }
        }
    }
    $outparms[0]['record_count'] = $i - 1;
    return $outparms;
}

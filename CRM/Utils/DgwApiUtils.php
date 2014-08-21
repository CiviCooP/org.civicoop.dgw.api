<?php
/*
+--------------------------------------------------------------------+
| Project       :   CiviCRM De Goede Woning - Upgrade CiviCRM 4.3    |
| Author        :   Jaap Jansma (CiviCooP, jaap.jansma@civicoop.org  |
| Date          :   16 April 20134                                   |
| Description   :   Class with DGW helper functions for the          |
|                   custom DGWApi                                    |
+--------------------------------------------------------------------+
| BOS1404581 return get values in expected sequence                  |
| Erik Hommel, 17 April 2014                                         |
+--------------------------------------------------------------------+
| BOS1307269 add function to parse HOV address                       |
| Erik Hommel <erik.hommel@civicoop.org> 5 May 0214                  |
+--------------------------------------------------------------------+
*/

/**
*
* @package CRM
* @copyright CiviCRM LLC (c) 2004-2013
* $Id$
*
*/
class CRM_Utils_DgwApiUtils {

    public static function parseEntity($action) {
        $entities = array(
            'phone' => 'DgwPhone',
            'note' => 'DgwNote',
            'email' => 'DgwEmail',
            'address' => 'DgwAddress',
            'group' => 'DgwGroup',
            'tag' => 'DgwTag',
            'hov' => 'DgwHov',
            'relationship' => 'DgwRelationship',
            'firstsync' => 'DgwFirstsync',
        );
        $return['entity'] = 'DgwContact';
        $return['action'] = $action;
	foreach($entities as $key => $value) {
            if (strpos($action, $key) === 0) {
                $return['entity'] = $value;
                $return['action'] = str_replace($key, "", $action);
            }
        }
        return $return;
    }

    public static function getLocationByid($id) {
        $civiparms2 = array('version' => 3, 'id' => $id);
        $civires2 = civicrm_api('LocationType', 'getsingle', $civiparms2);
        $locationType = "";
        if (!civicrm_error($civires2)) {
            $locationType = $civires2['name'];
        }
        return $locationType;
    }

    public static function getLocationIdByName($name) {
        $civiparms2 = array('version' => 3, 'name' => $name);
        $civires2 = civicrm_api('LocationType', 'getsingle', $civiparms2);
        $locationType = "";
        if (!civicrm_error($civires2)) {
            $locationType = $civires2['id'];
        }
        return $locationType;
    }

    public static function getContactTypeByName($name) {
        $civiparms2 = array('version' => 3, 'name' => $name);
        $civires2 = civicrm_api('ContactType', 'getsingle', $civiparms2);
        $locationType = "";
        if (!civicrm_error($civires2)) {
            $locationType = $civires2['id'];
        }
        return $locationType;
    }

    public static function getGroupIdByTitle($title) {
        $civiparms2 = array('version' => 3, 'title' => $title);
        $civires2 = civicrm_api('Group', 'getsingle', $civiparms2);
        $id = false;
        if (!civicrm_error($civires2)) {
            $id = $civires2['id'];
        }
        return $id;
    }

    public static function getOptionGroupIdByTitle($title) {
        $civiparms2 = array('version' => 3, 'name' => $title);
        $civires2 = civicrm_api('OptionGroup', 'getsingle', $civiparms2);
        $id = false;
        if (!civicrm_error($civires2)) {
            $id = $civires2['id'];
        }
        return $id;
    }

    public static function getOptionValuesByGroupId($group_id) {
        $civiparms2 = array('version' => 3, 'option_group_id' => $group_id);
        $civires2 = civicrm_api('OptionValue', 'get', $civiparms2);
        $return = array();
        if (!civicrm_error($civires2)) {
            foreach($civires2['values'] as $val) {
                $return[$val['value']] = $val;
            }
        }
        return $return;
    }
    
    public static function retrieveRelationshipTypeIdByNameAB($name) {
        $id = 0;
        $parms = array(
            'version' => 3,
            'name_a_b' => $name
        );
        $res = civicrm_api('RelationshipType', 'getsingle', $parms);
        if (!civicrm_error($res)) {
            $id = $res['id'];
        }
        return $id;
    }

    public static function getEntityIdFromSyncTable($first_key, $entity_type) {
        $id = 0;
        $cde_refno_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('key_first');
        $entity_id_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('entity_id');
        $entity_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('entity');
        $cde_refno_field_froup = CRM_Utils_DgwApiUtils::retrieveCustomGroupByid($cde_refno_field['custom_group_id']);

        /*
         * Onderstaande query is niet om te bouwen naar API calls
         * Want er moet dan gebruik gemaakt worden van de CustomValues van de api
         * maar om die te gebruiken hebben entity_id nodig en die verwijst in de database
         * altijd naar het contact in de tabel voor synchronisatie.
         * En omdat het een inactief (verborgen) veld is kunnen we ook niet zoeken via
         * de Contact api met als parameter custom_*
        */
        $query = "SELECT ".$entity_id_field['column_name']." AS `entity_id` FROM ".$cde_refno_field_froup['table_name']." WHERE ".$cde_refno_field['column_name']." = '$first_key' AND ".$entity_field['column_name']." = '".$entity_type."'";
        $daoSync = CRM_Core_DAO::executeQuery($query);
        if ($daoSync->fetch()) {
            $id = $daoSync->entity_id;
        }
        return $id;
    }

    public static function getHovFromTable($hovnummer, $hovnr_field) {
        $id = 0;
        $hovnr_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName($hovnr_field);
        $hovnr_field_field_froup = CRM_Utils_DgwApiUtils::retrieveCustomGroupByid($hovnr_field['custom_group_id']);

        /*
         * Onderstaande query is niet om te bouwen naar API calls
         * Want er moet dan gebruik gemaakt worden van de CustomValues van de api
         * maar om die te gebruiken hebben entity_id nodig en die verwijst in de database
         * altijd naar het contact in de tabel voor synchronisatie.
         * En omdat het een inactief (verborgen) veld is kunnen we ook niet zoeken via
         * de Contact api met als parameter custom_*
         */
        $query = "SELECT `entity_id` FROM ".$hovnr_field_field_froup['table_name']." WHERE ".$hovnr_field['column_name']." = '$hovnummer'";
        $daoSync = CRM_Core_DAO::executeQuery($query);
        if ($daoSync->fetch()) {
            $id = $daoSync->entity_id;
        }
        return $id;
    }

    public static function retrieveCustomGroupByid($group_id) {
        $civiparms2 = array('version' => 3, 'id' => $group_id);
        $civires2 = civicrm_api('CustomGroup', 'getsingle', $civiparms2);
        $id = false;
        if (!civicrm_error($civires2)) {
            return $civires2;
		}
		return false;
	}

	public static function retrieveCustomGroupByName($name) {
		$civiparms2 = array('version' => 3, 'name' => $name);
		$civires2 = civicrm_api('CustomGroup', 'getsingle', $civiparms2);
		$id = false;
		if (!civicrm_error($civires2)) {
			return $civires2;
		}
		return false;
	}

	public static function retrieveCustomFieldByName($name) {
		$civiparms2 = array('version' => 3, 'name' => $name);
		$civires2 = civicrm_api('CustomField', 'getsingle', $civiparms2);
		$id = false;
		if (!civicrm_error($civires2)) {
                    return $civires2;
		}
		return false;
	}

	public static function removeCustomValuesRecord($group_id, $entity_id, $fields) {
		$custom_group = CRM_Utils_DgwApiUtils::retrieveCustomGroupByid($group_id);
		$where = "";
		foreach($fields as $key => $value) {
			$custom_field = self::retrieveCustomFieldByName($key);
			if (is_array($custom_field)) {
				$field_name = $custom_field['column_name'];
				$where .= " AND `".$field_name."` = '".mysql_escape_string($value)."'";
			}
		}
		if (strlen($where)) {
			$qry1 = "DELETE FROM ".$custom_group['table_name']." WHERE entity_id = ".$entity_id.$where;
			CRM_Core_DAO::executeQuery($qry1);
		}
	}

	public static function retrieveCustomValuesForContactAndCustomGroupSorted($contact_id, $group_id) {
		$customValues = CRM_Utils_DgwApiUtils::retrieveCustomValuesForContactAndCustomGroup($contact_id, $group_id);
		$fields = array();
		if (isset($customValues['values']) && is_array($customValues['values'])) {
			foreach($customValues['values'] as $values) {
				foreach($values as $key => $v) {
					if ($key != 'entity_id' && $key != 'id' && $key != 'latest' && $key != 'name') {
						$fields[$key][$values['name']] = $v;
					}
				}
			}
		}
		return $fields;
	}

	public static function retrieveCustomValuesForContactAndCustomGroup($contact_id, $group_id) {
		$data['contact_id'] = $contact_id;
		$return['is_error'] = '0';
		if (!isset($data['contact_id'])) {
			$return['is_error'] = '1';
			$return['error_message'] ='Invalid input parameters expected contact_id and contact_type';
			return $return;
		}

		$params = array(
				'version' => 3,
				'sequential' => 1,
				'entity_id' => $data['contact_id'],
				'onlyActiveFields' => '0',
		);
		$values = civicrm_api('CustomValue', 'get', $params);
		if (isset($values['is_error']) && $values['is_error'] == '1') {
			return $values;
		}

		$i = 0;
		foreach($values['values'] as $value) {
			$params = array(
					'version' => 3,
					'sequential' => 1,
					'id' => $value['id'],
					'custom_group_id' => $group_id
			);
			$fields = civicrm_api('CustomField', 'getsingle', $params);
			if (!isset($fields['is_error'])) {
				$return['values'][$i] = $value;
				$return['values'][$i]['name'] = $fields['name'];
				$i++;
			}
		}
		return $return;
	}

	public static function retrievePersoonsNummerFirst($contact_id) {
		$pers_first = "";
		$return = self::retrieveCustomValuesForContact(array('contact_id' => $contact_id));
		if (isset($return['values']) && isset($return['values']['Persoonsnummer_First']) && isset($return['values']['Persoonsnummer_First']['value']) && strlen(isset($return['values']['Persoonsnummer_First']['value'])) ) {
			$pers_first = $return['values']['Persoonsnummer_First']['value'];
		}
		return $pers_first;
	}

	public static function retrieveCustomValuesForContact($data) {
		$return['is_error'] = '0';
		if (!isset($data['contact_id'])) {
			$return['is_error'] = '1';
			$return['error_message'] ='Invalid input parameters expected contact_id and contact_type';
			return $return;
		}

		$params = array(
				'version' => 3,
				'sequential' => 1,
				'entity_id' => $data['contact_id']
		);
		$values = civicrm_api('CustomValue', 'get', $params);
		if (isset($values['is_error']) && $values['is_error'] == '1') {
			return $values;
		}
		foreach($values['values'] as $value) {
			$params = array(
				'version' => 3,
				'sequential' => 1,
				'id' => $value['id']
			);
			$fields = civicrm_api('CustomField', 'getsingle', $params);
			if (!isset($fields['is_error'])) {
				$name = $fields['name'];
				$return['values'][$name]['name'] = $name;
				$return['values'][$name]['value'] = $value['latest'];
				if (isset($fields['option_group_id'])) {
					$params = array(
						'version' => 3,
						'sequential' => 1,
						'option_group_id' => $fields['option_group_id'],
						'value' => $return['values'][$name]['value']
					);
					$options = civicrm_api('OptionValue', 'getsingle', $params);
					if (!isset($options['is_error'])) {
						$return['values'][$name]['normalized_value'] = $options['label'];
					}
				}

			}
		}
		return $return;
	}
        /**
         * static function to return a structured set of data about a
         * custom value. You should pass in the result array of an individual
         * custom field as retrieved from the CustomValue API.
         *
         * @author Erik Hommel (erik.hommel@civicoop.org)
         * @params $params array (single element from resul['values'] array from CustomValue API
         * @return $result holding entity_id, custom_id, record_id and value
         */
        static function getCustomValueTableElement( $params ) {
            $results = array( );
            $ignoredKeys = array( "id", "latest", "name", "entity_id" );
            if ( empty( $params ) ) {
                return $results;
            }
            if ( !isset( $params['id'] ) || !isset( $params['entity_id'] ) ) {
                return $results;
            }
            foreach ( $params as $key => $value ) {
                if ( !in_array( $key, $ignoredKeys ) ) {
                    $result['entity_id'] = $params['entity_id'];
                    $result['custom_id'] = $params['id'];
                    $result['record_id'] = $key;
                    $result['value'] = $value;
                    $results[] = $result;
                }
            }
            return $results;
        }
        /**
         * static function to retrieve sync records in sequence of
         * all actions for contacts first
         * 
         * @author Erik Hommel (erik.hommel@civicoop.org)
         *                      http://www/civicoop.org
         * @params $contactId contact for which sync records are retrieved
         * @return $syncRecords array holding sync records
         */
        static function retrieveSyncRecords($contactId) {
            $syncRecords = array();
            if (empty($contactId)) {
                return $syncRecords;
            }
            require_once 'CRM/Utils/DgwUtils.php';
            $syncTableName = CRM_Utils_DgwUtils::getDgwConfigValue('synchronisatietabel first');
            $syncTable = CRM_Utils_DgwUtils::getCustomGroupTableName($syncTableName);
            $selectSyncContact = "SELECT * FROM ".$syncTable." WHERE entity_id = $contactId";
            $customField = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('action');
            if (isset($customField['column_name'])) {
                $actionField = $customField['column_name'];
                $selectSyncContact .= " AND ".$actionField." = 'ins'";
            }
            $customField = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('entity');
            if (isset($customField['column_name'])) {
                $entityField = $customField['column_name'];
                $selectSyncContact .= " AND ".$entityField." = 'contact'";
            }
            $customField = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('entity_id');
            if (isset($customField['column_name'])) {
                $entityIdField = $customField['column_name'];
            }
            $customField = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('key_first');
            if (isset($customField['column_name'])) {
                $keyFirstField = $customField['column_name'];
            }
            $contactInserted = FALSE;
            /*
             * all contact insert transaction first
             */
            $daoSync = CRM_Core_DAO::executeQuery($selectSyncContact);
            while ($daoSync->fetch()) {
                $contactInserted = TRUE;
                $syncRecord = array();
                $syncRecord['action'] = $daoSync->$actionField;
                $syncRecord['entity'] = "contact";
                $syncRecord['entity_id'] = $daoSync->$entityIdField;
                $syncRecord['key_first'] = $daoSync->$keyFirstField;
                $syncRecords[] = $syncRecord;
            }
            $selectSyncContact = "SELECT * FROM ".$syncTable." WHERE entity_id = $contactId";
            if (isset($entityField)) {
                $selectSyncContact .= " AND ".$entityField." = 'contact'";
            }
            if (isset($actionField)) {
                $selectSyncContact .= " AND ".$actionField." <> 'ins' AND ".$actionField." <> 'none'";
            }
            $daoSync = CRM_Core_DAO::executeQuery($selectSyncContact);
            while ($daoSync->fetch()) {
                $syncRecord = array();
                $syncRecord['action'] = $daoSync->$actionField;
                $syncRecord['entity'] = "contact";
                $syncRecord['entity_id'] = $daoSync->$entityIdField;
                $syncRecord['key_first'] = $daoSync->$keyFirstField;
                $syncRecords[] = $syncRecord;
            }
            /*
             * all non-contact inserts next
             * if there has been a contact insert, ignore the insert address, email and phone for this run.
             * They will be part of the next run. This is because First Noa can not process
             * the contact create quicky enough to also add address, email and phone in the
             * same run. 
             * 
             */
            if (!$contactInserted) {
                $selectSyncContact = "SELECT * FROM ".$syncTable." WHERE entity_id = $contactId";
                if (isset($entityField)) {
                    if (!$contactInserted) {
                        $selectSyncContact .= " AND ".$entityField." <> 'contact'";
                    }
                }
                if (isset($actionField)) {
                    $selectSyncContact .= " AND ".$actionField." = 'ins'";
                }
                $daoSync = CRM_Core_DAO::executeQuery($selectSyncContact);
                while ($daoSync->fetch()) {
                    $syncRecord = array();
                    $syncRecord['action'] = $daoSync->$actionField;
                    $syncRecord['entity'] = $daoSync->$entityField;
                    $syncRecord['entity_id'] = $daoSync->$entityIdField;
                    $syncRecord['key_first'] = $daoSync->$keyFirstField;
                    $syncRecords[] = $syncRecord;
                }
            }
            /*
             * all other transactions last
             */
            $selectSyncContact = "SELECT * FROM ".$syncTable." WHERE entity_id = $contactId";
            if (isset($entityField)) {
                $selectSyncContact .= " AND ".$entityField." <> 'contact'";
            }
            if (isset($actionField)) {
                $selectSyncContact .= " AND ".$actionField." <> 'ins' AND ".$actionField." <> 'none'";
            }
            $daoSync = CRM_Core_DAO::executeQuery($selectSyncContact);
            while ($daoSync->fetch()) {
                $syncRecord = array();
                $syncRecord['action'] = $daoSync->$actionField;
                $syncRecord['entity'] = $daoSync->$entityField;
                $syncRecord['entity_id'] = $daoSync->$entityIdField;
                $syncRecord['key_first'] = $daoSync->$keyFirstField;
                $syncRecords[] = $syncRecord;
            }
            return $syncRecords;
    }
    /**
     * Function to set values in the right sequence and then return the array
     * Result of call BOS1404581, sequence of values incorrect for First Noa
     * 
     * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
     * @date 17 Apr 2014
     * @param array $sequence - array with mandatory sequence of elements
     * @param array $values - array with values
     * @return array $result - result array with correct sequence
     * @access public
     * @static
     */
    public static function setValuesInSeq($sequence, $values) {
      $result = array();
      if (empty($values)) {
        return $result;
      }
      if (empty($sequence)) {
        $result = $values;
        return $result;
      }
      /*
       * first fill array with values in sequence and remove value element
       */
      foreach ($sequence as $valueName) {
        if (isset($values[$valueName])) {
          $result[$valueName] = $values[$valueName];
          unset($values[$valueName]);
        }
      }
      return $result;
    }
  /**
   * BOS1307269 function to parse incoming HOV address
   * 
   * @author Erik Hommel <erik.hommel@civicoop.org>
   * @date 5 may 2014
   * @param string $address
   * @eturn array $result address elements
   * @access public
   * @static
   */  
  public static function parseVgeAddress($address) {
    $result = $address;
    $parts = explode(',', $address);
    /*
     * possible patterns coming from First:
     * 1. street_name, street_number, postal_code, city
     * 2. street_name, street_number, street_unit, postal_code, city
     * all other patterns return incoming param
     */
    if (count($parts) == 4 || count($parts== 5)) {
      $result = self::_parseVgeParts($parts);
    }
    return $result;
  }
  private static function _parseVgeParts($parts) {
    $result = array();
    $result['street_name'] = $parts[0];
    $result['street_number'] = $parts[1];
    if (count($parts) == 5) {
      $result['street_unit'] = $parts[2];
      $result['postal_code'] = $parts[3];
      $result['city'] = $parts[4];
    } else {
      $result['postal_code'] = $parts[2];
      $result['city'] = $parts[3];
    }
    return $result;
  }  
}



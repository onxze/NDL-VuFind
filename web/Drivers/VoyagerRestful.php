<?php
/**
 * Voyager ILS Driver
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  ILS_Drivers
 * @author   Andrew S. Nagy <vufind-tech@lists.sourceforge.net>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Luke O'Sullivan <l.osullivan@swansea.ac.uk>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_an_ils_driver Wiki
 */

require_once 'Voyager.php';

/**
 * Voyager Restful ILS Driver
 *
 * @category VuFind
 * @package  ILS_Drivers
 * @author   Andrew S. Nagy <vufind-tech@lists.sourceforge.net>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Luke O'Sullivan <l.osullivan@swansea.ac.uk>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_an_ils_driver Wiki
 */
class VoyagerRestful extends Voyager
{
    protected $ws_host;
    protected $ws_port;
    protected $ws_app;
    protected $ws_dbKey;
    protected $ws_patronHomeUbId;
    protected $ws_pickUpLocations;
    protected $defaultPickUpLocation;
    protected $holdCheckLimit;
    protected $callSlipCheckLimit;
    protected $itemHolds;
    protected $requestGroups;
    protected $defaultRequestGroup;
    protected $pickupLocationsInRequestGroup;

    protected $getCache = array();
    protected $sessionId = '';

    /**
     * Constructor
     *
     * @param string $configFile Name of configuration file to load (relative to
     * web/conf folder; defaults to VoyagerRestful.ini).
     *
     * @access public
     */
    public function __construct($configFile = 'VoyagerRestful.ini')
    {
        // Call the parent's constructor...
        parent::__construct($configFile);

        // Define Voyager Restful Settings
        $this->ws_host = $this->config['WebServices']['host'];
        $this->ws_port = $this->config['WebServices']['port'];
        $this->ws_app = $this->config['WebServices']['app'];
        $this->ws_dbKey = $this->config['WebServices']['dbKey'];
        $this->ws_patronHomeUbId = $this->config['WebServices']['patronHomeUbId'];
        $this->ws_pickUpLocations
            = (isset($this->config['pickUpLocations']))
            ? $this->config['pickUpLocations'] : false;
        $this->defaultPickUpLocation
            = (isset($this->config['Holds']['defaultPickUpLocation']))
            ? $this->config['Holds']['defaultPickUpLocation'] : false;
        if ($this->defaultPickUpLocation == '0') {
            $this->defaultPickUpLocation = false;
        }
        $this->holdCheckLimit
            = isset($this->config['Holds']['holdCheckLimit'])
            ? $this->config['Holds']['holdCheckLimit'] : "15";
        $this->callSlipCheckLimit
            = isset($this->config['CallSlips']['checkLimit'])
            ? $this->config['CallSlips']['checkLimit'] : "15";
        $this->itemHolds
            = isset($this->config['Holds']['enableItemHolds'])
            ? $this->config['Holds']['enableItemHolds'] : true;
        $this->requestGroups
            = isset($this->config['Holds']['enableRequestGroups'])
            ? $this->config['Holds']['enableRequestGroups'] : false;
        $this->defaultRequestGroup
            = isset($this->config['Holds']['defaultRequestGroup'])
            ? $this->config['Holds']['defaultRequestGroup'] : false;
        $this->pickupLocationsInRequestGroup
            = isset($this->config['Holds']['pickupLocationsInRequestGroup'])
            ? $this->config['Holds']['pickupLocationsInRequestGroup'] : false;
    }

    /**
     * Public Function which retrieves renew, hold and cancel settings from the
     * driver ini file.
     *
     * @param string $function The name of the feature to be checked
     *
     * @return array An array with key-value pairs.
     * @access public
     */
    public function getConfig($function)
    {
        if (isset($this->config[$function]) ) {
            $functionConfig = $this->config[$function];
        } else {
            $functionConfig = false;
        }
        return $functionConfig;
    }

    /**
     * Support method for VuFind Hold Logic. Take an array of status strings
     * and determines whether or not an item is holdable based on the
     * valid_hold_statuses settings in configuration file
     *
     * @param array $statusArray The status codes to analyze.
     *
     * @return bool Whether an item is holdable
     * @access protected
     */
    protected function isHoldable($statusArray)
    {
        // User defined hold behaviour
        $is_holdable = true;

        if (isset($this->config['Holds']['valid_hold_statuses'])) {
            $valid_hold_statuses_array
                = explode(":", $this->config['Holds']['valid_hold_statuses']);

            if (count($valid_hold_statuses_array > 0)) {
                foreach ($statusArray as $status) {
                    if (!in_array($status, $valid_hold_statuses_array)) {
                        $is_holdable = false;
                    }
                }
            }
        }
        return $is_holdable;
    }

    /**
     * Support method for VuFind Hold Logic. Takes an item type id
     * and determines whether or not an item is borrowable based on the
     * borrowable and non_borrowable settings in configuration file
     *
     * @param string $itemTypeID The item type id to analyze.
     *
     * @return bool Whether an item is borrowable
     * @access protected
     */
    protected function isBorrowable($itemTypeID)
    {
        if (isset($this->config['Holds']['borrowable'])) {
            $borrow = explode(":", $this->config['Holds']['borrowable']);
            if (!in_array($itemTypeID, $borrow)) {
                return false;
            }
        }
        if (isset($this->config['Holds']['non_borrowable'])) {
            $non_borrow = explode(":", $this->config['Holds']['non_borrowable']);
            if (in_array($itemTypeID, $non_borrow)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Support method for VuFind Call Slip Logic. Take a holdings row array
     * and determine whether or not a call slip is allowed based on the
     * valid_call_slip_locations settings in configuration file
     *
     * @param array $holdingsRow The holdings row to analyze.
     *
     * @return bool Whether an item is holdable
     * @access protected
     */
    protected function isCallSlipAllowed($holdingsRow)
    {
        $holdingsRow = $holdingsRow['_fullRow'];
        if (isset($this->config['CallSlips']['valid_item_types'])) {
            $validTypes = explode(":", $this->config['CallSlips']['valid_item_types']);

            $type = $holdingsRow['TEMP_ITEM_TYPE_ID'] ? $holdingsRow['TEMP_ITEM_TYPE_ID'] : $holdingsRow['ITEM_TYPE_ID'];
            return in_array($type, $validTypes);
        }
        return true;
    }

    /**
     * Support method for VuFind UB Logic. Take a holdings row array
     * and determine whether or not a call slip is allowed based on the
     * valid_call_slip_locations settings in configuration file
     *
     * @param array $holdingsRow The holdings row to analyze.
     *
     * @return bool Whether an item is holdable
     * @access protected
     */
    protected function isUBRequestAllowed($holdingsRow)
    {
        return true;
    }

    /**
     * Protected support method for getHolding.
     *
     * @param array $id A Bibliographic id
     *
     * @return array Keyed data for use in an sql query
     * @access protected
     */
    protected function getHoldingItemsSQL($id)
    {
        $sqlArray = parent::getHoldingItemsSQL($id);
        $sqlArray['expressions'][] = "ITEM.ITEM_TYPE_ID";
        $sqlArray['expressions'][] = "ITEM.TEMP_ITEM_TYPE_ID";

        return $sqlArray;
    }

    /**
     * Protected support method for getHolding.
     *
     * @param array $id A Bibliographic id
     *
     * @return array Keyed data for use in an sql query
     * @access protected
     */
    protected function getHoldingNoItemsSQL($id)
    {
        $sqlArray = parent::getHoldingNoItemsSQL($id);
        $sqlArray['expressions'][] = "null as ITEM_TYPE_ID";
        $sqlArray['expressions'][] = "null as TEMP_ITEM_TYPE_ID";

        return $sqlArray;
    }

    /**
     * Protected support method for getHolding.
     *
     * @param array $sqlRow SQL Row Data
     *
     * @return array Keyed data
     * @access protected
     */
    protected function processHoldingRow($sqlRow)
    {
        $row = parent::processHoldingRow($sqlRow);
        $row += array('item_id' => $sqlRow['ITEM_ID'], '_fullRow' => $sqlRow);
        return $row;
    }

    /**
     * Protected support method for getHolding.
     *
     * @param array  $data   Item Data
     * @param string $id     The record id
     * @param mixed  $patron Patron Data or boolean false
     *
     * @return array Keyed data
     * @access protected
     */

    protected function processHoldingData($data, $id, $patron = false)
    {
        $holding = parent::processHoldingData($data, $id, $patron);
        $mode = CatalogConnection::getHoldsMode();

        foreach ($holding as $i => $row) {
            $is_borrowable = $this->isBorrowable($row['_fullRow']['ITEM_TYPE_ID']);
            $is_holdable = $this->itemHolds && $this->isHoldable($row['_fullRow']['STATUS_ARRAY']);
            $isCallSlipAllowed = $this->isCallSlipAllowed($row);
            // If the item cannot be borrowed or if the item is not holdable,
            // set is_holdable to false
            if (!$is_borrowable || !$is_holdable) {
                $is_holdable = false;
            }

            // Only used for driver generated hold links
            $addLink = false;
            $addCallSlipLink = false;
            $holdType = '';
            $callslip = '';

            // Hold Type - If we have patron data, we can use it to dermine if a
            // hold link should be shown
            if ($is_holdable) {
                if ($patron && $mode == "driver") {
                    // This limit is set as the api is slow to return results
                    if ($i < $this->holdCheckLimit && $this->holdCheckLimit != "0") {
                        $holdType = $this->determineHoldType(
                            $patron['id'], $row['id'], $row['item_id']
                        );
                        $addLink = $holdType ? $holdType : false;
                    } else {
                        $holdType = "auto";
                        $addLink = "check";
                    }
                } else {
                    $holdType = "auto";
                }
            }
            if ($isCallSlipAllowed) {
                if ($patron && $mode == "driver") {
                    if ($i < $this->callSlipCheckLimit && $this->callSlipCheckLimit != "0") {
                        $callslip = false;
                        if ($this->isCallSlipAllowed($row)) {
                            $callslip = $this->checkItemRequests($patron['id'], 'callslip', $row['id'], $row['item_id']);
                        }
                    } else {
                        $callslip = "auto";
                        $addCallSlipLink = "check";
                    }
                } else {
                    $callslip = "auto";
                }
            }

            $UBRequest = '';
            $addUBRequestLink = false;
            if ($patron && isset($this->config['UBRequests']['enabled']) && $this->config['UBRequests']['enabled']) {
                $UBRequest = 'auto';
                $addUBRequestLink = 'check';
            }

            $holding[$i] += array(
                'is_holdable' => $is_holdable,
                'holdtype' => $holdType,
                'addLink' => $addLink,
                'level' => "copy",
                'callslip' => $callslip,
                'addCallSlipLink' => $addCallSlipLink,
                'ubrequest' => $UBRequest,
                'addUBRequestLink' => $addUBRequestLink
            );
            unset($holding[$i]['_fullRow']);
        }
        return $holding;
    }

    /**
     * checkRequestIsValid
     *
     * This is responsible for determining if an item is requestable
     *
     * @param string $id     The Bib ID
     * @param array  $data   An Array of item data
     * @param patron $patron An array of patron data
     *
     * @return string True if request is valid, false if not
     * @access public
     */
    public function checkRequestIsValid($id, $data, $patron)
    {
        $holdType = isset($data['holdtype']) ? $data['holdtype'] : "auto";
        $level = isset($data['level']) ? $data['level'] : "copy";
        $mode = ("title" == $level) ? CatalogConnection::getTitleHoldsMode()
            : CatalogConnection::getHoldsMode();
        if ("driver" == $mode && "auto" == $holdType) {
            $itemID = isset($data['item_id']) ? $data['item_id'] : false;
            $result = $this->determineHoldType($patron['id'], $id, $itemID);
            if (!$result || $result == 'block') {
                return $result;
            }
        }

        if ('title' == $level && $this->requestGroups) {
            // Verify that there are valid request groups
            if (!$this->getRequestGroups($id, $patron['id'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * checkCallSlipRequestIsValid
     *
     * This is responsible for determining if an item is requestable
     *
     * @param string $id     The Bib ID
     * @param array  $data   An Array of item data
     * @param patron $patron An array of patron data
     *
     * @return string True if request is valid, false if not
     * @access public
     */
    public function checkCallSlipRequestIsValid($id, $data, $patron)
    {
        if ($this->checkAccountBlocks($patron['id'])) {
            return 'block';
        }

        $level = isset($data['level']) ? $data['level'] : "copy";
        $itemID = ($level != 'title' && isset($data['item_id'])) ? $data['item_id'] : false;
        $result = $this->checkItemRequests($patron['id'], 'callslip', $id, $itemID);
        if (!$result || $result == 'block') {
            return $result;
        }
        return true;
    }

    /**
     * Determine Renewability
     *
     * This is responsible for determining if an item is renewable
     *
     * @param string $patronId The user's patron ID
     * @param string $itemId   The Item Id of item including db key
     *
     * @return mixed Array of the renewability status and associated
     * message
     * @access protected
     */
    protected function isRenewable($patronId, $itemId)
    {
        // Build Hierarchy
        $hierarchy = array(
            "patron" => $patronId,
            "circulationActions" => "loans"
        );

        // Add Required Params
        $params = array(
            "patron_homedb" => $this->ws_patronHomeUbId,
            "view" => "full"
        );

        // Add to Hierarchy
        $hierarchy[$itemId] = false;

        $renewability = $this->makeRequest($hierarchy, $params, "GET");
        $renewability = $renewability->children();
        $node = "reply-text";
        $reply = (string)$renewability->$node;
        if ($reply == "ok") {
            $loanAttributes = $renewability->resource->loan->attributes();
            $canRenew = (string)$loanAttributes['canRenew'];
            if ($canRenew == "Y") {
                $renewData['message'] = false;
                $renewData['renewable'] = true;
            } else {
                $renewData['message'] = "renew_item_no";
                $renewData['renewable'] = false;
            }
        } else {
            $renewData['message'] = "renew_determine_fail";
            $renewData['renewable'] = false;
        }
        return $renewData;
    }

    /**
     * Protected support method for getMyTransactions.
     *
     * @param array $sqlRow An array of keyed data
     * @param array $patron An array of keyed patron data
     *
     * @return array Keyed data for display by template files
     * @access protected
     */
    protected function processMyTransactionsData($sqlRow, $patron)
    {
        $transactions = parent::processMyTransactionsData($sqlRow, $patron);

        // We'll check renewability later in getMyTransactions
        $transactions['renewable'] = true;
        $transactions['message'] = false;

        return $transactions;
    }

    /**
     * Sort function for sorting pickup locations
     *
     * @param array $a Pickup location
     * @param array $b Pickup location
     *
     * @return number
     */
    protected function pickUpLocationsSortFunction($a, $b)
    {
        $pickUpLocationOrder = isset($this->config['Holds']['pickUpLocationOrder']) ? explode(":", $this->config['Holds']['pickUpLocationOrder']) : array();
        $pickUpLocationOrder = array_flip($pickUpLocationOrder);
        if (isset($pickUpLocationOrder[$a['locationID']])) {
            if (isset($pickUpLocationOrder[$b['locationID']])) {
                return $pickUpLocationOrder[$a['locationID']] - $pickUpLocationOrder[$b['locationID']];
            }
            return -1;
        }
        if (isset($pickUpLocationOrder[$b['locationID']])) {
            return 1;
        }
        return strcasecmp($a['locationDisplay'], $b['locationDisplay']);
    }

    /**
     * Get Pick Up Locations
     *
     * This is responsible for gettting a list of valid library locations for
     * holds / recall retrieval
     *
     * @param array $patron      Patron information returned by the patronLogin
     * method.
     * @param array $holdDetails Optional array, only passed in when getting a list
     * in the context of placing a hold; contains most of the same values passed to
     * placeHold, minus the patron data.  May be used to limit the pickup options
     * or may be ignored.  The driver must not add new options to the return array
     * based on this data or other areas of VuFind may behave incorrectly.
     *
     * @return array        An array of associative arrays with locationID and
     * locationDisplay keys
     * @access public
     */
    public function getPickUpLocations($patron = false, $holdDetails = null)
    {
        $pickResponse = array();
        if ($this->ws_pickUpLocations) {
            foreach ($this->ws_pickUpLocations as $code => $library) {
                $pickResponse[] = array(
                    'locationID' => $code,
                    'locationDisplay' => $library
                );
            }
        } else {
            $params = array();
            if ($this->requestGroups && $this->pickupLocationsInRequestGroup && isset($holdDetails['requestGroupId'])) {
                $sql = "SELECT CIRC_POLICY_LOCS.LOCATION_ID as location_id, " .
                    "NVL(LOCATION.LOCATION_DISPLAY_NAME, LOCATION.LOCATION_NAME) " .
                    "as location_name from " .
                    $this->dbName . ".CIRC_POLICY_LOCS, $this->dbName.LOCATION, " .
                    "$this->dbName.REQUEST_GROUP_LOCATION rgl " .
                    "where CIRC_POLICY_LOCS.PICKUP_LOCATION = 'Y' ".
                    "and CIRC_POLICY_LOCS.LOCATION_ID = LOCATION.LOCATION_ID " .
                    "and rgl.GROUP_ID=:requestGroupId and rgl.LOCATION_ID = LOCATION.LOCATION_ID ";
                $params['requestGroupId'] = $holdDetails['requestGroupId'];
            } else {
                $sql = "SELECT CIRC_POLICY_LOCS.LOCATION_ID as location_id, " .
                    "NVL(LOCATION.LOCATION_DISPLAY_NAME, LOCATION.LOCATION_NAME) " .
                    "as location_name from " .
                    $this->dbName . ".CIRC_POLICY_LOCS, $this->dbName.LOCATION " .
                    "where CIRC_POLICY_LOCS.PICKUP_LOCATION = 'Y' ".
                    "and CIRC_POLICY_LOCS.LOCATION_ID = LOCATION.LOCATION_ID";
            }

            try {
                $sqlStmt = $this->db->prepare($sql);
                $this->debugLogSQL(__FUNCTION__, $sql, $params);
                $sqlStmt->execute($params);
            } catch (PDOException $e) {
                return new PEAR_Error($e->getMessage());
            }

            // Read results
            while ($row = $sqlStmt->fetch(PDO::FETCH_ASSOC)) {
                $pickResponse[] = array(
                    "locationID" => $row['LOCATION_ID'],
                    "locationDisplay" => utf8_encode($row['LOCATION_NAME'])
                );
            }
        }

        // Sort pick up locations
        usort($pickResponse, array($this, 'pickUpLocationsSortFunction'));

        return $pickResponse;
    }

    /**
     * Get Default Pick Up Location
     *
     * Returns the default pick up location set in VoyagerRestful.ini
     *
     * @param array $patron      Patron information returned by the patronLogin
     * method.
     * @param array $holdDetails Optional array, only passed in when getting a list
     * in the context of placing a hold; contains most of the same values passed to
     * placeHold, minus the patron data.  May be used to limit the pickup options
     * or may be ignored.
     *
     * @return string       The default pickup location for the patron.
     */
    public function getDefaultPickUpLocation($patron = false, $holdDetails = null)
    {
        return $this->defaultPickUpLocation;
    }

    /**
     * Get Default Request Group
     *
     * Returns the default request group set in VoyagerRestful.ini
     *
     * @param array $patron      Patron information returned by the patronLogin
     * method.
     * @param array $holdDetails Optional array, only passed in when getting a list
     * in the context of placing a hold; contains most of the same values passed to
     * placeHold, minus the patron data.  May be used to limit the request group options
     * or may be ignored.
     *
     * @return string       The default request group for the patron.
     */
    public function getDefaultRequestGroup($patron = false, $holdDetails = null)
    {
        return $this->defaultRequestGroup;
    }

    /**
     * Sort function for sorting request groups
     *
     * @param array $a Request group
     * @param array $b Request group
     *
     * @return number
     */
    protected function requestGroupSortFunction($a, $b)
    {
        $requestGroupOrder = isset($this->config['Holds']['requestGroupOrder']) ? explode(":", $this->config['Holds']['requestGroupOrder']) : array();
        $requestGroupOrder = array_flip($requestGroupOrder);
        if (isset($requestGroupOrder[$a['id']])) {
            if (isset($requestGroupOrder[$b['id']])) {
                return $requestGroupOrder[$a['id']] - $requestGroupOrder[$b['id']];
            }
            return -1;
        }
        if (isset($requestGroupOrder[$b['id']])) {
            return 1;
        }
        return strcasecmp($a['name'], $b['name']);
    }

    /**
     * Get request groups
     *
     * @param integer $bibId    BIB ID
     * @param array   $patronId Patron information returned by the patronLogin
     * method.
     *
     * @return array  False if request groups not in use or an array of
     * associative arrays with id and name keys
     */
    public function getRequestGroups($bibId, $patronId)
    {
        if (!$this->requestGroups) {
            return false;
        }

        $itemCheck = isset($this->config['Holds']['checkItemsExist']) && $this->config['Holds']['checkItemsExist'];

        if ($itemCheck) {
            // First get hold information for the list of items Voyager
            // thinks are holdable
            $request = $this->determineHoldType($patronId, $bibId);
            if ($request != 'hold' && $result != 'recall') {
                return false;
            }

            $hierarchy = array();

            // Build Hierarchy
            $hierarchy['record'] = $bibId;
            $hierarchy[$request] = false;

            // Add Required Params
            $params = array(
                "patron" => $patronId,
                "patron_homedb" => $this->ws_patronHomeUbId,
                "view" => "full"
            );

            $results = $this->makeRequest($hierarchy, $params, "GET", false);

            if ($results === false) {
                return new PEAR_Error('Could not fetch hold information');
            }

            $items = array();
            foreach ($results->hold as $hold) {
                foreach ($hold->items->item as $item) {
                    $items[(string)$item->item_id] = 1;
                }
            }
        }

        // Find request groups (with items if item check is enabled)
        if ($itemCheck) {
            $sqlExpressions = array(
                'rg.GROUP_ID',
                'rg.GROUP_NAME',
                'bi.ITEM_ID'
            );

            $sqlFrom = array(
                "$this->dbName.BIB_ITEM bi",
                "$this->dbName.MFHD_ITEM mi",
                "$this->dbName.MFHD_MASTER mm",
                "$this->dbName.REQUEST_GROUP rg",
                "$this->dbName.REQUEST_GROUP_LOCATION rgl",
            );

            $sqlWhere = array(
                'bi.BIB_ID=:bibId',
                'mi.ITEM_ID=bi.ITEM_ID',
                'mm.MFHD_ID=mi.MFHD_ID',
                'rgl.LOCATION_ID=mm.LOCATION_ID',
                'rg.GROUP_ID=rgl.GROUP_ID'
            );

            $sqlBind = array(
                'bibId' => $bibId
            );
        } else {
            $sqlExpressions = array(
                'rg.GROUP_ID',
                'rg.GROUP_NAME',
            );

            $sqlFrom = array(
                "$this->dbName.REQUEST_GROUP rg",
                "$this->dbName.REQUEST_GROUP_LOCATION rgl"
            );

            $sqlWhere = array(
                'rg.GROUP_ID=rgl.GROUP_ID'
            );

            $sqlBind = array(
            );

            if ($this->pickupLocationsInRequestGroup) {
                // Limit to request groups that have valid pickup locations
                $sqlFrom[] = "$this->dbName.REQUEST_GROUP_LOCATION rgl";
                $sqlFrom[] = "$this->dbName.CIRC_POLICY_LOCS cpl";

                $sqlWhere[] = "rgl.GROUP_ID=rg.GROUP_ID";
                $sqlWhere[] = "cpl.LOCATION_ID=rgl.LOCATION_ID";
                $sqlWhere[] = "cpl.PICKUP_LOCATION='Y'";
            }
        }

        if (isset($this->config['Holds']['checkItemsAvailable']) && $this->config['Holds']['checkItemsAvailable']) {

            // Build inner query first
            $subExpressions = array(
                'sub_rgl.GROUP_ID',
                'sub_i.ITEM_ID',
                'max(sub_ist.ITEM_STATUS) as STATUS'
            );

            $subFrom = array(
                "$this->dbName.ITEM_STATUS sub_ist",
                "$this->dbName.BIB_ITEM sub_bi",
                "$this->dbName.ITEM sub_i",
                "$this->dbName.REQUEST_GROUP_LOCATION sub_rgl",
                "$this->dbName.MFHD_ITEM sub_mi",
                "$this->dbName.MFHD_MASTER sub_mm"
            );

            $subWhere = array(
                'sub_bi.BIB_ID=:subBibId',
                'sub_i.ITEM_ID=sub_bi.ITEM_ID',
                'sub_ist.ITEM_ID=sub_i.ITEM_ID',
                'sub_mi.ITEM_ID=sub_i.ITEM_ID',
                'sub_mm.MFHD_ID=sub_mi.MFHD_ID',
                'sub_rgl.LOCATION_ID=sub_mm.LOCATION_ID'
            );

            $subGroup = array(
                'sub_rgl.GROUP_ID',
                'sub_i.ITEM_ID'
            );

            $sqlBind['subBibId'] = $bibId;

            $subArray = array(
                'expressions' => $subExpressions,
                'from' => $subFrom,
                'where' => $subWhere,
                'group' => $subGroup,
                'bind' => array()
            );

            $subSql = $this->buildSqlFromArray($subArray);

            $sqlWhere[] = "not exists (select status.GROUP_ID from ({$subSql['string']}) status where status.status=1 and status.GROUP_ID = rgl.GROUP_ID)";
        }

        $sqlArray = array(
            'expressions' => $sqlExpressions,
            'from' => $sqlFrom,
            'where' => $sqlWhere,
            'bind' => $sqlBind
        );

        $sql = $this->buildSqlFromArray($sqlArray);

        try {
            $this->debugLogSQL(__FUNCTION__, $sql['string'], $sql['bind']);
            $sqlStmt = $this->db->prepare($sql['string']);
            $sqlStmt->execute($sql['bind']);
        } catch (PDOException $e) {
            return new PEAR_Error($e->getMessage());
        }

        $groups = array();
        while ($row = $sqlStmt->fetch(PDO::FETCH_ASSOC)) {
            if (!$itemCheck || isset($items[$row['ITEM_ID']])) {
                $groups[$row['GROUP_ID']] = utf8_encode($row['GROUP_NAME']);
            }
        }

        $results = array();
        foreach ($groups as $groupId => $groupName) {
            $results[] = array('id' => $groupId, 'name' => $groupName);
        }

        // Sort request groups
        usort($results, array($this, 'requestGroupSortFunction'));

        return $results;
    }

     /**
     * Make Request
     *
     * Makes a request to the Voyager Restful API
     *
     * @param array  $hierarchy Array of key-value pairs to embed in the URL path of
     * the request (set value to false to inject a non-paired value).
     * @param array  $params    A keyed array of query data
     * @param string $mode      The http request method to use (Default of GET)
     * @param string $xml       An optional XML string to send to the API
     *
     * @return obj  A Simple XML Object loaded with the xml data returned by the API
     * @access protected
     */
    protected function makeRequest($hierarchy, $params = false, $mode = "GET",
        $xml = false
    ) {
        // Build Url Base
        $urlParams = "http://{$this->ws_host}:{$this->ws_port}/{$this->ws_app}";

        // Add Hierarchy
        foreach ($hierarchy as $key => $value) {
            $hierarchyString[] = ($value !== false)
                ? urlencode($key) . "/" . urlencode($value) : urlencode($key);
        }

        // Add Params
        foreach ($params as $key => $param) {
            $queryString[] = $key. "=" . urlencode($param);
        }

        // Build Hierarchy
        $urlParams .= "/" . implode("/", $hierarchyString);

        // Build Params
        if (isset($queryString)) {
            $urlParams .= "?" . implode("&", $queryString);
        }

        if ($mode == 'GET' && isset($this->getCache[$urlParams])) {
            return $this->getCache[$urlParams];
        }

        // Create Proxy Request
        $client = new Proxy_Request($urlParams);
        if ($this->sessionId) {
            $client->addCookie('JSESSIONID', $this->sessionId);
        }

        // Select Method
        if ($mode == "POST") {
            $client->setMethod(HTTP_REQUEST_METHOD_POST);
            if ($xml) {
                $client->addRawPostData($xml);
            }
        } else if ($mode == "PUT") {
            $client->setMethod(HTTP_REQUEST_METHOD_PUT);
            $client->addRawPostData($xml);
        } else if ($mode == "DELETE") {
            $client->setMethod(HTTP_REQUEST_METHOD_DELETE);
        } else {
            $client->setMethod(HTTP_REQUEST_METHOD_GET);
        }

        // Send Request and Retrieve Response
        $startTime = microtime(true);
        if (PEAR::isError($client->sendRequest())) {
            error_log("VoyagerRestful: failed to send request to $urlParams");
            return false;
        }
        $code = $client->getResponseCode();
        if ($code >= 400) {
            error_log("VoyagerRestful: HTTP Request failed with error code $code. Request url: $urlParams, response: " . $client->getResponseBody());
        }
        $cookies = $client->getResponseCookies();
        if ($cookies) {
            foreach ($cookies as $cookie) {
                if ($cookie['name'] == 'JSESSIONID') {
                    $this->sessionId = $cookie['value'];
                }
            }
        }
        $xmlResponse = $client->getResponseBody();
        $this->debugLog('[' . round(microtime(true) - $startTime, 4) . "s] {$this->sessionId} $mode request $urlParams, body:\n$xml\nResults:\n$xmlResponse");
        $oldLibXML = libxml_use_internal_errors();
        libxml_use_internal_errors(true);
        $simpleXML = simplexml_load_string($xmlResponse);
        libxml_use_internal_errors($oldLibXML);

        if ($simpleXML === false) {
            $logger = new Logger();
            $error = libxml_get_last_error();
            $logger->log('Failed to parse response XML: ' . $error->message . ", response:\n" . $xmlResponse, PEAR_LOG_ERR);
            $this->debugLog('Failed to parse response XML: ' . $error->message . ", response:\n" . $xmlResponse);
            return false;
        }
        if ($mode == 'GET') {
            $this->getCache[$urlParams] = $simpleXML;
        }
        return $simpleXML;
    }

    /**
     * Build Basic XML
     *
     * Builds a simple xml string to send to the API
     *
     * @param array $xml A keyed array of xml node names and data
     *
     * @return string    An XML string
     * @access protected
     */

    protected function buildBasicXML($xml)
    {
        $xmlString = "";

        foreach ($xml as $root => $nodes) {
            $xmlString .= "<" . $root . ">";

            foreach ($nodes as $nodeName => $nodeValue) {
                $xmlString .= "<" . $nodeName . ">";
                $xmlString .= htmlspecialchars($nodeValue, ENT_COMPAT, "UTF-8");
                // Split out any attributes
                $nodeName = strtok($nodeName, ' ');
                $xmlString .= "</" . $nodeName . ">";
            }

            // Split out any attributes
            $root = strtok($root, ' ');
            $xmlString .= "</" . $root . ">";
        }

        $xmlComplete = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" . $xmlString;

        return $xmlComplete;
    }

    /**
     * Check Account Blocks
     *
     * Checks if a user has any blocks against their account which may prevent them
     * performing certain operations
     *
     * @param string $patronId       A Patron ID
     * @param string $patronHomeUbId An override for UbId in configuration
     *
     * @return mixed           A boolean false if no blocks are in place and an array
     * of block reasons if blocks are in place
     * @access protected
     */

    protected function checkAccountBlocks($patronId, $patronHomeUbId = false)
    {
        $blockReason = false;

        // Build Hierarchy
        $hierarchy = array(
            "patron" =>  $patronId,
            "patronStatus" => "blocks"
        );

        // Add Required Params
        $params = array(
            "patron_homedb" => $patronHomeUbId ? $patronHomeUbId : $this->ws_patronHomeUbId,
            "view" => "full"
        );

        $blocks = $this->makeRequest($hierarchy, $params);

        if ($blocks) {
            $node = "reply-text";
            $reply = (string)$blocks->$node;

            // Valid Response
            if ($reply == "ok" && isset($blocks->blocks)) {
                $blockReason = array();
                $borrowingBlocks = $blocks->xpath("//blocks/institution[@id='LOCAL']/borrowingBlock");
                if (count($borrowingBlocks)) {
                    $blockReason[] = translate('Borrowing Block Message');
                }
                foreach ($borrowingBlocks as $borrowBlock) {
                    $code = (int)$borrowBlock->blockCode;
                    $reason = "Borrowing Block Voyager Reason $code";
                    $translated = translate($reason);
                    if ($reason !== $translated) {
                        $reason = $translated;
                        if ($code == 19) {
                            // Fine limit
                            $reason = str_replace('%%blockCount%%', $borrowBlock->blockCount, $reason);
                            $reason = str_replace('%%blockLimit%%', $borrowBlock->blockLimit, $reason);
                        }
                        $blockReason[] = $reason;
                    }
                }
            }
        }

        return $blockReason;
    }

    /**
     * Renew My Items
     *
     * Function for attempting to renew a patron's items.  The data in
     * $renewDetails['details'] is determined by getRenewDetails().
     *
     * @param array $renewDetails An array of data required for renewing items
     * including the Patron ID and an array of renewal IDS
     *
     * @return array              An array of renewal information keyed by item ID
     * @access public
     */
    public function renewMyItems($renewDetails)
    {
        $finalResult = array('blocks' => array(), 'details' => array());
        $patron = $renewDetails['patron'];

        // Get Account Blocks
        $finalResult['blocks'] = $this->checkAccountBlocks($patron['id']);

        if ($finalResult['blocks'] === false) {
            // Add Items and Attempt Renewal
            $itemIdentifiers = '';

            foreach ($renewDetails['details'] as $renewID) {
                list($dbKey, $loanId) = explode("|", $renewID);

                if (!$dbKey) {
                    $dbKey = $this->ws_dbKey;
                }
                $dbKey = htmlspecialchars($dbKey, ENT_COMPAT, 'UTF-8');
                $loanId = htmlspecialchars($loanId, ENT_COMPAT, 'UTF-8');

                $itemIdentifiers .= <<<EOT
      <myac:itemIdentifier>
       <myac:itemId>$loanId</myac:itemId>
       <myac:ubId>$dbKey</myac:ubId>
      </myac:itemIdentifier>

EOT;
            }

            $patronId = htmlspecialchars($patron['id'], ENT_COMPAT, 'UTF-8');
            $lastname = htmlspecialchars($patron['lastname'], ENT_COMPAT, 'UTF-8');
            $barcode = htmlspecialchars($patron['cat_username'], ENT_COMPAT, 'UTF-8');
            $localUbId = htmlspecialchars($this->ws_patronHomeUbId, ENT_COMPAT, 'UTF-8');

            // The RenewService has a weird prerequisite that AuthenticatePatronService
            // must be called first and JSESSIONID header be preserved. There's no
            // explanation why this is required, and a quick check implies that RenewService
            // works without it at least in Voyager 8.1, but who knows if it fails with UB
            // or something, so let's try to play along with the rules.
            $xml = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<ser:serviceParameters xmlns:ser="http://www.endinfosys.com/Voyager/serviceParameters">
  <ser:patronIdentifier lastName="$lastname" patronHomeUbId="$localUbId">
    <ser:authFactor type="B">$barcode</ser:authFactor>
  </ser:patronIdentifier>
</ser:serviceParameters>
EOT;

            $response = $this->makeRequest(array('AuthenticatePatronService' => false), array(), 'POST', $xml);
            if ($response === false) {
                return new PEAR_Error('renew_error_system');
            }

            $xml = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<ser:serviceParameters xmlns:ser="http://www.endinfosys.com/Voyager/serviceParameters">
   <ser:parameters/>
   <ser:definedParameters xsi:type="myac:myAccountServiceParametersType" xmlns:myac="http://www.endinfosys.com/Voyager/myAccount" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
$itemIdentifiers
   </ser:definedParameters>
  <ser:patronIdentifier lastName="$lastname" patronHomeUbId="$localUbId" patronId="$patronId">
    <ser:authFactor type="B">$barcode</ser:authFactor>
  </ser:patronIdentifier>
</ser:serviceParameters>
EOT;

            $response = $this->makeRequest(array('RenewService' => false), array(), 'POST', $xml);
            if ($response === false) {
                return false;
            }

            // Process
            $myac_ns = 'http://www.endinfosys.com/Voyager/myAccount';
            $response->registerXPathNamespace('ser', 'http://www.endinfosys.com/Voyager/serviceParameters');
            $response->registerXPathNamespace('myac', $myac_ns);
            // The service doesn't actually return messages (in Voyager 8.1), but maybe in the future...
            foreach ($response->xpath('//ser:message') as $message) {
                if ($message->attributes()->type == 'system') {
                    return false;
                }
            }
            foreach ($response->xpath('//myac:clusterChargedItems') as $cluster) {
                $cluster = $cluster->children($myac_ns);
                $dbKey = (string)$cluster->cluster->ubSiteId;
                foreach ($cluster->chargedItem as $chargedItem) {
                    $chargedItem = $chargedItem->children($myac_ns);
                    $renewStatus = $chargedItem->renewStatus;
                    if (!$renewStatus) {
                        continue;
                    }
                    $renewed = false;
                    foreach ($renewStatus->status as $status) {
                        if ((string)$status == 'Renewed') {
                            $renewed = true;
                        }
                    }

                    $result = array();
                    $result['item_id'] = (string)$chargedItem->itemId;
                    $result['sysMessage'] = (string)$renewStatus->status;

                    $dueDate = (string)$chargedItem->dueDate;
                    $newDate = $this->dateFormat->convertToDisplayDate(
                        "Y-m-d H:i", $dueDate
                    );
                    $newTime = $this->dateFormat->convertToDisplayTime(
                        "Y-m-d H:i", $dueDate
                    );
                    $result['new_date'] = PEAR::isError($newDate) ? $dueDate : $newDate;
                    $result['new_time'] = PEAR::isError($newTime) ? '' : $newTime;
                    $result['success'] = $renewed;

                    $finalResult['details'][$result['item_id']] = $result;
                }
            }
        }
        return $finalResult;
    }

    /**
     * Process Renewals
     *
     * A support method of renewMyItems which determines if the renewal attempt
     * was successful
     *
     * @param object $renewalObj A simpleXML object loaded with renewal data
     *
     * @return array             An array with the item id, success, new date (if
     * available) and system message (if available)
     * @access protected
     */
    protected function processRenewals($renewalObj)
    {
        // Not Sure Why, but necessary!
        $renewal = $renewalObj->children();
        $node = "reply-text";
        $reply = (string)$renewal->$node;

        // Valid Response
        if ($reply == "ok") {
            $loan = $renewal->renewal->institution->loan;
            $itemId = (string)$loan->itemId;
            $renewalStatus = (string)$loan->renewalStatus;

            $response['item_id'] = $itemId;
            $response['sysMessage'] = $renewalStatus;

            if ($renewalStatus == "Success") {
                $dueDate = (string)$loan->dueDate;
                if (!empty($dueDate)) {
                    // Convert Voyager Format to display format
                    $newDate = $this->dateFormat->convertToDisplayDate(
                        "Y-m-d H:i", $dueDate
                    );
                    $newTime = $this->dateFormat->convertToDisplayTime(
                        "Y-m-d H:i", $dueDate
                    );
                    if (!PEAR::isError($newDate)) {
                        $response['new_date'] = $newDate;
                    }
                    if (!PEAR::isError($newTime)) {
                        $response['new_time'] = $newTime;
                    }
                }
                $response['success'] = true;
            } else {
                $response['success'] = false;
                $response['new_date'] = false;
                $response['new_time'] = false;
            }

            return $response;
        } else {
            // System Error
            return false;
        }
    }

    /**
     * Check Item Requests
     *
     * Determines if a user can place a hold or recall on a specific item
     *
     * @param string $patronId The user's Patron ID
     * @param string $request  The request type (hold or recall)
     * @param string $bibId    An item's Bib ID
     * @param string $itemId   An item's Item ID (optional)
     *
     * @return boolean         true if the request can be made, false if it cannot
     * @access protected
     */
    protected function checkItemRequests($patronId, $request, $bibId,
        $itemId = false
    ) {
        if (!empty($bibId) && !empty($patronId) && !empty($request) ) {

            $hierarchy = array();

            // Build Hierarchy
            $hierarchy['record'] = $bibId;

            if ($itemId) {
                $hierarchy['items'] = $itemId;
            }

            $hierarchy[$request] = false;

            // Add Required Params
            $params = array(
                "patron" => $patronId,
                "patron_homedb" => $this->ws_patronHomeUbId,
                "view" => "full"
            );

            $check = $this->makeRequest($hierarchy, $params, "GET", false);

            if ($check) {
                // Process
                $check = $check->children();
                $node = "reply-text";
                $reply = (string)$check->$node;

                // Valid Response
                if ($reply == "ok") {
                    if ($check->$request ) {
                        $requestAttributes = $check->$request->attributes();
                        if ($requestAttributes['allowed'] == 'N') {
                            return false;
                        }
                    }
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Make Item Requests
     *
     * Places a Hold or Recall for a particular item
     *
     * @param string $patronId    The user's Patron ID
     * @param string $request     The request type (hold or recall)
     * @param string $level       The request level (title or copy)
     * @param array  $requestData An array of data to submit with the request,
     * may include comment, lastInterestDate and pickUpLocation
     * @param string $bibId       An item's Bib ID
     * @param string $itemId      An item's Item ID (optional)
     *
     * @return array             An array of data from the attempted request
     * including success, status and a System Message (if available)
     * @access protected
     */
    protected function makeItemRequests($patronId, $request, $level,
        $requestData, $bibId, $itemId = false
    ) {
        $response = array('success' => false, 'status' =>"hold_error_fail");

        if (!empty($bibId) && !empty($patronId) && !empty($requestData)
            && !empty($request)
        ) {
            $hierarchy = array();

            // Build Hierarchy
            $hierarchy['record'] = $bibId;

            if ($itemId) {
                $hierarchy['items'] = $itemId;
            }

            $hierarchy[$request] = false;

            // Add Required Params
            $params = array(
                "patron" => $patronId,
                "patron_homedb" => $this->ws_patronHomeUbId,
                "view" => "full"
            );

            if ("title" == $level) {
                $xmlParameter = ("recall" == $request)
                    ? "recall-title-parameters" : "hold-title-parameters";
                $request = $request . "-title";
            } else {
                $xmlParameter = ("recall" == $request)
                    ? "recall-parameters" : "hold-request-parameters";
            }


            $xml[$xmlParameter] = array(
                "pickup-location" => $requestData['pickupLocation'],
                "last-interest-date" => $requestData['lastInterestDate'],
                "comment" => $requestData['comment'],
                "dbkey" => $this->ws_dbKey
            );

            // Generate XML
            $requestXML = $this->buildBasicXML($xml);

            // Get Data
            $result = $this->makeRequest($hierarchy, $params, "PUT", $requestXML);

            if ($result) {
                // Process
                $result = $result->children();
                $node = "reply-text";
                $reply = (string)$result->$node;

                $responseNode = "create-".$request;
                $note = (isset($result->$responseNode))
                    ? trim((string)$result->$responseNode->note) : false;

                // Valid Response
                if ($reply == "ok" && $note == "Your request was successful.") {
                    $response['success'] = true;
                    $response['status'] = "hold_success";
                } else {
                    // Failed
                    $response['sysMessage'] = $note;
                }
            }
        }
        return $response;
    }

    /**
     * Determine Hold Type
     *
     * Determines if a user can place a hold or recall on a particular item
     *
     * @param string $patronId The user's Patron ID
     * @param string $bibId    An item's Bib ID
     * @param string $itemId   An item's Item ID (optional)
     *
     * @return string The name of the request method to use or false on
     * failure
     * @access protected
     */
    protected function determineHoldType($patronId, $bibId, $itemId = false)
    {
        if ($itemId && !$this->itemHolds) {
            return false;
        }

        // Check for account Blocks
        if ($this->checkAccountBlocks($patronId)) {
            return "block";
        }

        // Check Recalls First
        $recall = false;
        if (!isset($this->config['Holds']['enableRecalls']) || $this->config['Holds']['enableRecalls']) {
            $recall = $this->checkItemRequests($patronId, "recall", $bibId, $itemId);
        }
        if ($recall) {
            return "recall";
        } else {
            // Check Holds
            $hold = $this->checkItemRequests($patronId, "hold", $bibId, $itemId);
            if ($hold) {
                return "hold";
            }
        }
        return false;
    }

    /**
     * Hold Error
     *
     * Returns a Hold Error Message
     *
     * @param string $msg An error message string
     *
     * @return array An array with a success (boolean) and sysMessage key
     * @access protected
     */
    protected function holdError($msg)
    {
        return array(
                    "success" => false,
                    "sysMessage" => $msg
        );
    }

    /**
     * Check whether the given patron has the given bib record on loan.
     *
     * @param integer $patronId Patron ID
     * @param integer $bibId    BIB ID
     *
     * @return boolean
     */
    protected function isRecordOnLoan($patronId, $bibId)
    {
        $sqlExpressions = array(
            'count(cta.ITEM_ID) CNT'
        );

        $sqlFrom = array(
            "$this->dbName.BIB_ITEM bi",
            "$this->dbName.CIRC_TRANSACTIONS cta"
        );

        $sqlWhere = array(
            'cta.PATRON_ID=:patronId',
            'bi.BIB_ID=:bibId',
            'bi.ITEM_ID=cta.ITEM_ID'
        );

        if ($this->requestGroups) {
            $sqlFrom[] = "$this->dbName.REQUEST_GROUP_LOCATION rgl";
            $sqlFrom[] = "$this->dbName.MFHD_ITEM mi";
            $sqlFrom[] = "$this->dbName.MFHD_MASTER mm";

            $sqlWhere[] = "mi.ITEM_ID=cta.ITEM_ID";
            $sqlWhere[] = "mm.MFHD_ID=mi.MFHD_ID";
            $sqlWhere[] = "rgl.LOCATION_ID=mm.LOCATION_ID";
        }

        $sqlBind = array('patronId' => $patronId, 'bibId' => $bibId);

        $sqlArray = array(
            'expressions' => $sqlExpressions,
            'from' => $sqlFrom,
            'where' => $sqlWhere,
            'bind' => $sqlBind
        );

        $sql = $this->buildSqlFromArray($sqlArray);

        try {
            $sqlStmt = $this->db->prepare($sql['string']);
            $this->debugLogSQL(__FUNCTION__, $sql['string'], $sql['bind']);
            $sqlStmt->execute($sql['bind']);
            $sqlRow = $sqlStmt->fetch(PDO::FETCH_ASSOC);
            return $sqlRow['CNT'] > 0;
        } catch (PDOException $e) {
            return new PEAR_Error($e->getMessage());
        }
    }

    /**
     * Check whether items exist for the given BIB ID
     *
     * @param integer $bibId          BIB ID
     * @param integer $requestGroupId Request group ID or null
     *
     * @return boolean;
     */
    protected function itemsExist($bibId, $requestGroupId)
    {
        $sqlExpressions = array(
            'count(i.ITEM_ID) CNT'
        );

        $sqlFrom = array(
            "$this->dbName.BIB_ITEM bi",
            "$this->dbName.ITEM i",
            "$this->dbName.MFHD_ITEM mi",
            "$this->dbName.MFHD_MASTER mm"
        );

        $sqlWhere = array(
            'bi.BIB_ID=:bibId',
            'i.ITEM_ID=bi.ITEM_ID',
            'mi.ITEM_ID=i.ITEM_ID',
            'mm.MFHD_ID=mi.MFHD_ID'
        );

        if (isset($this->config['Holds']['excludedItemLocations']) && $this->config['Holds']['excludedItemLocations']) {
            $sqlWhere[] = 'mm.LOCATION_ID not in (' . str_replace(':', ',', $this->config['Holds']['excludedItemLocations']) . ')';
        }

        $sqlBind = array('bibId' => $bibId);

        if ($this->requestGroups && isset($requestGroupId)) {
            $sqlFrom[] = "$this->dbName.REQUEST_GROUP_LOCATION rgl";

            $sqlWhere[] = "rgl.LOCATION_ID=mm.LOCATION_ID";
            $sqlWhere[] = "rgl.GROUP_ID=:requestGroupId";

            $sqlBind['requestGroupId'] = $requestGroupId;
        }

        $sqlArray = array(
            'expressions' => $sqlExpressions,
            'from' => $sqlFrom,
            'where' => $sqlWhere,
            'bind' => $sqlBind
        );

        $sql = $this->buildSqlFromArray($sqlArray);
        try {
            $sqlStmt = $this->db->prepare($sql['string']);
            $this->debugLogSQL(__FUNCTION__, $sql['string'], $sql['bind']);
            $sqlStmt->execute($sql['bind']);
            $sqlRow = $sqlStmt->fetch(PDO::FETCH_ASSOC);
            return $sqlRow['CNT'] > 0;
        } catch (PDOException $e) {
            return new PEAR_Error($e->getMessage());
        }
    }

    /**
     * Check whether there are items available for loan for the given BIB ID
     *
     * @param integer $bibId          BIB ID
     * @param integer $requestGroupId Request group ID or null
     *
     * @return boolean;
     */
    protected function itemsAvailable($bibId, $requestGroupId)
    {
        // Build inner query first
        $sqlExpressions = array(
            'i.ITEM_ID',
            'max(ist.ITEM_STATUS) as STATUS'
        );

        $sqlFrom = array(
            "$this->dbName.ITEM_STATUS ist",
            "$this->dbName.BIB_ITEM bi",
            "$this->dbName.ITEM i",
            "$this->dbName.MFHD_ITEM mi",
            "$this->dbName.MFHD_MASTER mm"
        );

        $sqlWhere = array(
            'bi.BIB_ID=:bibId',
            'i.ITEM_ID=bi.ITEM_ID',
            'ist.ITEM_ID=i.ITEM_ID',
            'mi.ITEM_ID=i.ITEM_ID',
            'mm.MFHD_ID=mi.MFHD_ID'
        );

        if (isset($this->config['Holds']['excludedItemLocations']) && $this->config['Holds']['excludedItemLocations']) {
            $sqlWhere[] = 'mm.LOCATION_ID not in (' . str_replace(':', ',', $this->config['Holds']['excludedItemLocations']) . ')';
        }

        $sqlGroup = array(
            'i.ITEM_ID'
        );

        $sqlBind = array('bibId' => $bibId);

        if ($this->requestGroups && isset($requestGroupId)) {
            $sqlFrom[] = "$this->dbName.REQUEST_GROUP_LOCATION rgl";

            $sqlWhere[] = "rgl.LOCATION_ID=mm.LOCATION_ID";
            $sqlWhere[] = "rgl.GROUP_ID=:requestGroupId";

            $sqlBind['requestGroupId'] = $requestGroupId;
        }

        $sqlArray = array(
            'expressions' => $sqlExpressions,
            'from' => $sqlFrom,
            'where' => $sqlWhere,
            'group' => $sqlGroup,
            'bind' => $sqlBind
        );

        $sql = $this->buildSqlFromArray($sqlArray);
        $outersql = "select count(avail.item_id) CNT from (${sql['string']}) avail where avail.STATUS=1"; // 1 = not charged

        try {
            $sqlStmt = $this->db->prepare($outersql);
            $this->debugLogSQL(__FUNCTION__, $outersql, $sql['bind']);
            $sqlStmt->execute($sql['bind']);
            $sqlRow = $sqlStmt->fetch(PDO::FETCH_ASSOC);
            return $sqlRow['CNT'] > 0;
        } catch (PDOException $e) {
            return new PEAR_Error($e->getMessage());
        }
    }

    /**
     * Place Hold
     *
     * Attempts to place a hold or recall on a particular item and returns
     * an array with result details or a PEAR error on failure of support classes
     *
     * @param array $holdDetails An array of item and patron data
     *
     * @return mixed An array of data on the request including
     * whether or not it was successful and a system message (if available) or a
     * PEAR error on failure of support classes
     * @access public
     */
    public function placeHold($holdDetails)
    {
        $patron = $holdDetails['patron'];
        $type = isset($holdDetails['holdtype']) && !empty($holdDetails['holdtype'])
            ? $holdDetails['holdtype'] : "auto";
        $level = isset($holdDetails['level']) && !empty($holdDetails['level'])
            ? $holdDetails['level'] : "copy";
        $pickUpLocation = !empty($holdDetails['pickUpLocation'])
            ? $holdDetails['pickUpLocation'] : $this->defaultPickUpLocation;
        $itemId = isset($holdDetails['item_id']) ? $holdDetails['item_id'] : false;
        $comment = isset($holdDetails['comment']) ? $holdDetails['comment'] : '';
        $bibId = $holdDetails['id'];
        // Request was initiated before patron was logged in -
        // Let's determine Hold Type now
        if ($type == "auto") {
            $type = $this->determineHoldType($patron['id'], $bibId, $itemId);
            if (!$type || $type == "block") {
                return $this->holdError("hold_error_blocked");
            }
        }

        // Convert last interest date from Display Format to Voyager required format
        $lastInterestDate = $this->dateFormat->convertFromDisplayDate(
            "Y-m-d", $holdDetails['requiredBy']
        );
        if (PEAR::isError($lastInterestDate)) {
            // Hold Date is invalid
            return $this->holdError("hold_date_invalid");
        }

        $checkTime =  $this->dateFormat->convertFromDisplayDate(
            "U", $holdDetails['requiredBy']
        );
        if (PEAR::isError($checkTime) || !is_numeric($checkTime)) {
            return $checkTime;
        }

        if (time() > $checkTime) {
            // Hold Date is in the past
            return $this->holdError("hold_date_past");
        }

        // Make Sure Pick Up Library is Valid
        $pickUpValid = false;
        $pickUpLibs = $this->getPickUpLocations($patron, $holdDetails);
        foreach ($pickUpLibs as $location) {
            if ($location['locationID'] == $pickUpLocation) {
                $pickUpValid = true;
            }
        }
        if (!$pickUpValid) {
            // Invalid Pick Up Point
            return $this->holdError("hold_invalid_pickup");
        }

        // Optional check that the bib has items
        if (isset($this->config['Holds']['checkItemsExist']) && $this->config['Holds']['checkItemsExist']) {
            $result = $this->itemsExist($bibId, isset($holdDetails['requestGroupId']) ? $holdDetails['requestGroupId'] : null);
            if (PEAR::isError($result)) {
                return $result;
            }
            if (!$result) {
                return $this->holdError('hold_no_items');
            }
        }

        // Optional check that the bib has no available items
        if (isset($this->config['Holds']['checkItemsAvailable']) && $this->config['Holds']['checkItemsAvailable']) {
            $disabledGroups = isset($this->config['Holds']['disableAvailabilityCheckForRequestGroups']) ? explode(':', $this->config['Holds']['disableAvailabilityCheckForRequestGroups']) : array();
            if (!isset($holdDetails['requestGroupId']) || !in_array($holdDetails['requestGroupId'], $disabledGroups)) {
                $result = $this->itemsAvailable($bibId, isset($holdDetails['requestGroupId']) ? $holdDetails['requestGroupId'] : null);
                if (PEAR::isError($result)) {
                    return $result;
                }
                if ($result) {
                    return $this->holdError('hold_items_available');
                }
            }
        }

        // Optional check that the patron doesn't already have the bib on loan
        if (isset($this->config['Holds']['checkLoans']) && $this->config['Holds']['checkLoans']) {
            $result = $this->isRecordOnLoan($patron['id'], $bibId);
            if (PEAR::isError($result)) {
                return $result;
            }
            if ($result) {
                return $this->holdError('hold_record_already_on_loan');
            }
        }

        // Use XML Over HTTP Web Services for request group specific requests
        //  since the restful interface doesn't support them.
        if ($this->requestGroups && !$itemId) {
            if (!isset($holdDetails['requestGroupId']) || $holdDetails['requestGroupId'] === '') {
                return $this->holdError('hold_invalid_request_group');
            }

            $patronId = htmlspecialchars($patron['id'], ENT_COMPAT, 'UTF-8');
            $lastname = htmlspecialchars($patron['lastname'], ENT_COMPAT, 'UTF-8');
            $barcode = htmlspecialchars($patron['cat_username'], ENT_COMPAT, 'UTF-8');
            $pickupLocation = htmlspecialchars($pickUpLocation, ENT_COMPAT, 'UTF-8');
            $requestGroupId = htmlspecialchars($holdDetails['requestGroupId'], ENT_COMPAT, 'UTF-8');
            $comment = htmlspecialchars($comment, ENT_COMPAT, 'UTF-8');
            $bibId = htmlspecialchars($bibId, ENT_COMPAT, 'UTF-8');
            $localUbId = htmlspecialchars($this->ws_patronHomeUbId, ENT_COMPAT, 'UTF-8');

            // Attempt Request
            $xml =  <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<ser:serviceParameters xmlns:ser="http://www.endinfosys.com/Voyager/serviceParameters">
  <ser:parameters>
    <ser:parameter key="bibId">
      <ser:value>$bibId</ser:value>
    </ser:parameter>
    <ser:parameter key="bibDbCode">
      <ser:value>LOCAL</ser:value>
    </ser:parameter>
    <ser:parameter key="requestCode">
      <ser:value>HOLD</ser:value>
    </ser:parameter>
    <ser:parameter key="requestSiteId">
      <ser:value>$localUbId</ser:value>
    </ser:parameter>
    <ser:parameter key="CVAL">
      <ser:value>anyCopyAt</ser:value>
    </ser:parameter>
    <ser:parameter key="requestGroupId">
      <ser:value>$requestGroupId</ser:value>
    </ser:parameter>
    <ser:parameter key="PICK">
      <ser:value>$pickupLocation</ser:value>
    </ser:parameter>
    <ser:parameter key="REQNNA">
      <ser:value>$lastInterestDate</ser:value>
    </ser:parameter>
    <ser:parameter key="REQCOMMENTS">
      <ser:value>$comment</ser:value>
    </ser:parameter>
  </ser:parameters>
  <ser:patronIdentifier lastName="$lastname" patronHomeUbId="$localUbId" patronId="$patronId">
    <ser:authFactor type="B">$barcode</ser:authFactor>
  </ser:patronIdentifier>
</ser:serviceParameters>
EOT;

            $response = $this->makeRequest(array('SendPatronRequestService' => false), array(), 'POST', $xml);

            if ($response === false) {
                return $this->holdError('hold_error_system');
            }
            // Process
            $response->registerXPathNamespace('ser', 'http://www.endinfosys.com/Voyager/serviceParameters');
            $response->registerXPathNamespace('req', 'http://www.endinfosys.com/Voyager/requests');
            foreach ($response->xpath('//ser:message') as $message) {
                if ($message->attributes()->type == 'success') {
                    return array(
                        'success' => true,
                        'status' => 'hold_request_success'
                    );
                }
                if ($message->attributes()->type == 'system') {
                    return $this->holdError('hold_error_system');
                }
            }

            return $this->holdError('hold_error_blocked');
        }

        if ($this->checkItemRequests($patron['id'], $type, $bibId, $itemId)) {
            // Attempt Request
            // Build Request Data
            $requestData = array(
                'pickupLocation' => $pickUpLocation,
                'lastInterestDate' => $lastInterestDate,
                'comment' => $comment
            );

            $result = $this->makeItemRequests(
                $patron['id'], $type, $level, $requestData, $bibId, $itemId
            );
            if ($result) {
                return $result;
            }
        }
        return $this->holdError("hold_error_blocked");
    }

    /**
     * Cancel Holds
     *
     * Attempts to Cancel a hold or recall on a particular item. The
     * data in $cancelDetails['details'] is determined by getCancelHoldDetails().
     *
     * @param array $cancelDetails An array of item and patron data
     *
     * @return array               An array of data on each request including
     * whether or not it was successful and a system message (if available)
     * @access public
     */
    public function cancelHolds($cancelDetails)
    {
        $details = $cancelDetails['details'];
        $patron = $cancelDetails['patron'];
        $count = 0;
        $response = array();

        foreach ($details as $cancelDetails) {
            list($itemId, $cancelCode) = explode("|", $cancelDetails);

             // Create Rest API Cancel Key
            $cancelID = $this->ws_dbKey. "|" . $cancelCode;

            // Build Hierarchy
            $hierarchy = array(
                "patron" => $patron['id'],
                 "circulationActions" => "requests",
                 "holds" => $cancelID
            );

            // Add Required Params
            $params = array(
                "patron_homedb" => $this->ws_patronHomeUbId,
                "view" => "full"
            );

            // Get Data
            $cancel = $this->makeRequest($hierarchy, $params, "DELETE");

            if ($cancel) {

                // Process Cancel
                $cancel = $cancel->children();
                $node = "reply-text";
                $reply = (string)$cancel->$node;
                $count = ($reply == "ok") ? $count+1 : $count;

                $response[$itemId] = array(
                    'success' => ($reply == "ok") ? true : false,
                    'status' => ($reply == "ok")
                        ? "hold_cancel_success" : "hold_cancel_fail",
                    'sysMessage' => ($reply == "ok") ? false : $reply,
                );

            } else {
                $response[$itemId] = array(
                    'success' => false, 'status' => "hold_cancel_fail"
                );
            }
        }
        $result = array('count' => $count, 'items' => $response);
        return $result;
    }

    /**
     * Get Cancel Hold Details
     *
     * In order to cancel a hold, Voyager requires the patron details an item ID
     * and a recall ID. This function returns the item id and recall id as a string
     * separated by a pipe, which is then submitted as form data in Hold.php. This
     * value is then extracted by the CancelHolds function.
     *
     * @param array $holdDetails An array of item data
     *
     * @return string Data for use in a form field
     * @access public
     */
    public function getCancelHoldDetails($holdDetails)
    {
        $cancelDetails = $holdDetails['item_id']."|".$holdDetails['reqnum'];
        return $cancelDetails;
    }

    /**
     * Get Renew Details
     *
     * In order to renew an item, Voyager requires the patron details and an item
     * id. This function returns the item id as a string which is then used
     * as submitted form data in checkedOut.php. This value is then extracted by
     * the RenewMyItems function.
     *
     * @param array $checkOutDetails An array of item data
     *
     * @return string Data for use in a form field
     * @access public
     */
    public function getRenewDetails($checkOutDetails)
    {
        $renewDetails = (isset($checkOutDetails['institution_dbkey']) ? $checkOutDetails['institution_dbkey'] : '')
            . '|' . $checkOutDetails['item_id'];
        return $renewDetails;
    }

    /**
     * Get Patron Profile
     *
     * This is responsible for retrieving the profile for a specific patron.
     *
     * @param array $patron The patron array
     *
     * @return mixed        Array of the patron's profile data on success,
     * PEAR_Error otherwise.
     * @access public
     */
    public function getMyProfile($patron)
    {
        $result = parent::getMyProfile($patron);
        if ($result) {
            $result['blocks'] = $this->checkAccountBlocks($patron['id']);
        }
        return $result;
    }

    /**
     * Get Patron Transactions
     *
     * This is responsible for retrieving all transactions (i.e. checked out items)
     * by a specific patron.
     *
     * @param array $patron The patron array from patronLogin
     *
     * @return mixed        Array of the patron's transactions on success,
     * PEAR_Error otherwise.
     * @access public
     */
    public function getMyTransactions($patron)
    {
        $transactions = parent::getMyTransactions($patron);

        if (PEAR::isError($transactions)) {
            return $transactions;
        }

        // Build Hierarchy
        $hierarchy = array(
            'patron' =>  $patron['id'],
            'circulationActions' => 'loans'
        );

        // Add Required Params
        $params = array(
            "patron_homedb" => $this->ws_patronHomeUbId,
            "view" => "full"
        );

        $results = $this->makeRequest($hierarchy, $params);

        if ($results === false) {
            return new PEAR_Error('System error fetching loans');
        }

        $replyCode = (string)$results->{'reply-code'};
        if ($replyCode != 0 && $replyCode != 8) {
            return new PEAR_Error('System error fetching loans');
        }
        if (isset($results->loans->institution)) {
            foreach ($results->loans->institution as $institution) {
                foreach ($institution->loan as $loan) {

                    if ((string)$institution->attributes()->id == 'LOCAL') {
                        // Take only renewability for local loans, other information we
                        // have already
                        $renewable = (string)$loan->attributes()->canRenew == 'Y';

                        foreach ($transactions as &$transaction) {
                            if (!isset($transaction['institution_id']) && $transaction['item_id'] == (string)$loan->itemId) {
                                $transaction['renewable'] = $renewable;
                                break;
                            }
                        }
                        continue;
                    }

                    $dueStatus = false;
                    if (!empty($sqlRow['FULLDATE'])) {
                        $now = time();
                        $dueTimeStamp = strtotime((string)$loan->dueDate);
                        if (!PEAR::isError($dueTimeStamp) && is_numeric($dueTimeStamp)) {
                            if ($now > $dueTimeStamp) {
                                $dueStatus = "overdue";
                            } else if ($now > $dueTimeStamp-(1*24*60*60)) {
                                $dueStatus = "due";
                            }
                        }
                    }

                    $transactions[] = array(
                        // This is bogus, but we need something..
                        'id' => (string)$institution->attributes()->id . '.' . (string)$loan->itemId,
                        'item_id' => (string)$loan->itemId,
                        'duedate' =>  $this->dateFormat->convertToDisplayDate('Y-m-d H:i', (string)$loan->dueDate),
                        'dueTime' =>  $this->dateFormat->convertToDisplayTime('Y-m-d H:i', (string)$loan->dueDate),
                        'dueStatus' => $dueStatus,
                        'title' => (string)$loan->title,
                        'renewable' => (string)$loan->attributes()->canRenew == 'Y',
                        'institution_id' => (string)$institution->attributes()->id,
                        'institution_name' => (string)$loan->dbName,
                        'institution_dbkey' => (string)$loan->dbKey,
                    );
                }
            }
        }
        return $transactions;
    }

    /**
     * Get Patron Fines
     *
     * This is responsible for retrieving all fines by a specific patron.
     *
     * @param array $patron The patron array from patronLogin
     *
     * @return mixed        Array of the patron's fines on success, PEAR_Error
     * otherwise.
     * @access public
     */
    public function getMyFines($patron)
    {
        $fines = parent::getMyFines($patron);

        if (PEAR::isError($fines)) {
            return $fines;
        }

        // Build Hierarchy
        $hierarchy = array(
            'patron' =>  $patron['id'],
            'circulationActions' => 'debt',
            'fines' => null
        );

        // Add Required Params
        $params = array(
            "patron_homedb" => $this->ws_patronHomeUbId,
            "view" => "full"
        );

        $results = $this->makeRequest($hierarchy, $params);

        if ($results === false) {
            return new PEAR_Error('System error fetching fines');
        }

        $replyCode = (string)$results->{'reply-code'};
        if ($replyCode != 0 && $replyCode != 8) {
            return new PEAR_Error('System error fetching fines');
        }
        if (isset($results->fines->institution)) {
            foreach ($results->fines->institution as $institution) {
                if ((string)$institution->attributes()->id == 'LOCAL') {
                    // We have local fines already
                    continue;
                }

                foreach ($institution->fine as $fine) {
                    $amount = preg_match(
                        '/(\d+\.\d+)/', (string)$fine->amount, $matches
                    ) ? $matches[1] * 100 : 0;

                    $fines[] = array(
                        'fine' => (string)$fine->fineType,
                        'title' => (string)$fine->itemTitle,
                        'balance' => $amount,
                        'createdate' => $this->dateFormat->convertToDisplayDate(
                            'Y-m-d H:i', (string)$fine->fineDate
                        ),
                        'chargedate' => '',
                        'duedate' => '',
                        'id' => '',
                        'institution_id' => (string)$institution->attributes()->id,
                        'institution_name' => (string)$fine->dbName,
                        'institution_dbkey' => (string)$fine->dbKey,
                    );
                }
            }
        }
        return $fines;
    }

    /**
     * Get Patron Holds
     *
     * This is responsible for retrieving all holds by a specific patron.
     *
     * @param array $patron The patron array from patronLogin
     *
     * @return mixed        Array of the patron's holds on success, PEAR_Error
     * otherwise.
     * @access public
     */
    public function getMyHolds($patron)
    {
        $holds = parent::getMyHolds($patron);

        if (PEAR::isError($holds)) {
            return $holds;
        }

        // Build Hierarchy
        $hierarchy = array(
            'patron' =>  $patron['id'],
            'circulationActions' => 'requests',
            'holds' => false
        );

        // Add Required Params
        $params = array(
            "patron_homedb" => $this->ws_patronHomeUbId,
            "view" => "full"
        );

        $results = $this->makeRequest($hierarchy, $params);

        if ($results === false) {
            return new PEAR_Error('System error fetching holds');
        }

        $replyCode = (string)$results->{'reply-code'};
        if ($replyCode != 0 && $replyCode != 8) {
            return new PEAR_Error('System error fetching holds');
        }
        if (isset($results->holds->institution)) {
            foreach ($results->holds->institution as $institution) {
                foreach ($institution->hold as $hold) {
                    $item = $hold->requestItem;

                    // Check that this item is not already on the list retrieved from the database
                    $id = (string)$item->holdRecallId;
                    foreach ($holds as $hold) {
                        if ($hold['reqnum'] == $id) {
                            continue 2;
                        }
                    }

                    $holds[] = array(
                        'id' => '',
                        'type' => (string)$item->holdType,
                        'location' => (string)$item->pickupLocation,
                        'expired' => (string)$item->expiredDate
                            ? $this->dateFormat->convertToDisplayDate('Y-m-d', (string)$item->expiredDate)
                            : '',
                        // Looks like expired date shows creation date for UB requests, but who knows
                        'created' => (string)$item->expiredDate
                            ? $this->dateFormat->convertToDisplayDate('Y-m-d', (string)$item->expiredDate)
                            : '',
                        'position' => (string)$item->queuePosition,
                        'available' => (string)$item->status == '2',
                        'reqnum' => (string)$item->holdRecallId,
                        'item_id' => (string)$item->itemId,
                        'volume' => '',
                        'publication_year' => '',
                        'title' => (string)$item->itemTitle,
                        'institution_id' => (string)$institution->attributes()->id,
                        'institution_name' => (string)$item->dbName,
                        'institution_dbkey' => (string)$item->dbKey,
                        'in_transit' => (substr((string)$item->statusText, 0, 13) == 'In transit to')
                          ? substr((string)$item->statusText, 14)
                          : ''
                    );
                }
            }
        }
        return $holds;
    }

    /**
     * Get Patron Call Slips. Gets local call slips from the database,
     * then remote callslips via the API.
     *
     * This is responsible for retrieving all call slips by a specific patron.
     *
     * @param array $patron The patron array from patronLogin
     *
     * @return mixed        Array of the patron's holds on success, PEAR_Error
     * otherwise.
     * @access public
     */
    public function getMyCallSlips($patron)
    {
        $callslips = parent::getMyCallSlips($patron);

        if (PEAR::isError($callslips)) {
            return $callslips;
        }

        // Build Hierarchy
        $hierarchy = array(
            'patron' =>  $patron['id'],
            'circulationActions' => 'requests',
            'callslips' => false
        );

        // Add Required Params
        $params = array(
            "patron_homedb" => $this->ws_patronHomeUbId,
            "view" => "full"
        );

        $results = $this->makeRequest($hierarchy, $params);

        if ($results === false) {
            return new PEAR_Error('System error fetching call slips');
        }

        $replyCode = (string)$results->{'reply-code'};
        if ($replyCode != 0 && $replyCode != 8) {
            return new PEAR_Error('System error fetching call slips');
        }
        if (isset($results->callslips->institution)) {
            foreach ($results->callslips->institution as $institution) {
                if ((string)$institution->attributes()->id == 'LOCAL') {
                    // Ignore local callslips, we have them already
                    continue;
                }
                foreach ($institution->callslip as $callslip) {
                    $item = $callslip->requestItem;
                    $callslips[] = array(
                        'id' => '',
                        'type' => (string)$item->holdType,
                        'location' => (string)$item->pickupLocation,
                        'expired' => (string)$item->expiredDate
                            ? $this->dateFormat->convertToDisplayDate('Y-m-d', (string)$item->expiredDate)
                            : '',
                        // Looks like expired date shows creation date for UB requests, but who knows
                        'created' => (string)$item->expiredDate
                            ? $this->dateFormat->convertToDisplayDate('Y-m-d', (string)$item->expiredDate)
                            : '',
                        'position' => (string)$item->queuePosition,
                        'available' => (string)$item->status == '4',
                        'reqnum' => (string)$item->holdRecallId,
                        'item_id' => (string)$item->itemId,
                        'volume' => '',
                        'publication_year' => '',
                        'title' => (string)$item->itemTitle,
                        'institution_id' => (string)$institution->attributes()->id,
                        'institution_name' => (string)$item->dbName,
                        'institution_dbkey' => (string)$item->dbKey,
                        'processed' => (substr((string)$item->statusText, 0, 6) == 'Filled')
                          ? $this->dateFormat->convertToDisplayDate('Y-m-d', substr((string)$item->statusText, 7))
                          : ''
                    );
                }
            }
        }
        return $callslips;
    }

    /**
     * Place Call Slip Request
     *
     * Attempts to place a call slip request on a particular item and returns
     * an array with result details or a PEAR error on failure of support classes
     *
     * @param array $details An array of item and patron data
     *
     * @return mixed An array of data on the request including
     * whether or not it was successful and a system message (if available) or a
     * PEAR error on failure of support classes
     * @access public
     */
    public function placeCallSlipRequest($details)
    {
        $patron = $details['patron'];
        $level = isset($details['level']) && !empty($details['level'])
            ? $details['level'] : 'copy';
        $pickUpLocation = !empty($details['pickUpLocation'])
            ? $details['pickUpLocation'] : false;
        $itemId = isset($details['item_id']) ? $details['item_id'] : false;
        $mfhdId = isset($details['mfhd_id']) ? $details['mfhd_id'] : false;
        $comment = $details['comment'];
        $bibId = $details['id'];

        // Make Sure Pick Up Library is Valid
        if ($pickUpLocation !== false) {
            $pickUpValid = false;
            $pickUpLibs = $this->getPickUpLocations($patron, $holdDetails);
            foreach ($pickUpLibs as $location) {
                if ($location['locationID'] == $pickUpLocation) {
                    $pickUpValid = true;
                }
            }
            if (!$pickUpValid) {
                // Invalid Pick Up Point
                return $this->holdError("call_slip_invalid_pickup");
            }
        }

        // Attempt Request
        $hierarchy = array();

        // Build Hierarchy
        $hierarchy['record'] = $bibId;

        if ($itemId && $level != 'title') {
            $hierarchy['items'] = $itemId;
        }

        $hierarchy['callslip'] = false;

        // Add Required Params
        $params = array(
            'patron' => $patron['id'],
            'patron_homedb' => $this->ws_patronHomeUbId,
            'view' => 'full'
        );

        if ('title' == $level) {
            $xml['call-slip-title-parameters'] = array(
                'comment' => $comment,
                'reqinput field="1"' => $details['volume'],
                'reqinput field="2"' => $details['issue'],
                'reqinput field="3"' => $details['year'],
                'dbkey' => $this->ws_dbKey,
                'mfhdId' => $mfhdId
            );
            if ($pickUpLocation !== false) {
                $xml['call-slip-title-parameters']['pickup-location']
                    = $pickUpLocation;
            }
        } else {
            $xml['call-slip-parameters'] = array(
                'comment' => $comment,
                'dbkey' => $this->ws_dbKey,
            );
            if ($pickUpLocation !== false) {
                $xml['call-slip-parameters']['pickup-location']
                    = $pickUpLocation;
            }
        }

        // Generate XML
        $requestXML = $this->buildBasicXML($xml);

        // Get Data
        $result = $this->makeRequest($hierarchy, $params, "PUT", $requestXML);

        if ($result) {
            // Process
            $result = $result->children();
            $reply = (string)$result->{'reply-text'};

            $responseNode = 'title' == $level ? 'create-call-slip-title' : 'create-call-slip';
            $note = (isset($result->$responseNode))
                ? trim((string)$result->$responseNode->note) : false;

            // Valid Response
            if ($reply == "ok" && $note == "Your request was successful.") {
                $response['success'] = true;
                $response['status'] = "call_slip_success";
            } else {
                // Failed
                $response['sysMessage'] = $note;
            }
            return $response;
        }

        return $this->holdError('call_slip_error_blocked');
    }

    /**
     * Cancel Call Slips
     *
     * Attempts to Cancel a call slip on a particular item. The
     * data in $cancelDetails['details'] is determined by getCancelCallSlipDetails().
     *
     * @param array $cancelDetails An array of item and patron data
     *
     * @return array               An array of data on each request including
     * whether or not it was successful and a system message (if available)
     * @access public
     */
    public function cancelCallSlips($cancelDetails)
    {
        $details = $cancelDetails['details'];
        $patron = $cancelDetails['patron'];
        $count = 0;
        $response = array();

        foreach ($details as $cancelDetails) {
            list($dbKey, $itemId, $cancelCode) = explode("|", $cancelDetails);

             // Create Rest API Cancel Key
            $cancelID = ($dbKey ? $dbKey : $this->ws_dbKey) . "|" . $cancelCode;

            // Build Hierarchy
            $hierarchy = array(
                "patron" => $patron['id'],
                 "circulationActions" => 'requests',
                 "callslips" => $cancelID
            );

            // Add Required Params
            $params = array(
                "patron_homedb" => $this->ws_patronHomeUbId,
                "view" => "full"
            );

            // Get Data
            $cancel = $this->makeRequest($hierarchy, $params, "DELETE");

            if ($cancel) {

                // Process Cancel
                $cancel = $cancel->children();
                $node = "reply-text";
                $reply = (string)$cancel->$node;
                $count = ($reply == "ok") ? $count+1 : $count;

                $response[$itemId] = array(
                    'success' => ($reply == "ok") ? true : false,
                    'status' => ($reply == "ok")
                        ? "call_slip_cancel_success" : "call_slip_cancel_fail",
                    'sysMessage' => ($reply == "ok") ? false : $reply,
                );

            } else {
                $response[$itemId] = array(
                    'success' => false, 'status' => "call_slip_cancel_fail"
                );
            }
        }
        $result = array('count' => $count, 'items' => $response);
        return $result;
    }

    /**
     * Get Cancel Call Slip Details
     *
     * In order to cancel a call slip, Voyager requires the patron details an item ID
     * and a recall ID. This function returns the item id and call slip id as a string
     * separated by a pipe, which is then submitted as form data in CallSlip.php. This
     * value is then extracted by the CancelCallSlips function.
     *
     * @param array $details An array of item data
     *
     * @return string Data for use in a form field
     * @access public
     */
    public function getCancelCallSlipDetails($details)
    {
        $details = (isset($details['institution_dbkey']) ? $details['institution_dbkey'] : '')
            . '|' . $details['item_id']
            . '|' . $details['reqnum'];
        return $details;
    }

    /**
     * Change Password
     *
     * Attempts to change patron password (PIN code)
     *
     * @param array $details An array of patron id and old and new password:
     *
     * 'patron'      The patron array from patronLogin
     * 'oldPassword' Old password
     * 'newPassword' New password
     *
     * @return mixed An array of data on the request including
     * whether or not it was successful and a system message (if available) or a
     * PEAR error on failure of support classes
     * @access public
     */
    public function changePassword($details)
    {
        $patron = $details['patron'];
        $id = htmlspecialchars($patron['id'], ENT_COMPAT, 'UTF-8');
        $lastname = htmlspecialchars($patron['lastname'], ENT_COMPAT, 'UTF-8');
        $ubId = htmlspecialchars($this->ws_patronHomeUbId, ENT_COMPAT, 'UTF-8');
        $oldPIN = trim(
            htmlspecialchars(
                $this->sanitizePIN($details['oldPassword']), ENT_COMPAT, 'UTF-8'
            )
        );
        if ($oldPIN === '') {
            // Voyager requires the PIN code to be set even
            $oldPIN = '     ';
        }
        $newPIN = trim(
            htmlspecialchars(
                $this->sanitizePIN($details['newPassword']), ENT_COMPAT, 'UTF-8'
            )
        );
        $barcode = htmlspecialchars($patron['cat_username'], ENT_COMPAT, 'UTF-8');

        $xml =  <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<ser:serviceParameters xmlns:ser="http://www.endinfosys.com/Voyager/serviceParameters">
   <ser:parameters>
      <ser:parameter key="oldPatronPIN">
         <ser:value>$oldPIN</ser:value>
      </ser:parameter>
      <ser:parameter key="newPatronPIN">
         <ser:value>$newPIN</ser:value>
      </ser:parameter>
   </ser:parameters>
   <ser:patronIdentifier lastName="$lastname" patronHomeUbId="$ubId" patronId="$id">
      <ser:authFactor type="B">$barcode</ser:authFactor>
   </ser:patronIdentifier>
</ser:serviceParameters>
EOT;

        $result = $this->makeRequest(array('ChangePINService' => false), array(), 'POST', $xml);

        $result->registerXPathNamespace('ser', 'http://www.endinfosys.com/Voyager/serviceParameters');
        $error = $result->xpath("//ser:message[@type='error']");
        if (!empty($error)) {
            $error = reset($error);
            if ($error->attributes()->errorCode == 'com.endinfosys.voyager.patronpin.PatronPIN.ValidateException') {
                return array('success' => false, 'status' => 'change_password_error_old_wrong');
            }
            if ($error->attributes()->errorCode == 'com.endinfosys.voyager.patronpin.PatronPIN.ValidateUniqueException') {
                return array('success' => false, 'status' => 'change_password_error_code_not_unique');
            }
            if ($error->attributes()->errorCode == 'com.endinfosys.voyager.patronpin.PatronPIN.ValidateLengthException') {
                return array('success' => false, 'status' => 'change_password_error_invalid_length');
            }
            return new PEAR_Error((string)$error);
        }
        return array('success' => true, 'status' => 'change_password_ok');
    }

    /**
     * Get UB Request Details
     *
     * This is responsible for getting information on whether a UB request
     * can be made and the possible pickup locations
     *
     * @param array $details BIB, item and patron information
     *
     * @return bool|array False if request not allowed, or an array of associative
     * arrays with items, libraries, default library locations and default required by date.
     * @access public
     */
    public function getUBRequestDetails($details)
    {
        $patron = $details['patron'];
        if (strstr($patron['id'], '.') === false) {
            return false;
        }
        list($source, $patronId) = explode('.', $patron['id'], 2);
        if (!isset($this->config['UBRequestSources'][$source])) {
            return $this->holdError('ub_request_unknown_patron_source');
        }

        list($catSource, $catUsername) = explode('.', $patron['cat_username'], 2);
        $patronId = htmlspecialchars($patronId, ENT_COMPAT, 'UTF-8');
        $patronHomeUbId = htmlspecialchars($this->config['UBRequestSources'][$source], ENT_COMPAT, 'UTF-8');
        $lastname = htmlspecialchars($patron['lastname'], ENT_COMPAT, 'UTF-8');
        $ubId = htmlspecialchars($patronHomeUbId, ENT_COMPAT, 'UTF-8');
        $barcode = htmlspecialchars($catUsername, ENT_COMPAT, 'UTF-8');
        $bibId = htmlspecialchars($details['id'], ENT_COMPAT, 'UTF-8');
        $bibDbName = htmlspecialchars($this->config['Catalog']['database'], ENT_COMPAT, 'UTF-8');
        $localUbId = htmlspecialchars($this->ws_patronHomeUbId, ENT_COMPAT, 'UTF-8');

        // Call PatronRequestsService first to check that UB is an available request type.
        // Additionally, this seems to be mandatory, as PatronRequestService may fail otherwise.
        $xml = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<ser:serviceParameters xmlns:ser="http://www.endinfosys.com/Voyager/serviceParameters">
  <ser:parameters>
    <ser:parameter key="bibId">
      <ser:value>$bibId</ser:value>
    </ser:parameter>
    <ser:parameter key="bibDbCode">
      <ser:value>LOCAL</ser:value>
    </ser:parameter>
  </ser:parameters>
  <ser:patronIdentifier lastName="$lastname" patronHomeUbId="$ubId" patronId="$patronId">
    <ser:authFactor type="B">$barcode</ser:authFactor>
  </ser:patronIdentifier>
</ser:serviceParameters>
EOT;

        $response = $this->makeRequest(array('PatronRequestsService' => false), array(), 'POST', $xml);

        if ($response === false) {
            return false;
        }
        // Process
        $response->registerXPathNamespace('ser', 'http://www.endinfosys.com/Voyager/serviceParameters');
        $response->registerXPathNamespace('req', 'http://www.endinfosys.com/Voyager/requests');
        foreach ($response->xpath('//ser:message') as $message) {
            // Any message means a problem, right?
            return false;
        }
        if (count($response->xpath("//req:requestIdentifier[@requestCode='UB']")) == 0) {
            // UB request not available
            return false;
        }

        $xml =  <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<ser:serviceParameters xmlns:ser="http://www.endinfosys.com/Voyager/serviceParameters">
  <ser:parameters>
    <ser:parameter key="bibId">
      <ser:value>$bibId</ser:value>
    </ser:parameter>
    <ser:parameter key="bibDbCode">
      <ser:value>LOCAL</ser:value>
    </ser:parameter>
    <ser:parameter key="bibDbName">
      <ser:value>$bibDbName</ser:value>
    </ser:parameter>
    <ser:parameter key="requestCode">
      <ser:value>UB</ser:value>
    </ser:parameter>
    <ser:parameter key="requestSiteId">
      <ser:value>$localUbId</ser:value>
    </ser:parameter>
  </ser:parameters>
  <ser:patronIdentifier lastName="$lastname" patronHomeUbId="$ubId" patronId="$patronId">
    <ser:authFactor type="B">$barcode</ser:authFactor>
  </ser:patronIdentifier>
</ser:serviceParameters>
EOT;

        $response = $this->makeRequest(array('PatronRequestService' => false), array(), 'POST', $xml);

        if ($response === false) {
            return false;
        }
        // Process
        $response->registerXPathNamespace('ser', 'http://www.endinfosys.com/Voyager/serviceParameters');
        $response->registerXPathNamespace('req', 'http://www.endinfosys.com/Voyager/requests');
        foreach ($response->xpath('//ser:message') as $message) {
            // Any message means a problem, right?
            return false;
        }
        $items = array();
        $libraries = array();
        $locations = array();
        $requiredByDate = '';
        foreach ($response->xpath('//req:field') as $field) {
            switch ($field->attributes()->labelKey) {
            case 'selectItem':
                foreach ($field->xpath('./req:select/req:option') as $option) {
                    $items[] = array(
                        'id' => (string)$option->attributes()->id,
                        'name' => (string)$option
                    );
                }
                break;
            case 'pickupLib':
                foreach ($field->xpath('./req:select/req:option') as $option) {
                    $libraries[] = array(
                        'id' => (string)$option->attributes()->id,
                        'name' => (string)$option,
                        'isDefault' => $option->attributes()->isDefault == 'Y'
                    );
                }
                break;
            case 'pickUpAt':
                foreach ($field->xpath('./req:select/req:option') as $option) {
                    $locations[] = array(
                        'id' => (string)$option->attributes()->id,
                        'name' => (string)$option,
                        'isDefault' => $option->attributes()->isDefault == 'Y'
                    );
                }
                break;
            case 'notNeededAfter':
                $node = current($field->xpath('./req:text'));
                $requiredByDate = $this->dateFormat->convertToDisplayDate(
                    "Y-m-d H:i", (string)$node
                );
                break;
            }
        }

        $libraries = $this->filterAllowedUBPickupLibraries($libraries);

        return array(
            'items' => $items,
            'libraries' => $libraries,
            'locations' => $locations,
            'requiredBy' => $requiredByDate
        );
    }

    /**
     * Get UB Pickup Locations
     *
     * This is responsible for getting a list of possible pickup locations for a library
     *
     * @param array $details BIB, item, pickupLib and patron information
     *
     * @return boo|array False if request not allowed, or an array of
     * locations.
     * @access public
     */
    public function getUBPickupLocations($details)
    {
        $patron = $details['patron'];
        list($source, $patronId) = explode('.', $patron['id'], 2);
        if (!isset($this->config['UBRequestSources'][$source])) {
            return $this->holdError('ub_request_unknown_patron_source');
        }

        list($catSource, $catUsername) = explode('.', $patron['cat_username'], 2);
        $patronId = htmlspecialchars($patronId, ENT_COMPAT, 'UTF-8');
        $patronHomeUbId = htmlspecialchars($this->config['UBRequestSources'][$source], ENT_COMPAT, 'UTF-8');
        $lastname = htmlspecialchars($patron['lastname'], ENT_COMPAT, 'UTF-8');
        $ubId = htmlspecialchars($patronHomeUbId, ENT_COMPAT, 'UTF-8');
        $barcode = htmlspecialchars($catUsername, ENT_COMPAT, 'UTF-8');
        $localUbId = htmlspecialchars($this->ws_patronHomeUbId, ENT_COMPAT, 'UTF-8');
        $pickupLib = htmlspecialchars($details['pickupLibrary'], ENT_COMPAT, 'UTF-8');

        $xml =  <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<ser:serviceParameters xmlns:ser="http://www.endinfosys.com/Voyager/serviceParameters">
  <ser:parameters>
    <ser:parameter key="pickupLibId">
      <ser:value>$pickupLib</ser:value>
    </ser:parameter>
  </ser:parameters>
  <ser:patronIdentifier lastName="$lastname" patronHomeUbId="$ubId" patronId="$patronId">
    <ser:authFactor type="B">$barcode</ser:authFactor>
  </ser:patronIdentifier>
</ser:serviceParameters>
EOT;

        $response = $this->makeRequest(array('UBPickupLibService' => false), array(), 'POST', $xml);

        if ($response === false) {
            return new PEAR_Error('ub_request_error_technical');
        }
        // Process
        $response->registerXPathNamespace('ser', 'http://www.endinfosys.com/Voyager/serviceParameters');
        $response->registerXPathNamespace('req', 'http://www.endinfosys.com/Voyager/requests');
        foreach ($response->xpath('//ser:message') as $message) {
            // Any message means a problem, right?
            return new PEAR_Error('ub_request_error_technical');
        }
        $locations = array();
        foreach ($response->xpath('//req:location') as $location) {
            $locations[] = array(
                'id' => (string)$location->attributes()->id,
                'name' => (string)$location,
                'isDefault' => $location->attributes()->isDefault == 'Y'
            );
        }

        $locations = $this->filterAllowedUBPickupLocations($locations);

        return $locations;
    }

    /**
     * Utility function for filtering the given UB pickup libraries
     * based on allowed pickup locations within the users local library.
     * If allowed pickup locations are configured, only users local library
     * is returned. If not, no filtering is done.
     *
     * @param array $libraries array of libraries
     *
     * @return boo|array False if request not allowed, or an array of
     * allowed pickup libraries.
     * @access protected
     */
    protected function filterAllowedUBPickupLibraries($libraries)
    {
        if (!$allowedIDs = $this->getAllowedUBPickupLocationIDs()) {
            return $libraries;
        }

        if (!$patronHomeUBID = $this->getUserHomeUBID()) {
            return false;
        }

        $allowedLibraries = array();
        foreach ($libraries as $library) {
            if ($patronHomeUBID === $library['id']) {
                $allowedLibraries[] = $library;
            }
        }

        return $allowedLibraries;
    }

    /**
     * Utility function for filtering the given UB locations
     * based on allowed pickup locations within the users local library.
     * If allowed pickup locations are not configured, no filtering is done.
     *
     * @param array $locations array of locations
     *
     * @return array array of allowed pickup locations.
     * @access protected
     */
    protected function filterAllowedUBPickupLocations($locations)
    {
        if (!$allowedIDs = $this->getAllowedUBPickupLocationIDs()) {
            return $locations;
        }

        $allowedLocations = array();
        foreach ($locations as $location) {
            if (in_array($location['id'], $allowedIDs)) {
                $allowedLocations[] = $location;
            }
        }

        return $allowedLocations;
    }

    /**
     * Return users local library UB id.
     *
     * @return boo|string False if request not allowed, or UB id
     * @access protected
     */
    protected function getUserHomeUBID()
    {
        if (!$config = $this->getUserDriverConfig()) {
            return false;
        }

        if (!isset($config['WebServices']['patronHomeUbId'])) {
            return false;
        }

        return $config['WebServices']['patronHomeUbId'];
    }

    /**
     * Return list of allowed UB pickup locations
     * within the users home local library.
     *
     * @return boo|array False if allowed pickup locations are
     * not configured, or array of location codes
     * @access protected
     */
    protected function getAllowedUBPickupLocationIDs()
    {
        if (!$config = $this->getUserDriverConfig()) {
            return false;
        }

        if (!isset($config['UBRequests']['pickUpLocations'])) {
            return false;
        }

        return explode(':', $config['UBRequests']['pickUpLocations']);
    }

    /**
     * Return configuration for the users active library card driver.
     *
     * @return boo|array False if no driver configuration was found,
     * or configuration.
     * @access protected
     */
    protected function getUserDriverConfig()
    {
        global $user;

        $tmp = (array)$user;
        if (($pos = strpos($tmp['cat_username'], '.')) > 0) {
            $source = substr($tmp['cat_username'], 0, $pos);

            $configFile = "conf/VoyagerRestful_$source.ini";
            if (is_file($configFile)) {
                return parse_ini_file($configFile, true);
            }
        }

        return false;
    }

    /**
     * Place UB Request
     *
     * Attempts to place an UB request on a particular item and returns
     * an array with result details or a PEAR error on failure of support classes
     *
     * @param array $details An array of item and patron data
     *
     * @return mixed An array of data on the request including
     * whether or not it was successful and a system message (if available) or a
     * PEAR error on failure of support classes
     * @access public
     */
    public function placeUBRequest($details)
    {
        $patron = $details['patron'];
        list($source, $patronId) = explode('.', $patron['id'], 2);
        if (!isset($this->config['UBRequestSources'][$source])) {
            return $this->holdError('ub_request_unknown_patron_source');
        }

        list($catSource, $catUsername) = explode('.', $patron['cat_username'], 2);
        $patronId = htmlspecialchars($patronId, ENT_COMPAT, 'UTF-8');
        $patronHomeUbId = htmlspecialchars($this->config['UBRequestSources'][$source], ENT_COMPAT, 'UTF-8');
        $lastname = htmlspecialchars($patron['lastname'], ENT_COMPAT, 'UTF-8');
        $ubId = htmlspecialchars($patronHomeUbId, ENT_COMPAT, 'UTF-8');
        $barcode = htmlspecialchars($catUsername, ENT_COMPAT, 'UTF-8');
        $pickupLocation = htmlspecialchars($details['pickupLocation'], ENT_COMPAT, 'UTF-8');
        $pickupLibrary = htmlspecialchars($details['pickupLibrary'], ENT_COMPAT, 'UTF-8');
        $itemId = htmlspecialchars($details['itemId'], ENT_COMPAT, 'UTF-8');
        $comment = htmlspecialchars($details['comment'], ENT_COMPAT, 'UTF-8');
        $bibId = htmlspecialchars($details['id'], ENT_COMPAT, 'UTF-8');
        $bibDbName = htmlspecialchars(strtolower($this->config['Catalog']['database']), ENT_COMPAT, 'UTF-8');
        $localUbId = htmlspecialchars($this->ws_patronHomeUbId, ENT_COMPAT, 'UTF-8');

        // Convert last interest date from Display Format to Voyager required format
        $lastInterestDate = $this->dateFormat->convertFromDisplayDate(
            "Y-m-d", $details['requiredBy']
        );
        if (PEAR::isError($lastInterestDate)) {
            // Hold Date is invalid
            return $this->holdError("ub_request_date_invalid");
        }

        // Attempt Request
        $xml =  <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<ser:serviceParameters xmlns:ser="http://www.endinfosys.com/Voyager/serviceParameters">
  <ser:parameters>
    <ser:parameter key="bibId">
      <ser:value>$bibId</ser:value>
    </ser:parameter>
    <ser:parameter key="bibDbCode">
      <ser:value>LOCAL</ser:value>
    </ser:parameter>
    <ser:parameter key="bibDbName">
      <ser:value>$bibDbName</ser:value>
    </ser:parameter>
    <ser:parameter key="Select_Library">
      <ser:value>$localUbId</ser:value>
    </ser:parameter>
    <ser:parameter key="requestCode">
      <ser:value>UB</ser:value>
    </ser:parameter>
    <ser:parameter key="requestSiteId">
      <ser:value>$localUbId</ser:value>
    </ser:parameter>
    <ser:parameter key="itemId">
      <ser:value>$itemId</ser:value>
    </ser:parameter>
    <ser:parameter key="Select_Pickup_Lib">
      <ser:value>$pickupLibrary</ser:value>
    </ser:parameter>
    <ser:parameter key="PICK">
      <ser:value>$pickupLocation</ser:value>
    </ser:parameter>
    <ser:parameter key="REQNNA">
      <ser:value>$lastInterestDate</ser:value>
    </ser:parameter>
    <ser:parameter key="REQCOMMENTS">
      <ser:value>$comment</ser:value>
    </ser:parameter>
  </ser:parameters>
  <ser:patronIdentifier lastName="$lastname" patronHomeUbId="$ubId" patronId="$patronId">
    <ser:authFactor type="B">$barcode</ser:authFactor>
  </ser:patronIdentifier>
</ser:serviceParameters>
EOT;

        $response = $this->makeRequest(array('SendPatronRequestService' => false), array(), 'POST', $xml);

        if ($response === false) {
            return $this->holdError('ub_request_error_system');
        }
        // Process
        $response->registerXPathNamespace('ser', 'http://www.endinfosys.com/Voyager/serviceParameters');
        $response->registerXPathNamespace('req', 'http://www.endinfosys.com/Voyager/requests');
        foreach ($response->xpath('//ser:message') as $message) {
            if ($message->attributes()->type == 'success') {
                return array(
                    'success' => true,
                    'status' => 'ub_request_success'
                );
            }
            if ($message->attributes()->type == 'system') {
                return $this->holdError('ub_request_error_system');
            }
        }

        return $this->holdError('ub_request_error_blocked');
    }

}

?>

<?php
/**
 * Axiell Web Services Driver.
 *
 * PHP version 5
 *
 * Copyright (C) The National Library of Finland 2011.
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
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_an_ils_driver Wiki
 */
require_once 'Interface.php';
require_once 'sys/VuFindDate.php';


/**
 * Axiell Web Services Driver.
 *
 * @category VuFind
 * @package  ILS_Drivers
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_an_ils_driver Wiki

 // TODO: language-dependent stuff

 */
class AxiellWebServices implements DriverInterface
{
    protected $arenaMember = '';
    protected $catalogue_wsdl = '';
    protected $patron_wsdl = '';
    protected $loans_wsdl = '';
    protected $payments_wsdl = '';
    protected $reservations_wsdl = '';
    protected $dateFormat;
    protected $logFile = '';
    protected $durationLogPrefix = '';
    protected $verbose = false;

    protected $soapOptions = array(
        'soap_version' => SOAP_1_1,
        'exceptions' => true,
        'trace' => 1,
        'connection_timeout' => 60,
        'typemap' => array(
            array(
                'type_ns' => 'http://www.w3.org/2001/XMLSchema',
                'type_name' => 'anySimpleType',
                'from_xml' => array('\AxiellWebServices', 'anySimpleTypeToString'),
                'to_xml' => array('\AxiellWebServices', 'stringToAnySimpleType')
        )
        )
    );

    /**
     * Constructor
     *
     * @param string $configFile Configuration file
     *
     * @access public
     */
    public function __construct($configFile = 'AxiellWebServices.ini')
    {
        // Load Configuration for this Module
        $this->config = parse_ini_file('conf/'.$configFile, true);

        $this->arenaMember = $this->config['Catalog']['arena_member'];
        $this->catalogue_wsdl = 'conf/' . $this->config['Catalog']['catalogue_wsdl'];
        $this->patron_wsdl = 'conf/' . $this->config['Catalog']['patron_wsdl'];
        $this->loans_wsdl = 'conf/' . $this->config['Catalog']['loans_wsdl'];
        $this->payments_wsdl = 'conf/' . $this->config['Catalog']['payments_wsdl'];
        $this->reservations_wsdl = 'conf/' . $this->config['Catalog']['reservations_wsdl'];

        $this->defaultPickUpLocation
            = (isset($this->config['Holds']['defaultPickUpLocation']))
            ? $this->config['Holds']['defaultPickUpLocation'] : false;
        if ($this->defaultPickUpLocation == '0') {
            $this->defaultPickUpLocation = false;
        }

        if (isset($this->config['Debug']['durationLogPrefix'])) {
            $this->durationLogPrefix = $this->config['Debug']['durationLogPrefix'];
        }

        if (isset($this->config['Debug']['verbose'])) {
            $this->verbose = $this->config['Debug']['verbose'];
        }

        if (isset($this->config['Debug']['log'])) {
            $this->logFile = $this->config['Debug']['log'];
        }

        // Set up object for formatting dates and times:
        $this->dateFormat = new VuFindDate();
    }

    /**
     * Get Status
     *
     * This is responsible for retrieving the status information of a certain
     * record.
     *
     * @param string $id The record id to retrieve the holdings for
     *
     * @return mixed     On success, an associative array with the following keys:
     * id, availability (boolean), status, location, reserve, callnumber; on
     * failure, a PEAR_Error.
     * @access public
     */
    public function getStatus($id)
    {
        return $this->getHolding($id);
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
        // There's currently not much that can be checked here with AWS.
        return true;
    }

    /**
     * Get Statuses
     *
     * This is responsible for retrieving the status information for a
     * collection of records.
     *
     * @param array $ids The array of record ids to retrieve the status for
     *
     * @return mixed     An array of getStatus() return values on success,
     * a PEAR_Error object otherwise.
     * @access public
     */
    public function getStatuses($ids)
    {
        $items = array();
        foreach ($ids as $id) {
            $items[] = $this->getHolding($id);
        }
        return $items;
    }


    /**
     * Get Holding
     *
     * This is responsible for retrieving the holding information of a certain
     * record.
     *
     * @param string $id     The record id to retrieve the holdings for
     * @param object $patron User, if logged in
     *
     * @return mixed     On success, an associative array with the following keys:
     * id, availability (boolean), status, location, reserve, callnumber, duedate,
     * number, barcode; on failure, a PEAR_Error.
     * @access public
     */

    public function getHolding($id, $patron = false)
    {
        $functionResult = 'GetHoldingResult';
        $result = $this->doSOAPRequest($this->catalogue_wsdl, 'GetHoldings', $functionResult, $id, array('GetHoldingsRequest' => array('arenaMember' => $this->arenaMember, 'id' => $id, 'language' => $this->getLanguage())));

        if (PEAR::isError($result)) {
            return $result;
        }

        if (!isset($result->$functionResult->catalogueRecord->compositeHolding)) {
            return array();
        }

        $holdings = is_object($result->$functionResult->catalogueRecord->compositeHolding)
            ? array($result->$functionResult->catalogueRecord->compositeHolding)
            : $result->$functionResult->catalogueRecord->compositeHolding;

        $vfHoldings = array();
        if (isset($holdings[0]->type) && $holdings[0]->type == 'year') {
            foreach ($holdings as $holding) {
                $year = $holding->value;
                $holdingsEditions = is_object($holding->compositeHolding)
                    ? array($holding->compositeHolding)
                    : $holding->compositeHolding;
                foreach ($holdingsEditions as $holdingsEdition) {
                    $edition = $holdingsEdition->value;
                    $holdingsOrganisations = is_object($holdingsEdition->compositeHolding)
                        ? array($holdingsEdition->compositeHolding)
                        : $holdingsEdition->compositeHolding;
                    $this->parseHoldings($holdingsOrganisations, $id, $vfHoldings, $year, $edition);
                }
            }
        } else {
            $this->parseHoldings($holdings, $id, $vfHoldings, '', '');
        }
        // Sort holdings
        foreach ($vfHoldings as $key => $location) {
            $branches = array();
            if (isset($location['holdings']) && is_array($location['holdings'])) {
                foreach ($location['holdings'] as $i => $holding) {
                    $branches[$i] = $holding['branch'];
                }
                array_multisort($branches, SORT_ASC, $vfHoldings[$key]['holdings']);
            }
        }
        return empty($vfHoldings) ? false : $vfHoldings;
    }

    /**
     * This is responsible for iterating the organisation holdings
     *
     * @param array     $organisationHoldings Organisation holdings
     * @param string    $id                   The record id to retrieve the holdings
     * @param reference &$vfHoldings          Reference
     * @param string    $year                 Publication year of the magazine
     * @param string    $edition              Edition of the magazine
     *
     * @return null
     * @access protected
     */
    protected function parseHoldings($organisationHoldings, $id, &$vfHoldings, $year, $edition)
    {
        if ($organisationHoldings[0]->status == 'noHolding') {
            return;
        }
        if ($organisationHoldings[0]->type != 'organisation') {
            return;
        }
        foreach ($organisationHoldings as $organisation) {
            $group = $organisation->value;
            $holdingsBranch = is_object($organisation->compositeHolding) ?
                array($organisation->compositeHolding) :
                $organisation->compositeHolding;
            if ($holdingsBranch[0]->type == 'branch') {
                foreach ($holdingsBranch as $branch) {
                    $branchName = $branch->value;
                    $reservableId = isset($branch->reservable)
                        ? $branch->reservable : '';
                    // This holding is only holdable if it has a reservable id
                    // different from the record id
                    $holdable = $branch->reservationButtonStatus == 'reservationOk'
                        && $reservableId != $id;
                    $departments = is_object($branch->holdings->holding)
                        ? array($branch->holdings->holding)
                        : $branch->holdings->holding;

                    foreach ($departments as $department) {
                        // Get holding data
                        $dueDate = isset($department->firstLoanDueDate)
                            ? $this->dateFormat->convertToDisplayDate(
                                '* M d G:i:s e Y',
                                $department->firstLoanDueDate
                            ) : '';
                        $departmentName = $department->department;
                        $locationName = isset($department->location)
                            ? $department->location : '';
                        $nofAvailableForLoan
                            = isset($department->nofAvailableForLoan)
                            ? $department->nofAvailableForLoan : 0;
                        $nofTotal = isset($department->nofTotal)
                            ? $department->nofTotal : 0;
                        $nofOrdered = isset($department->nofOrdered)
                            ? $department->nofOrdered : 0;

                        // Group journals by issue number
                        $journal = false;
                        if ($year !== '' || $edition !== '') {
                            if ($year !== '' && $edition !== '') {
                                if (strncmp($year, $edition, strlen($year)) == 0) {
                                    $group = $edition;
                                } else {
                                    $group = "$year, $edition";
                                }
                            } else {
                                $group = $year . $edition;
                            }
                            $journal = true;
                        }

                        // Status & availability
                        $status = $department->status;
                        $available = false;
                        if ($status == 'availableForLoan'
                            || $status == 'returnedToday'
                        ) {
                            $available = true;
                        }

                        // Special status: On reference desk
                        if ($status == 'nonAvailableForLoan'
                            && isset($department->nofReference)
                            && $department->nofReference == 0
                        ) {
                            $status = 'onRefDesk';
                        }

                        // Status table
                        $statusArray = array(
                            'availableForLoan' => 'Available',
                            'onLoan' => 'Charged',
                            //'nonAvailableForLoan' => 'Not Available',
                            'nonAvailableForLoan' => 'On Reference Desk',
                            'onRefDesk' => 'On Reference Desk',
                            'overdueLoan' => 'overdueLoan',
                            'ordered' => 'Ordered',
                            'returnedToday' => 'returnedToday'
                        );

                        // Convert status text
                        if (isset($statusArray[$status])) {
                            $status = $statusArray[$status];
                        } else {
                            $this->debugLog(
                                'Unhandled status ' +
                                $department->status + " for $id"
                            );
                        }

                        // Holding table
                        $holding = array(
                            'availability' => $available,
                            'available'    => $nofAvailableForLoan,
                            'organisation' => $organisation->value,
                            'branch'       => $branchName,
                            'department'   => $departmentName,
                            'duedate'      => $dueDate,
                            'location'     => $locationName,
                            'id'           => $id,
                            'is_holdable'  => $holdable,
                            'addLink'      => $holdable ? 'hold' : false,
                            'item_id'      => $reservableId,
                            'ordered'      => $nofOrdered,
                            'status'       => $status,
                            'total'        => $nofTotal,
                            'reservations' => isset($branch->nofReservations)
                                ? $branch->nofReservations : 0
                        );

                        $vfKey = false;
                        // Does this location exist?
                        foreach ($vfHoldings as $key => $vfHolding) {
                            if ($vfHolding['location'] == $group) {
                                $vfKey = $key;
                            }
                        }

                        // If new location, initialize
                        if ($vfKey === false) {
                            $vfKey = count($vfHoldings);
                            $reservations = isset($organisation->nofReservations)
                                ? $organisation->nofReservations : 0;
                            $shelfMark = isset($department->shelfMark) ?
                                $department->shelfMark : '';
                            $branchHoldable = $branch->reservationButtonStatus ==
                                'reservationOk';
                            $vfHoldings[$vfKey] = array(
                                'id' => $id,
                                'callnumber'   => $shelfMark,
                                'holdings'     => array(),
                                'journal'      => $journal,
                                'location'     => $group,
                                'organisation' => $organisation->value,
                                'status'       => array(
                                    'available' => false,
                                    'availableCount' => 0,
                                    'closestDueDate' => '',
                                    'dueDateStamp' => '',
                                    'text' => '',
                                    'reservations' => $reservations,
                                ),
                                'title'        => $group,
                                'number'       => $vfKey,
                                'is_holdable'  => $branchHoldable
                            );
                        }

                        $vfHoldings[$vfKey]['holdings'][] = $holding;

                        // Location level
                        $availableLocation
                            = $vfHoldings[$vfKey]['status']['available'];

                        if ($available) {
                            $vfHoldings[$vfKey]['status']['availableCount']++;
                            $vfHoldings[$vfKey]['status']['text'] = 'Available';
                            $vfHoldings[$vfKey]['status']['available'] = true;
                        } else if ($dueDate != '' && !$availableLocation) {
                            $thisDueDate
                                = strtotime($department->firstLoanDueDate);
                            $closestDueDate
                                = $vfHoldings[$vfKey]['status']['dueDateStamp'];

                            // If no closest due date set or
                            // if closest due date > this due date, then save
                            if ($closestDueDate == ''
                                || $closestDueDate > $thisDueDate
                            ) {
                                $vfHoldings[$vfKey]['status']['dueDateStamp']
                                    = $thisDueDate;
                                $vfHoldings[$vfKey]['status']['closestDueDate']
                                    = $dueDate;
                            }
                            $vfHoldings[$vfKey]['status']['text']
                                = 'Closest due';
                        } else if (!$availableLocation) {
                            $vfHoldings[$vfKey]['status']['text'] = $status;
                        }
                    }
                }
            }
        }
    }

    /**
     * Get Purchase History
     *
     * This is responsible for retrieving the acquisitions history data for the
     * specific record (usually recently received issues of a serial).
     *
     * @param string $id The record id to retrieve the info for
     *
     * @return mixed     An array with the acquisitions data on success, PEAR_Error
     * on failure
     * @access public
     */
    public function getPurchaseHistory($id)
    {
        return array();
    }

    /**
     * Get New Items
     *
     * Retrieve the IDs of items recently added to the catalog.
     *
     * @param int $page    Page number of results to retrieve (counting starts at 1)
     * @param int $limit   The size of each page of results to retrieve
     * @param int $daysOld The maximum age of records to retrieve in days (max. 30)
     * @param int $fundId  optional fund ID to use for limiting results (use a value
     * returned by getFunds, or exclude for no limit); note that "fund" may be a
     * misnomer - if funds are not an appropriate way to limit your new item
     * results, you can return a different set of values from getFunds. The
     * important thing is that this parameter supports an ID returned by getFunds,
     * whatever that may mean.
     *
     * @return array       Associative array with 'count' and 'results' keys
     * @access public
     */
    public function getNewItems($page, $limit, $daysOld, $fundId = null)
    {
        return array('count' => 0, 'results' => array());
    }

    /**
     * Find Reserves
     *
     * Obtain information on course reserves.
     *
     * @param string $course ID from getCourses (empty string to match all)
     * @param string $inst   ID from getInstructors (empty string to match all)
     * @param string $dept   ID from getDepartments (empty string to match all)
     *
     * @return mixed An array of associative arrays representing reserve items
     * (or a PEAR_Error object if there is a problem)
     * @access public
     */
    public function findReserves($course, $inst, $dept)
    {
        return array();
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
        $this->debugLog("getMyProfile called");
        return $patron;
    }

    /**
     * Patron Login
     *
     * This is responsible for authenticating a patron against the catalog.
     *
     * @param string $username The patron username
     * @param string $password The patron password
     *
     * @return mixed           Associative array of patron info on successful login,
     * null on unsuccessful login, PEAR_Error on error.
     * @access public
     */
    public function patronLogin($username, $password)
    {
        $functionResult = 'patronInformationResult';
        $result = $this->doSOAPRequest($this->patron_wsdl, 'getPatronInformation', $functionResult, $username, array('patronInformationParam' => array('arenaMember' => $this->arenaMember, 'user' => $username, 'password' => $password, 'language' => $this->getLanguage())));

        if (PEAR::isError($result)) {
            if ($result->getMessage() == 'authentication_error_invalid') {
                return null;
            }
            return $result;
        }

        $info = $result->$functionResult->patronInformation;

        $user = array();
        $user['id'] = $username;
        $user['cat_username'] = $username;
        $user['cat_password'] = $password;
        // TODO: do we always get full name?
        $names = explode(' ', $info->patronName);
        $user['lastname'] = array_pop($names);
        $user['firstname'] = implode(' ', $names);
        $user['email'] = '';
        $user['emailId'] = '';
        $user['address1'] = '';
        $user['zip'] = '';
        $user['phone'] = '';
        $user['phoneId'] = '';
        $user['phoneLocalCode'] = '';
        $user['phoneAreaCode'] = '';

        if (isset($info->emailAddresses)) {
            $emailAddresses = is_object($info->emailAddresses->emailAddress) ? array($info->emailAddresses->emailAddress) : $info->emailAddresses->emailAddress;
            foreach ($emailAddresses as $emailAddress) {
                if ($emailAddress->isActive == 'yes') {
                    $user['email'] = isset($emailAddress->address) ? $emailAddress->address : '';
                    $user['emailId'] = isset($emailAddress->id) ? $emailAddress->id : '';
                }
            }
        }

        if (isset($info->addresses)) {
            $addresses = is_object($info->addresses->address) ? array($info->addresses->address) : $info->addresses->address;
            foreach ($addresses as $address) {
                if ($address->isActive == 'yes') {
                    $user['address1'] = isset($address->streetAddress) ? $address->streetAddress : '';
                    $user['zip'] = isset($address->zipCode) ? $address->zipCode : '';
                    if (isset($address->city)) {
                        if ($user['zip']) {
                            $user['zip'] .= ', ';
                        }
                        $user['zip'] .= $address->city;
                    }
                    if (isset($address->country)) {
                        if ($user['zip']) {
                            $user['zip'] .= ', ';
                        }
                        $user['zip'] .= $address->country;
                    }
                }
            }
        }

        if (isset($info->phoneNumbers)) {
            $phoneNumbers = is_object($info->phoneNumbers->phoneNumber) ? array($info->phoneNumbers->phoneNumber) : $info->phoneNumbers->phoneNumber;
            foreach ($phoneNumbers as $phoneNumber) {
                if ($phoneNumber->sms->useForSms == 'yes') {
                    $user['phone'] = isset($phoneNumber->areaCode) ? $phoneNumber->areaCode : '';
                    $user['phoneAreaCode'] = $user['phone'];
                    if (isset($phoneNumber->localCode)) {
                        $user['phone'] .= $phoneNumber->localCode;
                        $user['phoneLocalCode'] = $phoneNumber->localCode;
                    }
                    if (isset($phoneNumber->id)) {
                        $user['phoneId'] = $phoneNumber->id;
                    }
                }
            }
        }


        $user['messagingServices'] = array();
        $services = array('pickUpNotice', 'overdueNotice', 'dueDateAlert');

        foreach ($services as $service) {
            $data = array(
                'active' => false,
                'type' => translate("messaging_settings_type_$service")
            );
            if ($service == 'dueDateAlert') {
                $data['sendMethods']['email'] = array('active' => false, 'type' => 'email');
            } else {
                $data['sendMethods'] = array(
                    'snailMail' => array('active' => false, 'type' => 'letter'),
                    'email' => array('active' => false, 'type' => 'email'),
                    'sms' => array('active' => false, 'type' => 'sms')
                );
            }
            $user['messagingServices'][$service] = $data;
        }

        if (isset($info->messageServices)) {
            foreach ($info->messageServices->messageService as $service) {
                $methods = array();
                $type = $service->serviceType;
                $numOfDays = $service->nofDays->value;
                $active = $service->isActive === 'yes';

                if (array_values((array)$service->sendMethods) === (array)$service->sendMethods) {
                    foreach ($service->sendMethods as $method) {
                        $user['messagingServices'][$type]['sendMethods'][$method->value]['active'] = $method->isActive === 'yes';
                    }
                } else {
                    if (isset($service->sendMethods->sendMethod->value)) {
                        $user['messagingServices'][$type]['sendMethods'][$service->sendMethods->sendMethod->value]['active'] = $service->sendMethods->sendMethod->isActive === 'yes';
                    }
                }
                foreach ($user['messagingServices'][$type]['sendMethods'] as $key => &$data) {
                    $methodType = $data['type'];
                    $typeLabel = translate("messaging_settings_type_$type");
                    $methodLabel = translate("messaging_settings_method_$methodType");

                    if ($numOfDays > 0) {
                        $days = translate(
                            $numOfDays == 1
                            ? 'messaging_settings_num_of_days'
                            : 'messaging_settings_num_of_days_plural'
                        );
                        $methodLabel = str_replace('{1}', $numOfDays, $days);
                    }

                    if (!$active) {
                        $methodLabel = translate('messaging_settings_method_none');
                    }
                    $data['method'] = $methodLabel;
                }

                if (isset($user['messagingServices'][$type])) {
                    $user['messagingServices'][$type]['active'] = $active;
                    $user['messagingServices'][$type]['numOfDays'] = $numOfDays;
                }
            }
        }

        return $user;
    }

    /**
     * Get Patron Transactions
     *
     * This is responsible for retrieving all transactions (i.e. checked out items)
     * by a specific patron.
     *
     * @param array $user The patron array from patronLogin
     *
     * @return mixed      Array of the patron's transactions on success,
     * PEAR_Error otherwise.
     * @access public
     */
    public function getMyTransactions($user)
    {
        $username = $user['cat_username'];
        $password = $user['cat_password'];

        $functionResult = 'loansResponse';
        $result = $this->doSOAPRequest($this->loans_wsdl, 'GetLoans', $functionResult, $username, array('loansRequest' => array('arenaMember' => $this->arenaMember, 'user' => $username, 'password' => $password, 'language' => $this->getLanguage())));

        if (PEAR::isError($result)) {
            return $result;
        }

        $transList = array();
        if (!isset($result->$functionResult->loans->loan)) {
            return $transList;
        }
        $loans = is_object($result->$functionResult->loans->loan) ? array($result->$functionResult->loans->loan) : $result->$functionResult->loans->loan;

        foreach ($loans as $loan) {
            $title = $loan->catalogueRecord->title;
            if ($loan->note) {
                $title .= ' (' . $loan->note . ')';
            }

            $trans = array();
            $trans['id'] = $loan->catalogueRecord->id;
            $trans['item_id'] = $loan->id;
            $trans['title'] = $title;
            $trans['duedate'] = $loan->loanDueDate;
            $trans['renewable'] = $loan->loanStatus->isRenewable == 'yes';
            $trans['message'] = $this->mapStatus($loan->loanStatus->status);
            $trans['barcode'] = $loan->id;
            $trans['renewalCount'] = max(array(0, $this->config['Loans']['renewalLimit'] - $loan->remainingRenewals));
            $trans['renewalLimit'] = $this->config['Loans']['renewalLimit'];
            $transList[] = $trans;
        }

        // Sort the Loans
        $date = array();
        foreach ($transList as $key => $row) {
            $date[$key] = $row['duedate'];
        }
        array_multisort($date, SORT_ASC, $transList);

        // Convert Axiell format to display date format
        foreach ($transList as &$row) {
            $row['duedate'] = $this->formatDate($row['duedate']);
        }

        return $transList;
    }

    /**
     * Renew Details
     *
     * This is responsible for getting the details required for renewing loans.
     *
     * @param string $checkoutDetails The request details
     *
     * @return string           Required details passed to renewMyItems
     *
     * @access public
     */
    public function getRenewDetails($checkoutDetails)
    {
        return $checkoutDetails['barcode'];
    }

    /**
     * Renew Items
     *
     * This is responsible for renewing items.
     *
     * @param string $renewDetails The request details
     *
     * @return array           Associative array of the results
     *
     * @access public
     */
    public function renewMyItems($renewDetails)
    {
        $succeeded = 0;
        $results = array('blocks' => array(), 'details' => array());
        foreach ($renewDetails['details'] as $id) {
            $username = $renewDetails['patron']['cat_username'];
            $password = $renewDetails['patron']['cat_password'];

            $functionResult = 'renewLoansResponse';
            $result = $this->doSOAPRequest($this->loans_wsdl, 'RenewLoans', $functionResult, $username, array('renewLoansRequest' => array('arenaMember' => $this->arenaMember, 'user' => $username, 'password' => $password, 'language' => 'en', 'loans' => array($id))));

            if (PEAR::isError($result)) {
                $results['details'][$id] = array(
                    'success' => false,
                    'status' => 'Renewal failed', // TODO
                    'sys_message' => $result->getMessage()
                );
            } else {
                $status = trim($result->$functionResult->loans->loan->loanStatus->status);
                $success = $status === 'isRenewedToday';

                $results['details'][$id] = array(
                    'success' => $success,
                    'status' => $success ? 'Loan renewed' : 'Renewal failed',
                    'sysMessage' => $status,
                    'item_id' => $id,
                    'new_date' => $this->formatDate($result->$functionResult->loans->loan->loanDueDate),
                    'new_time' => ''
                );
            }
        }
        return $results;
    }


    /**
     * Get Patron Fines
     *
     * This is responsible for retrieving all fines by a specific patron.
     *
     * @param array $user The patron array from patronLogin
     *
     * @return mixed      Array of the patron's fines on success, PEAR_Error
     * otherwise.
     * @access public
     */
    public function getMyFines($user)
    {
        $username = $user['cat_username'];
        $password = $user['cat_password'];

        $functionResult = 'debtsResponse';
        $result = $this->doSOAPRequest($this->payments_wsdl, 'GetDebts', $functionResult, $username, array('debtsRequest' => array('arenaMember' => $this->arenaMember,'user' => $username, 'password' => $password, 'language' => $this->getLanguage(), 'fromDate' => '1699-12-31', 'toDate' => time())));

        if (PEAR::isError($result)) {
            return $result;
        }

        $finesList = array();
        if (!isset($result->$functionResult->debts->debt))
            return $finesList;
        $debts = is_object($result->$functionResult->debts->debt) ? array($result->$functionResult->debts->debt) : $result->$functionResult->debts->debt;

        foreach ($debts as $debt) {
            $fine = array();
            $fine['debt_id'] = $debt->id;
            $fine['amount'] = str_replace(',', '.', $debt->debtAmountFormatted) * 100;
            $fine['checkout'] = '';
            $fine['fine'] = $debt->debtType . ' - ' . $debt->debtNote;
            $fine['balance'] = str_replace(',', '.', $debt->debtAmountFormatted) * 100;
            // Convert Axiell format to display date format
            $fine['createdate'] = $this->formatDate($debt->debtDate);
            $fine['duedate'] = $this->formatDate($debt->debtDate);
            $finesList[] = $fine;
        }
        return $finesList;
    }

    /**
     * Get Patron Holds
     *
     * This is responsible for retrieving all holds by a specific patron.
     *
     * @param array $user The patron array from patronLogin
     *
     * @return mixed      Array of the patron's holds on success, PEAR_Error
     * otherwise.
     * @access public
     */
    public function getMyHolds($user)
    {
        $username = $user['cat_username'];
        $password = $user['cat_password'];

        $functionResult =  'getReservationsResult';
        $result = $this->doSOAPRequest($this->reservations_wsdl, 'getReservations', $functionResult, $username, array('getReservationsParam' => array('arenaMember' => $this->arenaMember, 'user' => $username, 'password' => $password, 'language' => $this->getLanguage())));

        $statusAWS = $result->$functionResult->status;

        if (PEAR::isError($result)) {
            return $result;
        }

        $holdsList = array();
        if (!isset($result->$functionResult->reservations->reservation))
            return $holdsList;
        $reservations = is_object($result->$functionResult->reservations->reservation) ? array($result->$functionResult->reservations->reservation) : $result->$functionResult->reservations->reservation;

        foreach ($reservations as $reservation) {
            $expireDate = $reservation->reservationStatus == 'fetchable' ? $reservation->pickUpExpireDate : $reservation->validToDate;
            $title = isset($reservation->catalogueRecord->title) ? $reservation->catalogueRecord->title : '';
            if (isset($reservation->note)) {
                $title .= ' (' . $reservation->note . ')';
            }

            $hold = array(
                'type' => $reservation->reservationStatus,
                'id' => $reservation->catalogueRecord->id,
                'location' => $reservation->pickUpBranchId,
                'reqnum' => ($reservation->isDeletable == 'yes' && isset($reservation->id)) ? $reservation->id : '',
                'pickupnum' => isset($reservation->pickUpNo) ? $reservation->pickUpNo : '',
                'expire' => $this->formatDate($expireDate),
                'create' => $reservation->validFromDate,
                'position' => isset($reservation->queueNo) ? $reservation->queueNo : '-',
                'available' => $reservation->reservationStatus == 'fetchable',
                'modifiable' => $reservation->reservationStatus == 'active',
                'item_id' => '',
                'reservation_id' => $reservation->id,
                'volume' => isset($reservation->catalogueRecord->volume) ? $reservation->catalogueRecord->volume : '',
                'publication_year' => isset($reservation->catalogueRecord->publicationYear) ? $reservation->catalogueRecord->publicationYear : '',
                'title' => $title
            );
            $holdsList[] = $hold;
        }

        // Sort the Holds
        $date = array();
        foreach ($holdsList as $key => $row) {
            $date[$key] = $row['create'];
        }
        array_multisort($date, SORT_DESC, $holdsList);

        // Convert Axiell format to display date format
        foreach ($holdsList as &$row) {
            $row['create'] = $this->formatDate($row['create']);
        }
        return $holdsList;
    }

    /**
     * Get Pickup Locations
     *
     * This is responsible for retrieving pickup locations.
     *
     * @param array $user        The patron array from patronLogin
     * @param array $holdDetails Hold details
     *
     * @return mixed      Array of the patron's fines on success, PEAR_Error
     * otherwise.
     * @access public
     */
    public function getPickUpLocations($user, $holdDetails)
    {
        $username = $user['cat_username'];
        $password = $user['cat_password'];

        $id = !empty($holdDetails['item_id'])
            ? $holdDetails['item_id'] : $holdDetails['id'];

        $functionResult = 'getReservationBranchesResult';
        $result = $this->doSOAPRequest($this->reservations_wsdl, 'getReservationBranches', $functionResult, $username, array('getReservationBranchesParam' => array('arenaMember' => $this->arenaMember, 'user' => $username, 'password' => $password, 'language' => $this->getLanguage(), 'country' => 'FI', 'reservationEntities' => $id, 'reservationType' => 'normal')));
        if (PEAR::isError($result)) {
            return $result;
        }

        $locationsList = array();
        if (!isset($result->$functionResult->organisations->organisation)) {
            // If we didn't get any pickup locations for item_id, fall back to id
            // and try again... This seems to happen when there are only ordered
            // items in the branch
            if (!empty($holdDetails['item_id'])) {
                unset($holdDetails['item_id']);
                return $this->getPickUpLocations($user, $holdDetails);
            }
            return $locationsList;
        }
        $organisations = is_object($result->$functionResult->organisations->organisation)
            ? array($result->$functionResult->organisations->organisation)
            : $result->$functionResult->organisations->organisation;

        foreach ($organisations as $organisation) {
            if (!isset($organisation->branches->branch)) {
                continue;
            }

            $organisationID = $organisation->id;

            // TODO: Make it configurable whether organisation names should be included in the location name
            $branches = is_object($organisation->branches->branch)
                ? array($organisation->branches->branch)
                : $organisation->branches->branch;

            if (is_object($organisation->branches->branch)) {
                $locationsList[] = array(
                    'locationID' => $organisationID . '.' .  $organisation->branches->branch->id,
                    'locationDisplay' => $organisation->branches->branch->name
                );
            } else {
                foreach ($organisation->branches->branch as $branch) {
                    $locationsList[] = array(
                        'locationID' => $organisationID . '.' . $branch->id,
                        'locationDisplay' => $branch->name
                    );
                }
            }
        }

        // Sort pick up locations
        usort($locationsList, array($this, 'pickUpLocationsSortFunction'));

        return $locationsList;
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
     * Returns the default request group
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
        $requestGroups = $this->getRequestGroups(0, 0);
        return $requestGroups[0]['id'];
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

        // Request Groups are not used for reservations
        return false;
    }

    /**
     * Place Hold
     *
     * This is responsible for both placing holds as well as placing recalls.
     *
     * @param string $holdDetails The request details
     *
     * @return mixed           True if successful, false if unsuccessful, PEAR_Error
     * on error
     * @access public
     */
    public function placeHold($holdDetails)
    {
        global $configArray;

        if (isset($holdDetails['item_id']) && $holdDetails['item_id']) {
            $entityId = $holdDetails['item_id'];
            $reservationSource = 'holdings';
        } else {
            $entityId = $holdDetails['id'];
            $reservationSource = 'catalogueRecordDetail';
        }

        $username = $holdDetails['patron']['cat_username'];
        $password = $holdDetails['patron']['cat_password'];

        $validFromDate = date('Y-m-d');

        if (!isset($holdDetails['requiredBy'])) {
            $validToDate = $this->getDefaultRequiredByDate();
        } else {
            $validToDate = $this->dateFormat->convertFromDisplayDate(
                'Y-m-d', $holdDetails['requiredBy']
            );
        }

        if (PEAR::isError($validToDate)) {
            // Hold Date is invalid
            return $this->holdError("hold_date_invalid");
        }

        $pickUpLocation = $holdDetails['pickUpLocation'];
        list($organisation, $branch) = explode('.', $pickUpLocation, 2);

        $result = $this->doSOAPRequest($this->reservations_wsdl, 'addReservation', 'addReservationResult', $username, array('addReservationParam' => array('arenaMember' => $this->arenaMember, 'user' => $username, 'password' => $password, 'language' => 'en', 'reservationEntities' => $entityId, 'reservationSource' => $reservationSource, 'reservationType' => 'normal', 'organisationId' => $organisation, 'pickUpBranchId' => $branch, 'validFromDate' => $validFromDate, 'validToDate' => $validToDate )));

        if (PEAR::isError($result)) {
            return array(
                'success' => false,
                'sysMessage' => $result->getMessage()
            );
        }

        return array(
            'success' => true
        );
    }

    /**
     * Cancel Holds
     *
     * This is responsible for canceling holds.
     *
     * @param string $cancelDetails The request details
     *
     * @return array           Associative array of the results
     *
     * @access public
     */
    public function cancelHolds($cancelDetails)
    {
        $username = $cancelDetails['patron']['cat_username'];
        $password = $cancelDetails['patron']['cat_password'];
        $succeeded = 0;
        $results = array();

        foreach ($cancelDetails['details'] as $details) {
            $result = $this->doSOAPRequest($this->reservations_wsdl, 'removeReservation', 'removeReservationResult', $username, array('removeReservationsParam' => array('arenaMember' => $this->arenaMember, 'user' => $username, 'password' => $password, 'language' => 'en', 'id' => $details)));

            if (PEAR::isError($result)) {
                $results[] = array(
                    'success' => false,
                    'status' => 'hold_cancel_fail', // TODO
                    'sysMessage' => $result->getMessage()
                );
            } else {
                $results[$details] = array(
                    'success' => true,
                    'status' => 'Hold canceled', // TODO
                    'sysMessage' => ''
                );
                ++$succeeded;
            }
        }
        $results['count'] = $succeeded;
        return $results;
    }

    /**
     * Cancel Hold Details
     *
     * This is responsible for getting the details required for canceling holds.
     *
     * @param string $holdDetails The request details
     *
     * @return string           Required details passed to cancelHold
     *
     * @access public
     */
    public function getCancelHoldDetails($holdDetails)
    {
        return $holdDetails['reqnum'];
    }

    /**
     * Change pickup location
     *
     * This is responsible for changing the pickup location of a hold
     *
     * @param string $patron      Patron array
     * @param string $holdDetails The request details
     *
     * @return array Response
     * @access public
     */
    public function changePickupLocation($patron, $holdDetails)
    {
        global $configArray;
        $username = $patron['cat_username'];
        $password = $patron['cat_password'];
        $pickUpLocation = $holdDetails['pickup'];
        $created = $this->dateFormat->convertFromDisplayDate(
            "Y-m-d", $holdDetails['created']
        );
        $expires = $this->dateFormat->convertFromDisplayDate(
            "Y-m-d", $holdDetails['expires']
        );
        $reservationId = $holdDetails['reservationId'];
        list($organisation, $branch) = explode('.', $pickUpLocation, 2);

        $result = $this->doSOAPRequest($this->reservations_wsdl, 'changeReservation', 'changeReservationResult', $username, array('changeReservationsParam' => array('arenaMember' => $this->arenaMember, 'user' => $username, 'password' => $password, 'language' => 'en', 'id' => $reservationId, 'pickUpBranchId' => $branch, 'validFromDate' => $created, 'validToDate' => $expires )));

        if (PEAR::isError($result)) {
            return array(
                'success' => false,
                'sysMessage' => $result->getMessage()
            );
        }
        return array(
            'success' => true
        );
    }

    /**
     * Check if patron is authorized (e.g. to access licensed electronic material).
     *
     * @param array $patron The patron array from patronLogin
     *
     * @return bool True if patron is authorized, false if not
     */
    public function getPatronAuthorizationStatus($patron)
    {
        // For now there are no rules, any authenticated user is also authorized
        return true;
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
     * Set patron phone number
     *
     * @param array  $patron Patron array
     * @param string $phone  Phone number
     *
     * @return array Response
     */
    public function setPhoneNumber($patron, $phone)
    {
        $username = $patron['cat_username'];
        $password = $patron['cat_password'];
        $phoneCountry = isset($patron['phoneCountry']) ? $patron['phoneCountry'] : 'FI';
        $areaCode = '';

        $conf = array(
            'arenaMember'  => $this->arenaMember,
            'language'     => 'en',
            'user'         => $username,
            'password'     => $password,
            'areaCode'     => $areaCode,
            'country'      => $phoneCountry,
            'localCode'    => $phone,
            'useForSms'    => 'yes'
        );

        if (!empty($patron['phoneId'])) {
            $conf['id'] = $patron['phoneId'];
            $result = $this->doSOAPRequest($this->patron_wsdl, 'changePhone', 'changePhoneNumberResult', $username, array('changePhoneNumberParam' => $conf));
        } else {
            $result = $this->doSOAPRequest($this->patron_wsdl, 'addPhone', 'addPhoneNumberResult', $username, array('addPhoneNumberParam' => $conf));
        }

        if (PEAR::isError($result)) {
            $results = array(
                'success' => false,
                'status' => 'Changing the phone number failed',
                'sys_message' => $result->getMessage()
            );
        } else {
            $results = array(
                'success' => true,
                'status' => 'Phone number changed',
                'sys_message' => ''
            );
        }
        return $results;
    }

    /**
     * Set patron email address
     *
     * @param array  $patron Patron array
     * @param String $email  User Email
     *
     * @return array Response
     */
    public function setEmailAddress($patron, $email)
    {
        $username = $patron['cat_username'];
        $password = $patron['cat_password'];

        $conf = array(
            'arenaMember'  => $this->arenaMember,
            'language'     => 'en',
            'user'         => $username,
            'password'     => $password,
            'address'      => $email,
            'isActive'     => 'yes'
        );

        if (!empty($patron['emailId'])) {
            $conf['id'] = $patron['emailId'];
            $result = $this->doSOAPRequest($this->patron_wsdl, 'changeEmail', 'changeEmailAddressResult', $username, array('changeEmailAddressParam' => $conf));
        } else {
            $result = $this->doSOAPRequest($this->patron_wsdl, 'addEmail', 'addEmailAddressResult', $username, array('addEmailAddressParam' => $conf));
        }

        if (PEAR::isError($result)) {
            $results = array(
                'success' => false,
                'status' => 'Changing the email address failed',
                'sys_message' => $result->getMessage()
            );
        } else {
            $results = array(
                'success' => true,
                'status' => 'Email address changed',
                'sys_message' => '',
            );
        }

        return $results;
    }

    /**
     * Change pin code
     *
     * @param String $cardDetails Patron card data
     *
     * @return array Response
     */
    public function changePassword($cardDetails)
    {
        $username = $cardDetails['patron']['cat_username'];
        $password = $cardDetails['patron']['cat_password'];

        $conf = array(
            'arenaMember'  => $this->arenaMember,
            'cardNumber'   => $cardDetails['patron']['cat_username'],
            'cardPin'      => $cardDetails['oldPassword'],
            'newCardPin'   => $cardDetails['newPassword'],
        );

        $result = $this->doSOAPRequest($this->patron_wsdl, 'changeCardPin', 'changeCardPinResult', $username, array('changeCardPinParam' => $conf));

        if (PEAR::isError($result)) {
            $results = array(
                'success' => false,
                'status' => $result->getMessage()
            );
        } else {
            $results = array(
                'success' => true,
                'status' => '',
            );
        }
        return $results;
    }

    /**
     * Convert anySimpleType from XML to a string
     *
     * @param string $xml XML fragment to convert
     *
     * @return string Converted string
     */
    public static function anySimpleTypeToString($xml)
    {
        if (preg_match('/>(.*)</', $xml, $matches)) {
            return $matches[1];
        }
        return '';
    }

    /**
     * Convert anySimpleType from string to XML
     *
     * @param string $value String to convert
     *
     * @return string String converted to XML
     */
    public static function stringToAnySimpleType($value)
    {
        return '<BOGUS>' . htmlspecialchars($value, ENT_COMPAT, 'UTF-8')
            . '</BOGUS>';
    }

    /**
     * Send a SOAP request
     *
     * @param string $wsdl           Name of the wsdl file
     * @param string $function       Name of the function
     * @param string $functionResult Name of the Result tag
     * @param string $id             Username or record id
     * @param array  $params         Parameters needed for the SOAP call
     *
     * @return object|PEAR_Error SOAP response or PEAR_Error
     */
    protected function doSOAPRequest($wsdl, $function, $functionResult, $id, $params)
    {
        $client = new SoapClient($wsdl, $this->soapOptions);
        try {
            $this->debugLog("$function Request for '$id'");

            $startTime = microtime(true);
            $result = $client->$function($params);

            if ($this->durationLogPrefix) {
                file_put_contents($this->durationLogPrefix . '_' . $function . '.log', date('Y-m-d H:i:s ') . round(microtime(true) - $startTime, 4) . "\n", FILE_APPEND);
            }

            $statusAWS = $result->$functionResult->status;

            if ($statusAWS->type != 'ok') {
                $message = $this->handleError($function, $statusAWS->message, $id, $client);
                return new PEAR_Error($message);
            }

            if ($this->verbose) {
                $this->debugLog("$function Request: " . $this->formatXML($client->__getLastRequest()));
                $this->debugLog("$function Response: " . $this->formatXML($client->__getLastResponse()));
            }

            return $result;
        } catch (Exception $e) {
            $message = $this->handleError($function, $e->getMessage(), $id, $client);
            return new PEAR_Error($message);
        }
    }

    /**
     * Get patron id from user name and password
     *
     * @param string $username User name
     * @param string $password Password
     *
     * @return string|PEAR_Error ID or error
     */
    protected function getPatronId($username, $password)
    {
        $functionResult = 'authenticatePatronResult';
        $result = $this->doSOAPRequest($this->patron_wsdl, 'authenticatePatron', $functionResult, $username, array('authenticatePatronParam' => array('arenaMember' => $this->arenaMember, 'user' => $username, 'password' => $password, 'language' => $this->getLanguage())));

        if (PEAR::isError($result)) {
            return $result;
        }
        return $result->$functionResult->patronId;
    }

    /**
     * Handle the error messages from Axiell Web Services
     *
     * @param string     $function Function name
     * @param string     $message  Error message
     * @param string     $id       ID for logging
     * @param SoapClient &$client  Soap client
     *
     * @return PEAR_Error PEAR Error message
     */
    protected function handleError($function, $message, $id, &$client)
    {
        $this->debugLog("$function Request failed for '$id'");
        $this->debugLog("$function Request: " . $this->formatXML($client->__getLastRequest()));
        $this->debugLog("$function Response: " . $this->formatXML($client->__getLastResponse()));
        $this->debugLog("AWS error: '$message'");

        $status = array (
            // Axiell system status error messages
            'InvalidAccountCard'     => 'authentication_error_invalid',
            'InvalidUser'            => 'authentication_error_invalid',
            'InvalidPatron'          => 'authentication_error_invalid',
            'InvalidLogin'           => 'authentication_error_invalid',
            'InvalidPinCode'         => 'authentication_error_invalid',
            'InvalidBorrCard'        => 'authentication_error_invalid',
            'BackendError'           => 'catalog_connection_failed',
            'LocalServiceTimeout'    => 'catalog_connection_failed',
            'DatabaseError'          => 'catalog_connection_failed',
            'ReservationDenied'      => 'hold_error_blocked',
            'BlockedBorrCard'        => 'catalog_connection_patron_blocked',

            // Default system status error messages for different functions
            'addReservation'         => 'hold_error_system',
            'authenticatePatron'     => 'authentication_error_technical',
            'GetDebts'               => 'catalog_connection_failed',
            'getPatronInformation'   => 'patron_login_error_technical',
            'GetHoldings'            => 'catalog_connection_failed',
            'getReservations'        => 'hold_error_system',
            'getReservationBranches' => 'hold_error_system',
            'GetLoans'               => 'catalog_connection_failed',
            'removeReservation'      => 'hold_error_system',
            'renewLoans'             => 'renew_error',
            'setEmailAddress'        => 'catalog_connection_failed',
            'setPhoneNumber'         => 'catalog_connection_failed'
            );

        if (isset($status[$message])) {
            return $status[$message];
        } elseif (isset($status[$function])) {
            return $status[$function];
        } return 'catalog_connection_failed';
    }


    /**
     * Format date
     *
     * @param string $dateString Date as a string
     *
     * @return string Formatted date
     */
    protected function formatDate($dateString)
    {
        // remove timezone from Axiell obscure dateformat
        $date = substr($dateString, 0, strpos("$dateString*", "+"));
        if (PEAR::isError($date)) {
            return $dateString;
        }
        return $this->dateFormat->convertToDisplayDate("Y-m-d", $date);
    }


    /**
     * Get the language to be used in the interface
     *
     * @return string Language as string
     */
    protected function getLanguage()
    {
        global $interface;
        $language = $interface->getLanguage();
        if (!in_array($language, array('en', 'sv', 'fi'))) {
            $language = 'en';
        }
        return $language;
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
     * Map statuses
     *
     * @param string $status as a string
     *
     * @return string Mapped status
     */
    protected function mapStatus($status)
    {
        $statuses = array (
            'isLoanedToday'         => 'Borrowed today',
            'isRenewedToday'        => 'Renewed today',
            'isOverdue'             => 'renew_item_overdue',
            'patronIsDeniedLoan'    => 'fine_limit_patron',
            'patronHasDebt'         => 'renew_item_patron_has_debt',
            'maxNofRenewals'        => 'renew_item_limit',
            'patronIsInvoiced'      => 'renew_item_patron_is_invoiced',
            'copyHasSpecialCircCat' => 'Copy has special circulation',
            'copyIsReserved'        => 'renew_item_requested',
            'renewalIsDenied'       => 'renew_denied'
        );

        if (isset($statuses[$status])) {
            return $statuses[$status];
        }
        return $status;
    }


    /**
     * Write to debug log, if defined
     *
     * @param string $msg Message to write
     *
     * @return void
     */
    protected function debugLog($msg)
    {
        if (!$this->logFile) {
            return;
        }
        $msg = date('Y-m-d H:i:s') . ' [' . getmypid() . "] $msg\n";
        file_put_contents($this->logFile, $msg, FILE_APPEND);
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
     * Pretty-print an XML string
     *
     * @param string $xml XML string
     *
     * @return string Pretty XML string
     */
    protected function formatXML($xml)
    {
        if (!$xml) {
            return $xml;
        }
        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml);
        return $dom->saveXML();
    }

    /**
     * Get default required by date, functionally identical to
     * services/Record/Hold::getDefaultDueDate
     *
     * @return string Date stamp
     */
    protected function getDefaultRequiredByDate()
    {
        list($d, $m, $y) = isset($this->config['Holds']['defaultRequiredDate'])
             ? explode(':', $this->config['Holds']['defaultRequiredDate'])
             : array(0, 1, 0);
        $date = mktime(
            0, 0, 0, date('m')+$m, date('d')+$d, date('Y')+$y
        );

        return $this->dateFormat->convertFromDisplayDate(
            'Y-m-d',
            $this->dateFormat->convertToDisplayDate('U', $date)
        );
    }
}

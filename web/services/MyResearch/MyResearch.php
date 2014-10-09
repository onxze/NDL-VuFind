<?php
/**
 * Base class for most MyResearch module actions
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
 * @package  Controller_MyResearch
 * @author   Andrew S. Nagy <vufind-tech@lists.sourceforge.net>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_a_module Wiki
 */
require_once 'Action.php';

require_once 'services/MyResearch/lib/User.php';
require_once 'services/MyResearch/lib/Resource.php';

/**
 * Base class for most MyResearch module actions
 *
 * @category VuFind
 * @package  Controller_MyResearch
 * @author   Andrew S. Nagy <vufind-tech@lists.sourceforge.net>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_a_module Wiki
 */
class MyResearch extends Action
{
    protected $db;
    protected $catalog;
    protected $errorMsg;
    protected $infoMsg;
    protected $showExport;
    protected $followupUrl;
    protected $checkHolds;
    protected $checkRenew;
    protected $cancelHolds;

    /**
     * Constructor
     *
     * @param bool $skipLogin Set to true to bypass the default login requirement.
     *
     * @access public
     */
    public function __construct($skipLogin = false)
    {
        global $interface;
        global $configArray;
        global $user;

        if (!$skipLogin && !UserAccount::isLoggedIn()) {
            include_once 'Login.php';
            Login::launch();
            exit();
        }

        // Setup Search Engine Connection
        $this->db = ConnectionManager::connectToIndex();

        // Connect to Database
        $this->catalog = ConnectionManager::connectToCatalog();

        // Is Placing Holds allowed?
        $this->checkHolds = $this->catalog->checkFunction("Holds", null);

        // Is Cancelling Holds allowed?
        $this->cancelHolds = $this->catalog->checkFunction("cancelHolds", null);

        // Is Renewing Items allowed?
        $this->checkRenew = $this->catalog->checkFunction("Renewals", null);

        // Register Library Catalog Account
        if (isset($_POST['submit']) && !empty($_POST['submit']) && $this->catalog
            && isset($_POST['cat_username']) && isset($_POST['cat_password'])
        ) {
            $username = $_POST['cat_username'];
            $password = $_POST['cat_password'];
            $loginTarget = isset($_POST['login_target']) ? $_POST['login_target'] : false;
            if ($loginTarget) {
                $username = "$loginTarget.$username";
            }

            if (UserAccount::processCatalogLogin($username, $password)) {
                $interface->assign('user', $user);
            } else {
                $interface->assign('loginError', 'Invalid Patron Login');
            }
        }

        // Assign Exporter Options
        $exportOptions = array();
        if ($configArray['BulkExport']['enabled']) {
            $options = explode(':', $configArray['BulkExport']['options']);
            foreach ($options as $option) {
                if ($configArray['Export'][$option] == true) {
                    $exportOptions[] = $option;
                }
            }
            $interface->assign('exportOptions', $exportOptions);
        }
        // Get Messages
        $this->infoMsg = isset($_GET['infoMsg']) ? $_GET['infoMsg'] : false;
        $this->errorMsg = isset($_GET['errorMsg']) ? $_GET['errorMsg'] : false;
        $this->showExport = isset($_GET['showExport']) ? $_GET['showExport'] : false;
        $this->followupUrl = false;
    }

    /**
     * Determines whether an error is a catalog connection error that should be
     * displayed to the user or raised as a general error. Formats a displayable
     * error appropriately.
     *
     * @param PEAR_Error $error PEAR error
     *
     * @return void
     */
    protected function handleCatalogError($error)
    {
        global $configArray, $interface, $user;

        $msg = $error->getMessage();
        if ($msg == 'authentication_error_technical') {
            $interface->assign('errorMsg', 'catalog_connection_failure_explanation');
        } else if ($msg == 'catalog_login_failed') {
            if ($user) {
                $msg = translate($msg) . ' ' . translate('catalog_login_check_account');
                $accountId = null;
                // A failed login clears cat_username from $user, so fetch it from
                // interface
                $currentAccount = $interface->get_template_vars('currentCatalogAccount');
                $catAccounts = $user->getCatalogAccounts();

                foreach ($catAccounts as $catAccount) {
                    if ($catAccount['cat_username'] == $currentAccount) {
                        $accountId = $catAccount['id'];
                        break;
                    }
                }
                $accountUrl = $configArray['Site']['url'] . "/MyResearch/Accounts";
                if (!is_null($accountId)) {
                    $accountUrl .= "?edit=$accountId";
                }
            }
            $interface->assign('errorMsg', str_replace('%%url%%', $accountUrl, $msg));
        } else {
            PEAR::raiseError($error);
        }
    }


}

?>

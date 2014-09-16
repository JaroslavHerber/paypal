<?php
/**
 * This file is part of OXID eSales PayPal module.
 *
 * OXID eSales PayPal module is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OXID eSales PayPal module is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OXID eSales PayPal module.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link      http://www.oxid-esales.com
 * @copyright (C) OXID eSales AG 2003-2013
 */


//require_once 'PHPUnit/Extensions/SeleniumTestCase.php';

//require_once 'test_config.inc.php';

class oxidAdditionalSeleniumFunctions extends PHPUnit_Extensions_SeleniumTestCase
{
    protected $captureScreenshotOnFailure = TRUE;

    protected $screenshotPath = null;
    protected $screenshotUrl = null;
    protected $skipDbRestore = false;

    /**
     * @param  string $name
     * @param  array $data
     * @param  string $dataName
     * @param  array $browser
     * @throws InvalidArgumentException
     */
    public function __construct($name = NULL, array $data = array(), $dataName = '', array $browser = array())
    {
        $this->screenshotUrl = getenv('SELENIUM_SCREENSHOTS_URL');
        $this->screenshotPath = getenv('SELENIUM_SCREENSHOTS_PATH');

        parent::__construct($name, $data, $dataName, $browser);
    }

//---------------------------- general functions --------------------------------

    /**
     * Sets up default environment for tests.
     *
     * @param bool $skipDemoData
     */
    protected function setUp($skipDemoData = false)
    {
        try {
            if (is_string(hostUrl)) {
                $this->setHost(hostUrl);
            }
            $this->setBrowser(browserName);
            $this->setBrowserUrl(shopURL);

            $this->setTimeout(90000);

        } catch (Exception $e) {
            $this->stopTesting("Failed preparing testing environment! Reason: " . $e->getMessage());
        }
    }

    /**
     * Restores database after every test.
     */
    protected function tearDown()
    {
        if (!$this->skipDbRestore) {
            $this->restoreDB();
        }

        parent::tearDown();
    }


    /**
     * adds some demo data to database.
     */
    public function addDemoData($demo)
    {
        if (filesize($demo)) {
            $myConfig = oxRegistry::getConfig();

            $sUser = $myConfig->getConfigParam('dbUser');
            $sPass = $myConfig->getConfigParam('dbPwd');
            $sDbName = $myConfig->getConfigParam('dbName');
            $sHost = $myConfig->getConfigParam('dbHost');
            $sCmd = 'mysql -h' . escapeshellarg($sHost) . ' -u' . escapeshellarg($sUser) . ' -p' . escapeshellarg($sPass) . ' --default-character-set=utf8 ' . escapeshellarg($sDbName) . '  < ' . escapeshellarg($demo) . ' 2>&1';
            exec($sCmd, $sOut, $ret);
            $sOut = implode("\n", $sOut);
            if ($ret > 0) {
                throw new Exception($sOut);
            }
            if (OXID_VERSION_EE) :
                oxDb::getDb()->execute("delete from oxcache");
            endif;
        }
    }

    /**
     * deletes all files in tmp dir
     */
    public function clearTmp()
    {
        $this->open(shopURL . "_deleteTmp.php");
    }

    /**
     * opens shop frontend and runs checkForErrors().
     *
     */
    public function openShop($blForceMainShop = false, $isSubShop = false)
    {
        $this->selectWindow(null);
        $this->windowMaximize(null);
        $this->selectFrame("relative=top");
        // Remove port if there is one, as we want to call _cc.php directly.
        $this->open(preg_replace('/:{1}\d{1,}/', '', shopURL) . "_cc.php?no_redirect=1");
        $this->open(shopURL);
        /* if ( OXID_VERSION_EE ) : */
        if (isSUBSHOP || $isSubShop) {
            if (!$blForceMainShop) {
                $this->clickAndWait("link=subshop");
            } else {
                $sShopNr = $this->getShopVersionNumber();
                $this->clickAndWait("link=OXID eShop " . $sShopNr);
            }
        }
        /* endif; */
        $this->checkForErrors();
    }

    /**
     * tests if none of php possible errors are displayed into shop frontend page.
     */
    public function checkForErrors()
    {
        if (($this->isTextPresent("Warning: ")) || ($this->isTextPresent("ADODB_Exception")) ||
            ($this->isTextPresent("Fatal error: ")) || ($this->isTextPresent("Catchable fatal error: ")) ||
            ($this->isTextPresent("Notice: ")) || ($this->isTextPresent("exception '")) ||
            ($this->isTextPresent("ERROR : Tran")) || ($this->isTextPresent("does not exist or is not accessible!")) ||
            ($this->isTextPresent("EXCEPTION_"))
        ) {
            $this->refresh();
        }
        $this->assertFalse($this->isTextPresent("Warning: "), "PHP Warning is in the page");
        $this->assertFalse($this->isTextPresent("ADODB_Exception"), "ADODB Exception is in the page");
        $this->assertFalse($this->isTextPresent("Fatal error: "), "PHP Fatal error is in the page");
        $this->assertFalse($this->isTextPresent("Catchable fatal error: "), " Catchable fatal error is in the page");
        $this->assertFalse($this->isTextPresent("Notice: "), "PHP Notice is in the page");
        $this->assertFalse($this->isTextPresent("exception '"), "Uncaught exception is in the page");
        $this->assertFalse($this->isTextPresent("does not exist or is not accessible!"), "Warning about not existing function is in the page ");
        $this->assertFalse($this->isTextPresent("ERROR : Tran"), "Missing translation for constant (ERROR : Translation for...)");
        $this->assertFalse($this->isTextPresent("EXCEPTION_"), "Exception - component not found (EXCEPTION_)");
    }

    /**
     * removes \n signs and it leading spaces from string. keeps only single space in the ends of each row.
     *
     * @param string $sLine not formatted string (with spaces and \n signs).
     *
     * @return string formatted string with single spaces and no \n signs.
     */
    public function clearString($sLine)
    {
        return trim(preg_replace("/[ \t\r\n]+/", ' ', $sLine));
    }

    /**
     * clicks link/button and waits till page will be loaded. then checks for errors.
     * recommended to use in frontend. use in admin only, if this click wont relode frames.
     *
     * @param string $locator link/button locator in the page.
     * @param string $element element locator for additional check if page is fully loaded (optional).
     */
    public function clickAndWait($locator, $element = null)
    {
        if (!$this->isElementPresent($locator)) {
            $this->waitForElement($locator);
        }
        $this->click($locator);
        $this->waitForPageToLoad("90000");
        //additional check if page is really loaded. on demand only for places, that have this problem.
        if ($element) {
            sleep(1);
            $this->waitForElement($element);
        }
        $this->checkForErrors();
    }

    /**
     * selects label in select list and waits till page will be loaded. then checks for errors.
     * recommended to use in frontend. use in admin only, if this select wont reload frames.
     *
     * @param string $locator select list locator.
     * @param string $selection option to select.
     * @param string $element element locator for additional check if page is fully loaded (optional).
     */
    public function selectAndWait($locator, $selection, $element = null)
    {
        if (!$this->isElementPresent($locator)) {
            $this->waitForElement($locator);
        }
        $this->select($locator, $selection);
        $this->waitForPageToLoad("90000");
        //additional check if page is really loaded. on demand only for places, that have this problem.
        if ($element) {
            sleep(1);
            $this->waitForElement($element);
        }
        $this->checkForErrors();
    }

    /**
     * waits till element will appear in page (only IF such element DID NOT EXIST BEFORE).
     *
     * @param $sElement string element locator.
     * @param $iTime    int    seconds to wait
     */
    public function waitForElement($sElement, $iTime = 30)
    {
        $blRefreshed = false;
        for ($iSecond = 0; $iSecond <= $iTime; $iSecond++) {
            if ($iSecond >= $iTime && !$blRefreshed) {
                $blRefreshed = true;
                $iSecond = 0;
                $this->refresh();
            } else if ($iSecond >= $iTime) {
                $this->assertTrue($this->isElementPresent($sElement), "timeout while waiting for element " . $sElement);
            }
            try {
                if ($this->isElementPresent($sElement)) {
                    break;
                }
            } catch (Exception $e) {
            }
            sleep(1);
        }
    }

    /**
     * waits for element to show up (only IF such element ALREADY EXIST AS HIDDEN AND WILL BE SHOWN AS VISIBLE).
     *
     * @param string $locator element locator.
     * @param int $iTime number
     */
    public function waitForItemAppear($locator, $iTime = 90)
    {
        for ($second = 0; ; $second++) {
            if ($second >= $iTime) {
                $this->assertTrue($this->isElementPresent($locator), "timeout waiting for element " . $locator);
                $this->assertTrue($this->isVisible($locator), "element " . $locator . " is not visible");
            }
            try {
                if ($this->isElementPresent($locator)) {
                    if ($this->isVisible($locator)) {
                        break;
                    }
                }
            } catch (Exception $e) {
                echo $e->getMessage();
            }
            sleep(1);
        }
    }

    /**
     * waits for element to disappear (only IF such element WILL BE MARKED AS NOT VISIBLE).
     *
     * @param string $locator element locator.
     */
    public function waitForItemDisappear($locator)
    {
        for ($second = 0; ; $second++) {
            if ($second >= 90) {
                $this->assertFalse($this->isVisible($locator), "timeout. Element " . $locator . " still visible");
            }
            try {
                if (!$this->isVisible($locator)) {
                    break;
                }
            } catch (Exception $e) {
            }
            sleep(1);
        }
    }

    /**
     * Waits till text will appear in page. If array is passed, waits for any of texts in array to appear.
     *
     * @param string $textMsg text.
     * @param bool $printSource print source (default false).
     * @param int $timeout timeout (default 90).
     */
    public function waitForText($textMsg, $printSource = false, $timeout = 90)
    {
        if (is_array($textMsg)) {
            $aMsg = $textMsg;
        } else {
            $aMsg[] = $textMsg;
        }
        for ($second = 0; ; $second++) {
            if ($second >= $timeout) {
                if ($printSource) {
                    echo "<hr> " . $this->getHtmlSource() . " <hr>";
                }
                $this->assertTrue(false, "Timeout while waiting for text: " . implode(' | ', $aMsg));
                break;
            }
            try {
                foreach ($aMsg as $textLine) {
                    if ($this->isTextPresent($textLine)) {
                        break 2;
                    }
                }
            } catch (Exception $e) {
            }
            sleep(1);
        }
    }

    /**
     * waits till text will disappear from page.
     *
     * @param string $textLine text.
     */
    public function waitForTextDisappear($textLine)
    {
        for ($second = 0; ; $second++) {
            if ($second >= 90) {
                $this->assertFalse($this->isTextPresent($textLine), "timeout. Text " . $textLine . " still visible");
            }
            try {
                if (!$this->isTextPresent($textLine)) {
                    break;
                }
            } catch (Exception $e) {
            }
            sleep(1);
        }
    }

//---------------------------------- Admin side only functions --------------------------

    /**
     * login to admin with default admin pass and opens needed menu.
     *
     * @param string $menuLink1 menu link (e.g. master settings, shop settings).
     * @param string $menuLink2 sub menu link (e.g. administer products, discounts, vat).
     * @param string $editElement element to check in edit frame (optional).
     * @param string $listElement element to check in list frame (optional).
     * @param bool $forceMainShop force main shop.
     * @param string $user shop admin username.
     * @param string $pass shop admin password.
     */
    public function loginAdmin($menuLink1, $menuLink2, $editElement = null, $listElement = null, $forceMainShop = false, $user = "admin@myoxideshop.com", $pass = "admin0303")
    {
        $this->selectWindow(null);
        $this->windowMaximize(null);
        $this->selectFrame("relative=top");
        $this->open(shopURL . "_cc.php?no_redirect=1");
        $this->open(shopURL . "admin");
        $this->checkForErrors();
        $this->assertTrue($this->isElementPresent("user"), "Admin login page failed to load");
        $this->assertTrue($this->isElementPresent("pwd"), "Admin login page failed to load");
        $this->type("user", $user);
        $this->type("pwd", $pass);
        $this->select("chlanguage", "label=English");
        $this->select("profile", "label=Standard");
        $this->click("//input[@type='submit']");
        $this->waitForElement("nav");
        $this->selectFrame("relative=top");
        $this->selectFrame("navigation");
        /* if ( OXID_VERSION_EE ) : */
        if (isSUBSHOP && !$forceMainShop) { // selecting active subshop
            $this->selectAndWaitFrame("selectshop", "label=subshop", "edit");
        }
        /* endif; */
        $this->waitForElement("link=" . $menuLink1);
        $this->checkForErrors();
        $this->click("link=" . $menuLink1);
        $this->clickAndWaitFrame("link=" . $menuLink2, "edit");
        //testing edit frame for errors
        $this->frame("edit", $editElement);
        //testing list frame for errors
        $this->frame("list", $listElement);
    }

    /**
     * login to admin for PayPal shop with admin pass and opens needed menu.
     *
     * @param string $menuLink1 menu link (e.g. master settings, shop settings).
     * @param string $menuLink2 sub menu link (e.g. administer products, discounts, vat).
     * @param string $editElement element to check in edit frame (optional).
     * @param string $listElement element to check in list frame (optional).
     * @param bool $forceMainShop force main shop.
     * @param string $user shop admin username.
     * @param string $pass shop admin password.
     */
    public function loginAdminForModule($menuLink1, $menuLink2, $editElement = null, $listElement = null, $forceMainShop = false, $user = "admin", $pass = "admin")
    {
        $this->selectWindow(null);
        $this->windowMaximize(null);
        $this->selectFrame("relative=top");
        $this->open(shopURL . "_cc.php");
        $this->open(shopURL . "admin");
        $this->checkForErrors();
        $this->assertTrue($this->isElementPresent("user"), "Admin login page failed to load");
        $this->assertTrue($this->isElementPresent("pwd"), "Admin login page failed to load");
        $this->type("user", $user);
        $this->type("pwd", $pass);
        $this->select("chlanguage", "label=English");
        $this->select("profile", "label=Standard");
        $this->click("//input[@type='submit']");
        $this->waitForElement("nav");
        $this->selectFrame("relative=top");
        $this->selectFrame("navigation");

        /* if ( OXID_VERSION_EE ) : */
        if (isSUBSHOP && !$forceMainShop) { // selecting active subshop
            $this->selectAndWaitFrame("selectshop", "label=subshop", "edit");
        }
        /* endif; */

        $this->waitForElement("link=" . $menuLink1);
        $this->checkForErrors();
        $this->click("link=" . $menuLink1);
        $this->clickAndWaitFrame("link=" . $menuLink2, "edit");

        //testing edit frame for errors
        $this->frame("edit", $editElement);

        //testing list frame for errors
        $this->frame("list", $listElement);
    }

    /**
     * selects other menu in admin interface.
     *
     * @param string $menuLink1 menu link (e.g. master settings, shop settings).
     * @param string $menuLink2 sub menu link (e.g. administer products, discounts, vat).
     * @param string $editElement element to check in edit frame (optional).
     * @param string $listElement element to check in list frame (optional).
     */
    public function selectMenu($menuLink1, $menuLink2, $editElement = null, $listElement = null)
    {
        $this->selectWindow(null);
        $this->selectFrame("relative=top");
        $this->selectFrame("navigation");
        $this->waitForElement("link=" . $menuLink1);
        $this->checkForErrors();
        $this->click("link=" . $menuLink1);
        $this->clickAndWaitFrame("link=" . $menuLink2, "edit");

        //testing edit frame for errors
        $this->frame("edit", $editElement);

        //testing list frame for errors
        $this->frame("list", $listElement);
    }

    /**
     * select frame in Admin interface.
     *
     * @param string $frameLocator name of needed admin frame.
     * @param string $frameElement name of element to check (optional).
     */
    public function frame($frameLocator, $frameElement = null)
    {
        $this->selectFrame("relative=top");
        $this->selectFrame("basefrm");

        $this->waitForElement($frameLocator);

        $this->selectFrame($frameLocator);

        if ($frameElement && $frameElement != "") { //additional checking if element is loaded (optional)
            sleep(1);
            $this->waitForElement($frameElement);
        }
        $this->checkForErrors();
    }

    /**
     * selects element and waits till needed frame will be loaded. same frame as before will be selected.
     *
     * @param string $locator select list locator.
     * @param string $selection option to select.
     * @param string $frame frame which should be also loaded (this frame will be loaded after current frame is loaded).
     * @param string $element element locator for additional check if page is fully loaded (optional).
     */
    public function selectAndWaitFrame($locator, $selection, $frame, $element = null)
    {
        if (!$this->isElementPresent($locator)) {
            $this->waitForElement($locator);
        }
        $this->select($locator, $selection);
        $this->waitForFrameToLoad($frame, "90000");
        sleep(1);
        if ($element) {
            $this->waitForElement($element);
        }
        $this->checkForErrors();
    }

    /**
     * selects element and waits till needed frame will be loaded. same frame as before will be selected.
     *
     * @param string $locator select list locator.
     * @param string $frame frame which should be also loaded (this frame will be loaded after current frame is loaded).
     * @param string $element element locator for additional check if page is fully loaded (optional).
     * @param int $sleep seconds to wait, default 1.
     */
    public function clickAndWaitFrame($locator, $frame, $element = null, $sleep = 1)
    {
        if (!$this->isElementPresent($locator)) {
            $this->waitForElement($locator);
        }
        $this->setTimeout(90);
        $this->click($locator);
        $this->waitForFrameToLoad($frame, "90000");
        sleep($sleep);
        if ($element) {
            $this->waitForElement($element);
        }
        $this->checkForErrors();
    }

    /**
     * clicks entered link in list frame and selects edit frame.
     *
     * @param string $linkLocator link name or tab name that is pressed.
     * @param string $elementLocator locator for element which will be checked for page loading success (optional).
     * @param int $sleep seconds to wait, default 1.
     */
    public function openTab($linkLocator, $elementLocator = "btn.help", $sleep = 1)
    {
        $frameLocator = "edit";
        $this->click($linkLocator);
        $this->waitForFrameToLoad($frameLocator, "90000");
        sleep($sleep);
        $this->waitForElement($linkLocator);
        $this->assertTrue($this->isElementPresent($linkLocator), "problems with reloading frame. Element " . $linkLocator . " not found in it.");
        $this->checkForErrors();
        $this->frame($frameLocator, $elementLocator);
    }

    /**
     * click button and confirms dialog.
     *
     * @param string $locator locator for delete button.
     * @param string $element locator for element which will be checked for page loading success.
     * @param string $frame frame which should be also loaded (this frame will be loaded after current frame is loaded).
     */
    public function clickAndConfirm($locator, $element = null, $frame = "edit")
    {
        if (!$this->isElementPresent($locator)) {
            $this->waitForElement($locator);
        }
        $this->click($locator);
        sleep(1);
        $this->getConfirmation();
        $this->waitForFrameToLoad($frame, "90000");
        if ($element) {
            sleep(1);
            $this->waitForElement($element);
        }
        $this->checkForErrors();
    }

//---------------------------- Ajax functions for admin side ------------------------------------------------

    /**
     * selects popUp window and waits till it is fully loaded.
     * @param string $popUpElement element used to check if popUp is fully loaded.
     */
    public function usePopUp($popUpElement = "//div[@id='container1_c']/table/tbody[2]/tr[1]/td[1]")
    {
        $this->waitForPopUp("ajaxpopup", "90000");
        $this->selectWindow("name=ajaxpopup");
        $this->windowMaximize("name=ajaxpopup");
        $this->waitForElement($popUpElement);
        sleep(1);
        $this->checkForErrors();
    }

    /**
     * waits for element to show up in specific place.
     * @param string $value expected text to show up.
     * @param string $locator place where specified text must show up.
     */
    public function waitForAjax($value, $locator)
    {
        for ($second = 0; ; $second++) {
            if ($second >= 90) {
                $this->assertTrue($this->isElementPresent($locator), "Ajax timeout");
                $this->assertEquals($value, $this->getText($locator));
            }
            try {
                if ($this->isElementPresent($locator)) {
                    if ($value == $this->getText($locator)) {
                        return;
                    }
                }
            } catch (Exception $e) {
            }
            sleep(1);
        }
        $this->checkForErrors();
    }

    /**
     * drags and drops element to specified location.
     * @param string $item element which will be dragged and dropped.
     * @param string $container place where to drop specified element.
     */
    public function dragAndDrop($item, $container)
    {
        $this->click($item);
        $this->checkForErrors();
        $this->dragAndDropToObject($item, $container);
    }

//------------------------ Subshop related functions ----------------------------------------

    /**
     * login to admin with admin pass, selects subshop and opens needed menu.
     * @param string $menuLink1 menu link (e.g. master settings, shop settings).
     * @param string $menuLink2 sub menu link (e.g. administer products, discounts, vat).
     */
    public function loginSubshopAdmin($menuLink1, $menuLink2)
    {
        $this->selectWindow(null);
        $this->windowMaximize(null);
        $this->selectFrame("relative=top");
        $this->open(shopURL . "_cc.php");
        $this->open(shopURL . "admin");
        $this->checkForErrors();
        $this->type("user", "admin@myoxideshop.com");
        $this->type("pwd", "admin0303");
        $this->select("chlanguage", "label=English");
        $this->select("profile", "label=Standard");
        $this->click("//input[@type='submit']");
        $this->waitForElement("nav");
        $this->selectFrame("relative=top");
        $this->selectFrame("navigation");
        $this->selectAndWaitFrame("selectshop", "label=subshop", "edit");
        $this->waitForElement("link=" . $menuLink1);
        $this->checkForErrors();
        $this->click("link=" . $menuLink1);
        $this->clickAndWaitFrame("link=" . $menuLink2, "edit");
        //testing edit frame for errors
        $this->frame("edit");
        //testing list frame for errors
        $this->frame("list");
    }

    /**
     * opens subshop frontend and switch to EN language.
     */
    public function openSubshopFrontend()
    {
        $this->openShop(false, true);
    }

//---------------------------- Setup related functions ------------------------------
    /**
     * prints error message, closes active browsers windows and stops.
     * @param string $sErrorMsg message to display about error place (more easy to find for programmers).
     * @param Exception $oErrorException Exception to throw on error.
     */
    public function stopTesting($sErrorMsg, $oErrorException = null)
    {
        if ($oErrorException) {
            try {
                $this->onNotSuccessfulTest($oErrorException);
            } catch (Exception $oE) {
                if ($oE instanceof PHPUnit_Framework_ExpectationFailedException) {
                    $sErrorMsg .= "\n\n---\n" . $oE->getCustomMessage();
                }
            }
        }
        echo $sErrorMsg;
        echo " Selenium tests terminated.";
        $this->stop();
        exit(1);
    }

//----------------------------- eFire modules for shop ------------------------------------
    /**
     * downloads eFire connector.
     *
     * @param string $sNameEfi user name for eFire.
     * @param string $sPswEfi user password for eFire.
     */
    public function downloadConnector($sNameEfi, $sPswEfi)
    {
        $this->selectFrame("relative=top");
        $this->selectFrame("navigation");
        $this->waitForElement("link=OXID eFire");
        $this->checkForErrors();
        $this->click("link=OXID eFire");
        $this->clickAndWaitFrame("link=Shop connector", "edit");

        //testing edit frame for errors
        $this->frame("edit");
        $this->assertFalse($this->isTextPresent("Shop connector downloaded successfully"));
        $this->type("etUsername", $sNameEfi);
        $this->type("etPassword", $sPswEfi);
        $this->clickAndWait("etSubmit");
        $this->assertTrue($this->isTextPresent("Shop connector downloaded successfully"), "connector was not downloaded successfully");
        $this->clearTmp();
        echo " connector downloaded successfully. ";
    }

//----------------------------- new templates for eShop frontend ------------------------------------
    /**
     * login customer by using login fly out form.
     *
     * @param string $userName user name (email).
     * @param string $userPass user password.
     * @param boolean $waitForLogin if needed to wait until user get logged in.
     */
    public function loginInFrontend($userName, $userPass, $waitForLogin = true)
    {
        $this->selectWindow(null);
        $this->click("//ul[@id='topMenu']/li[1]/a");
        $this->waitForItemAppear("loginBox");
        $this->type("//div[@id='loginBox']//input[@name='lgn_usr']", $userName);
        $this->type("//div[@id='loginBox']//input[@name='lgn_pwd']", $userPass);
        if ($waitForLogin) {
            $this->clickAndWait("//div[@id='loginBox']//button[@type='submit']", "//a[@id='logoutLink']");
        } else {
            $this->clickAndWait("//div[@id='loginBox']//button[@type='submit']");
        }
    }

    /**
     * mouseOver element and then click specified link.
     * @param string $element1 mouseOver element.
     * @param string $element2 clickable element.
     */
    public function mouseOverAndClick($element1, $element2)
    {
        $this->mouseOver($element1);
        $this->waitForItemAppear($element2);
        $this->clickAndWait($element2);
    }

    /**
     * performs search for selected parameter.
     * @param string $searchParam search parameter.
     */
    public function searchFor($searchParam)
    {
        $this->type("//input[@id='searchParam']", $searchParam);
        $this->keyPress("searchParam", "\\13"); //presing enter key
        $this->waitForPageToLoad();
        $this->checkForErrors();
    }

    /**
     * opens basket.
     * @param string $language active language in shop.
     */
    public function openBasket($language = "English")
    {
        if ($language == 'Deutsch') {
            $sLink = "Warenkorb zeigen";
        } else {
            $sLink = "Display cart";
        }
        $this->click("//div[@id='miniBasket']/img");
        $this->waitForItemAppear("//div[@id='basketFlyout']//a[text()='" . $sLink . "']");
        $this->clickAndWait("//div[@id='basketFlyout']//a[text()='" . $sLink . "']");
    }

    /**
     * selects specified value from dropdown (sorting, items per page etc).
     *
     * @param int $elementId drop down element id.
     * @param string $itemValue item to select.
     * @param string $extraIdent additional identification for element.
     */
    public function selectDropDown($elementId, $itemValue = '', $extraIdent = '')
    {
        $this->assertTrue($this->isElementPresent($elementId));
        $this->assertFalse($this->isVisible("//div[@id='" . $elementId . "']//ul"));
        $this->click("//div[@id='" . $elementId . "']//p");
        $this->waitForItemAppear("//div[@id='" . $elementId . "']//ul");
        if ('' == $itemValue) {
            $this->clickAndWait("//div[@id='" . $elementId . "']//ul/" . $extraIdent . "/a");
        } else {
            $this->clickAndWait("//div[@id='" . $elementId . "']//ul/" . $extraIdent . "/a[text()='" . $itemValue . "']");
        }
    }

    /**
     * selects specified value from dropdown (for multidimensional variants).
     *
     * @param string $elementId container id.
     * @param int $elementNr select list number (e.g. 1, 2).
     * @param string $itemValue item to select.
     * @param string $textMsg text that must appear after selecting md variant.
     */
    public function selectVariant($elementId, $elementNr, $itemValue, $textMsg = '')
    {
        $this->assertTrue($this->isElementPresent($elementId));
        $this->assertFalse($this->isVisible("//div[@id='" . $elementId . "']/div[" . $elementNr . "]//ul"));
        $this->click("//div[@id='" . $elementId . "']/div[" . $elementNr . "]//p");
        $this->waitForItemAppear("//div[@id='" . $elementId . "']/div[" . $elementNr . "]//ul");
        $this->click("//div[@id='" . $elementId . "']/div[" . $elementNr . "]//ul//a[text()='" . $itemValue . "']");
        if (!empty($textMsg)) {
            $this->waitForText($textMsg);
        } else {
            $this->waitForPageToLoad("90000");
        }
    }

    /**
     * executes given sql. for EE version cash is also cleared.
     * @param string $sql sql line.
     */
    public function executeSql($sql)
    {
        oxDb::getDb()->execute($sql);
        if (OXID_VERSION_EE) :
            oxDb::getDb()->execute("delete from oxcache");
        endif;
    }

    /**
     * gets clean heading text without any additional info as rss labels and so..
     * @param string $element path to element.
     * @return string
     */
    public function getHeadingText($element)
    {
        $text = $this->getText($element);
        if ($this->isElementPresent($element . "/a")) {
            $search = $this->getText($element . "/a");
            $text = str_replace($search, "", $text);
        }
        return trim($text);
    }

    /**
     * selects shop language in frontend.
     * @param string $language language title.
     */
    public function switchLanguage($language)
    {
        $this->click("languageTrigger");
        $this->waitForItemAppear("languages");
        $this->clickAndWait("//ul[@id='languages']//li/a/span[text()='" . $language . "']");
        $this->assertFalse($this->isVisible("//ul[@id='languages']"));
    }

    // --------------------------- trusted shops ------------------------------

    /**
     * login to trusted shops in admin.
     * @param string $link1
     * @param string $link2
     */
    public function loginAdminTs($link1 = "link=Seal of quality", $link2 = "link=Trusted Shops")
    {
        oxDb::getInstance()->getDb()->Execute("UPDATE `oxconfig` SET `OXVARVALUE` = 0xce92 WHERE `OXVARNAME` = 'sShopCountry';");
        $this->selectWindow(null);
        $this->windowMaximize(null);
        $this->selectFrame("relative=top");
        $this->open(shopURL . "_cc.php");
        $this->open(shopURL . "admin");
        $this->checkForErrors();
        $this->type("user", "admin@myoxideshop.com");
        $this->type("pwd", "admin0303");
        $this->select("chlanguage", "label=English");
        $this->select("profile", "label=Standard");
        $this->click("//input[@type='submit']");
        $this->waitForElement("nav");
        $this->selectFrame("relative=top");
        $this->selectFrame("navigation");
        /* if ( OXID_VERSION_EE ) : */
        if (isSUBSHOP) { // selecting active subshop
            $this->selectAndWaitFrame("selectshop", "label=subshop", "edit");
        }
        /* endif; */
        $this->waitForElement($link1);
        $this->checkForErrors();
        $this->click($link1);
        $this->clickAndWaitFrame($link2, "edit");

        //testing edit frame for errors
        $this->frame("edit");
    }

    /**
     * Checks which tables of the db changed and then restores these tables.
     *
     * Uses dump file '/tmp/tmp_db_dump' for comparison and restoring.
     *
     * @param string $sTmpPrefix temp file name
     *
     * @throws Exception on error while restoring db
     *
     * @return null
     */
    public function restoreDB($sTmpPrefix = null)
    {
        $myConfig = oxRegistry::getConfig();

        $sUser = $myConfig->getConfigParam('dbUser');
        $sPass = $myConfig->getConfigParam('dbPwd');
        $sDbName = $myConfig->getConfigParam('dbName');
        $sHost = $myConfig->getConfigParam('dbHost');
        if (!$sTmpPrefix) {
            $sTmpPrefix = 'tmp_db_dump';
        }
        $demo = oxCCTempDir . '/' . $sTmpPrefix . '_' . $sDbName;

        $sCmd = 'mysql -h' . escapeshellarg($sHost) . ' -u' . escapeshellarg($sUser) . ' -p' . escapeshellarg($sPass) . ' --default-character-set=utf8 ' . escapeshellarg($sDbName) . '  < ' . escapeshellarg($demo) . ' 2>&1';
        exec($sCmd, $sOut, $ret);
        $sOut = implode("\n", $sOut);
        if ($ret > 0) {
            throw new Exception("ERROR: Failed restoring database after test: " . $sOut . " " . $ret . " " . $sCmd);
        }
    }

    /**
     * Creates a dump of the current database, stored in the file '/tmp/tmp_db_dump'
     * the dump includes the data and sql insert statements.
     *
     * @param string $sTmpPrefix temp file name.
     *
     * @throws Exception on error while dumping.
     *
     * @return null
     */
    public function dumpDB($sTmpPrefix = null)
    {
        $time = microtime(true);
        $myConfig = oxRegistry::getConfig();

        $sUser = $myConfig->getConfigParam('dbUser');
        $sPass = $myConfig->getConfigParam('dbPwd');
        $sDbName = $myConfig->getConfigParam('dbName');
        $sHost = $myConfig->getConfigParam('dbHost');
        if (!$sTmpPrefix) {
            $sTmpPrefix = 'tmp_db_dump';
        }
        $demo = oxCCTempDir . '/' . $sTmpPrefix . '_' . $sDbName;

        $sCmd = 'mysqldump -h' . escapeshellarg($sHost) . ' -u' . escapeshellarg($sUser) . ' -p' . escapeshellarg($sPass) . ' --add-drop-table ' . escapeshellarg($sDbName) . '  > ' . escapeshellarg($demo);
        exec($sCmd, $sOut, $ret);
        $sOut = implode("\n", $sOut);
        if ($ret > 0) {
            throw new Exception($sOut);
        }
        echo("db Dumptime: " . (microtime(true) - $time) . "\n");
    }

    /**
     * Call shop seleniums connector to execute code in shop.
     * @example call to update information to database.
     *
     * @param string $sCl class name.
     * @param string $sFnc function name.
     * @param string $sOxid id of object.
     * @param array $aClassParams params to set to object.
     * @param string $sShopId object shop id.
     *
     * @return void
     */
    public function callShopSC($sCl, $sFnc, $sOxid = null, $aClassParams = array(), $sShopId = null)
    {
        $oConfig = oxRegistry::getConfig();

        $sShopUrl = $oConfig->getShopMainUrl() . '/_sc.php';

        $sScFilePath = $oConfig->getConfigParam('sShopDir') . '/_sc.php';

        if (!file_exists($sScFilePath)) {
            $this->fail("File not found: $sScFilePath");
        }

        $sClassParams = '';
        foreach ($aClassParams as $sParamKey => $sParamValue) {
            if (is_array($sParamValue)) {
                foreach ($sParamValue as $sSubParamKey => $sSubParamValue) {
                    $sSubParamValue = urlencode($sSubParamValue);
                    $sClassParams = $sClassParams . "&" . "classparams[" . $sParamKey . "][" . $sSubParamKey . "]=" . $sSubParamValue;
                }
            } else {
                $sParamValue = urlencode($sParamValue);
                $sClassParams = $sClassParams . "&" . "classparams[" . $sParamKey . "]=" . $sParamValue;
            }
        }
        $sParams = "?cl=" . $sCl . "&fnc=" . $sFnc
            . (!empty($sOxid) ? ("&oxid=" . $sOxid) : "")
            . (!empty($sClassParams) ? $sClassParams : "");


        // Pass shopId as to change in different shop we need to make it active.
        if ($sShopId && oxSHOPID != 'oxbaseshop') {
            $sParams .= "&shp=" . $sShopId;
        } elseif (isSUBSHOP) {
            $sParams .= "&shp=" . oxSHOPID;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $sShopUrl . $sParams);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Cache-Control: no-cache;'));

        curl_setopt($ch, CURLOPT_USERAGENT, "OXID-SELENIUMS-CONNECTOR");
        $sRes = curl_exec($ch);
        curl_close($ch);
    }

    /**
     * Check if object parameters have same value as expected.
     *
     * @param string $sClassName class name.
     * @param string $sOxid id of object.
     * @param array $aClassParams params to set to object.
     *
     * @return bool
     */
    public function isObjectCorrect($sClassName, $sOxid = null, $aClassParams = null)
    {
        // We might not have oxid if object is created with seleniums.
        // In such case we take last create record.
        if (!$sOxid) {
            $sOxid = $this->getLatestCreateRowId($sClassName);
            // We cannot create and check object if we do not have its id.
            if (!$sOxid) {
                return false;
            }
        }

        $oObject = $this->getObject($sClassName, $sOxid);
        // Check if object exist. We cannot perform any check on not existing object.
        if (null === $oObject) {
            return false;
        }

        foreach ($aClassParams as $sParamKey => $sParamValue) {
            $sDBFieldName = $this->getDBFieldName($sClassName, $sParamKey);
            if (!isset($oObject->$sDBFieldName->value) || $oObject->$sDBFieldName->value != $sParamValue) {
                return false;
            }
        }

        return true;
    }

    /**
     * Load and return object.
     *
     * @param string $sClassName class name.
     * @param string $sOxid object id.
     *
     * @return object|null
     */
    public function getObject($sClassName, $sOxid)
    {
        $oObject = oxNew($sClassName);
        if (!$oObject->load($sOxid)) {
            $oObject = null;
        }
        return $oObject;
    }

    /**
     * Get id of latest created row.
     * @param string $sClassName class name.
     * @return string|null
     */
    public function getLatestCreateRowId($sClassName)
    {
        $sOxid = null;
        $oDb = oxDb::getDb(oxDb::FETCH_MODE_ASSOC);

        $sTableName = $this->getTableNameFromClassName($sClassName);
        $sSql = 'SELECT OXID FROM ' . $sTableName . ' ORDER BY OXTIMESTAMP LIMIT 1';
        $rs = $oDb->select($sSql);

        if ($rs != false && $rs->recordCount() > 0) {
            $aFields = $rs->fields;
            $sOxid = $aFields['OXID'];
        }

        return $sOxid;
    }

    /**
     * Form DB field name by class and param names.
     * @param $sClassName
     * @param $sParamKey
     * @return string
     */
    public function getDBFieldName($sClassName, $sParamKey)
    {
        $sTableName = $this->getTableNameFromClassName($sClassName);
        $sDBFieldName = $sTableName . '__' . $sParamKey;
        return $sDBFieldName;
    }

    /**
     * Return table name by class name.
     * @param string $sClassName class name.
     * @return string
     */
    public function getTableNameFromClassName($sClassName)
    {
        $aClassNameWithoutS = array("oxarticle", "oxrole", "oxrating", "oxreview", "oxrecommlist", "oxmanufacturer", "oxvoucherserie");
        $aClassNameWithoutIes = array("oxcategory");

        $sTableName = strtolower($sClassName);
        if (in_array(strtolower($sClassName), $aClassNameWithoutS)) {
            $sTableName = strtolower($sClassName) . "s";
        } elseif (in_array(strtolower($sClassName), $aClassNameWithoutIes)) {
            $sTableName = substr(strtolower($sClassName), 0, -1) . "ies";
        }
        return $sTableName;
    }

    /**
     * Return main shop number.
     * To use to form link to main shop and etc.
     *
     * @return string
     */
    public function getShopVersionNumber()
    {
        return '5';
    }

    /**
     * Overrides original method - additionally can check
     * is text present by parsing text according given path
     *
     * @param string $sText text to be searched
     * @param string $sPath text path
     *
     * @return bool
     */
    public function isTextPresent($sText, $sPath = null)
    {
        if ($sPath) {
            $sParsedText = $this->getText($sPath);
            return (strpos($sParsedText, $sText) !== false);
        } else {
            try {
                return parent::isTextPresent($sText);
            } catch (Exception $e) {
                sleep(1);
                return parent::isTextPresent($sText);
            }
        }
    }

    /**
     * Fix for showing stack trace with phpunit 3.6 and later
     *
     * @param Exception $e
     * @throws PHPUnit_Framework_Error
     * @throws PHPUnit_Framework_IncompleteTestError
     * @throws PHPUnit_Framework_SkippedTestError
     */
    protected function onNotSuccessfulTest(Exception $e)
    {
        try {
            parent::onNotSuccessfulTest($e);
        } catch (PHPUnit_Framework_IncompleteTestError $e) {
            throw $e;
        } catch (PHPUnit_Framework_SkippedTestError $e) {
            throw $e;
        } catch (Exception $e_parent) {
            $error_msg = "\n\n" . PHPUnit_Util_Filter::getFilteredStacktrace($e);

            $oTrace = $e_parent;
            if (version_compare(PHPUnit_Runner_Version::id(), '3.7', '<')) {
                $oTrace = $e_parent->getTrace();
            }
            throw new PHPUnit_Framework_Error($e_parent->getMessage() . $error_msg, $e_parent->getCode(), $e_parent->getFile(), $e_parent->getLine(), $oTrace);
        }
    }

    /**
     * Returns data value from file
     *
     * @param $sVarName
     * @param $sFilePath
     * @return string
     */
    public function getArrayValueFromFile($sVarName, $sFilePath)
    {
        $aData = null;
        if (file_exists($sFilePath)) {
            $aData = include $sFilePath;
        }

        return $aData[$sVarName];
    }

    /**
     * Opens a popup window (if a window with that ID isn't already open) and waits till page is loaded.
     *
     * @param string $sUrl the URL to open, which can be blank
     * @param string $sWindowID the JavaScript window ID of the window to select
     * @param string $sTimeout a timeout in milliseconds, after which this command will return with an error
     */
    public function openWindowAndWait($sUrl, $sWindowID, $sTimeout = "30000")
    {
        $this->openWindow($sUrl, $sWindowID);
        $this->selectWindow($sWindowID);
        $this->waitForPageToLoad($sTimeout);
    }

    /**
     * Skip test code until given date.
     *
     * @param string $sDate Date string in format 'Y-m-d'.
     *
     * @return bool
     */
    public function skipTestBlockUntil($sDate)
    {
        $blSkip = false;
        $oDate = DateTime::createFromFormat('Y-m-d', $sDate);
        if (time() >= $oDate->getTimestamp()) {
            $blSkip = true;
        }
        return $blSkip;
    }

    /**
     * Mark the test as skipped until given date.
     * Wrapper function for PHPUnit_Framework_Assert::markTestSkipped.
     *
     * @param string $sDate Date string in format 'Y-m-d'.
     * @param string $sMessage Message.
     *
     * @throws PHPUnit_Framework_SkippedTestError
     */
    public function markTestSkippedUntil($sDate, $sMessage = '')
    {
        $oDate = DateTime::createFromFormat('Y-m-d', $sDate);
        if (time() < $oDate->getTimestamp()) {
            $this->captureScreenshotOnFailure = false; // Workaround for phpunit 3.6, disable screenshots before skip!
            $this->markTestSkipped($sMessage);
        }
    }
}
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
 * @copyright (C) OXID eSales AG 2003-2017
 */

namespace OxidEsales\PayPalModule\Tests\Acceptance;

/**
 * @todo add dependency between external tests. If one fails next should not start.
 */
class AcceptanceTest extends \OxidEsales\TestingLibrary\AcceptanceTestCase
{
    const PAYPAL_LOGIN_BUTTON_ID_OLD = "id=submitLogin";
    const PAYPAL_LOGIN_BUTTON_ID_NEW = "id=btnLogin";

    const SELECTOR_ADD_TO_BASKET = "//form[@name='tobasketsearchList_1']//button";
    const SELECTOR_BASKET_NEXTSTEP = "//button[text()='Weiter zum nächsten Schritt']";

    const LOGIN_USERNAME = "testing_account@oxid-esales.dev";
    const LOGIN_USERPASS = "useruser";

    private $newPayPalUserInterface = true;
    const PAYPAL_FRAME_NAME = "injectedUl";
    const THANK_YOU_PAGE_IDENTIFIER = "Thank you";
    const IDENTITY_COLUMN_ORDER_PAYPAL_TAB_PRICE_VALUE = 2;

    /** @var int How much time to wait for pages to load. Wait time is multiplied by this value. */
    protected $_iWaitTimeMultiplier = 7;

    protected $retryTimes = 1;

    /**
     * Activates PayPal and adds configuration
     *
     * @param string $testSuitePath
     *
     * @throws \Exception
     */
    public function addTestData($testSuitePath)
    {
        parent::addTestData($testSuitePath);

        $this->callShopSC('oxConfig', null, null, array(
            'sOEPayPalTransactionMode' => array(
                'type' => 'select',
                'value' => 'Authorization',
                'module' => 'module:oepaypal'
            ),
            'sOEPayPalUsername' => array(
                'type' => 'str',
                'value' => $this->getLoginDataByName('sOEPayPalUsername'),
                'module' => 'module:oepaypal'
            ),
            'sOEPayPalPassword' => array(
                'type' => 'password',
                'value' => $this->getLoginDataByName('sOEPayPalPassword'),
                'module' => 'module:oepaypal'
            ),
            'sOEPayPalSignature' => array(
                'type' => 'str',
                'value' => $this->getLoginDataByName('sOEPayPalSignature'),
                'module' => 'module:oepaypal'
            ),
            'blOEPayPalSandboxMode' => array(
                'type' => 'bool',
                'value' => 1,
                'module' => 'module:oepaypal'
            ),
            'sOEPayPalSandboxUsername' => array(
                'type' => 'str',
                'value' => $this->getLoginDataByName('sOEPayPalSandboxUsername'),
                'module' => 'module:oepaypal'
            ),
            'sOEPayPalSandboxPassword' => array(
                'type' => 'password',
                'value' => $this->getLoginDataByName('sOEPayPalSandboxPassword'),
                'module' => 'module:oepaypal'
            ),
            'sOEPayPalSandboxSignature' => array(
                'type' => 'str',
                'value' => $this->getLoginDataByName('sOEPayPalSandboxSignature'),
                'module' => 'module:oepaypal'
            ),
            'blPayPalLoggerEnabled' => array(
                'type' => 'str',
                'value' => true,
                'module' => 'module:oepaypal'
            )
        ));

        $this->callShopSC(\OxidEsales\PayPalModule\Tests\Acceptance\PayPalLogHelper::class, 'cleanPayPalLog');
    }

    /**
     * Before we retry a PayPal test log the page source.
     * Log it in PayPal log.
     * Move log under different name.
     *
     * @param string $message
     */
    public function retryTest($message = '')
    {
        if (false !== stripos($message, 'Timeout')) {
            $this->callShopSC(\OxidEsales\PayPalModule\Tests\Acceptance\PayPalLogHelper::class, 'setLogPermissions');
            $this->callShopSC(\OxidEsales\PayPalModule\Core\Logger::class, 'log', null, null, [$this->getHtmlSource()]);
            $this->callShopSC(\OxidEsales\PayPalModule\Tests\Acceptance\PayPalLogHelper::class, 'renamePayPalLog');
        }
        parent::retryTest($message);
    }

    /**
     * Set up fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->clearCache();
        $this->clearCookies();
        $this->clearTemp();

        $this->callShopSC('oxConfig', null, null, [
            'sOEPayPalTransactionMode' => [
                'type' => 'select',
                'value' => 'Sale',
                'module' => 'module:oepaypal'
            ]]);

        $this->callShopSC(\OxidEsales\PayPalModule\Tests\Acceptance\PayPalLogHelper::class, 'cleanPayPalLog');
    }

    /**
     * Tear down fixture.
     */
    protected function tearDown()
    {
        $this->newPayPalUserInterface = true;

        parent::tearDown();
    }

    // ------------------------ PayPal module ----------------------------------

    /**
     * testing different countries with shipping rules assigned to this countries
     *
     * @group paypal_standalone
     * @group paypal_external
     */
    public function testForLoginUserChangeUserCountryToUnassignedPaymentMethod()
    {
        $this->addToBasket('1001');
        $this->loginToShopFrontend();

        $this->waitForElement('paypalExpressCheckoutButton');
        $this->clickNextStepInShopBasket();

        // Check that the user mail address exists and is the expected.
        $this->assertEquals("E-mail: testing_account@oxid-esales.dev SeleniumTestCase Äß'ü Testing acc for Selenium Mr Testing user acc Äß'ü PayPal Äß'ü Musterstr. Äß'ü 1 79098 Musterstadt Äß'ü Germany", $this->clearString($this->getText("//ul[@id='addressText']//li")), "User address is incorect");

        // Change to new one which has not PayPal assigned as payment method inside PayPal
        $this->changeCountryInBasketStepTwo('United States');
        $this->clickFirstStepInShopBasket();

        $this->assertFalse($this->isElementPresent('paypalPartnerLogo'), 'PayPal logo should not be displayed for US');
    }

    /**
     * testing PayPal payment selection
     *
     * @group paypal_standalone
     * @group paypal_external
     *
     */
    public function testPayPalRegularCheckoutPayment()
    {
        //Set transaction mode to Authorization because we want to capture manually via shop admin
        $this->callShopSC('oxConfig', null, null, [
            'sOEPayPalTransactionMode' => [
                'type' => 'select',
                'value' => 'Authorization',
                'module' => 'module:oepaypal'
            ]]);

        // Startup/configure shop
        $this->openShop();
        $this->switchLanguage("Deutsch");
        $this->searchFor("1001");

        // add found article to basket
        $this->clickAndWait(self::SELECTOR_ADD_TO_BASKET);

        $this->openBasket("Deutsch");
        $this->loginInFrontend(self::LOGIN_USERNAME, self::LOGIN_USERPASS);

        // advance to next step (choose address/Adresse wählen)
        $this->clickAndWait(self::SELECTOR_BASKET_NEXTSTEP);
        $this->click("userChangeAddress");
        // add remark/comment
        $this->waitForItemAppear("order_remark");
        $this->type("order_remark", "Testing paypal");

        $this->clickAndWait(self::SELECTOR_BASKET_NEXTSTEP);
        $this->click("payment_oxidpaypal");

        // go to PayPal page
        $this->clickAndWait(self::SELECTOR_BASKET_NEXTSTEP);

        $this->standardCheckoutWillBeUsed();
        $this->payWithPayPal();

        // returned to basket step 4 (verify)
        $this->assertElementPresent("//button[text()='Zahlungspflichtig bestellen']");
        $this->assertEquals("0,99 €", $this->getText("basketGrandTotal"), "Grand total price changed or didn't displayed");
        $this->assertEquals("Zahlungsart Ändern PayPal", $this->clearString($this->getText("orderPayment")));
        $this->assertEquals("Versandart Ändern Test S&H set", $this->clearString($this->getText("orderShipping")));
        $this->assertEquals("Adressen Ändern Rechnungsadresse E-Mail: testing_account@oxid-esales.dev SeleniumTestCase Äß'ü Testing acc for Selenium Herr Testing user acc Äß'ü PayPal Äß'ü Musterstr. Äß'ü 1 79098 Musterstadt Äß'ü Deutschland Ihre Mitteilung an uns Testing paypal", $this->clearString($this->getText("orderAddress")));
        $this->clickAndWait("//button[text()='Zahlungspflichtig bestellen']", 90);
        $this->assertTextPresent("Vielen Dank für Ihre Bestellung im OXID eShop", "Order is not finished successful");

        // Admin

        //Checking if order is saved in Admin
        $this->loginAdminForModule("Administer Orders", "Orders", "btn.help", "link=2");
        $this->assertEquals("Testing user acc Äß'ü", $this->getText("//tr[@id='row.1']/td[6]"));
        $this->assertEquals("PayPal Äß'ü", $this->getText("//tr[@id='row.1']/td[7]"), "Wrong user last name is displayed in order");
        $this->openListItem("2");

        // Go to PayPal tab to check all order info
        $this->frame("list");
        $this->clickAndWaitFrame("//a[contains(@href, '#oepaypalorder_paypal')]", 'edit');
        $this->frame("edit");
        $this->assertTextPresent("Shop payment status:", "record 'Shop payment status:' is not displayed in admin PayPal tab");
        $this->assertTextPresent("Full order price:", "record 'Full order price:': is not displayed in admin PayPal tab");
        $this->assertTextPresent("Captured amount:", "record 'Captured amount:': is not displayed in admin PayPal tab");
        $this->assertTextPresent("Refunded amount:", "Refunded amount:': is not displayed in admin PayPal tab");
        $this->assertTextPresent("Resulting payment amount:", "Resulting payment amount:': is not displayed in admin PayPal tab");
        $this->assertTextPresent("Voided amount:", "record 'Voided amount:': is not displayed in admin PayPal tab");
        $this->assertTextPresent("Money capture:", "Money capture:': is not displayed in admin PayPal tab");
        $this->assertTextPresent("Pending", "status 'Pending': is not displayed in admin PayPal tab");

        $basketPrice = "0,99";
        $capturedPrice = "0,00";
        $this->checkOrderPayPalTabPricesCorrect($basketPrice, $capturedPrice);

        $this->assertElementPresent("id=captureButton");
        $this->assertElementPresent("id=voidButton");

        $actionName = "authorization";
        $amount = "0.99";
        $paypalStatus = "Pending";
        $this->checkOrderPayPalTabHistoryCorrect($actionName, $amount, $paypalStatus);

        $quantity = "1";
        $productNumber = "1001";
        $productTitle = "Test product 1";
        $productGrossPrice = "0,99";
        $productTotalPrice = "0,99";
        $productVat = "19";
        $this->checkOrderPayPalTabProductsCorrect($quantity, $productNumber, $productTitle, $productGrossPrice, $productTotalPrice, $productVat);

        // Perform capturing
        $this->click("id=captureButton");
        $this->frame("edit");
        $this->clickAndWait("id=captureSubmit", 90);
        $this->waitForItemDisappear("id=captureSubmit");

        $basketPrice = "0,99";
        $capturedPrice = "0,99";
        $this->checkOrderPayPalTabPricesCorrect($basketPrice, $capturedPrice);

        $this->assertElementPresent("id=refundButton0", "Refunding is not available");
        $this->assertEquals("Completed", $this->getText("//b"), "Money status is not displayed in admin PayPal tab");

        $actionName = "capture";
        $amount = "0.99";
        $paypalStatus = "Completed";
        $this->checkOrderPayPalTabHistoryCorrect($actionName, $amount, $paypalStatus);

        $this->assertEquals("authorization", $this->getText("//table[@id='historyTable']/tbody/tr[3]/td[2]"), "Money status is not displayed in admin PayPal tab");
        $this->assertEquals("0.99 EUR", $this->getText("//table[@id='historyTable']/tbody/tr[3]/td[3]"));
        $this->assertEquals("Pending", $this->getText("//table[@id='historyTable']/tbody/tr[3]/td[4]"), "Money status is not displayed in admin PayPal tab");

        // Perform Refund and check all info
        $this->click("id=refundButton0");
        $this->clickAndWaitFrame("id=refundSubmit", 'edit');
        $this->waitForItemDisappear("id=refundSubmit");
        $this->assertEquals("refund", $this->getText("//table[@id='historyTable']/tbody/tr[2]/td[2]"), "Money status is not displayed in admin PayPal tab");
        $this->assertEquals("0.99 EUR", $this->getText("//table[@id='historyTable']/tbody/tr[2]/td[3]"));
        $this->assertEquals("Instant", $this->getText("//table[@id='historyTable']/tbody/tr[2]/td[4]"), "Money status is not displayed in admin PayPal tab");
        $this->assertEquals("capture", $this->getText("//table[@id='historyTable']/tbody/tr[3]/td[2]"), "Money status is not displayed in admin PayPal tab");
        $this->assertEquals("0.99 EUR", $this->getText("//table[@id='historyTable']/tbody/tr[3]/td[3]"));
        $this->assertEquals("Completed", $this->getText("//table[@id='historyTable']/tbody/tr[3]/td[4]"), "Money status is not displayed in admin PayPal tab");
        $this->assertEquals("authorization", $this->getText("//table[@id='historyTable']/tbody/tr[4]/td[2]"), "Money status is not displayed in admin PayPal tab");
        $this->assertEquals("0.99 EUR", $this->getText("//table[@id='historyTable']/tbody/tr[4]/td[3]"));
        $this->assertEquals("Pending", $this->getText("//table[@id='historyTable']/tbody/tr[4]/td[4]"), "Money status is not displayed in admin PayPal tab");
    }

    /**
     * Checkout a single product and change the quantity of the product to 5 afterards.
     *
     * @group paypal_standalone
     * @group paypal_external
     *
     */
    public function testPayPalRegularCheckoutAndChangeQuantityAfterwardsViaAdmin()
    {
        //Make an order with PayPal
        $this->openShop();
        $this->switchLanguage("Deutsch");
        $this->searchFor("1001");
        $this->clickAndWait(self::SELECTOR_ADD_TO_BASKET);
        $this->openBasket("Deutsch");
        $this->loginInFrontend(self::LOGIN_USERNAME, self::LOGIN_USERPASS);
        $this->clickAndWait(self::SELECTOR_BASKET_NEXTSTEP);

        $this->click("userChangeAddress");
        $this->waitForItemAppear("order_remark");
        $this->type("order_remark", "Testing paypal");
        $this->clickAndWait(self::SELECTOR_BASKET_NEXTSTEP);

        $this->click("name=sShipSet");
        $this->selectAndWait("sShipSet", "label=Test S&H set");
        $this->waitForItemAppear("payment_oxidpaypal");
        $this->click("id=payment_oxidpaypal");
        $this->clickAndWait(self::SELECTOR_BASKET_NEXTSTEP);

        $this->standardCheckoutWillBeUsed();
        $this->payWithPayPal();

        $this->assertElementPresent("//button[text()='Zahlungspflichtig bestellen']");
        $this->clickAndWait("//button[text()='Zahlungspflichtig bestellen']");
        $this->assertTextPresent("Vielen Dank für Ihre Bestellung im OXID eShop", "The order not finished successful");
        sleep(5);

        //Go to an admin and check this order nr
        $this->loginAdminForModule("Administer Orders", "Orders");
        $this->assertEquals("Testing user acc Äß'ü", $this->getText("//tr[@id='row.1']/td[6]"), "Wrong user name is displayed in order");
        $this->assertEquals("PayPal Äß'ü", $this->getText("//tr[@id='row.1']/td[7]"), "Wrong user last name is displayed in order");
        $this->openListItem("link=2");
        $this->assertTextPresent("Internal Status: OK");
        $this->assertTextPresent("Order No.: 2", "Order number is not displayed in admin");

        //Check user's order information in admin
        $this->assertEquals("1 *", $this->getText("//table[2]/tbody/tr/td[1]"), "Quantity of product is not correct in admin");
        $this->assertEquals("Test product 1", $this->getText("//td[3]"), "Purchased product name is not displayed in admin");
        $this->assertEquals("0,99 EUR", $this->getText("//td[5]"), "Unit price is not displayed in admin");
        $this->assertEquals("0,99", $this->getText("//table[@id='order.info']/tbody/tr[7]/td[2]"));

        $this->openTab("Products");
        $this->assertEquals("1", $this->getValue("//tr[@id='art.1']/td[1]/input"), "Quantity of product is not correct in admin");
        $this->assertEquals("0,99 EUR", $this->getText("//tr[@id='art.1']/td[7]"), "Unit price is not displayed in admin");
        $this->assertEquals("0,99 EUR", $this->getText("//tr[@id='art.1']/td[8]"), "Total price is not displayed in admin");

        //Update product quantities to 5
        $this->type("//tr[@id='art.1']/td[1]/input", "5");
        $this->clickAndWait("//input[@value='Update']");
        $this->assertEquals("0,99 EUR", $this->getText("//tr[@id='art.1']/td[7]"), "Unit price is not displayed in admin");
        $this->assertEquals("4,95 EUR", $this->getText("//tr[@id='art.1']/td[8]"), "Total price is incorrect after update");
        $this->assertEquals("4,95", $this->getText("//table[@id='order.info']/tbody/tr[7]/td[2]"));

        $this->openTab("Main");
        $this->assertEquals("Test S&H set", $this->getSelectedLabel("setDelSet"), "Shipping method is not displayed in admin");
        $this->assertEquals("PayPal", $this->getSelectedLabel("setPayment"));
    }

    /**
     * testing PayPal ECS in detail page and ECS in mini basket
     *
     * @group paypal_standalone
     * @group paypal_external
     *
     */
    public function testECS()
    {
        // Open shop and add product to the basket
        $this->openShop();
        $this->searchFor("1001");
        $this->clickAndWait("//ul[@id='searchList']/li/form/div/a[2]/span");
        $this->clickAndWait("id=toBasket");

        // Open mini basket
        $this->click("id=minibasketIcon");
        $this->assertElementPresent("//div[@id='paypalExpressCheckoutDetailsBox']/div/a", "No express PayPal button in mini cart");
        $this->assertElementPresent("id=paypalExpressCheckoutDetailsButton", "No express PayPal button in mini cart");
        $this->assertElementPresent("displayCartInPayPal", "No express PayPal checkbox for displaying cart in PayPal in mini cart");
        $this->assertTextPresent("Display cart in PayPal", "No express PayPal text about displaying cart in PayPal in mini cart");
        $this->assertElementPresent("id=paypalExpressCheckoutMiniBasketImage", "No express PayPal image in mini cart");
        $this->assertElementPresent("id=paypalHelpIconMiniBasket", "No express PayPal checkbox help button for displaying cart in PayPal in mini cart");

        // Open ECS in details page
        $this->clickAndWait("id=paypalExpressCheckoutDetailsButton");
        $this->assertElementPresent("//div[@id='popupECS']/p", "No Express PayPal popup appears");
        $this->assertElementPresent("id=actionNotAddToBasketAndGoToCheckout", "No button in PayPal popup");
        $this->assertElementPresent("id=actionAddToBasketAndGoToCheckout", "No button in PayPal popup");
        $this->assertElementPresent("link=open current cart", "No link open current cart in popup");
        $this->assertElementPresent("//div[@id='popupECS']/div/div/button", "No cancel button in PayPal popup");

        // Select add to basket and go to checkout
        $this->selectPayPalExpressCheckout("id=actionAddToBasketAndGoToCheckout");

        //Check what was communicated with PayPal
        $assertRequest = ['L_PAYMENTREQUEST_0_AMT0' => 0.99,
                          'PAYMENTREQUEST_0_AMT' => 1.98,
                          'L_PAYMENTREQUEST_0_QTY0' => 2,
                          'PAYMENTREQUEST_0_CURRENCYCODE' => 'EUR'];
        $assertResponse = ['ACK' => 'Success'];
        $this->assertLogData($assertRequest, $assertResponse);

        // Cancel order
        $this->clickAndWait("cancel_return");
        // Go to checkout with PayPal  with same amount in basket
        $this->clickAndWait("id=paypalExpressCheckoutDetailsButton");
        $this->clickAndWait("id=actionNotAddToBasketAndGoToCheckout");
        //Check what was communicated with PayPal
        $this->assertLogData($assertRequest, $assertResponse);

        // Cancel order
        $this->clickAndWait("cancel_return");

        // Go to home page and purchase via PayPal
        $this->assertTextPresent("2 x Test product 1", "Item quantity doesn't mach ot didn't displayed");
        $this->assertTextPresent("1,98 €", "Item price doesn't mach ot didn't displayed");
        $this->assertElementPresent("id=paypalHelpIconMiniBasket");
        $this->assertElementPresent("id=paypalExpressCheckoutMiniBasketBox");
        $this->assertElementPresent("displayCartInPayPal");
        $this->clickAndWait("id=paypalExpressCheckoutMiniBasketImage");
        $this->assertLogData($assertRequest, $assertResponse);

        $this->payWithPayPal();

        //Check what was communicated with PayPal
        $assertRequest = ['METHOD' => 'GetExpressCheckoutDetails'];
        $assertResponse = ['L_PAYMENTREQUEST_0_NAME0' => 'Test product 1',
                           'PAYMENTREQUEST_0_CURRENCYCODE' => 'EUR',
                           'L_PAYMENTREQUEST_0_QTY0' => '2',
                           'ACK' => 'Success'];
        $this->assertLogData($assertRequest, $assertResponse);

        $this->assertElementPresent("link=Test product 1", "Purchased product name is not displayed in last order step");
        $this->assertTextPresent("Item #: 1001", "Product number not displayed in last order step");
        $this->assertEquals("1,98 €", $this->getText("basketGrandTotal"), "Grand total price changed  or didn't displayed");
        $this->assertTextPresent("PayPal", "Payment method not displayed in last order step");
        $this->clickAndWait("//button[text()='Order now']");
        $this->assertTextPresent(self::THANK_YOU_PAGE_IDENTIFIER, "Order is not finished successful");
    }

    /**
     * testing paypal express button
     *
     * @group paypal_standalone
     * @group paypal_external
     *
     */
    public function testPayPalExpress2()
    {
        //Testing when user is logged in
        $this->openShop();
        $this->switchLanguage("Deutsch");
        $this->searchFor("1001");
        $this->clickAndWait(self::SELECTOR_ADD_TO_BASKET);
        $this->openBasket("Deutsch");

        $this->waitForElement("paypalExpressCheckoutButton");
        $this->assertElementPresent("paypalExpressCheckoutButton");
        $this->loginInFrontend(self::LOGIN_USERNAME, self::LOGIN_USERPASS);
        $this->waitForElement("paypalExpressCheckoutButton");
        $this->assertElementPresent("paypalExpressCheckoutButton", "PayPal express button not displayed in the cart");

        //Go to PayPal express
        $this->payWithPayPalExpressCheckout();

        //Check what was communicated with PayPal
        $assertRequest = ['METHOD' => 'GetExpressCheckoutDetails'];
        $assertResponse = ['PAYMENTREQUEST_0_AMT' => '0.99',
                           'PAYMENTREQUEST_0_CURRENCYCODE' => 'EUR',
                           'L_PAYMENTREQUEST_0_NAME0' => 'Test product 1',
                           'PAYMENTREQUEST_0_SHIPTONAME' => "Testing user acc Äß\\'ü PayPal Äß\\'ü",
                           'PAYMENTREQUEST_0_SHIPTOSTREET' => "Musterstr. Äß\\'ü 1",
                           'ACK' => 'Success'];
        $this->assertLogData($assertRequest, $assertResponse);

        //Testing when user is not logged in
        $this->clearCache();
        $this->openShop();
        $this->switchLanguage("Deutsch");
        $this->searchFor("1001");
        $this->clickAndWait(self::SELECTOR_ADD_TO_BASKET);
        $this->openBasket("Deutsch");

        $this->waitForElement("paypalExpressCheckoutButton");
        $this->assertElementPresent("paypalExpressCheckoutButton", "PayPal express button not displayed in the cart");

        //Go to PayPal express
        $this->payWithPayPalExpressCheckout();

        //Check what was communicated with PayPal
        $assertRequest = ['METHOD' => 'GetExpressCheckoutDetails'];
        $assertResponse = ['PAYMENTREQUEST_0_AMT' => '0.99',
                           'PAYMENTREQUEST_0_CURRENCYCODE' => 'EUR',
                           'L_PAYMENTREQUEST_0_NAME0' => 'Test product 1',
                           'ACK' => 'Success'];
        $this->assertLogData($assertRequest, $assertResponse);

        //User is on the 4th page
        $this->assertElementPresent("//button[text()='Zahlungspflichtig bestellen']");
        $this->assertEquals("Gesamtbetrag: 0,99 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[5]")));
        $this->assertEquals("Zahlungsart Ändern PayPal", $this->clearString($this->getText("orderPayment")));
        $this->assertEquals("Adressen Ändern Rechnungsadresse E-Mail: {$this->getLoginDataByName('sBuyerLogin')} {$this->getLoginDataByName('sBuyerFirstName')} {$this->getLoginDataByName('sBuyerLastName')} ESpachstr. 1 79111 Freiburg Deutschland", $this->clearString($this->getText("orderAddress")));
        $this->assertEquals("Versandart Ändern Test S&H set", $this->clearString($this->getText("orderShipping")));
        $this->clickAndWait("//button[text()='Zahlungspflichtig bestellen']");
        $this->assertTextPresent("Vielen Dank für Ihre Bestellung im OXID eShop", "Order is not finished successful");

        //Checking if order is saved in Admin
        $this->loginAdminForModule("Administer Orders", "Orders");
        $this->openListItem("2");

        $this->openTab("Main");
        $this->assertEquals("Test S&H set", $this->getSelectedLabel("setDelSet"));
    }

    /**
     * testing if express button is not visible when PayPal is not active
     *
     * @group paypal_standalone
     */
    public function testPayPalExpressWhenPayPalInactive()
    {
        //Disable PayPal
        $this->loginAdminForModule("Extensions", "Modules");
        $this->openListItem("PayPal");
        $this->frame("edit");
        $this->clickAndWait("module_deactivate");
        $this->assertElementPresent("id=module_activate", "The button Activate module is not displayed ");

        //After PayPal module is deactivated,  PayPal express button should  not be available in basket
        $this->clearCache();
        $this->openShop();
        $this->switchLanguage("Deutsch");
        $this->searchFor("1001");
        $this->clickAndWait(self::SELECTOR_ADD_TO_BASKET);
        $this->openBasket("Deutsch");
        $this->assertElementNotPresent("paypalExpressCheckoutBox", "PayPal should not be displayed, because Paypal is deactivated");
        $this->loginInFrontend(self::LOGIN_USERNAME, self::LOGIN_USERPASS);
        $this->assertElementNotPresent("paypalExpressCheckoutBox", "PayPal should not be displayed, because Paypal is deactivated");

        //On 2nd step
        $this->clickAndWait(self::SELECTOR_BASKET_NEXTSTEP);
        $this->waitForText("Lieferadresse");

        //On 3rd step
        $this->clickAndWait(self::SELECTOR_BASKET_NEXTSTEP);
        $this->waitForText("Bitte wählen Sie Ihre Versandart");
        $this->selectAndWait("sShipSet", "label=Standard");
        $this->assertEquals("Kosten: 3,90 €", $this->getText("shipSetCost"));
        $this->assertElementNotPresent("//input[@value='oxidpaypal']");
        $this->selectAndWait("sShipSet", "label=Test S&H set");
        $this->assertElementNotPresent("//input[@value='oxidpaypal']");

        // clearing cache as disabled module is cached
        $this->clearCache();
    }

    /**
     * Testing ability to change country in standard PayPal.
     * NOTE: this test originally asserted data on PayPal page.
     * ($this->assertFalse($this->isElementPresent("id=changeAddressButton"), "In standard PayPal there should be not possibility to change address");)

     *
     * @group paypal_standalone
     * @group paypal_external
     *
     */
    public function testPayPalStandard()
    {
        //Login to shop and go standard PayPal
        $this->openShop();
        $this->switchLanguage("English");
        $this->searchFor("1001");
        $this->clickAndWait(self::SELECTOR_ADD_TO_BASKET);
        $this->openBasket("English");
        $this->loginInFrontend(self::LOGIN_USERNAME, self::LOGIN_USERPASS);
        $this->clickNextStepInShopBasket();
        $this->assertTextPresent("Germany", "Users country should be Germany");
        $this->clickNextStepInShopBasket();
        $this->assertElementPresent("//input[@value='oxidpaypal']");
        $this->click("payment_oxidpaypal");
        $this->clickNextStepInShopBasket();

        $this->payWithPayPal();

        $this->assertTextPresent("PayPal", "Payment method not displayed in last order step");
        $this->clickAndWait("//button[text()='Order now']");
        $this->assertTextPresent(self::THANK_YOU_PAGE_IDENTIFIER, "Order is not finished successful");
    }


    /**
     * test if payment method PayPal is deactivated in shop backend, the PayPal express button should also disappear.
     *
     * @group paypal_standalone
     */
    public function testPayPalActive()
    {
        // Set PayPal payment inactive.
        $this->importSql(__DIR__ . '/testSql/setPayPalPaymentInactive.sql');

        //Go to shop to check is PayPal not visible in front end
        $this->openShop();
        $this->assertFalse($this->isElementPresent("paypalPartnerLogo"), "PayPal logo not shown in frontend page");
        $this->switchLanguage("Deutsch");
        $this->assertFalse($this->isElementPresent("paypalPartnerLogo"), "PayPal logo not shown in frontend page");
        $this->switchLanguage("English");

        //Go to basket and check is express PayPal not visible
        $this->searchFor("1001");
        $this->clickAndWait(self::SELECTOR_ADD_TO_BASKET);
        $this->openBasket("English");
        $this->assertFalse($this->isElementPresent("paypalExpressCheckoutButton"), "PayPal express button should be not visible in frontend");

        //Login to shop and go to the basket
        $this->loginInFrontend(self::LOGIN_USERNAME, self::LOGIN_USERPASS);
        $this->assertFalse($this->isElementPresent("paypalExpressCheckoutButton"), "PayPal express button should be not visible in frontend");
    }


    /**
     * test if discounts working correct with PayPal.
     *
     * @group paypal_standalone
     * @group paypal_external
     *
     */
    public function testPayPalDiscountsCategory()
    {
        // Add vouchers to shop
        $this->importSql(__DIR__ . '/testSql/newDiscounts_' . SHOP_EDITION . '.sql');

        //Go to shop and add product
        $this->openShop();
        $this->switchLanguage("English");
        $this->searchFor("1000");
        $this->clickAndWait(self::SELECTOR_ADD_TO_BASKET);
        $this->openBasket("English");

        //Login to shop and go to basket
        $this->loginInFrontend(self::LOGIN_USERNAME, self::LOGIN_USERPASS);
        $this->assertTextPresent("Test product 0");
        $this->assertTextPresent("Test product 1", "Purchased product name is not displayed");
        $this->assertTextPresent("+1");
        $this->assertEquals("5,00 €", $this->getText("basketGrandTotal"), "Grand total price changed or didn't displayed");
        $this->assertEquals("5,00 € \n10,00 €", $this->getText("//tr[@id='cartItem_1']/td[6]"), "price with discount not shown in basket");
        // Go to 2nd step
        $this->clickNextStepInShopBasket();

        //Go to 3rd step and select PayPal as payment method
        $this->clickNextStepInShopBasket();
        $this->waitForItemAppear("id=payment_oxidpaypal");
        $this->click("id=payment_oxidpaypal");
        $this->clickNextStepInShopBasket();

        $this->payWithPayPal();

        //Check what was communicated with PayPal
        $assertRequest = ['METHOD' => 'GetExpressCheckoutDetails'];
        $assertResponse = ['ACK' => 'Success',
                           'EMAIL' => $this->getLoginDataByName('sBuyerLogin'),
                           'SHIPTONAME' => "Testing user acc Äß\\'ü PayPal Äß\\'ü",
                           'AMT' => '5.00',
                           'L_NAME0' => 'Test product 0',
                           'L_NAME1' => 'Test product 1',
                           'L_NUMBER0' => '1000',
                           'L_NUMBER1' => '1001',
                           'L_QTY0' => '1',
                           'L_QTY1' => '1',
                           'L_AMT0' => '5.00',
                           'L_AMT1' => '0.00'];
        $this->assertLogData($assertRequest, $assertResponse);

        //Go to shop to finish the order
        $this->assertTextPresent("Test product 0", "Purchased product name is not displayed in last order step");
        $this->assertTextPresent("Test product 1", "Purchased product name is not displayed in last order step");
        $this->assertEquals("Item #: 1001", $this->getText("//tr[@id='cartItem_2']/td[2]/div[2]"), "Product number not displayed in last order step");
        $this->assertEquals("Item #: 1000", $this->getText("//tr[@id='cartItem_1']/td[2]/div[2]"), "Product number not displayed in last order step");
        $this->assertTextPresent("1 +1");
        $this->assertEquals("4,20 €", $this->getText("basketTotalProductsNetto"), "Neto price changed or didn't displayed");
        $this->assertEquals("plus 19% tax, amount: 0,80 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[2]")));
        $this->assertEquals("5,00 €", $this->getText("basketTotalProductsGross"), "Bruto price changed  or didn't displayed");
        $this->assertEquals("0,00 €", $this->getText("basketDeliveryGross"), "Shipping price changed  or didn't displayed");
        $this->assertEquals("5,00 €", $this->getText("basketGrandTotal"), "Grand total price changed or didn't displayed");

        $this->clickAndWait("//button[text()='Order now']");
        $this->assertTextPresent(self::THANK_YOU_PAGE_IDENTIFIER, "Order is not finished successful");

        //Go to admin and check the order
        $this->loginAdminForModule("Administer Orders", "Orders", "btn.help", "link=2");
        $this->assertEquals("Testing user acc Äß'ü", $this->getText("//tr[@id='row.1']/td[6]"), "Wrong user name is displayed in order");
        $this->assertEquals("PayPal Äß'ü", $this->getText("//tr[@id='row.1']/td[7]"), "Wrong user last name is displayed in order");
        $this->openListItem("2");
        $this->assertTextPresent("Internal Status: OK");
        $this->assertEquals("5,00 EUR", $this->getText("//td[5]"));
        $this->assertEquals("Billing Address: Company SeleniumTestCase Äß'ü Testing acc for Selenium Mr Testing user acc Äß'ü PayPal Äß'ü Musterstr. Äß'ü 1 79098 Musterstadt Äß'ü Germany E-mail: testing_account@oxid-esales.dev", $this->clearString($this->getText("//td[1]/table[1]/tbody/tr/td[1]")));
        $this->assertEquals("5,00", $this->getText("//table[@id='order.info']/tbody/tr[1]/td[2]"));
        $this->assertEquals("- 0,00", $this->getText("//table[@id='order.info']/tbody/tr[2]/td[2]"));
        $this->assertEquals("4,20", $this->getText("//table[@id='order.info']/tbody/tr[3]/td[2]"));
        $this->assertEquals("0,80", $this->getText("//table[@id='order.info']/tbody/tr[4]/td[2]"));
        $this->assertEquals("0,00", $this->getText("//table[@id='order.info']/tbody/tr[5]/td[2]"));
        $this->assertEquals("0,00", $this->getText("//table[@id='order.info']/tbody/tr[6]/td[2]"));
        $this->assertElementPresent("//table[@id='order.info']/tbody/tr[2]", "line with discount info is not displayed");
        $this->assertElementPresent("//table[@id='order.info']/tbody/tr[2]/td[1]", "line with discount info is not displayed");
        $this->assertElementPresent("//table[@id='order.info']/tbody/tr[2]/td[2]", "line with discount info is not displayed");
        $this->assertEquals("0,00", $this->getText("//table[@id='order.info']/tbody/tr[5]/td[2]"));
        $this->assertEquals("PayPal", $this->getText("//table[4]/tbody/tr[1]/td[2]"), "Payment method not displayed in admin");
        $this->assertEquals("Test S&H set", $this->getText("//table[4]/tbody/tr[2]/td[2]"), "Shipping method is not displayed in admin");
    }

    /**
     * test if few different discounts working correct with PayPal.
     *
     * @group paypal_standalone
     * @group paypal_external
     *
     */
    public function testPayPalDiscountsFromTill()
    {
        // Add vouchers to shop
        $this->importSql(__DIR__ . '/testSql/newDiscounts_' . SHOP_EDITION . '.sql');

        //Go to shop and add product
        $this->openShop();
        $this->switchLanguage("English");
        $this->searchFor("1004");
        $this->clickAndWait(self::SELECTOR_ADD_TO_BASKET);
        $this->openBasket("English");

        //Login to shop and go to basket
        $this->loginInFrontend(self::LOGIN_USERNAME, self::LOGIN_USERPASS);
        $this->assertTextPresent("Test product 4");

        $this->assertEquals("Discount discount from 10 till 20", $this->getText("//div[@id='basketSummary']/table/tbody/tr[2]/th"));
        $this->assertEquals("-0,30 €", $this->getText("//div[@id='basketSummary']/table/tbody/tr[2]/td"));
        $this->assertEquals("Grand total: 14,70 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[6]")), "Grand total is not displayed correctly");

        // Go to 2nd step
        $this->clickNextStepInShopBasket();

        //Go to 3rd step and select PayPal as payment method
        $this->clickNextStepInShopBasket();
        $this->waitForItemAppear("id=payment_oxidpaypal");
        $this->click("id=payment_oxidpaypal");
        $this->clickNextStepInShopBasket();

        //Go to PayPal
        $this->payWithPayPal();

        //Check what was communicated with PayPal
        $assertRequest = ['METHOD' => 'GetExpressCheckoutDetails'];
        $assertResponse = ['ACK' => 'Success',
                           'EMAIL' => $this->getLoginDataByName('sBuyerLogin'),
                           'SHIPTONAME' => "Testing user acc Äß\\'ü PayPal Äß\\'ü",
                           'AMT' => '14.70',
                           'ITEMAMT' => '15.00',
                           'TAXAMT' => '0.00',
                           'SHIPDISCAMT' => '-0.30',
                           'L_NAME0' => 'Test product 4',
                           'L_NAME1' => 'Test product 1',
                           'L_NUMBER0' => '1004',
                           'L_NUMBER1' => '1001',
                           'L_QTY0' => '1',
                           'L_QTY1' => '1',
                           'L_AMT0' => '15.00',
                           'L_AMT1' => '0.00'];
        $this->assertLogData($assertRequest, $assertResponse);

        //Go to last step to check the order
        $this->assertTextPresent("Test product 4", "Purchased product name is not displayed");
        $this->assertTextPresent("Test product 1", "Purchased product name is not displayed");
        $this->assertEquals("Item #: 1004", $this->getText("//tr[@id='cartItem_1']/td[2]/div[2]"), "Product number not displayed in last order step");
        $this->assertEquals("Item #: 1001", $this->getText("//tr[@id='cartItem_2']/td[2]/div[2]"), "Product number not displayed in last order step");
        $this->assertTextPresent("1 +1");
        $this->assertEquals("-0,30 €", $this->getText("//div[@id='basketSummary']/table/tbody/tr[2]/td"));

        $this->assertEquals("Total products (incl. tax): 15,00 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[1]")));
        $this->assertEquals("Discount discount from 10 till 20 -0,30 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[2]")));
        $this->assertEquals("Total products (excl. tax): 12,35 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[3]")));
        $this->assertEquals("plus 19% tax, amount: 2,35 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[4]")));
        $this->assertEquals("Shipping costs: 0,00 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[5]")), "Shipping costs is not displayed correctly");
        $this->assertEquals("Grand total: 14,70 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[6]")), "Grand total is not displayed correctly");

        //Go back to 1st order step and change product quantities to 3
        $this->clickFirstStepInShopBasket();
        $this->type("id=am_1", "3");
        $this->click("id=basketUpdate");
        sleep(5);
        $this->assertEquals("Grand total: 42,75 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[6]")), "Grand total is not displayed correctly");
        $this->assertEquals("Discount discount from 20 till 50", $this->getText("//div[@id='basketSummary']/table/tbody/tr[2]/th"));
        $this->assertEquals("-2,25 €", $this->getText("//div[@id='basketSummary']/table/tbody/tr[2]/td"));
        // Go to 2nd step
        $this->clickNextStepInShopBasket();

        //Go to 3rd step and select PayPal as payment method
        $this->clickNextStepInShopBasket();
        $this->waitForItemAppear("id=payment_oxidpaypal");
        $this->click("id=payment_oxidpaypal");
        $this->clickNextStepInShopBasket();

        $this->standardCheckoutWillBeUsed();
        $this->payWithPayPal();

        //Check what was communicated with PayPal
        $assertRequest = ['METHOD' => 'GetExpressCheckoutDetails'];
        $assertResponse = ['ACK' => 'Success',
                           'EMAIL' => $this->getLoginDataByName('sBuyerLogin'),
                           'PAYMENTREQUEST_0_CURRENCYCODE' => 'EUR',
                           'PAYMENTREQUEST_0_AMT' => '42.75',
                           'PAYMENTREQUEST_0_ITEMAMT' => '45.00',
                           'PAYMENTREQUEST_0_SHIPDISCAMT' => '-2.25',
                           'L_PAYMENTREQUEST_0_NAME0' => 'Test product 4',
                           'L_PAYMENTREQUEST_0_NAME1' => 'Test product 1',
                           'L_PAYMENTREQUEST_0_NUMBER0' => '1004',
                           'L_PAYMENTREQUEST_0_NUMBER1' => '1001',
                           'L_PAYMENTREQUEST_0_QTY0' => '3',
                           'L_PAYMENTREQUEST_0_QTY1' => '1',
                           'L_PAYMENTREQUEST_0_AMT0' => '15.00',
                           'L_PAYMENTREQUEST_0_AMT1' => '0.00',];
        $this->assertLogData($assertRequest, $assertResponse);

        //Go to shop to finish the order
        $this->assertTextPresent("Test product 4", "Purchased product name is not displayed");
        $this->assertTextPresent("Test product 1", "Purchased product name is not displayed");
        $this->assertEquals("Item #: 1004", $this->getText("//tr[@id='cartItem_1']/td[2]/div[2]"), "Product number not displayed in last order step");
        $this->assertEquals("Item #: 1001", $this->getText("//tr[@id='cartItem_2']/td[2]/div[2]"), "Product number not displayed in last order step");
        $this->assertTextPresent("1 +1");
        $this->assertEquals("-2,25 €", $this->getText("//div[@id='basketSummary']/table/tbody/tr[2]/td"));

        $this->assertEquals("Total products (incl. tax): 45,00 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[1]")));
        $this->assertEquals("Discount discount from 20 till 50 -2,25 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[2]")));
        $this->assertEquals("Total products (excl. tax): 35,92 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[3]")));
        $this->assertEquals("plus 19% tax, amount: 6,83 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[4]")));
        $this->assertEquals("Shipping costs: 0,00 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[5]")), "Shipping costs is not displayed correctly");
        $this->assertEquals("Grand total: 42,75 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[6]")), "Grand total is not displayed correctly");
        $this->clickAndWait("//button[text()='Order now']");
        $this->assertTextPresent(self::THANK_YOU_PAGE_IDENTIFIER, "Order is not finished successful");

        //Go to admin and check the order
        $this->loginAdminForModule("Administer Orders", "Orders", "btn.help", "link=2");
        $this->assertEquals("Testing user acc Äß'ü", $this->getText("//tr[@id='row.1']/td[6]"), "Wrong user name is displayed in order");
        $this->assertEquals("PayPal Äß'ü", $this->getText("//tr[@id='row.1']/td[7]"), "Wrong user last name is displayed in order");
        $this->openListItem("link=2");
        $this->assertTextPresent("Internal Status: OK");
        $this->assertEquals("0,00 EUR", $this->getText("//td[5]"));

        $this->assertEquals("Billing Address: Company SeleniumTestCase Äß'ü Testing acc for Selenium Mr Testing user acc Äß'ü PayPal Äß'ü Musterstr. Äß'ü 1 79098 Musterstadt Äß'ü Germany E-mail: testing_account@oxid-esales.dev", $this->clearString($this->getText("//td[1]/table[1]/tbody/tr/td[1]")));
        $this->assertEquals("45,00", $this->getText("//table[@id='order.info']/tbody/tr[1]/td[2]"));
        $this->assertEquals("- 2,25", $this->getText("//table[@id='order.info']/tbody/tr[2]/td[2]"));
        $this->assertEquals("35,92", $this->getText("//table[@id='order.info']/tbody/tr[3]/td[2]"));
        $this->assertEquals("6,83", $this->getText("//table[@id='order.info']/tbody/tr[4]/td[2]"));
        $this->assertEquals("0,00", $this->getText("//table[@id='order.info']/tbody/tr[5]/td[2]"));
        $this->assertEquals("42,75", $this->getText("//table[@id='order.info']/tbody/tr[7]/td[2]"));
        $this->assertElementPresent("//table[@id='order.info']/tbody/tr[2]", "line with discount info is not displayed");
        $this->assertElementPresent("//table[@id='order.info']/tbody/tr[2]/td[1]", "line with discount info is not displayed");
        $this->assertElementPresent("//table[@id='order.info']/tbody/tr[2]/td[2]", "line with discount info is not displayed");
        $this->assertEquals("0,00", $this->getText("//table[@id='order.info']/tbody/tr[6]/td[2]"));
        $this->assertEquals("PayPal", $this->getText("//table[4]/tbody/tr[1]/td[2]"), "Payment method not displayed in admin");
        $this->assertEquals("Test S&H set", $this->getText("//table[4]/tbody/tr[2]/td[2]"), "Shipping method is not displayed in admin");
    }

    /**
     * test if vouchers working correct with PayPal
     *
     * @group paypal_standalone
     * @group paypal_external
     *
     */
    public function testPayPalVouchers()
    {
        $this->importSql(__DIR__ . '/testSql/newVouchers_' . SHOP_EDITION . '.sql');

        //Go to shop and add product
        $this->openShop();
        $this->switchLanguage("English");
        $this->searchFor("1003");
        $this->clickAndWait(self::SELECTOR_ADD_TO_BASKET);
        $this->openBasket("English");

        //Login to shop and go to basket
        $this->loginInFrontend(self::LOGIN_USERNAME, self::LOGIN_USERPASS);
        $this->assertTextPresent("Test product 3");
        $this->assertEquals("Grand total: 15,00 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[5]")), "Grand total is not displayed correctly");
        $this->type("voucherNr", "111111");
        $this->clickAndWait("//button[text()='Submit coupon']");
        $this->assertTextPresent("Remove");
        $this->assertTextPresent("Coupon (No. 111111)");
        $this->assertEquals("Coupon (No. 111111) Remove -10,00 €", $this->getText("//div[@id='basketSummary']//tr[2]"));
        $this->assertEquals("Grand total: 5,00 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[6]")), "Grand total is not displayed correctly");

        // Go to 2nd step
        $this->clickNextStepInShopBasket();

        //Go to 3rd step and select paypla as payment method
        $this->clickNextStepInShopBasket();
        $this->waitForItemAppear("id=payment_oxidpaypal");
        $this->click("id=payment_oxidpaypal");
        $this->clickNextStepInShopBasket();

        $this->payWithPayPal();

        //Check what was communicated with PayPal
        $assertRequest = ['METHOD' => 'GetExpressCheckoutDetails'];
        $assertResponse = ['ACK' => 'Success',
                           'EMAIL' => $this->getLoginDataByName('sBuyerLogin'),
                           'SHIPTONAME' => "Testing user acc Äß\\'ü PayPal Äß\\'ü",
                           'AMT' => '5.00',
                           'ITEMAMT' => '15.00',
                           'SHIPPINGAMT' => '0.00',
                           'SHIPDISCAMT' => '-10.00',
                           'L_NAME0' => 'Test product 3',
                           'L_NUMBER0' => '1003',
                           'L_QTY0' => '1',
                           'L_TAXAMT0' => '0.00',
                           'L_AMT0' => '15.00',];
        $this->assertLogData($assertRequest, $assertResponse);

        //Go to shop to finish the order
        $this->assertTextPresent("Test product 3");
        $this->assertEquals("Item #: 1003", $this->getText("//tr[@id='cartItem_1']/td[2]/div[2]"), "Product number not displayed in last order step");

        $this->assertEquals("Total products (incl. tax): 15,00 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[1]")));
        $this->assertEquals("Total products (excl. tax): 4,20 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[3]")));
        $this->assertEquals("plus 19% tax, amount: 0,80 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[4]")));
        $this->assertEquals("Shipping costs: 0,00 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[5]")), "Shipping costs: is not displayed correctly");
        $this->assertEquals("Grand total: 5,00 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[6]")), "Grand total is not displayed correctly");
        $this->clickAndWait("//button[text()='Order now']");
        $this->assertTextPresent(self::THANK_YOU_PAGE_IDENTIFIER, "Order is not finished successful");

        //Go to admin and check the order
        $this->loginAdminForModule("Administer Orders", "Orders", "btn.help", "link=2");
        $this->assertEquals("Testing user acc Äß'ü", $this->getText("//tr[@id='row.1']/td[6]"), "Wrong user name is displayed in order");
        $this->assertEquals("PayPal Äß'ü", $this->getText("//tr[@id='row.1']/td[7]"), "Wrong user last name is displayed in order");
        $this->openListItem("link=2");
        $this->assertTextPresent("Internal Status: OK");
        $this->assertEquals("15,00 EUR", $this->getText("//td[5]"));
        $this->assertEquals("Billing Address: Company SeleniumTestCase Äß'ü Testing acc for Selenium Mr Testing user acc Äß'ü PayPal Äß'ü Musterstr. Äß'ü 1 79098 Musterstadt Äß'ü Germany E-mail: testing_account@oxid-esales.dev", $this->clearString($this->getText("//td[1]/table[1]/tbody/tr/td[1]")));
        $this->assertEquals("15,00", $this->getText("//table[@id='order.info']/tbody/tr[1]/td[2]"));
        $this->assertEquals("- 0,00", $this->getText("//table[@id='order.info']/tbody/tr[2]/td[2]"));
        $this->assertEquals("4,20", $this->getText("//table[@id='order.info']/tbody/tr[3]/td[2]"));
        $this->assertEquals("0,80", $this->getText("//table[@id='order.info']/tbody/tr[4]/td[2]"));
        $this->assertEquals("- 10,00", $this->getText("//table[@id='order.info']/tbody/tr[5]/td[2]"));
        $this->assertEquals("0,00", $this->getText("//table[@id='order.info']/tbody/tr[6]/td[2]"));
        $this->assertEquals("0,00", $this->getText("//table[@id='order.info']/tbody/tr[7]/td[2]"));
        $this->assertEquals("5,00", $this->getText("//table[@id='order.info']/tbody/tr[8]/td[2]"));

        $this->assertElementPresent("//table[@id='order.info']/tbody/tr[2]", "line with discount info is not displayed");
        $this->assertElementPresent("//table[@id='order.info']/tbody/tr[2]/td[1]", "line with discount info is not displayed");
        $this->assertElementPresent("//table[@id='order.info']/tbody/tr[2]/td[2]", "line with discount info is not displayed");
        $this->assertEquals("- 10,00", $this->getText("//table[@id='order.info']/tbody/tr[5]/td[2]"));
        $this->assertEquals("PayPal", $this->getText("//table[4]/tbody/tr[1]/td[2]"), "Payment method not displayed in admin");
        $this->assertEquals("Test S&H set", $this->getText("//table[4]/tbody/tr[2]/td[2]"), "Shipping method is not displayed in admin");
    }


    /**
     * test if VAT is calculated in PayPal correct with different VAT options set in admins
     *
     * @group paypal_standalone
     * @group paypal_external
     *
     */
    public function testPayPalVAT()
    {
        // Change price for PayPal payment methode
        $this->importSql(__DIR__ . '/testSql/vatOptions.sql');
        $this->importSql(__DIR__ . '/testSql/testPaypaVAT_' . SHOP_EDITION . '.sql');

        //Go to shop and add product
        $this->openShop();
        $this->switchLanguage("English");
        $this->searchFor("1003");
        $this->clickAndWait(self::SELECTOR_ADD_TO_BASKET);
        $this->openBasket("English");

        //Login to shop and go to basket
        $this->loginInFrontend(self::LOGIN_USERNAME, self::LOGIN_USERPASS);
        $this->assertTextPresent("Test product 3");
        $this->assertEquals("Test product 3", $this->getText("//tr[@id='cartItem_1']/td[3]/div[1]"));

        //Added wrapping and card to basket
        $this->click("id=header");
        $this->click("link=add");
        $this->click("id=wrapping_a6840cc0ec80b3991.74884864");
        $this->click("id=chosen_81b40cf0cd383d3a9.70988998");
        $this->clickAndWait("//button[text()='Apply']");

        $this->assertEquals("Total products (excl. tax): 15,00 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[1]")));
        $this->assertEquals("plus 19% tax, amount: 2,85 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[2]")));
        $this->assertEquals("Total products (incl. tax): 17,85 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[3]")));
        $this->assertEquals("Shipping (excl. tax): 13,00 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[4]")));
        $this->assertEquals("plus 19% tax, amount: 2,47 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[5]")));
        $this->assertEquals("3,51 €", $this->getText("basketWrappingGross"), "Wrapping price changed or didn't displayed");
        $this->assertEquals("3,57 €", $this->getText("basketGiftCardGross"), "Card price changed or didn't displayed");
        $this->assertEquals("40,40 €", $this->getText("basketGrandTotal"), "Grand total price changed or didn't displayed");

        // Go to 2nd step
        $this->clickNextStepInShopBasket();

        //Go to 3rd step and select PayPal as payment method
        $this->clickNextStepInShopBasket();
        $this->waitForItemAppear("id=payment_oxidpaypal");
        $this->click("id=payment_oxidpaypal");
        $this->clickNextStepInShopBasket();

        $this->payWithPayPal();

        //Check what was communicated with PayPal
        $assertRequest = ['METHOD' => 'GetExpressCheckoutDetails'];
        $assertResponse = ['ACK' => 'Success',
                           'EMAIL' => $this->getLoginDataByName('sBuyerLogin'),
                           'SHIPTONAME' => "Testing user acc Äß\\'ü PayPal Äß\\'ü",
                           'AMT' => '52.90',
                           'ITEMAMT' => '37.43',
                           'SHIPPINGAMT' => '15.47',
                           'L_PAYMENTREQUEST_0_NAME0' => 'Test product 3',
                           'L_PAYMENTREQUEST_0_NAME1' => 'Surcharge Type of Payment',
                           'L_PAYMENTREQUEST_0_NAME2' => 'Giftwrapper',
                           'L_PAYMENTREQUEST_0_NAME3' => 'Greeting Card',
                           'L_PAYMENTREQUEST_0_NUMBER0' => '1003',
                           'L_PAYMENTREQUEST_0_QTY0' => '1',
                           'L_PAYMENTREQUEST_0_QTY1' => '1',
                           'L_PAYMENTREQUEST_0_QTY2' => '1',
                           'L_PAYMENTREQUEST_0_QTY3' => '1',
                           'L_PAYMENTREQUEST_0_AMT0' => '17.85',
                           'L_PAYMENTREQUEST_0_AMT1' => '12.50',
                           'L_PAYMENTREQUEST_0_AMT2' => '3.51',
                           'L_PAYMENTREQUEST_0_AMT3' => '3.57',];
        $this->assertLogData($assertRequest, $assertResponse);

        //Go to shop to finish the order
        $this->assertTextPresent("Test product 3");
        $this->assertEquals("Item #: 1003", $this->getText("//tr[@id='cartItem_1']/td[2]/div[2]"), "Product number not displayed in last order step");
        $this->assertTextPresent("Greeting card");
        $this->assertEquals("3,57 €", $this->getText("id=orderCardTotalPrice"));
        $this->assertEquals("3,51 €", $this->getText("//div[@id='basketSummary']/table/tbody/tr[8]/td"));

        $this->assertEquals("Total products (excl. tax): 15,00 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[1]")));
        $this->assertEquals("plus 19% tax, amount: 2,85 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[2]")));
        $this->assertEquals("Total products (incl. tax): 17,85 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[3]")));
        $this->assertEquals("Shipping (excl. tax): 13,00 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[4]")));
        $this->assertEquals("plus 19% tax, amount: 2,47 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[5]")));
        $this->assertEquals("Surcharge Payment method: 10,50 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[6]")));
        $this->assertEquals("Surcharge 19% tax, amount: 2,00 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[7]")));
        $this->assertEquals("3,51 €", $this->getText("basketWrappingGross"), "Wrapping price changed or didn't displayed");
        $this->assertEquals("3,57 €", $this->getText("basketGiftCardGross"), "Card price changed or didn't displayed");
        $this->assertEquals("52,90 €", $this->getText("basketGrandTotal"), "Grand total price changed or didn't displayed");
        $this->clickAndWait("//button[text()='Order now']");
        $this->assertTextPresent(self::THANK_YOU_PAGE_IDENTIFIER, "Order is not finished successful");

        //Go to admin and check the order
        $this->loginAdminForModule("Administer Orders", "Orders", "btn.help", "link=2");
        $this->assertEquals("Testing user acc Äß'ü", $this->getText("//tr[@id='row.1']/td[6]"), "Wrong user name is displayed in order");
        $this->assertEquals("PayPal Äß'ü", $this->getText("//tr[@id='row.1']/td[7]"), "Wrong user last name is displayed in order");
        $this->openListItem("link=2");
        $this->assertTextPresent("Internal Status: OK");
        $this->assertEquals("17,85 EUR", $this->getText("//td[5]"));
        $this->assertEquals("Billing Address: Company SeleniumTestCase Äß'ü Testing acc for Selenium Mr Testing user acc Äß'ü PayPal Äß'ü Musterstr. Äß'ü 1 79098 Musterstadt Äß'ü Germany E-mail: testing_account@oxid-esales.dev", $this->clearString($this->getText("//td[1]/table[1]/tbody/tr/td[1]")));
        $this->assertEquals("17,85", $this->getText("//table[@id='order.info']/tbody/tr[1]/td[2]"));
        $this->assertEquals("- 0,00", $this->getText("//table[@id='order.info']/tbody/tr[2]/td[2]"));
        $this->assertEquals("15,00", $this->getText("//table[@id='order.info']/tbody/tr[3]/td[2]"));
        $this->assertEquals("2,85", $this->getText("//table[@id='order.info']/tbody/tr[4]/td[2]"));
        $this->assertEquals("15,47", $this->getText("//table[@id='order.info']/tbody/tr[5]/td[2]"));
        $this->assertEquals("12,50", $this->getText("//table[@id='order.info']/tbody/tr[6]/td[2]"));
        $this->assertEquals("3,51", $this->getText("//table[@id='order.info']/tbody/tr[7]/td[2]"));
        $this->assertEquals("3,57", $this->getText("//table[@id='order.info']/tbody/tr[8]/td[2]"));
        $this->assertEquals("52,90", $this->getText("//table[@id='order.info']/tbody/tr[9]/td[2]"));

        $this->assertElementPresent("//table[@id='order.info']/tbody/tr[2]", "line with discount info is not displayed");
        $this->assertElementPresent("//table[@id='order.info']/tbody/tr[2]/td[1]", "line with discount info is not displayed");
        $this->assertElementPresent("//table[@id='order.info']/tbody/tr[2]/td[2]", "line with discount info is not displayed");
        $this->assertEquals("PayPal", $this->getText("//table[4]/tbody/tr[1]/td[2]"), "Payment method not displayed in admin");
        $this->assertEquals("Test S&H set", $this->getText("//table[4]/tbody/tr[2]/td[2]"), "Shipping method is not displayed in admin");
    }

    /**
     * test if option "Calculate default Shipping costs when User is not logged in yet" is working correct in PayPal
     *
     * @group paypal_standalone
     * @group paypal_external
     *
     */
    public function testPayPalShippingCostNotLoginUser()
    {
        // Change price for PayPal payment method
        $this->importSql(__DIR__ . '/testSql/vatOptions.sql');

        // Go to admin and set on "Calculate default Shipping costs when User is not logged in yet "
        $this->loginAdminForModule("Master Settings", "Core Settings");
        $this->openTab("Settings");
        $this->click("link=Other settings");
        sleep(1);
        $this->check("//input[@name='confbools[blCalculateDelCostIfNotLoggedIn]'and @value='true']");
        $this->clickAndWait("save");

        //Go to shop and add product
        $this->clearCache();
        $this->openShop();
        $this->switchLanguage("English");
        $this->searchFor("1003");
        $this->clickAndWait(self::SELECTOR_ADD_TO_BASKET);
        $this->openBasket("English");
        $this->assertTextPresent("Test product 3");
        $this->assertEquals("Test product 3", $this->getText("//tr[@id='cartItem_1']/td[3]/div[1]"));

        //Added wrapping and card to basket
        $this->click("id=header");
        $this->click("link=add");
        $this->click("id=wrapping_a6840cc0ec80b3991.74884864");
        $this->click("id=chosen_81b40cf0cd383d3a9.70988998");
        $this->clickAndWait("//button[text()='Apply']");

        $this->assertEquals("Total products (excl. tax): 12,61 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[1]")));
        $this->assertEquals("plus 19% tax, amount: 2,39 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[2]")));
        $this->assertEquals("Total products (incl. tax): 15,00 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[3]")));
        $this->assertEquals("Shipping costs: 3,90 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[4]")), "Shipping costs is not displayed correctly");
        $this->assertEquals("2,95 €", $this->getText("basketWrappingGross"), "Wrapping price changed or didn't displayed");
        $this->assertEquals("24,85 €", $this->getText("basketGrandTotal"), "Grand total price changed or didn't displayed");

        //Go to PayPal express
        $this->payWithPayPalExpressCheckout();

        //Check what was communicated with PayPal
        $assertRequest = ['METHOD' => 'GetExpressCheckoutDetails'];
        $assertResponse = ['ACK' => 'Success',
                           'EMAIL' => $this->getLoginDataByName('sBuyerLogin'),
                           'AMT' => '44.45',
                           'ITEMAMT' => '31.45',
                           'L_NAME0' => 'Test product 3',
                           'L_NAME1' => 'Surcharge Type of Payment',
                           'L_NAME2' => 'Giftwrapper',
                           'L_NAME3' => 'Greeting Card',
                           'L_NUMBER0' => '1003',
                           'L_QTY0' => '1',
                           'L_QTY1' => '1',
                           'L_QTY2' => '1',
                           'L_QTY3' => '1',
                           'L_AMT0' => '15.00',
                           'L_AMT1' => '10.50',
                           'L_AMT2' => '2.95',
                           'L_AMT3' => '3.00'];
        $this->assertLogData($assertRequest, $assertResponse);

        $this->assertTextPresent("Test product 3");
        $this->assertEquals("Item #: 1003", $this->getText("//tr[@id='cartItem_1']/td[2]/div[2]"), "Product number not displayed in last order step");
        $this->assertTextPresent("Greeting card");
        $this->assertEquals("3,00 €", $this->getText("id=orderCardTotalPrice"));

        $this->assertEquals("Total products (excl. tax): 12,61 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[1]")));
        $this->assertEquals("plus 19% tax, amount: 2,39 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[2]")));
        $this->assertEquals("Total products (incl. tax): 15,00 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[3]")));
        $this->assertEquals("Shipping costs: 13,00 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[4]")), "Shipping costs is not displayed correctly");
        $this->assertEquals("Surcharge Payment method: 10,50 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[5]")));
        $this->assertEquals("2,95 €", $this->getText("basketWrappingGross"), "Wrapping price changed or didn't displayed");
        $this->assertEquals("44,45 €", $this->getText("basketGrandTotal"), "Grand total price changed or didn't displayed");

        $this->clickAndWait("//button[text()='Order now']");
        $this->assertTextPresent(self::THANK_YOU_PAGE_IDENTIFIER, "Order is not finished successful");

        //Go to admin and check the order
        $this->loginAdminForModule("Administer Orders", "Orders", "btn.help", "link=2");
        $this->assertEquals($this->getLoginDataByName('sBuyerFirstName'), $this->getText("//tr[@id='row.1']/td[6]"));
        $this->assertEquals($this->getLoginDataByName('sBuyerLastName'), $this->getText("//tr[@id='row.1']/td[7]"));
        $this->openListItem("link=2");
        $this->assertTextPresent("Internal Status: OK");
        $this->assertEquals("15,00 EUR", $this->getText("//td[5]"));
        $this->assertEquals("Billing Address: {$this->getLoginDataByName('sBuyerFirstName')} {$this->getLoginDataByName('sBuyerLastName')} ESpachstr. 1 79111 Freiburg Germany E-mail: {$this->getLoginDataByName('sBuyerLogin')}", $this->clearString($this->getText("//td[1]/table[1]/tbody/tr/td[1]")));
        $this->assertEquals("15,00", $this->getText("//table[@id='order.info']/tbody/tr[1]/td[2]"));
        $this->assertEquals("- 0,00", $this->getText("//table[@id='order.info']/tbody/tr[2]/td[2]"));
        $this->assertEquals("12,61", $this->getText("//table[@id='order.info']/tbody/tr[3]/td[2]"));
        $this->assertEquals("2,39", $this->getText("//table[@id='order.info']/tbody/tr[4]/td[2]"));
        $this->assertEquals("13,00", $this->getText("//table[@id='order.info']/tbody/tr[5]/td[2]"));
        $this->assertEquals("10,50", $this->getText("//table[@id='order.info']/tbody/tr[6]/td[2]"));
        $this->assertEquals("2,95", $this->getText("//table[@id='order.info']/tbody/tr[7]/td[2]"));
        $this->assertEquals("3,00", $this->getText("//table[@id='order.info']/tbody/tr[8]/td[2]"));
        $this->assertEquals("44,45", $this->getText("//table[@id='order.info']/tbody/tr[9]/td[2]"));

        $this->assertElementPresent("//table[@id='order.info']/tbody/tr[2]", "line with discount info is not displayed");
        $this->assertElementPresent("//table[@id='order.info']/tbody/tr[2]/td[1]", "line with discount info is not displayed");
        $this->assertElementPresent("//table[@id='order.info']/tbody/tr[2]/td[2]", "line with discount info is not displayed");
        $this->assertEquals("PayPal", $this->getText("//table[4]/tbody/tr[1]/td[2]"), "Payment method not displayed in admin");
        $this->assertEquals("Test S&H set", $this->getText("//table[4]/tbody/tr[2]/td[2]"), "Shipping method is not displayed in admin");
    }

    /**
     * test if PayPal works correct when last product ir purchased.
     *
     * @group paypal_standalone
     * @group paypal_external
     *
     */
    public function testPayPalStockOneSale()
    {
        $this->importSql(__DIR__ . '/testSql/changeStock.sql');

        $this->openShop();
        $this->searchFor("1001");
        $this->clickAndWait(self::SELECTOR_ADD_TO_BASKET);
        $this->openBasket("English");

        //Login to shop and go to the basket
        $this->loginInFrontend(self::LOGIN_USERNAME, self::LOGIN_USERPASS);
        $this->waitForElement("paypalExpressCheckoutButton", "PayPal express button not displayed in the cart");
        $this->assertElementPresent("link=Test product 1", "Purchased product name is not displayed");
        $this->assertElementPresent("//tr[@id='cartItem_1']/td[3]/div[2]");
        $this->assertEquals("Grand total: 0,99 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[5]")), "Grand total is not displayed correctly");
        $this->assertTextPresent("Shipping costs:", "Shipping costs is not displayed correctly");
        $this->assertTextPresent("?");
        $this->assertTrue($this->isChecked("//input[@name='displayCartInPayPal' and @value='1']"));
        $this->assertTextPresent("Display cart in PayPal", "Text:Display cart in PayPal for checkbox not displayed");
        $this->assertElementPresent("displayCartInPayPal", "Checkbox:Display cart in PayPal not displayed");

        //Go to PayPal via PayPal Express with "Display cart in PayPal"
        $this->payWithPayPalExpressCheckout();

        //Check what was communicated with PayPal
        $assertRequest = ['METHOD' => 'GetExpressCheckoutDetails'];
        $assertResponse = ['ACK' => 'Success',
                           'EMAIL' => $this->getLoginDataByName('sBuyerLogin'),
                           'PAYMENTREQUEST_0_SHIPTONAME' => "Testing user acc Äß\\'ü PayPal Äß\\'ü",
                           'AMT' => '0.99',
                           'ITEMAMT' => '0.99',
                           'SHIPPINGAMT' => '0.00',
                           'L_PAYMENTREQUEST_0_NAME0' => 'Test product 1',
                           'L_PAYMENTREQUEST_0_NUMBER0' => '1001',
                           'L_PAYMENTREQUEST_0_QTY0' => '1',
                           'L_PAYMENTREQUEST_0_TAXAMT0' => '0.00',
                           'L_PAYMENTREQUEST_0_AMT0' => '0.99'];
        $this->assertLogData($assertRequest, $assertResponse);

        //Check are all info in the last order step correct
        $this->assertElementPresent("link=Test product 1", "Purchased product name is not displayed in last order step");
        $this->assertTextPresent("Item #: 1001", "Product number not displayed in last order step");
        $this->assertEquals("Shipping costs: 0,00 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[4]")), "Shipping costs is not displayed correctly");
        // $this->assertEquals( "OXID Surf and Kite Shop | Order | purchase online", $this->getTitle() );
        $this->assertEquals("Grand total: 0,99 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[5]")), "Grand total is not displayed correctly");
        $this->assertTextPresent("PayPal", "Payment method not displayed in last order step");
        $this->clickAndWait("//button[text()='Order now']");
        $this->assertTextPresent(self::THANK_YOU_PAGE_IDENTIFIER, "Order is not finished successful");

        //Go to admin and check the order
        $this->loginAdminForModule("Administer Orders", "Orders", "btn.help", "link=2");
        $this->openListItem("link=2");
        $this->assertTextPresent("Internal Status: OK");
    }

    /**
     * test if PayPal works correct when last product is purchased.
     * In transaction mode 'automatic' transaction mode 'authorization' is used when stock level drops below specified value.
     *
     * @group paypal_standalone
     * @group paypal_external
     *
     */
    public function testPayPalStockOneAutomatic()
    {
        $this->importSql(__DIR__ . '/testSql/changeStock.sql');

        $this->callShopSC('oxConfig', null, null, [
            'sOEPayPalTransactionMode' => [
                'type' => 'select',
                'value' => 'Automatic',
                'module' => 'module:oepaypal'
            ]]);

        $this->callShopSC('oxConfig', null, null, [
            'sOEPayPalEmptyStockLevel' => [
                'type' => 'select',
                'value' => '10',
                'module' => 'module:oepaypal'
            ]]);

        $this->openShop();
        $this->searchFor("1001");
        $this->clickAndWait(self::SELECTOR_ADD_TO_BASKET);
        $this->openBasket("English");

        //Login to shop and go to the basket
        $this->loginInFrontend(self::LOGIN_USERNAME, self::LOGIN_USERPASS);
        $this->waitForElement("paypalExpressCheckoutButton", "PayPal express button not displayed in the cart");
        $this->assertElementPresent("link=Test product 1", "Purchased product name is not displayed");
        $this->assertElementPresent("//tr[@id='cartItem_1']/td[3]/div[2]");
        $this->assertEquals("Grand total: 0,99 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[5]")), "Grand total is not displayed correctly");
        $this->assertTextPresent("Shipping costs:", "Shipping costs is not displayed correctly");
        $this->assertTextPresent("?");
        $this->assertTrue($this->isChecked("//input[@name='displayCartInPayPal' and @value='1']"));
        $this->assertTextPresent("Display cart in PayPal", "Text:Display cart in PayPal for checkbox not displayed");
        $this->assertElementPresent("displayCartInPayPal", "Checkbox:Display cart in PayPal not displayed");

        //Go to PayPal via PayPal Express with "Display cart in PayPal"
        $this->payWithPayPalExpressCheckout();

        //Check what was communicated with PayPal
        $assertRequest = ['METHOD' => 'GetExpressCheckoutDetails'];
        $assertResponse = ['ACK' => 'Success',
                           'EMAIL' => $this->getLoginDataByName('sBuyerLogin'),
                           'PAYMENTREQUEST_0_SHIPTONAME' => "Testing user acc Äß\\'ü PayPal Äß\\'ü",
                           'AMT' => '0.99',
                           'ITEMAMT' => '0.99',
                           'SHIPPINGAMT' => '0.00',
                           'L_PAYMENTREQUEST_0_NAME0' => 'Test product 1',
                           'L_PAYMENTREQUEST_0_NUMBER0' => '1001',
                           'L_PAYMENTREQUEST_0_QTY0' => '1',
                           'L_PAYMENTREQUEST_0_TAXAMT0' => '0.00',
                           'L_PAYMENTREQUEST_0_AMT0' => '0.99'];
        $this->assertLogData($assertRequest, $assertResponse);

        //Check are all info in the last order step correct
        $this->assertElementPresent("link=Test product 1", "Purchased product name is not displayed in last order step");
        $this->assertTextPresent("Item #: 1001", "Product number not displayed in last order step");
        $this->assertEquals("Shipping costs: 0,00 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[4]")), "Shipping costs is not displayed correctly");
        // $this->assertEquals( "OXID Surf and Kite Shop | Order | purchase online", $this->getTitle() );
        $this->assertEquals("Grand total: 0,99 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[5]")), "Grand total is not displayed correctly");
        $this->assertTextPresent("PayPal", "Payment method not displayed in last order step");
        $this->clickAndWait("//button[text()='Order now']");
        $this->assertTextPresent(self::THANK_YOU_PAGE_IDENTIFIER, "Order is not finished successful");

        //Go to admin and check the order
        $this->loginAdminForModule("Administer Orders", "Orders", "btn.help", "link=2");
        $this->openListItem("link=2");
        $this->assertTextPresent("Internal Status: NOT_FINISHED"); //means capture has to be done manually with these settings
    }

    /**
     * Test if PayPal works correct when last product is purchased.
     * In transaction mode 'automatic' transaction mode 'authorization' is used when stock level drops below specified value.
     *
     * @group paypal_standalone
     * @group paypal_external*
     */
    public function testPayPalStockSufficientAutomatic()
    {
        $this->importSql(__DIR__ . '/testSql/changeStockTo100.sql');

        $this->callShopSC('oxConfig', null, null, [
            'sOEPayPalTransactionMode' => [
                'type' => 'select',
                'value' => 'Automatic',
                'module' => 'module:oepaypal'
            ]]);

        $this->callShopSC('oxConfig', null, null, [
            'sOEPayPalEmptyStockLevel' => [
                'type' => 'select',
                'value' => '1',
                'module' => 'module:oepaypal'
            ]]);

        $this->openShop();
        $this->searchFor("1001");
        $this->clickAndWait(self::SELECTOR_ADD_TO_BASKET);
        $this->openBasket("English");

        //Login to shop and go to the basket
        $this->loginInFrontend(self::LOGIN_USERNAME, self::LOGIN_USERPASS);
        $this->waitForElement("paypalExpressCheckoutButton", "PayPal express button not displayed in the cart");
        $this->assertElementPresent("link=Test product 1", "Purchased product name is not displayed");
        $this->assertElementPresent("//tr[@id='cartItem_1']/td[3]/div[2]");
        $this->assertEquals("Grand total: 0,99 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[5]")), "Grand total is not displayed correctly");
        $this->assertTextPresent("Shipping costs:", "Shipping costs is not displayed correctly");
        $this->assertTextPresent("?");
        $this->assertTrue($this->isChecked("//input[@name='displayCartInPayPal' and @value='1']"));
        $this->assertTextPresent("Display cart in PayPal", "Text:Display cart in PayPal for checkbox not displayed");
        $this->assertElementPresent("displayCartInPayPal", "Checkbox:Display cart in PayPal not displayed");

        //Go to PayPal via PayPal Express with "Display cart in PayPal"
        $this->payWithPayPalExpressCheckout();

        //Check what was communicated with PayPal
        $assertRequest = ['METHOD' => 'GetExpressCheckoutDetails'];
        $assertResponse = ['ACK' => 'Success',
                           'EMAIL' => $this->getLoginDataByName('sBuyerLogin'),
                           'PAYMENTREQUEST_0_SHIPTONAME' => "Testing user acc Äß\\'ü PayPal Äß\\'ü",
                           'AMT' => '0.99',
                           'ITEMAMT' => '0.99',
                           'SHIPPINGAMT' => '0.00',
                           'L_PAYMENTREQUEST_0_NAME0' => 'Test product 1',
                           'L_PAYMENTREQUEST_0_NUMBER0' => '1001',
                           'L_PAYMENTREQUEST_0_QTY0' => '1',
                           'L_PAYMENTREQUEST_0_TAXAMT0' => '0.00',
                           'L_PAYMENTREQUEST_0_AMT0' => '0.99'];
        $this->assertLogData($assertRequest, $assertResponse);

        //Check are all info in the last order step correct
        $this->assertElementPresent("link=Test product 1", "Purchased product name is not displayed in last order step");
        $this->assertTextPresent("Item #: 1001", "Product number not displayed in last order step");
        $this->assertEquals("Shipping costs: 0,00 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[4]")), "Shipping costs is not displayed correctly");
        // $this->assertEquals( "OXID Surf and Kite Shop | Order | purchase online", $this->getTitle() );
        $this->assertEquals("Grand total: 0,99 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[5]")), "Grand total is not displayed correctly");
        $this->assertTextPresent("PayPal", "Payment method not displayed in last order step");
        $this->clickAndWait("//button[text()='Order now']");
        $this->assertTextPresent(self::THANK_YOU_PAGE_IDENTIFIER, "Order is not finished successful");

        //Go to admin and check the order
        $this->loginAdminForModule("Administer Orders", "Orders", "btn.help", "link=2");
        $this->openListItem("link=2");
        $this->assertTextPresent("Internal Status: OK");
    }

    /**
     * test if PayPal works when proportional calculation is used for additional products.
     *
     * @group paypal_standalone
     * @group paypal_external
     *
     */
    public function testPayPalProportional()
    {
        // Change price for PayPal payment method
        $this->importSql(__DIR__ . '/testSql/newVAT.sql');

        // Go to admin and set on all VAT options
        $this->loginAdminForModule("Master Settings", "Core Settings");
        $this->openTab("Settings");
        $this->click("link=VAT");
        sleep(1);
        $this->check("//input[@name='confbools[blShowVATForWrapping]'and @value='true']");
        $this->check("//input[@name='confbools[blShowVATForDelivery]'and @value='true']");
        $this->check("//input[@name='confbools[blShowVATForPayCharge]'and @value='true']");
        $this->clickAndWait("save");

        //Go to shop and add product
        $this->clearCache();
        $this->openShop();
        $this->switchLanguage("English");
        $this->searchFor("100");
        $this->clickAndWait(self::SELECTOR_ADD_TO_BASKET);
        $this->clickAndWait("//form[@name='tobasketsearchList_2']//button");
        $this->clickAndWait("//form[@name='tobasketsearchList_3']//button");
        $this->clickAndWait("//form[@name='tobasketsearchList_4']//button");

        $this->openBasket("English");

        //Login to shop and go to basket
        $this->loginInFrontend(self::LOGIN_USERNAME, self::LOGIN_USERPASS);
        $this->assertTextPresent("Test product 0");
        $this->assertTextPresent("Test product 1");
        $this->assertTextPresent("Test product 3");
        $this->assertTextPresent("Test product 4");

        //Added wrapping and card to basket
        $this->click("id=header");
        $this->click("link=add");
        $this->click("id=wrapping_a6840cc0ec80b3991.74884864");
        $this->click("id=chosen_81b40cf0cd383d3a9.70988998");
        $this->clickAndWait("//button[text()='Apply']");
        $this->assertEquals("Total products (excl. tax): 36,33 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[1]")));
        $this->assertEquals("plus 2% tax, amount: 0,20 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[2]")));
        $this->assertEquals("plus 13% tax, amount: 0,11 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[3]")));
        $this->assertEquals("plus 15% tax, amount: 1,96 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[4]")));
        $this->assertEquals("plus 19% tax, amount: 2,39 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[5]")));

        $this->assertEquals("Total products (incl. tax): 40,99 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[6]")));
        $this->assertEquals("Shipping (excl. tax): 0,00 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[7]")));
        $this->assertEquals("Gift wrapping (excl. tax): 2,89 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[8]")));
        $this->assertEquals("2,89 €", $this->getText("basketWrappingNetto"), "Wrapping price changed or didn't displayed");
        $this->assertEquals("0,06 €", $this->getText("basketWrappingVat"), "Wrapping vat changed or didn't displayed");

        $this->assertEquals("2,52 €", $this->getText("basketGiftCardNetto"), "Card price changed or didn't displayed");
        $this->assertEquals("0,48 €", $this->getText("basketGiftCardVat"), "Card VAT price changed or didn't displayed");
        $this->assertEquals("46,94 €", $this->getText("basketGrandTotal"), "Grand total price changed or didn't displayed");

        // Go to 2nd step
        $this->clickNextStepInShopBasket();

        //Go to 3rd step and select PayPal as payment method
        $this->clickNextStepInShopBasket();
        $this->waitForItemAppear("id=payment_oxidpaypal");
        $this->click("id=payment_oxidpaypal");
        $this->clickNextStepInShopBasket();

        //Go to PayPal
        $this->payWithPayPal();

        //Check what was communicated with PayPal
        $assertRequest = ['METHOD' => 'GetExpressCheckoutDetails'];
        $assertResponse = ['ACK' => 'Success',
                           'EMAIL' => $this->getLoginDataByName('sBuyerLogin'),
                           'PAYMENTREQUEST_0_SHIPTONAME' => "Testing user acc Äß\\'ü PayPal Äß\\'ü",
                           'PAYMENTREQUEST_0_CURRENCYCODE' => 'EUR',
                           'PAYMENTREQUEST_0_AMT' => '46.94',
                           'PAYMENTREQUEST_0_ITEMAMT' => '46.94',
                           'PAYMENTREQUEST_0_SHIPPINGAMT' => '0.00',
                           'PAYMENTREQUEST_0_HANDLINGAMT' => '0.00',
                           'L_PAYMENTREQUEST_0_NAME0' => 'Test product 0',
                           'L_PAYMENTREQUEST_0_NAME1' => 'Test product 1',
                           'L_PAYMENTREQUEST_0_NAME2' => 'Test product 3',
                           'L_PAYMENTREQUEST_0_NAME3' => 'Test product 4',
                           'L_PAYMENTREQUEST_0_NAME4' => 'Giftwrapper',
                           'L_PAYMENTREQUEST_0_NAME5' => 'Greeting Card',
                           'L_PAYMENTREQUEST_0_NUMBER0' => '1000',
                           'L_PAYMENTREQUEST_0_NUMBER1' => '1001',
                           'L_PAYMENTREQUEST_0_NUMBER2' => '1003',
                           'L_PAYMENTREQUEST_0_NUMBER3' => '1004',
                           'L_PAYMENTREQUEST_0_QTY0' => '1',
                           'L_PAYMENTREQUEST_0_QTY1' => '1',
                           'L_PAYMENTREQUEST_0_QTY2' => '1',
                           'L_PAYMENTREQUEST_0_QTY3' => '1',
                           'L_PAYMENTREQUEST_0_QTY4' => '1',
                           'L_PAYMENTREQUEST_0_QTY5' => '1',
                           'L_PAYMENTREQUEST_0_AMT0' => '10.00',
                           'L_PAYMENTREQUEST_0_AMT1' => '0.99',
                           'L_PAYMENTREQUEST_0_AMT2' => '15.00',
                           'L_PAYMENTREQUEST_0_AMT3' => '15.00',
                           'L_PAYMENTREQUEST_0_AMT4' => '2.95',
                           'L_PAYMENTREQUEST_0_AMT5' => '3.00'];
        $this->assertLogData($assertRequest, $assertResponse);

        //Go to shop to finish the order
        $this->assertTextPresent("Test product 0");
        $this->assertEquals("Item #: 1000", $this->getText("//tr[@id='cartItem_1']/td[2]/div[2]"), "Product number not displayed in last order step");
        $this->assertTextPresent("Test product 1");
        $this->assertEquals("Item #: 1001", $this->getText("//tr[@id='cartItem_2']/td[2]/div[2]"), "Product number not displayed in last order step");
        $this->assertTextPresent("Test product 3");
        $this->assertEquals("Item #: 1003", $this->getText("//tr[@id='cartItem_3']/td[2]/div[2]"), "Product number not displayed in last order step");
        $this->assertTextPresent("Test product 4");
        $this->assertEquals("Item #: 1004", $this->getText("//tr[@id='cartItem_4']/td[2]/div[2]"), "Product number not displayed in last order step");
        $this->assertTextPresent("Greeting card");

        $this->assertEquals("36,33 €", $this->getText("basketTotalProductsNetto"), "Net price changed or didn't displayed");
        $this->assertEquals("0,20 €", $this->getText("//div[@id='basketSummary']//tr[2]/td"), "VAT 2% changed ");
        $this->assertEquals("0,11 €", $this->getText("//div[@id='basketSummary']//tr[3]/td"), "VAT 13% changed ");
        $this->assertEquals("1,96 €", $this->getText("//div[@id='basketSummary']//tr[4]/td"), "VAT 15% changed ");
        $this->assertEquals("2,39 €", $this->getText("//div[@id='basketSummary']//tr[5]/td"), "VAT 19% changed ");
        $this->assertEquals("40,99 €", $this->getText("basketTotalProductsGross"), "Brut price changed  or didn't displayed");
        $this->assertEquals("0,00 €", $this->getText("basketDeliveryNetto"), "Shipping price changed  or didn't displayed");
        $this->assertEquals("2,89 €", $this->getText("basketWrappingNetto"), "Wrapping price changed  or didn't displayed");
        $this->assertEquals("0,06 €", $this->getText("basketWrappingVat"), "Wrapping price changed  or didn't displayed");
        $this->assertEquals("2,52 €", $this->getText("basketGiftCardNetto"), "Wrapping price changed  or didn't displayed");
        $this->assertEquals("0,48 €", $this->getText("basketGiftCardVat"), "Wrapping price changed  or didn't displayed");
        $this->assertEquals("46,94 €", $this->getText("basketGrandTotal"), "Grand total price changed  or didn't displayed");

        $this->clickAndWait("//button[text()='Order now']");
        $this->assertTextPresent(self::THANK_YOU_PAGE_IDENTIFIER, "Order is not finished successful");

        //Go to admin to activate proportional calculation
        $this->loginAdminForModule("Master Settings", "Core Settings");
        $this->openTab("Settings");
        $this->click("link=VAT");
        usleep(50000);
        $this->check("//input[@name='confstrs[sAdditionalServVATCalcMethod]'and @value='proportional']");
        $this->clickAndWait("save");

        //Go to shop and add product
        $this->clearCache();
        $this->openShop();
        $this->switchLanguage("English");
        $this->searchFor("100");
        $this->clickAndWait(self::SELECTOR_ADD_TO_BASKET);
        $this->clickAndWait("//form[@name='tobasketsearchList_2']//button");
        $this->clickAndWait("//form[@name='tobasketsearchList_3']//button");
        $this->clickAndWait("//form[@name='tobasketsearchList_4']//button");

        $this->openBasket("English");

        //Login to shop and go to basket
        $this->loginInFrontend(self::LOGIN_USERNAME, self::LOGIN_USERPASS);
        $this->assertTextPresent("Test product 0");
        $this->assertTextPresent("Test product 1");
        $this->assertTextPresent("Test product 3");
        $this->assertTextPresent("Test product 4");

        //Added wrapping and card to basket
        $this->click("id=header");
        $this->click("link=add");
        $this->click("id=wrapping_a6840cc0ec80b3991.74884864");
        $this->click("id=chosen_81b40cf0cd383d3a9.70988998");
        $this->clickAndWait("//button[text()='Apply']");

        $this->assertEquals("Total products (excl. tax): 36,33 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[1]")));
        $this->assertEquals("plus 2% tax, amount: 0,20 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[2]")));
        $this->assertEquals("plus 13% tax, amount: 0,11 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[3]")));
        $this->assertEquals("plus 15% tax, amount: 1,96 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[4]")));
        $this->assertEquals("plus 19% tax, amount: 2,39 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[5]")));

        $this->assertEquals("Total products (incl. tax): 40,99 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[6]")));
        $this->assertEquals("Shipping (excl. tax): 0,00 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[7]")));
        $this->assertEquals("Gift wrapping (excl. tax): 2,89 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[8]")));
        $this->assertEquals("2,89 €", $this->getText("basketWrappingNetto"), "Wrapping price changed or didn't displayed");
        $this->assertEquals("0,06 €", $this->getText("basketWrappingVat"), "Wrapping vat changed or didn't displayed");
        $this->assertEquals("2,66 €", $this->getText("basketGiftCardNetto"), "Card price changed or didn't displayed");
        $this->assertEquals("0,34 €", $this->getText("basketGiftCardVat"), "Card VAT price changed or didn't displayed");
        $this->assertEquals("46,94 €", $this->getText("basketGrandTotal"), "Grand total price changed or didn't displayed");

        // Go to 2nd step
        $this->clickNextStepInShopBasket();

        // Go to 3rd step and select PayPal as payment method
        $this->clickNextStepInShopBasket();
        $this->waitForItemAppear("id=payment_oxidpaypal");
        $this->click("id=payment_oxidpaypal");
        $this->clickNextStepInShopBasket();

        // Going to PayPal
        $this->standardCheckoutWillBeUsed();
        $this->payWithPayPal();

        $assertRequest = ['METHOD' => 'GetExpressCheckoutDetails'];
        $assertResponse = ['ACK'                           => 'Success',
                           'PAYMENTREQUEST_0_AMT'          => '46.94',
                           'PAYMENTREQUEST_0_CURRENCYCODE' => 'EUR',
                           'L_PAYMENTREQUEST_0_NAME0'      => 'Test product 0',
                           'L_PAYMENTREQUEST_0_AMT0'       => '10.00',
                           'L_PAYMENTREQUEST_0_QTY0'       => '1',
                           'L_PAYMENTREQUEST_0_NUMBER0'    => '1000',
                           'L_PAYMENTREQUEST_0_NAME1'      => 'Test product 1',
                           'L_PAYMENTREQUEST_0_AMT1'       => '0.99',
                           'L_PAYMENTREQUEST_0_QTY1'       => '1',
                           'L_PAYMENTREQUEST_0_NUMBER1'    => '1001',
                           'L_PAYMENTREQUEST_0_NAME2'      => 'Test product 3',
                           'L_PAYMENTREQUEST_0_AMT2'       => '15.00',
                           'L_PAYMENTREQUEST_0_QTY2'       => '1',
                           'L_PAYMENTREQUEST_0_NUMBER2'    => '1003',
                           'L_PAYMENTREQUEST_0_NAME3'      => 'Test product 4',
                           'L_PAYMENTREQUEST_0_AMT3'       => '15.00',
                           'L_PAYMENTREQUEST_0_QTY3'       => '1',
                           'L_PAYMENTREQUEST_0_NUMBER3'    => '1004',
                           'L_PAYMENTREQUEST_0_NAME4'      => 'Giftwrapper',
                           'L_PAYMENTREQUEST_0_AMT4'       => '2.95',
                           'L_PAYMENTREQUEST_0_QTY4'       => '1',
                           'L_PAYMENTREQUEST_0_NAME5'      => 'Greeting Card',
                           'L_PAYMENTREQUEST_0_AMT5'       => '3.00',
                           'L_PAYMENTREQUEST_0_QTY5'       => '1'];
        $this->assertLogData($assertRequest, $assertResponse);

        //Go to shop to finish the order
        $this->assertTextPresent("Test product 0");
        $this->assertEquals("Item #: 1000", $this->getText("//tr[@id='cartItem_1']/td[2]/div[2]"), "Product number not displayed in last order step");
        $this->assertTextPresent("Test product 1");
        $this->assertEquals("Item #: 1001", $this->getText("//tr[@id='cartItem_2']/td[2]/div[2]"), "Product number not displayed in last order step");
        $this->assertTextPresent("Test product 3");
        $this->assertEquals("Item #: 1003", $this->getText("//tr[@id='cartItem_3']/td[2]/div[2]"), "Product number not displayed in last order step");
        $this->assertTextPresent("Test product 4");
        $this->assertEquals("Item #: 1004", $this->getText("//tr[@id='cartItem_4']/td[2]/div[2]"), "Product number not displayed in last order step");
        $this->assertTextPresent("Greeting card");

        $this->assertEquals("36,33 €", $this->getText("basketTotalProductsNetto"), "Net price changed or didn't displayed");
        $this->assertEquals("0,20 €", $this->getText("//div[@id='basketSummary']//tr[2]/td"), "VAT 2% changed ");
        $this->assertEquals("0,11 €", $this->getText("//div[@id='basketSummary']//tr[3]/td"), "VAT 13% changed ");
        $this->assertEquals("1,96 €", $this->getText("//div[@id='basketSummary']//tr[4]/td"), "VAT 15% changed ");
        $this->assertEquals("2,39 €", $this->getText("//div[@id='basketSummary']//tr[5]/td"), "VAT 19% changed ");
        $this->assertEquals("40,99 €", $this->getText("basketTotalProductsGross"), "Brut price changed  or didn't displayed");
        $this->assertEquals("0,00 €", $this->getText("basketDeliveryNetto"), "Shipping price changed  or didn't displayed");
        $this->assertEquals("2,89 €", $this->getText("basketWrappingNetto"), "Wrapping price changed  or didn't displayed");
        $this->assertEquals("0,06 €", $this->getText("basketWrappingVat"), "Wrapping price changed  or didn't displayed");
        $this->assertEquals("2,66 €", $this->getText("basketGiftCardNetto"), "Wrapping price changed  or didn't displayed");
        $this->assertEquals("0,34 €", $this->getText("basketGiftCardVat"), "Wrapping price changed  or didn't displayed");
        $this->assertEquals("46,94 €", $this->getText("basketGrandTotal"), "Grand total price changed  or didn't displayed");

        $this->clickAndWait("//button[text()='Order now']");
        $this->assertTextPresent(self::THANK_YOU_PAGE_IDENTIFIER, "Order is not finished successful");

        //Go to admin and check the order
        $this->loginAdminForModule("Administer Orders", "Orders", "btn.help", "link=2");
        $this->assertEquals("Testing user acc Äß'ü", $this->getText("//tr[@id='row.2']/td[6]"), "Wrong user name is displayed in order");
        $this->assertEquals("PayPal Äß'ü", $this->getText("//tr[@id='row.2']/td[7]"), "Wrong user last name is displayed in order");
        $this->openListItem("link=2");
        $this->assertTextPresent("Internal Status: OK");
        $this->assertEquals("10,00 EUR", $this->getText("//td[5]"));

        $this->assertEquals("Billing Address: Company SeleniumTestCase Äß'ü Testing acc for Selenium Mr Testing user acc Äß'ü PayPal Äß'ü Musterstr. Äß'ü 1 79098 Musterstadt Äß'ü Germany E-mail: testing_account@oxid-esales.dev", $this->clearString($this->getText("//td[1]/table[1]/tbody/tr/td[1]")));
        $this->assertEquals("40,99", $this->getText("//table[@id='order.info']/tbody/tr[1]/td[2]"));
        $this->assertEquals("- 0,00", $this->getText("//table[@id='order.info']/tbody/tr[2]/td[2]"));
        $this->assertEquals("36,33", $this->getText("//table[@id='order.info']/tbody/tr[3]/td[2]"));
        $this->assertEquals("0,20", $this->getText("//table[@id='order.info']/tbody/tr[4]/td[2]"));
        $this->assertEquals("0,11", $this->getText("//table[@id='order.info']/tbody/tr[5]/td[2]"));
        $this->assertEquals("0,00", $this->getText("//table[@id='order.info']/tbody/tr[6]/td[2]"));
        $this->assertEquals("0,00", $this->getText("//table[@id='order.info']/tbody/tr[7]/td[2]"));
        $this->assertEquals("2,95", $this->getText("//table[@id='order.info']/tbody/tr[8]/td[2]"));
        $this->assertEquals("3,00", $this->getText("//table[@id='order.info']/tbody/tr[9]/td[2]"));
        $this->assertEquals("46,94", $this->getText("//table[@id='order.info']/tbody/tr[10]/td[2]"));

        $this->assertElementPresent("//table[@id='order.info']/tbody/tr[2]", "line with discount info is not displayed");
        $this->assertElementPresent("//table[@id='order.info']/tbody/tr[2]/td[1]", "line with discount info is not displayed");
        $this->assertElementPresent("//table[@id='order.info']/tbody/tr[2]/td[2]", "line with discount info is not displayed");
        $this->assertEquals("PayPal", $this->getText("//table[4]/tbody/tr[1]/td[2]"), "Payment method not displayed in admin");
        $this->assertEquals("Test S&H set", $this->getText("//table[4]/tbody/tr[2]/td[2]"), "Shipping method is not displayed in admin");
    }

    /**
     * test if PayPal works in Netto mode
     *
     * @group paypal_standalone
     * @group paypal_external
     *
     */
    public function testPayPalExpressNettoMode()
    {
        // Activate the necessary options Neto mode
        $this->importSql(__DIR__ . '/testSql/NettoModeTurnOn_' . SHOP_EDITION . '.sql');

        // Add articles to basket.
        $this->openShop();
        $this->searchFor("1401");
        $this->clickAndWait(self::SELECTOR_ADD_TO_BASKET);

        // Change price for PayPal payment method
        $this->importSql(__DIR__ . '/testSql/vatOptions.sql');

        $this->openBasket("English");

        //Added wrapping and card to basket.
        $this->click("id=header");
        $this->click("link=add");
        $this->click("id=wrapping_a6840cc0ec80b3991.74884864");
        $this->click("id=chosen_81b40cf0cd383d3a9.70988998");
        $this->clickAndWait("//button[text()='Apply']");

        // Check wrapping and card prices.
        $this->assertEquals("2,95 €", $this->getText("basketWrappingGross"), "Wrapping price changed or didn't display");
        $this->assertEquals("3,00 €", $this->getText("basketGiftCardGross"), "Card price changed or didn't display");

        // Check basket prices.
        $this->assertEquals("108,40 €", $this->getText("basketTotalProductsNetto"), "Net price changed or didn't display");
        $this->assertEquals("134,95 €", $this->getText("basketGrandTotal"), "Grand total price changed or didn't display");

        //Go to PayPal via PayPal Express with "Display cart in PayPal"
        $this->assertElementPresent("paypalExpressCheckoutButton");
        $this->selectPayPalExpressCheckout();

        //Check what was communicated with PayPal
        $assertRequest = ['PAYMENTREQUEST_0_AMT' => '145.45',
                          'PAYMENTREQUEST_0_CURRENCYCODE' => 'EUR',
                          'PAYMENTREQUEST_0_ITEMAMT' => '122.22',
                          'L_PAYMENTREQUEST_0_NAME0' => 'Harness SOL KITE',
                          'L_PAYMENTREQUEST_0_AMT0' => '108.40',
                          'L_PAYMENTREQUEST_0_NUMBER0' => '1401',
                          'L_PAYMENTREQUEST_0_AMT1' => '8.82',
                          'L_PAYMENTREQUEST_0_AMT2' => '2.48',
                          'L_PAYMENTREQUEST_0_AMT3' => '2.52'];
        $assertResponse = ['ACK' => 'Success'];
        $this->assertLogData($assertRequest, $assertResponse);

        $this->loginToSandbox();
        $this->clickPayPalContinue();

        //Check what was communicated with PayPal
        $assertRequest = ['METHOD' => 'GetExpressCheckoutDetails'];
        $assertResponse = ['ACK' => 'Success',
                           'EMAIL' => $this->getLoginDataByName('sBuyerLogin'),
                           'L_PAYMENTREQUEST_0_NAME0' => 'Harness SOL KITE',
                           'PAYMENTREQUEST_0_CURRENCYCODE' => 'EUR',
                           'PAYMENTREQUEST_0_AMT' => '158.45',
                           'PAYMENTREQUEST_0_ITEMAMT' => '122.22',
                           'PAYMENTREQUEST_0_SHIPPINGAMT' => '13.00',
                           'PAYMENTREQUEST_0_TAXAMT' => '23.23'];
        $this->assertLogData($assertRequest, $assertResponse);

        $this->waitForText("Please check all data on this overview before submitting your order!");
    }

    /**
     * test if PayPal works in Net mode
     *
     * @group paypal_standalone
     * @group paypal_external
     *
     */
    public function testPayPalStandardNettoMode()
    {
        // Activate the necessary options netto mode
        $this->importSql(__DIR__ . '/testSql/NettoModeTurnOn_' . SHOP_EDITION . '.sql');

        // Add articles to basket.
        $this->openShop();
        $this->searchFor("1401");
        $this->clickAndWait(self::SELECTOR_ADD_TO_BASKET);

        // Change price for PayPal payment method
        $this->importSql(__DIR__ . '/testSql/vatOptions.sql');

        // Need to wait after switching language as basket layout might not appear if JavaScript is not loaded.
        $this->switchLanguage("Deutsch");
        sleep(1);
        $this->openBasket("Deutsch");

        //Added wrapping and card to basket.
        $this->click("id=header");
        $this->click("link=hinzufügen");
        $this->click("id=wrapping_a6840cc0ec80b3991.74884864");
        $this->click("id=chosen_81b40cf0cd383d3a9.70988998");
        $this->clickAndWait("//button[text()='Übernehmen']");

        // Check wrapping and card prices.
        $this->assertEquals("2,95 €", $this->getText("basketWrappingGross"), "Wrapping price changed or didn't display");
        $this->assertEquals("3,00 €", $this->getText("basketGiftCardGross"), "Card price changed or didn't display");

        // Check basket prices.
        $this->assertEquals("108,40 €", $this->getText("basketTotalProductsNetto"), "Net price changed or didn't display");
        $this->assertEquals("134,95 €", $this->getText("basketGrandTotal"), "Grand total price changed or didn't display");

        // Add more articles so sum would be more than 500eur.
        // Without sleep basket update do not make update before checking actual prices.
        $this->type("am_1", "5");
        sleep(1);
        $this->clickAndWait("basketUpdate");
        sleep(1);

        // Check basket prices.
        $this->assertEquals("542,00 €", $this->getText("basketTotalProductsNetto"), "Net price changed or didn't display");
        $this->assertTextPresent("102,98 €", "Articles VAT changed or didn't display");
        $this->assertEquals("662,73 €", $this->getText("basketGrandTotal"), "Grand total price changed or didn't display");

        $this->loginInFrontend(self::LOGIN_USERNAME, self::LOGIN_USERPASS);

        //On 2nd step
        $this->clickAndWait(self::SELECTOR_BASKET_NEXTSTEP);
        $this->waitForText("Lieferadresse");

        //On 3rd step
        $this->clickAndWait(self::SELECTOR_BASKET_NEXTSTEP);
        $this->waitForText("Bitte wählen Sie Ihre Versandart");

        // Go to PayPal
        $this->click("payment_oxidpaypal");
        $this->click(self::SELECTOR_BASKET_NEXTSTEP);
        $this->payWithPayPal();

        //Check what was communicated with PayPal
        $assertRequest = ['METHOD' => 'GetExpressCheckoutDetails'];
        $assertResponse = ['ACK' => 'Success',
                           'EMAIL' => $this->getLoginDataByName('sBuyerLogin'),
                           'L_PAYMENTREQUEST_0_NAME0' => 'Trapez ION SOL KITE 2011',
                           'L_PAYMENTREQUEST_0_NUMBER0' => '1401',
                           'L_PAYMENTREQUEST_0_AMT0' => '108.40',
                           'L_PAYMENTREQUEST_0_AMT1' => '8.82',
                           'L_PAYMENTREQUEST_0_AMT2' => '12.39',
                           'L_PAYMENTREQUEST_0_AMT3' => '2.52',
                           'PAYMENTREQUEST_0_TAXAMT' => '107.50',
                           'PAYMENTREQUEST_0_AMT' => '686.23',
                           'PAYMENTREQUEST_0_CURRENCYCODE' => 'EUR',
                           'PAYMENTREQUEST_0_ITEMAMT' => '565.73',
                           'PAYMENTREQUEST_0_SHIPPINGAMT' => '13.00'];
        $this->assertLogData($assertRequest, $assertResponse);

        $this->assertElementPresent("//button[text()='Zahlungspflichtig bestellen']");
    }

    /**
     * testing when payment method has unassigned country Germany, user is not login to the shop, and purchase as PayPal user from Germany
     *
     * @group paypal_standalone
     * @group paypal_external
     *
     */
    public function testPayPalPaymentForGermany()
    {
        //Separate Germany from PayPal payment method and assign United States
        $this->importSql(__DIR__ . '/testSql/unasignCountryFromPayPal.sql');

        ///Go to make an order but do not finish it
        $this->clearCache();
        $this->openShop();

        //Check if PayPal logo in frontend is active in both languages
        $this->assertElementPresent("paypalPartnerLogo", "PayPal logo not shown in frontend page");
        $this->switchLanguage("Deutsch");
        $this->assertElementPresent("paypalPartnerLogo", "PayPal logo not shown in frontend page");
        $this->switchLanguage("English");

        //Search for the product and add to cart
        $this->searchFor("1001");
        $this->clickAndWait(self::SELECTOR_ADD_TO_BASKET);
        $this->openBasket("English");
        $this->waitForElement("paypalExpressCheckoutButton");
        $this->assertElementPresent("link=Test product 1", "Product:Test product 1 is not shown in 1st order step ");
        $this->assertElementPresent("//tr[@id='cartItem_1']/td[3]/div[2]", "There product:Test product 1 is not shown in 1st order step");
        $this->assertEquals("Grand total: 0,99 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[4]")), "Grand Total is not displayed correctly");
        $this->assertFalse($this->isTextPresent("Shipping costs:"), "Shipping costs should not be displayed");
        $this->assertTextPresent("?");
        $this->assertTrue($this->isChecked("//input[@name='displayCartInPayPal' and @value='1']"));
        $this->assertTextPresent("Display cart in PayPal", "An option text:Display cart in PayPal is not displayed");
        $this->assertElementPresent("name=displayCartInPayPal", "An option Display cart in PayPal is not displayed");

        //Go to PayPal express to make an order
        $this->payWithPayPalExpressCheckout('paypalExpressCheckoutButton', true);

        //Check what was communicated with PayPal
        $assertRequest = ['METHOD' => 'GetExpressCheckoutDetails'];
        $assertResponse = ['PAYMENTREQUEST_0_AMT' => '7.89',
                           'PAYMENTREQUEST_0_CURRENCYCODE' => 'EUR',
                           'L_PAYMENTREQUEST_0_NAME0' => 'Test product 1',
                           'L_PAYMENTREQUEST_0_NUMBER0' => '1001',
                           'L_PAYMENTREQUEST_0_QTY0' => '1',
                           'L_PAYMENTREQUEST_0_AMT0' => '0.99',
                           'EMAIL' => $this->getLoginDataByName('sBuyerUSLogin'),
                           'AMT' => '7.89',
                           'ITEMAMT' => '0.99',
                           'SHIPPINGAMT' => '6.90',
                           'SHIPPINGCALCULATIONMODE' => 'Callback',
                           'ACK' => 'Success'];
        $this->assertLogData($assertRequest, $assertResponse);

        //Now user is on the 1st "cart" step with an error message:
        $this->assertTextPresent("Based on your choice in PayPal Express Checkout, order total has changed. Please check your shopping cart and continue. Hint: for continuing with Express Checkout press Express Checkout button again.", "An error message is not dispayed in shop 1st order step");
        $this->assertElementPresent("id=basketRemoveAll", "an option Remove is not displayed in 1st cart step");
        $this->assertElementPresent("id=basketRemove", "an option All is not displayed in 1st cart step");
        $this->assertElementPresent("id=basketUpdate", "an option Update is not displayed in 1st cart step");
        $this->assertElementPresent("link=Test product 1", "Purchased product name is not displayed");
        $this->assertElementPresent("//tr[@id='cartItem_1']/td[3]/div[2]", "There product:Test product 1 is not shown in 1st order step");
        $this->assertEquals("Grand total: 7,73 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[5]")), "Grand total is not displayed correctly");
        $this->assertEquals("Shipping costs: 6,90 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[4]")), "Shipping costs is not displayed correctly");

        $this->assertTextPresent("Display cart in PayPal", "Text:Display cart in PayPal for checkbox not displayed");
        $this->assertElementPresent("name=displayCartInPayPal", "Checkbox:Display cart in PayPal not displayed in cart");
        $this->assertElementPresent("paypalExpressCheckoutButton", "PayPal express button not displayed in the cart");

        //Go to next step and change country to Germany
        $this->clickAndWait("css=.nextStep");
        $this->click("//button[@id='userChangeAddress']");
        $this->click("id=invCountrySelect");
        $this->select("invCountrySelect", "label=Germany");
        $this->click("id=userNextStepTop");
        $this->waitForPageToLoad("30000");

        //Check if PayPal is not displayed for Germany
        $this->assertElementNotPresent("//select[@name='sShipSet']/option[text()='Paypal']", "Paypal is displayed for Germany, but must be not shown");

        $this->assertEquals("COD (Cash on Delivery) (7,50 €)", $this->getText("//form[@id='payment']/dl[5]/dt/label/b"), "Wrong payment method is shown");
        $this->assertTextPresent("COD (Cash on Delivery) (7,50 €)", "Wrong payment method is shown");
        $this->assertFalse($this->isTextPresent("PayPal (0,00 €)"), "PayPal should not be displayed as payment method");

        //Also check if PayPal not displayed in the 1st cart step
        $this->click("link=1. Cart");
        $this->waitForPageToLoad("30000");
        $this->assertTextPresent("Display cart in PayPal", "Text:Display cart in PayPal for checkbox not displayed");
        $this->assertElementPresent("displayCartInPayPal", "Checkbox:Display cart in PayPal not displayed in cart");
        $this->assertElementPresent("paypalExpressCheckoutButton", "PayPal express button not displayed in the cart");

        //Go to admin and check previous order status and check if new order didn't appear in admin.
        $this->loginAdminForModule("Administer Orders", "Orders", "btn.help", "link=2");
        $this->selectMenu("Administer Orders", "Orders");
        $this->assertElementNotPresent("link=2");

        //Go to basket and make an order,
        $this->clearCache();
        $this->openShop();
        $this->searchFor("1001");
        $this->clickAndWait(self::SELECTOR_ADD_TO_BASKET);
        $this->openBasket("English");

        $this->assertEquals("Grand total: 0,99 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[4]")), "Grand total is not displayed correctly");
        $this->clickAndWait("//button[text()='Continue to the next step']");
        $this->loginInFrontend(self::LOGIN_USERNAME, self::LOGIN_USERPASS);
        $this->assertElementPresent("id=showShipAddress", "Shipping address is not displayed in 2nd order step");
        $this->click("id=userNextStepBottom");
        $this->waitForElement("paymentNextStepBottom");
        $this->assertElementPresent("name=sShipSet", "Shipping method drop down is not shown");
        $this->assertEquals("Test S&H set", $this->getSelectedLabel("sShipSet"), "Wrong shipping method is selected, should be:Test S&H set ");
        $this->click("id=paymentNextStepBottom");

        //go to last order step, check if payment method is not PayPal
        $this->waitForElement("orderAddress");
        $this->assertElementPresent("link=Test product 1", "Product name is not displayed in last order step");
        $this->assertTextPresent("Item #: 1001", "Product number not displayed in last order step");
        $this->assertEquals("Shipping costs: 0,00 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[4]")), "Shipping costs is not displayed correctly");
        $this->assertEquals("Surcharge Payment method: 7,50 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[5]")), "Payment price is not displayed in carts");
        $this->assertEquals("Grand total: 8,49 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[6]")), "Grand total is not displayed correctly");
        $this->assertTextPresent("Test S&H set");
        $this->assertTextPresent("COD");
        $this->clickAndWait("//button[text()='Order now']");
        $this->assertTextPresent(self::THANK_YOU_PAGE_IDENTIFIER, "Order is not finished successful");

        // After successful purchase, go to admin and check order status
        $this->loginAdminForModule("Administer Orders", "Orders", "btn.help", "link=2");
        $this->click("link=Order No.");
        $this->waitForPageToLoad("30000");

        $this->clickandWait("link=2");
        $this->assertEquals("Testing user acc Äß'ü", $this->getText("//tr[@id='row.1']/td[6]"), "Wrong user name is displayed in order");
        $this->assertEquals("PayPal Äß'ü", $this->getText("//tr[@id='row.1']/td[7]"), "Wrong user last name is displayed in order");
        $this->assertEquals("0000-00-00 00:00:00", $this->getText("//tr[@id='row.1']/td[4]"));
        $this->openListItem("2", "setfolder");
        $this->assertTextPresent("Internal Status: OK");
        $this->assertTextPresent("Order No.: 2", "Order number is not displayed in admin");
        $this->assertEquals("1 *", $this->getText("//table[2]/tbody/tr/td[1]"));
        $this->assertEquals("Test product 1", $this->getText("//td[3]"), "Purchased product name is not displayed in Admin");
        $this->assertEquals("8,49", $this->getText("//table[@id='order.info']/tbody/tr[7]/td[2]"));

        $this->openTab("Products");
        $this->assertEquals("7,50", $this->getText("//table[@id='order.info']/tbody/tr[6]/td[2]"), "charges of payment method is not displayed");
        $this->assertEquals("0,16", $this->getText("//table[@id='order.info']/tbody/tr[4]/td[2]"), "VAT is not displayed");
        $this->assertEquals("0,83", $this->getText("//table[@id='order.info']/tbody/tr[3]/td[2]"), "Product Net price is not displayed");

        $this->openTab("Main");
        $this->assertEquals("Test S&H set", $this->getSelectedLabel("setDelSet"), "Shipping method is not displayed in admin");
        $this->assertEquals("COD (Cash on Delivery)", $this->getSelectedLabel("setPayment"), "Payment method is not displayed in admin");
    }

    /**
     * Testing different countries with shipping rules assigned to this countries
     * NOTE: test selects payment method on PayPal page.
     *
     * @group paypal_standalone
     * @group paypal_external
     */
    public function testPayPalPaymentForLoginUser()
    {
        $this->addToBasket('1001');
        $this->loginToShopFrontend();

        // Created additional 3 shipping methods with Shipping costs rules for Austria
        $this->importSql(__DIR__ . '/testSql/newDeliveryMethod_' . SHOP_EDITION . '.sql');

        $this->openBasket();
        $this->clickNextStepInShopBasket();

        // Change country to Austria
        $this->changeCountryInBasketStepTwo('Austria');

        // Check all available shipping methods
        $this->assertTextPresent('PayPal');
        // Test Paypal:6 hour Price: €0.50 EUR
        $this->selectAndWait('sShipSet', 'label=Test Paypal:6 hour');

        $this->assertTextPresent('Charges: 0,50 €');
        $this->assertAllAvailableShippingMethodsAreDisplayed();

        // Go to 1st step and make an order via PayPal express
        $this->clickFirstStepInShopBasket();
        $this->selectPayPalExpressCheckout();

        $this->loginToSandbox();
        $this->waitForLoggedInToPayPalSandbox();

        //NOTE: isn't running locally (callback is not accessible from PayPal):
        $this->selectPayPalShippingMethod('Test Paypal:12 hour Price: €0,90 EUR');

        // Check, that the communication with PayPal was as expected
        $expectedRequest = ['METHOD' => 'SetExpressCheckout',
                            'PAYMENTREQUEST_0_CURRENCYCODE' => 'EUR',
                            'NOSHIPPING' => '2',
                            'PAYMENTREQUEST_0_AMT' => '1.49',
                            'PAYMENTREQUEST_0_ITEMAMT' => '0.99',
                            'PAYMENTREQUEST_0_SHIPPINGAMT' => '0.50',
                            'PAYMENTREQUEST_0_SHIPDISCAMT' => '0.00',
                            'L_SHIPPINGOPTIONISDEFAULT0' => 'true',
                            'L_SHIPPINGOPTIONNAME0' => 'Test Paypal:6 hour',
                            'PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE' => 'AT',
                            'L_PAYMENTREQUEST_0_NAME0' => 'Test product 1',
                            'L_PAYMENTREQUEST_0_NUMBER0' => '1001'
            ];
        $expectedResponse = ['ACK' => 'Success'];
        $this->assertLogData($expectedRequest, $expectedResponse);

        // Go to shop
        // NOTE: somehow in this case we need to click continue twice
        $this->expressCheckoutWillBeUsed();
        $this->clickPayPalContinue();
        $this->clickPayPalContinue();

        // Make sure we are back in shop
        $this->assertTrue($this->isElementPresent("id=breadCrumb"));

        //Check are all info in the last order step correct
        $this->assertElementPresent('link=Test product 1', 'Purchased product name is not displayed in last order step');
        $this->assertTextPresent('Item #: 1001', 'Product number not displayed in last order step');
        // next four lines aren't running locally (callback is not accessible from PayPal):
        $this->assertEquals('Shipping costs: 0,90 €', $this->clearString($this->getText("//div[@id='basketSummary']//tr[4]")), 'Shipping costs is not displayed correctly');
        $this->assertEquals('OXID Surf and Kite Shop | Order | purchase online', $this->getTitle());
        $this->assertEquals('Grand total: 1,89 €', $this->clearString($this->getText("//div[@id='basketSummary']//tr[5]")), 'Grand total is not displayed correctly');
        $this->assertTextPresent('Test Paypal:12 hour', 'Shipping method not displayed in order ');

        $this->assertTextPresent('PayPal', 'Payment method not displayed in last order step');
        $this->assertFalse($this->isTextPresent('COD'), 'Wrong payment method displayed in last order step');
    }

    /**
     * test if PayPal is not shown in frontend after configs is set in admin
     *
     * @group paypal_standalone
     */
    public function testPayPalShortcut()
    {
        // Turn Off all PayPal shortcut in frontend
        $this->importSql(__DIR__ . '/testSql/testPayPalShortcut_' . SHOP_EDITION . '.sql');

        // Add articles to basket.
        $this->openShop();
        $this->switchLanguage("English");
        $this->loginInFrontend(self::LOGIN_USERNAME, self::LOGIN_USERPASS);
        $this->searchFor("1001");
        $this->clickAndWait("//ul[@id='searchList']/li/form/div/a[2]/span");
        $this->assertFalse($this->isElementPresent("id=paypalExpressCheckoutDetailsButton"), "After PayPal is disabled in admin PayPal should not be visible in admin");
        $this->clickAndWait("id=toBasket");
        $this->click("id=minibasketIcon");
        $this->assertFalse($this->isElementPresent("id=paypalExpressCheckoutMiniBasketImage"));
        $this->clickAndWait("link=Display cart");
        $this->assertFalse($this->isElementPresent("//input[name='paypalExpressCheckoutButton']"));
        $this->clickAndWait("id=basketUpdate");
        $this->clickNextStepInShopBasket();
        $this->clickAndWait("id=userNextStepTop");
        $this->assertFalse($this->isElementPresent("id=payment_oxidpaypal"));
        $this->clickAndWait("id=paymentNextStepBottom");
        $this->waitForShop();
        $this->clickAndWait("//button[text()='Order now']");

        $this->assertTextPresent("Thank you for ordering at OXID eShop", "Order is not finished successful");

        //Go to Admin
        $this->loginAdminForModule("Administer Orders", "Orders", "btn.help", "link=2");
        $this->openListItem("2");

        // Go to PayPal tab
        $this->openTab("PayPal");
        $this->assertEquals("This tab is for orders with the PayPal payment method only", $this->getText("//div[2]/div[2]"));
    }

    /**
     * This is a regression test:
     * There was a bug in the PayPal module, that after deactivation of the PayPal module the admin was not working any
     * more until the browser session was cleared.
     * Technical background: the basket object is stored in/restored from the session on each page or frame reload,
     * As the PayPal module extends the basket object, an instance of the specific PayPal basket object is stored.
     * After module deactivation this object cannot be restored.
     */
    public function testModuleDeactivationDoesNotResultInMaintenancePage()
    {
        $pageReloadTime = 2; // seconds
        $this->loginAdminForModule("Extensions", "Modules");
        $this->openListItem("PayPal");
        $this->frame("edit");
        // Deactivate the PayPal module, if it is not active activate it first.
        try {
            $this->click("module_deactivate");
        } catch (\Exception $exception) {
            // The module was not active, so activate and deactivate it
            $this->click("module_activate");
            $this->logoutAdmin("link=Logout");
            $this->loginAdminForModule("Extensions", "Modules");
            $this->openListItem("PayPal");
            $this->frame("edit");
            $this->click("module_deactivate");
        }

        // It is not possible to use assertTextNotPresent here, as the timeout of that function is to long
        sleep($pageReloadTime);
        $this->assertFalse(
            $this->isTextPresent('Maintenance mode'),
            'The eShop Admin went into Maintenance mode after module deactivation. 
                The text "Maintenance mode" is present on the page.'
        );
    }

    /**
     * Login to PayPal sandbox.
     *
     * @param string $loginEmail    email to login.
     * @param string $loginPassword password to login.
     *
     * @todo wait, check that it actually logged in.
     */
    private function loginToSandbox($loginEmail = null, $loginPassword = null)
    {
        if (!isset($loginEmail)) {
            $loginEmail = $this->getLoginDataByName('sBuyerLogin');
        }
        if (!isset($loginPassword)) {
            $loginPassword = $this->getLoginDataByName('sBuyerPassword');
        }

        if ($this->newPayPalUserInterface) {
            $this->loginToNewSandbox($loginEmail, $loginPassword);
        } else {
            $this->loginToOldSandbox($loginEmail, $loginPassword);
        }
    }

    /**
     * New sandbox login.
     *
     * @param string $loginEmail
     * @param string $loginPassword
     */
    private function loginToNewSandbox($loginEmail, $loginPassword)
    {
        $this->selectCorrectLoginFrame();

        $this->type("login_email", $loginEmail);
        $this->type("login_password", $loginPassword);
        $this->click(self::PAYPAL_LOGIN_BUTTON_ID_NEW);

        $this->selectWindow(null);
        $this->_waitForAppear('isTextPresent', $this->getLoginDataByName('sBuyerFirstName'), 3, true);
        $this->_waitForAppear('isElementPresent', "//input[@id='confirmButtonTop']", 10, true);
    }

    /**
     * Old sandbox login.
     *
     * @param string $loginEmail
     * @param string $loginPassword
     */
    private function loginToOldSandbox($loginEmail, $loginPassword)
    {
        $this->type("login_email", $loginEmail);
        $this->type("login_password", $loginPassword);
        $this->clickAndWait(self::PAYPAL_LOGIN_BUTTON_ID_OLD);
        $this->waitForItemAppear("id=continue");
    }

    /**
     * Selects shipping method in PayPal page
     *
     * @param string $method Method label
     */
    private function selectPayPalShippingMethod($method)
    {
        $this->waitForItemAppear("id=shipping_method");
        $this->select("id=shipping_method", "label=$method");
        $this->waitForItemAppear("id=continue");
    }

    /**
     * Returns PayPal login data by variable name
     *
     * @param $varName
     *
     * @return mixed|null|string
     * @throws \Exception
     */
    private function getLoginDataByName($varName)
    {
        if (!$varValue = getenv($varName)) {
            $varValue = $this->getArrayValueFromFile($varName, __DIR__ .'/oepaypalData.php');
        }

        if (!$varValue) {
            throw new \Exception('Undefined variable: ' . $varName);
        }

        return $varValue;
    }

    /**
     * Standard PayPal uses new User Interface.
     */
    private function standardCheckoutWillBeUsed()
    {
        $this->newPayPalUserInterface = true;
    }

    /**
     * New PayPal interface uses iframe for user login.
     */
    private function selectCorrectLoginFrame()
    {
        if ($this->newPayPalUserInterface) {
            $this->frame(self::PAYPAL_FRAME_NAME);
        }
    }

    /**
     * Go to PayPal page by clicking Express Checkout button.
     *
     * @param string $expressCheckoutButtonIdentification PayPal Express Checkout button identification.
     */
    private function selectPayPalExpressCheckout($expressCheckoutButtonIdentification = "paypalExpressCheckoutButton")
    {
        $this->waitForItemAppear("//input[@id='{$expressCheckoutButtonIdentification}']", 10, true);
        $this->expressCheckoutWillBeUsed();
        $this->click($expressCheckoutButtonIdentification);
        $this->waitForPayPalPage();
    }

    /**
     * Express Checkout uses old User Interface.
     */
    private function expressCheckoutWillBeUsed()
    {
        $this->newPayPalUserInterface = false;
    }

    /**
     * PayPal has two pages with different layout.
     */
    private function clickPayPalContinue()
    {
        if ($this->newPayPalUserInterface) {
            $this->clickPayPalContinueNewPage();
        } else {
            $this->clickPayPalContinueOldPage();
        }

        //we should be redirected back to shop at this point
        $this->_waitForAppear('isElementPresent', "id=breadCrumb", 10, true);
    }

    /**
     * Continue button is visible before PayPal does callback.
     * Then it becomes invisible while PayPal does callback.
     * Button appears when PayPal gets callback result.
     */
    private function clickPayPalContinueNewPage()
    {
        $this->waitForItemAppear("//input[@id='confirmButtonTop']", 10, true);
        $this->waitForEditable("id=confirmButtonTop");
        $this->clickAndWait("id=confirmButtonTop");
    }

    /**
     * Continue button is visible before PayPal does callback.
     * Then it becomes invisible while PayPal does callback.
     * Button appears when PayPal gets callback result.
     */
    private function clickPayPalContinueOldPage()
    {
         $this->waitForItemAppear("//input[@id='continue']", 10, false);
         $this->waitForItemAppear("//input[@id='continue_abovefold']", 3, false);
         $this->waitForEditable("id=continue");
         if ($this->isElementPresent("id=continue_abovefold") && $this->isEditable("id=continue_abovefold")) {
           $this->clickAndWait("id=continue_abovefold");
         } else {
            $this->clickAndWait("id=continue");
         }
    }

    /**
     * Waits until PayPal page is loaded.
     * Decides if try to wait by new or old user interface.
     */
    private function waitForPayPalPage()
    {
        if ($this->newPayPalUserInterface) {
            $this->waitForPayPalNewPage();
        } else {
            $this->waitForPayPalOldPage();
        }
    }

    /**
     * Waits until PayPal page is loaded.
     * PayPal page is external and not Shop related.
     * New user interface has iFrame which must be selected.
     */
    private function waitForPayPalNewPage()
    {
        $this->waitForElement("id=injectedUnifiedLogin", 10, true);

        // We sometimes end up on the old PayPal login page
        if (!$this->isElementPresent("id=injectedUnifiedLogin") && $this->isElementPresent(self::PAYPAL_LOGIN_BUTTON_ID_OLD)) {
            $this->newPayPalUserInterface = false;
            return;
        }

        $this->selectCorrectLoginFrame();

        $this->waitForElement(self::PAYPAL_LOGIN_BUTTON_ID_NEW);

        $this->selectWindow(null);
    }

    /**
     * Waits until PayPal page is loaded.
     * PayPal page is external and not Shop related.
     */
    private function waitForPayPalOldPage()
    {
        $this->waitForElement(self::PAYPAL_LOGIN_BUTTON_ID_OLD);
    }

    /**
     * @param string $basketPrice
     * @param string $capturedPrice
     */
    private function checkOrderPayPalTabPricesCorrect($basketPrice, $capturedPrice)
    {
        $this->assertEquals("{$basketPrice} EUR", $this->getOrderPayPalTabBasketPrice(), "Full amount is not displayed in admin PayPal tab");
        $this->assertEquals("{$capturedPrice} EUR", $this->getOrderPayPalTabPrice(3, self::IDENTITY_COLUMN_ORDER_PAYPAL_TAB_PRICE_VALUE), "Captured amount is not displayed in admin PayPal tab");
        $this->assertEquals("0,00 EUR", $this->getOrderPayPalTabPrice(4, self::IDENTITY_COLUMN_ORDER_PAYPAL_TAB_PRICE_VALUE), "Refunded amount is not displayed in admin PayPal tab");
        $this->assertEquals("$capturedPrice EUR", $this->getOrderPayPalTabPrice(5, self::IDENTITY_COLUMN_ORDER_PAYPAL_TAB_PRICE_VALUE), "Resulting amount is not displayed in admin PayPal tab");
        $this->assertEquals("0,00 EUR", $this->getOrderPayPalTabPrice(6, self::IDENTITY_COLUMN_ORDER_PAYPAL_TAB_PRICE_VALUE), "Voided amount is not displayed in admin PayPal tab");
    }

    private function getOrderPayPalTabBasketPrice()
    {
        return $this->getOrderPayPalTabPrice(2, 2);
    }

    /**
     * @param integer $row
     * @param integer $column
     *
     * @return bool
     */
    private function getOrderPayPalTabPrice($row, $column)
    {
        return $this->getText("//table[@class='paypalActionsTable']/tbody/tr[" . $row . "]/td[" . $column . "]/b");
    }

    /**
     * @param $actionName
     * @param $amount
     * @param $paypalStatus
     */
    private function checkOrderPayPalTabHistoryCorrect($actionName, $amount, $paypalStatus)
    {
        $this->assertEquals($actionName, $this->getText("//table[@id='historyTable']/tbody/tr[2]/td[2]"), "Money status is not displayed in admin PayPal tab");
        $this->assertEquals("{$amount} EUR", $this->getText("//table[@id='historyTable']/tbody/tr[2]/td[3]"));
        $this->assertEquals($paypalStatus, $this->getText("//table[@id='historyTable']/tbody/tr[2]/td[4]"), "Money status is not displayed in admin PayPal tab");
    }

    /**
     * @param $quantity
     * @param $productNumber
     * @param $productTitle
     * @param $productGrossPrice
     * @param $productTotalPrice
     * @param $productVat
     */
    private function checkOrderPayPalTabProductsCorrect($quantity, $productNumber, $productTitle, $productGrossPrice, $productTotalPrice, $productVat)
    {
        $this->assertEquals($quantity, $this->getText("//tr[@id='art.1']/td"));
        $this->assertEquals($productNumber, $this->getText("//tr[@id='art.1']/td[2]"));
        $this->assertEquals($productTitle, $this->getText("//tr[@id='art.1']/td[3]"));
        $this->assertEquals("{$productGrossPrice} EUR", $this->getText("//tr[@id='art.1']/td[4]"));
        $this->assertEquals("{$productTotalPrice} EUR", $this->getText("//tr[@id='art.1']/td[5]"));
        $this->assertEquals($productVat, $this->getText("//tr[@id='art.1']/td[6]"));
    }

    /**
     * Validate last request/response pair in log.
     *
     * @param array $assertRequest  Values to assert.
     * @param array $assertResponse Values to assert.
     * @param bool  $cleanLog       Clean log after check.
     *
     */
    private function assertLogData($assertRequest, $assertResponse, $cleanLog = true)
    {
        $data = $this->callShopSC(\OxidEsales\PayPalModule\Tests\Acceptance\PayPalLogHelper::class, 'getLogData');

        //last thing in log has to be the response from PayPal
        $response = array_pop($data);
        $sessionId = $response->sid;
        $this->assertEquals('response', $response->type);
        $this->assertLogValues($response->data, $assertResponse);

        //following last element has to be the related request
        $request = array_pop($data);
        $this->assertEquals('request', $request->type);
        $this->assertEquals($sessionId, $response->sid);
        $this->assertLogValues($request->data, $assertRequest);

        if ($cleanLog) {
            $this->callShopSC(\OxidEsales\PayPalModule\Tests\Acceptance\PayPalLogHelper::class, 'cleanPayPalLog');
        }
    }

    /**
     * Validate log data.
     *
     * @param array $logData
     * @param array $expected
     */
    private function assertLogValues($logData, $expected)
    {
        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $logData[$key]);
        }
    }

    /**
     * Finish payment process part that's to be done on PayPal page.
     *
     * @param bool $expressCheckout
     * @param bool $usBuyer
     */
    private function payWithPayPal($expressCheckout = false, $usBuyer = false)
    {
        $loginMail = $this->getLoginDataByName('sBuyerLogin');

        //we might be automatically get logged in by PayPal, check before trying to log in again
        $this->selectWindow(null);
        $this->_waitForAppear('isTextPresent', $this->getLoginDataByName('sBuyerFirstName'), 10, true);
        if (!$this->isElementPresent("//input[@id='confirmButtonTop']") && !$this->isElementPresent("//input[@id='continue']")) {
            if (!$expressCheckout) {
                $this->waitForPayPalPage();
            }
            if ($usBuyer) {
                $loginMail = $this->getLoginDataByName('sBuyerUSLogin');
            }
            $this->loginToSandbox($loginMail);
        }
        $this->clickPayPalContinue();
    }

    /**
     * Wait, till the login to the PayPal sandbox is completed.
     */
    private function waitForLoggedInToPayPalSandbox()
    {
        $this->waitForItemAppear("id=continue");
        $this->waitForItemAppear("id=displayShippingAmount");
    }

    /**
     * Click on the link to go to the first step in the OXID eShop basket.
     */
    private function clickFirstStepInShopBasket()
    {
        $this->clickAndWait("link=1. Cart");
    }

    /**
     * Click on the link to go to the next step in the OXID eShop basket.
     */
    private function clickNextStepInShopBasket()
    {
        $this->clickAndWait("//button[text()='Continue to the next step']");
    }

    private function loginToShopFrontend()
    {
        $this->loginInFrontend(self::LOGIN_USERNAME, self::LOGIN_USERPASS);
        $this->waitForElement("paypalExpressCheckoutButton", "PayPal express button not displayed in the cart");
        $this->assertElementPresent("link=Test product 1", "Purchased product name is not displayed");
        $this->assertElementPresent("//tr[@id='cartItem_1']/td[3]/div[2]");
        $this->assertEquals("Grand total: 0,99 €", $this->clearString($this->getText("//div[@id='basketSummary']//tr[5]")), "Grand total is not displayed correctly");
        $this->assertTextPresent("Shipping costs:", "Shipping costs is not displayed correctly");
        $this->assertTextPresent("?");
        $this->assertTrue($this->isChecked("//input[@name='displayCartInPayPal' and @value='1']"));
        $this->assertTextPresent("Display cart in PayPal", "Text:Display cart in PayPal for checkbox not displayed");
        $this->assertElementPresent("displayCartInPayPal", "Checkbox:Display cart in PayPal not displayed");
    }

    private function assertAllAvailableShippingMethodsAreDisplayed()
    {
        $this->assertTextPresent("Test Paypal:6 hour", "Not all available shipping methods is displayed");
        $this->assertTextPresent("Test Paypal:12 hour", "Not all available shipping methods is displayed");
        $this->assertTextPresent("Standard", "Not all available shipping methods is displayed");
        $this->assertTextPresent("Example Set1: UPS 48 hours", "Not all available shipping methods is displayed");
        $this->assertTextPresent("Example Set2: UPS Express 24 hours", "Not all available shipping methods is displayed");
    }

    private function waitForShop()
    {
        $this->waitForItemAppear("id=breadCrumb");
    }

    /**
     * Select Belgium as the delivery address, if it not already is.
     */
    private function selectDeliveryAddressBelgium()
    {
        // @todo: introduce language independant if!
        if (!$this->isTextPresent("Test address in Belgium 15, Antwerp, Belgium")) {
            // adding new address (Belgium) to address list
            $this->clickAndWait("id=addShipAddress");
            $this->select("country_code", "label=Belgium");
            $this->type("id=shipping_address1", "Test address in Belgium 15");
            $this->type("id=shipping_city", "Antwerp");

            //returning to address list
            $this->click("//input[@id='continueBabySlider']");
        }

        $this->click("//label[@class='radio' and contains(.,'Test address in Belgium 15, Antwerp, Belgium')]/input");
    }

    private function changeCountryInBasketStepTwo($country)
    {
        $this->click('userChangeAddress');

        $this->waitForElement("//select[@id='invCountrySelect']/option[text()='$country']");
        $this->select("//select[@id='invCountrySelect']", "label=$country");

        $this->clickNextStepInShopBasket();
    }

    /**
     * Handle express checkout on PayPal page.
     *
     * @param string $expressCheckoutButtonIdentification
     * @param bool   $usBuyer
     */
    private function payWithPayPalExpressCheckout($expressCheckoutButtonIdentification = 'paypalExpressCheckoutButton', $usBuyer = false)
    {
        $this->_waitForAppear('isElementPresent', "//input[@id='{$expressCheckoutButtonIdentification}']", 3, true);
        $this->expressCheckoutWillBeUsed();
        $this->click($expressCheckoutButtonIdentification);
        $this->payWithPayPal(true, $usBuyer);
    }
}

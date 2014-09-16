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
 * @copyright (C) OXID eSales AG 2003-2014
 */

/**
 * PayPal response class for do reauthorize
 */
class oePayPalResponseDoReAuthorize extends oePayPalResponse
{

    /**
     * Return authorization id.
     *
     * @return string
     */
    public function getAuthorizationId()
    {
        return $this->_getValue('AUTHORIZATIONID');
    }

    /**
     * Return payment status.
     *
     * @return string
     */
    public function getPaymentStatus()
    {
        return $this->_getValue('PAYMENTSTATUS');
    }

    /**
     * Return transaction id.
     *
     * @return string
     */
    public function getCorrelationId()
    {
        return $this->_getValue('CORRELATIONID');
    }
}

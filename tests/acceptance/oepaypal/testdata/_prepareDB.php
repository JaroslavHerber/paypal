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

/**
 * Full reinstall
 */

ob_start();

class _config
{
    function __construct()
    {
        if (file_exists('_version_define.php')) {
            include_once '_version_define.php';
            define('EDITION_PREFIX', '_');
        } else {
            define('EDITION_PREFIX', '');
        }
        include "config.inc.php";
        include "core/oxconfk.php";
    }
}

$_cfg = new _config();

{
    if (isset($_SERVER['HTTP_COOKIE'])) {
        $aCookies = explode(';', $_SERVER['HTTP_COOKIE']);
        foreach ($aCookies as $sCookie) {
            $sRawCookie = explode('=', $sCookie);
            setcookie(trim($sRawCookie[0]), '', time() - 10000, '/');
            $aDeletedCookies[] = $sRawCookie[0];
        }
    }
}
echo "<h1>Full reinstall of OXID eShop</h1>";
echo "<h2>Delete cookies</h2>";
echo "<ol>";
echo "<li>" . (($aDeletedCookies) ? implode(" | ", $aDeletedCookies) : "No cookies found") . "</li>";
echo "</ol>";

echo "<hr>";

echo "<h2>Cleanup tmp directory</h2>";
echo "<ul>";
{
    $iTotalCleaned = 0;
    foreach (glob($_cfg->sCompileDir . "/*") as $filename) {
        if (is_file($filename)) {
            unlink($filename);
            $iTotalCleaned++;
        }
        if (is_dir($filename)) {
            rmdir($filename);
            $iTotalCleaned++;
        }
    }
    echo "<li>Total files cleaned: $iTotalCleaned</li>";
}
echo "</ul>";

if ($_GET["version"]) {
    $sVersion = $_GET["version"];
} else {
    if (OXID_VERSION_PE) {
        $sVersion = "PE";
    }
    if (OXID_VERSION_EE) {
        $sVersion = "EE";
    }
    if (OXID_VERSION_CE) {
        $sVersion = "CE";
    }
}

echo "<hr>";

echo "<h2>Install and configure database</h2>";
echo "<ol>";
{
    if ($sVersion == "PE") :
        $sSerial = '3Q3EQ-U4562-Y9JTE-2N6LP-JTJ9K-GNVLK';
        $iEdition = 1;
    endif;

    if ($sVersion == "EE") :
        $sSerial = 'EF7FV-B9TA8-3R3SD-MZNU4-7NWM3-AN7AU';
        $iEdition = 2;
        $sShopId = '1';
        $sPaypalSetupFile = '/setup/sql/selenium_paypalSetup_ee.sql';
    else:
        $sShopId = 'oxbaseshop';
        $sPaypalSetupFile = '/setup/sql/selenium_paypalSetup_pe.sql';
    endif;

    $sSQLDirectoryName = EDITION_PREFIX ? 'sql' . EDITION_PREFIX . strtolower($sVersion) : 'sql';

    $_key = $_cfg->sConfigKey;
    $oDB = mysql_connect('localhost', $_cfg->dbUser, $_cfg->dbPwd);

    if ($_cfg->iUtfMode) {
        mysql_query("anter schema character set utf8 collate utf8_general_ci", $oDB);
        mysql_query("set names 'utf8'", $oDB);
        mysql_query("set character_set_database=utf8", $oDB);
        mysql_query("set character set utf8", $oDB);
        mysql_query("set character_set_connection = utf8", $oDB);
        mysql_query("set character_set_results = utf8", $oDB);
        mysql_query("set character_set_server = utf8", $oDB);
    } else {
        mysql_query("alter schema character set latin1 collate latin1_general_ci", $oDB);
        mysql_query("set character set latin1", $oDB);
    }

    echo "<li>drop database '" . $_cfg->dbName . "'</li>";
    mysql_query('drop   database ' . $_cfg->dbName, $oDB);

    echo "<li>create database '" . $_cfg->dbName . "'</li>";
    mysql_query('create database ' . $_cfg->dbName, $oDB);

    echo "<li>select database '" . $_cfg->dbName . "'</li>";
    mysql_select_db($_cfg->dbName, $oDB);

    echo "<li>insert 'database.sql'</li>";
    passthru('mysql -u' . $_cfg->dbUser . ' -p' . $_cfg->dbPwd . ' ' . $_cfg->dbName . ' < ' . dirname(__FILE__) . "/setup/$sSQLDirectoryName/database.sql");

    echo "<li>insert 'demodata.sql'</li>";
    passthru('mysql -u' . $_cfg->dbUser . ' -p' . $_cfg->dbPwd . ' ' . $_cfg->dbName . ' < ' . dirname(__FILE__) . "/setup/$sSQLDirectoryName/demodata.sql");

    echo "<li><b>Configuring shop - 'selenium_shopConfig.sql'</b></li>";
    passthru('mysql -u' . $_cfg->dbUser . ' -p' . $_cfg->dbPwd . ' ' . $_cfg->dbName . ' < ' . dirname(__FILE__) . "/setup/$sSQLDirectoryName/selenium_shopConfig.sql");

    echo "<li><b>Adding PayPal data - 'selenium_paypalSetup.sql'</b></li>";
    $sUtf8Mode = $_cfg->iUtfMode ? "--default-character-set=utf8" : "";
    passthru('mysql -u' . $_cfg->dbUser . ' -p' . $_cfg->dbPwd . ' ' . $sUtf8Mode . ' ' . $_cfg->dbName . ' < ' . dirname(__FILE__) . $sPaypalSetupFile);

    echo "<li>set configuration parameters</li>";
    mysql_query("delete from oxconfig where oxvarname in ('iSetUtfMode','blLoadDynContents','sShopCountry')", $oDB);
    mysql_query(
        "insert into oxconfig (oxid, oxshopid, oxvarname, oxvartype, oxvarvalue) values " .
        "('config1', '{$sShopId}', 'iSetUtfMode',       'str',  ENCODE('0', '{$_key}') )," .
        "('config2', '{$sShopId}', 'blLoadDynContents', 'bool', ENCODE('1', '{$_key}') )," .
        "('config3', '{$sShopId}', 'sShopCountry',      'str',  ENCODE('de','{$_key}') )", $oDB
    );

    if ($sVersion != "CE" && !empty($sSerial)) {

        require_once "core/oxserial.php";

        $oSerial = new oxSerial();
        $oSerial->setEd($iEdition);
        $oSerial->isValidSerial($sSerial);

        echo "<li>add demo serial '{$sSerial}'</li>";

        mysql_query("update oxshops set oxserial = '{$sSerial}'", $oDB);
        mysql_query("delete from oxconfig where oxvarname in ('aSerials','sTagList','IMD','IMA','IMS')", $oDB);
        mysql_query(
            "insert into oxconfig (oxid, oxshopid, oxvarname, oxvartype, oxvarvalue) values " .
            "('serial1', '{$sShopId}', 'aSerials', 'arr', ENCODE('" . serialize(array($sSerial)) . "','{$_key}') )," .
            "('serial2', '{$sShopId}', 'sTagList', 'str', ENCODE('" . time() . "','{$_key}') )," .
            "('serial3', '{$sShopId}', 'IMD',      'str', ENCODE('" . $oSerial->getMaxDays($sSerial) . "','{$_key}') )," .
            "('serial4', '{$sShopId}', 'IMA',      'str', ENCODE('" . $oSerial->getMaxArticles($sSerial) . "','{$_key}') )," .
            "('serial5', '{$sShopId}', 'IMS',      'str', ENCODE('" . $oSerial->getMaxShops($sSerial) . "','{$_key}') )", $oDB
        );
    }

    if ($_cfg->iUtfMode) {
        echo "<li>convert shop config to utf8</li>";

        $rs = mysql_query("select oxvarname, oxvartype, DECODE( oxvarvalue, '{$_key}') as oxvarvalue from oxconfig where oxvartype in ('str', 'arr', 'aarr') ", $oDB);

        $aCnv = array();
        while ($aRow = mysql_fetch_assoc($rs)) {

            if ($aRow['oxvartype'] == 'arr' || $aRow['oxvartype'] == 'aarr') {
                $aRow['oxvarvalue'] = unserialize($aRow['oxvarvalue']);
            }
            $aRow['oxvarvalue'] = to_utf8($aRow['oxvarvalue']);
            $aCnv[] = $aRow;
        }

        foreach ($aCnv as $oCnf) {
            $_vnm = $oCnf['oxvarname'];
            $_val = $oCnf['oxvarvalue'];
            if (is_array($_val)) {
                $_val = mysql_real_escape_string(serialize($_val), $oDB);
            } elseif (is_string($_val)) {
                $_val = mysql_real_escape_string($_val, $oDB);
            }

            mysql_query("update oxconfig set oxvarvalue = ENCODE( '{$_val}','{$_key}') where oxvarname = '{$_vnm}';", $oDB);
        }
    }
}

function to_utf8($in)
{
    if (is_array($in)) {
        foreach ($in as $key => $value) {
            $out[to_utf8($key)] = to_utf8($value);
        }
    } elseif (is_string($in)) {
        return iconv('iso-8859-15', 'utf-8', $in);
    } else {
        return $in;
    }

    return $out;
}

echo "</ol>";

header("Location: " . $_cfg->sShopURL);

ob_end_flush();

/*
echo "<hr>",
     "<h3><a target='shp' href='".$_cfg->sShopURL."'>to Shop &raquo; </a></h3>",
     "<h3><a target='adm' href='".$_cfg->sShopURL."/admin/'>to Admin &raquo; </a></h3>";
*/



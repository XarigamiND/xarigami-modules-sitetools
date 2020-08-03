<?php
/**
 * @package modules
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Sitetools Module
 * @copyright (C) 2003-2012 2skies.com
 * @link http://xarigami.com/project/sitetools
 * @author Jo Dalle Nogare <icedlava@2skies.com>
 */

/**
 * @return int number of items held by this module
 * @throws DATABASE_ERROR
*/
function sitetools_adminapi_countitems()
{
    // Get database setup
    $dbconn = xarDBGetConn();
    $xartable = xarDBGetTables();

    $sitetoolstable = $xartable['sitetools'];

    $query = "SELECT COUNT(1)
            FROM $sitetoolstable";
    $result = $dbconn->Execute($query);
    // Check for an error with the database code, adodb has already raised
    // the exception so we just return
    if (!$result) return;
    // Obtain the number of items
    list($numitems) = $result->fields;
    // All successful database queries produce a result set, and that result
    // set should be closed when it has been finished with
    $result->Close();
    // Return the number of items
    return $numitems;
}
?>
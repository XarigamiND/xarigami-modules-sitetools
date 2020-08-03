<?php
/**
 * Site Tools Table function
 *
 * @package modules
 * @copyright (C) 2002-2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Sitetools Module
 * @copyright (C) 2003-2010 2skies.com
 * @link http://xarigami.com/project/sitetools
 * @author Jo Dalle Nogare <icedlava@2skies.com>
 */
/**
 * Return SiteTools table names to xaraya
 *
 * This function is called internally by the core whenever the module is
 * loaded.  It is loaded by xarMod__loadDbInfo().
 * 
 * @access private 
 * @return array 
 */
function sitetools_xartables()
{ 
    /* Initialise table array */
    $xarTables = array(); 
    /* Get the name for the example item table.  This is not necessary
     * but helps in the following statements and keeps them readable
     */
    $sitetoolsTable = xarDBGetSiteTablePrefix() . '_sitetools';
    /* Set the table name */
    $xarTables['sitetools'] = $sitetoolsTable;

    $xarTables['sitetools_links'] = xarDBGetSiteTablePrefix() . '_sitetools_links';
    /* Return the table information */
    return $xarTables;
}

?>
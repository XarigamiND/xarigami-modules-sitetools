<?php
/**
 * @package modules
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Sitetools Module
 * @copyright (C) 2003-2010 2skies.com
 * @link http://xarigami.com/project/sitetools
 */

/**
 * SiteTools Database abstraction class extension
 *
 * @author Richard Cave <rcave@xaraya.com>
 * @author Jo Dalle Nogare <jojodee@xaraya.com>
 * @access private
 */
require_once('modules/sitetools/xarclass/dbSiteTools.php');

class dbSiteTools_postgres extends dbSiteTools
{
    function _optimize()
    {
        $rowinfo = array();

        // Do something

        return $rowinfo;
    }

    function _backup()
    {
        return true;
    }
}

?>
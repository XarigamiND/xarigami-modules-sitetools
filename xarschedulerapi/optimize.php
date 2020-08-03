<?php
/**
 * Site Tools Scheduler for Optimize
 *
 * @package modules
 * @copyright (C) 2002-2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
*
 * @subpackage Xarigami Sitetools Module
 * @copyright (C) 2003-2009 2skies.com
 * @link http://xarigami.com/project/sitetools
 * @author Jo Dalle Nogare <icedlava@2skies.com>
 */

/**
 * optimize the database (executed by the scheduler module)
 * 
 * @author jojodee <http://xaraya.athomeandabout.com >
 * @access private
 */
function sitetools_schedulerapi_optimize($args)
{
    extract($args);

    /* DO LATER: get some configuration info here if necessary
     * for now lets just use current database
     */
    if (empty($dbname)){
        $dbconn = xarDBGetConn();
            $dbname= xarDBGetName();
    }

    /*   It may return true (or some logging text) if it succeeds, and null if it fails
     *   return
     */
     $tabledata=xarModAPIFunc('sitetools','admin','optimizedb',
                      array('dbname' => $dbname));

       $total_gain= $tabledata['total_gain'];
       $total_gain = round ($total_gain,3);
       //Add this new optimization record to the database
       return xarModAPIFunc('sitetools','admin','create',
                              array('totalgain' => $total_gain));
                              
    return true;
}

?>
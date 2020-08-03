<?php
/**
 * Overview displays standard Overview page
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
 * Overview displays standard Overview page
 *
 * Used to call the template that provides display of the overview
 *
 * @author jojodee
 * @returns array xarTplModule with $data containing template data
 * @return array containing the menulinks for the overview item on the main manu
 * @since 2 Nov 2005
 */
function sitetools_admin_overview()
{
   /* Security Check */
    if (!xarSecurityCheck('AdminSiteTools')) return;

    $data=array();

    //common menu link
    $data['menulinks']= xarModAPIFunc('sitetools','admin','getmenulinks');    
    /* if there is a separate overview function return data to it
     * else just call the main function that usually displays the overview
     */

    return xarTplModule('sitetools', 'admin', 'main', $data,'main');
}

?>
<?php
/**
 * Site Tools Main Admin
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Sitetools Module
 * @copyright (C) 2003-2010 2skies.com
 * @link http://xarigami.com/project/sitetools
 * @author Jo Dalle Nogare <icedlava@2skies.com>
 */

/**
 * The main administration function
 * This function is the default function, and is called whenever the
 * module is initiated without defining arguments.
 */
function sitetools_admin_main()
{ 
    /* Security check */
    if (!xarSecurityCheck('EditSiteTools')) return;


    //common menu link
    $data['menulinks']= xarModAPIFunc('sitetools','admin','getmenulinks');
        $data['welcome'] = '';
  
        xarResponseRedirect(xarModURL('sitetools', 'admin', 'modifyconfig'));

    return true;
}
?>
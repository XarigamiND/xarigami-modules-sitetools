<?php
/**
 * @package modules
 *
 * @subpackage Xarigami Sitetools Module
 * @copyright (C) 2003-2010 2skies.com
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xarigami.com/project/sitetools
 * @author Jo Dalle Nogare <icedlava@2skies.com>
 */
/**
 * utility function pass individual menu items to the main menu
 *
 * @author jojodee
 * @returns array
 * @return array containing the menulinks for the main menu items.
 */
function sitetools_adminapi_getmenulinks()
{ 
    $menulinks = array();

     /* Security Check */
    if (xarSecurityCheck('AdminSiteTools', 0)) {
        /* The main menu will look for this array and return it for a tree view of the module*/

        $menulinks[] = Array('url' => xarModURL('sitetools', 'admin','optimize'),
            'title' => xarML('Optimize a database'),
            'label' => xarML('Optimize database'),
            'active' => array('optimize')
            );

        $menulinks[] = Array('url' => xarModURL('sitetools','admin','backup'),
            'title' => xarML('Backup a database'),
            'label' => xarML('Backup database'),
            'active' => array('backup','downloaddel'),
            'activelabels' => array('',xarML('Delete Backup'))
            );
        $menulinks[] = Array('url' => xarModURL('sitetools','admin','terminal'),
            'title' => xarML('Access Database SQL via a simple web terminal'),
            'label' => xarML('SQL Terminal'),
            'active' => array('terminal')
            );
        $menulinks[] = Array('url' => xarModURL('sitetools','admin','cacheview'),
            'title' => xarML('Browse template cache files'),
            'label' => xarML('Browse cache'),
            'active' => array('cacheview')
            );

        $menulinks[] = Array('url' => xarModURL('sitetools','admin','deletecache'),
            'title' => xarML('Clear template, rss and adodb cache files'),
            'label' => xarML('Flush cache'),
            'active' => array('deletecache')
            );

        $menulinks[] = Array('url' => xarModURL('sitetools','admin','links'),
            'title' => xarML('Check URLs and images in articles, roles and so on'),
            'label' => xarML('Check links'),
            'active' => array('links')
            );

        $menulinks[] = Array('url' => xarModURL('sitetools','admin','modifyconfig'),
            'title' => xarML('Modify the configuration for the module'),
            'label' => xarML('Modify Config'),
            'active' => array('modifyconfig')
            );
    }

    /* The final thing that we need to do in this function is return the values back
     * to the main menu for display.
     */
    return $menulinks;
}
?>
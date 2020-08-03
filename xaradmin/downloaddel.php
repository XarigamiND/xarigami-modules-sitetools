<?php
/**
 * Site Tools Backup package
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

/*@function to delete the just completed backup
 *@parameter $bkfile is the name of the backup file
 *
*/
function sitetools_admin_downloaddel ($args)
{
    if (!xarVarFetch('savefile', 'str:1', $savefile,'')) return;

    /* Security checkn*/
    if (!xarSecurityCheck('AdminSiteTools')) return;

    if ((!isset($savefile)) || (empty($savefile))) {
        // Handle the user exceptions yourself
        $status = xarML('The file to delete does not exist.');
        return $status;
    }
    $info=array();
    $outcome = -1;
    //get the current options
    $sitetoolconfig = xarModGetVar('sitetools','sitetooloptions');
    $sitetoolconfig = unserialize($sitetoolconfig);

    foreach ($sitetoolconfig  as $varname =>$value) {
        $$varname = $value;
    }


    /*check the file exists */
    $pathtofile= $backuppath;

    $filetodelete = $pathtofile.'/'.$savefile;
    $info['menulinks']= xarModAPIFunc('sitetools','admin','getmenulinks');
    $info['filedeleted']=$filetodelete;
    if (!file_exists($filetodelete)) {
        /* Handle the user exceptions yourself */
        $status = xarML('The backup file requested for deletion does not exists.');
        xarTplSetMessage($status,'error');
        $info['outcome'] = -1;
        return $info;
    } else {
        $filedeleted=unlink($filetodelete);
        if ($filedeleted) {
            $outcome=1;

        } else {
            $outcome=0;
        }
    }
    $info['outcome'] = $outcome;
    //common menu link

    xarTplSetMessage(xarML('Your backup file was successfully deleted from the server.'),'status');
    return $info;
}
?>
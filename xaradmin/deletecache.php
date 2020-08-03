<?php
/**
 * Site Tools Template Cache Management
 *
 * @package modules
 * @copyright (C) 2002-2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Sitetools Module
 * @copyright (C) 2003-2011 2skies.com
 * @link http://xarigami.com/project/sitetools
 * @author Jo Dalle Nogare <icedlava@2skies.com>
 */

/**
 * Clear cache files
 * @author jojodee
 * @param  $ 'confirm' confirm that this item can be deleted
 */
function sitetools_admin_deletecache($args)
{
    extract($args);

    // Get parameters from whatever input we need.
    if (!xarVarFetch('delrss', 'checkbox', $delrss, false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('delstyle', 'checkbox', $delstyle, false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('deltempl', 'checkbox', $deltempl, false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('confirm', 'str:1:', $confirm, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('returnurl', 'str', $returnurl, '', XARVAR_NOT_REQUIRED)) return;

    /* Security check - important to do this as early as possible */
    if (!xarSecurityCheck('DeleteSiteTools')) {
        return;
    }
    $data=array();
    $data['returnurl'] = $returnurl;
   //get the current options
    $sitetoolconfig = xarModGetVar('sitetools','sitetooloptions');
    $sitetoolconfig = unserialize($sitetoolconfig);

    foreach ($sitetoolconfig  as $varname =>$value) {
        $$varname = $value;
    }
    $stylepath   = $stylecachepath;
    $rsspath  = $rsscachepath;
    $templpath = $templcachepath;
    $data['stylepath']   = $stylepath;
    $data['rsspath']   = $rsspath;
    $data['templpath'] = $templpath;
    //common menu link
    $data['menulinks']= xarModAPIFunc('sitetools','admin','getmenulinks');

    /* Check for confirmation. */
    if (empty($confirm)) {
        /* No confirmation yet - display a suitable form to obtain confirmation
         * of this action from the user
         */

        $data['delstyle']    = 0;
        $data['delrss']    = 0;
        $data['deltempl']  = 0;
        $data['delfin']    = false;
        /* Generate a one-time authorisation code for this operation */
        $data['authid'] = xarSecGenAuthKey('sitetools');
        /*Return the template variables defined in this function */
        return $data;
    }
    /* If we get here it means that the user has confirmed the action */

    /* Confirm authorisation code. */
    if (!xarSecConfirmAuthKey()) return;
    $errorcount = 0;
    if ($delstyle || $delrss || $deltempl) {
        if ($delstyle==1) {
        /* recursively delete all style cache files
         * Get site folder name
         */
            $var = is_dir($stylepath);
            if ($var) {
                if (!is_writable($stylepath)) {
                    $msg = xarML("The Styles cache directory and files in #(1) could not be deleted! Please make this directory writeable by the webserver.", $stylepath);
                    xarTplSetMessage($msg,'error');
                    $errorcount++;
                } else { /* making a few assumptions about structure of style cache subdirs and files here */
                    $handle=opendir($stylepath);
                     $skip_array = array('.','..','SCCS','index.htm','index.html');
                    while (false !== ($file = readdir($handle))) {
                      /* check the skip array and delete files that are not in it */
                      if (!in_array($file,$skip_array)) {
                        unlink($stylepath."/".$file);/* delete the file */
                      }
                    }
                    closedir($handle);
                    $msg = xarML('Style cache directory files successfully deleted.');
                    xarTplSetMessage($msg,'status');
                    $data['delfin']    = true;
                }
            }
        }
        if ($delrss==1) {
            /* delete all rss cache files */
            $var = is_dir($rsspath);
            if ($var) {
                /*chmod($templpath,0755); path should already be writable */
                if (!is_writable($rsspath)) {
                    $msg = xarML("The RSS cache directory and files in #(1) could not be deleted! Please make this directory writable by the web server.", $rsspath);
                     xarTplSetMessage($msg,'error');
                     $errorcount++;
                 } else {
                    $handle=opendir($rsspath);
                    /* iansym::these are the files we do not want to delete */
                    $skip_array = array('.','..','SCCS','index.htm','index.html');
                    while (false !== ($file = readdir($handle))) {
                      /* check the skip array and delete files that are not in it */
                      if (!in_array($file,$skip_array)) {
                        unlink($rsspath."/".$file);/* delete the file */
                      }
                    }
                    closedir($handle);
                    $msg = xarML('RSS Cache directory files successfully deleted.');
                    xarTplSetMessage($msg,'status');
                    $data['delfin']    = true;
                }
            }
        }

        if ($deltempl==1) {
            /*  delete all template cache files
             * Get site folder name
             */

            $var = is_dir($templpath);
            if ($var) {
                /*chmod($templpath,0755); ath should already be writable */
                if (!is_writable($templpath)) {
                    $msg = xarML("The Template cache directory and files in #(1) could not be deleted! Please make this directory writable by the web server.", $templpath);
                    xarTplSetMessage($msg,'error');
                    $errorcount++;
                } else {
                    $handle=opendir($templpath);
                    /* iansym::these are the files we do not want to delete */
                    $skip_array = array('.','..','index.htm','index.html','SCCS');
                    while (false !== ($file = readdir($handle))) {
                      /* check the skip array and delete files that are not in it */
                      if(!in_array($file,$skip_array)) {
                        unlink($templpath."/".$file); /* delete this file */
                      }
                    }
                    closedir($handle);
                    $msg = xarML('Template Cache directory files successfully deleted.');
                    xarTplSetMessage($msg,'status');
                    $data['delfin']    = true;
                }
            }

        }

       //for some modules that call this function directly let's return to them
            if (!empty($returnurl)) {

                xarResponseRedirect($returnurl);
                return;
            } else {
            xarResponseRedirect(xarModURL('sitetools', 'admin', 'deletecache'));
            }
    } else {
          $msg = xarML('No cache directory was selected. Please select one or more cache directories.');
                    xarTplSetMessage($msg,'alert');
    }
    /* This function generated no output, and so now it is complete we redirect
     * the user to an appropriate page for them to carry on their work */
    xarResponseRedirect(xarModURL('sitetools', 'admin', 'deletecache'));
    return true;
}
?>
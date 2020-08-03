<?php
/**
 * Site Tools Update Configuration
 *
 * @package modules
 * @copyright (C) 2003-2011 2skies.com
 * @link http://xarigami.com/project/sitetools
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @subpackage Xarigami Sitetools Module
 * @author Jo Dalle Nogare <icedlava@2skies.com>
 */

/**
 * This is a standard function to update the configuration parameters of the
 * module given the information passed back by the modification form
 *
 * @return bool true on success of update
 */
function sitetools_admin_updateconfig()
{
    if (!xarVarFetch('backuppath', 'str:1:254', $backuppath,'')) return;
    if (!xarVarFetch('defaultbktype', 'str:4', $defaultbktype,'')) return;
    if (!xarVarFetch('lineterm', 'str:0:4', $lineterm, "\n")) return;
    if (!xarVarFetch('quotechar', 'str:0:', $quotechar, '\'')) return;
    if (!xarVarFetch('backtickchar', 'str:0:', $backtickchar, '')) return;
    if (!xarVarFetch('timestamp', 'checkbox', $timestamp, true)) return;
  //  if (!xarVarFetch('usedbprefix', 'checkbox', $usedbprefix, false)) return;
    if (!xarVarFetch('colnumber', 'int:1:', $colnumber,3)) return;
    if (!xarVarFetch('dbcompresstype', 'str:0:4', $dbcompresstype, 'bzip2')) return;
    if (!xarVarFetch('createifnotexists', 'checkbox', $createifnotexists, false)) return;
    if (!xarVarFetch('dbnameincreate', 'checkbox', $dbnameincreate, true)) return;
    if (!xarVarFetch('replaceinto', 'checkbox', $replaceinto, false)) return;
    if (!xarVarFetch('restoredefault', 'checkbox', $restoredefault, false)) return;
    if (!xarVarFetch('nohtml', 'checkbox', $nohtml, false)) return;
    if (!xarVarFetch('hexblobs', 'checkbox', $hexblobs, true)) return;
    if (!xarVarFetch('disablemysqldump', 'checkbox', $disablemysqldump, TRUE)) return;
    if (!xarVarFetch('neverbackupdbtypes', 'array', $neverbackupdbtypes, array('HEAP'))) return;
    if (!xarVarFetch('compresslevel', 'int:0', $compresslevel, 6)) return;
    if (!xarVarFetch('confirm', 'str:4:254', $confirm, '', XARVAR_NOT_REQUIRED)) return;
    //ftp and scheduler options
    if (!xarVarFetch('ftpserver', 'str:4:254', $ftpserver, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('ftpuser', 'str:2:254', $ftpuser, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('ftpdir', 'str:1:254', $ftpdir, '.', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('ftppw', 'str:3:254', $ftppw, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('useftpbackup', 'checkbox', $useftpbackup, false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('usesftpbackup', 'checkbox', $usesftpbackup, false, XARVAR_NOT_REQUIRED)) return;

    if (!xarSecConfirmAuthKey()) return;
    $errorcount = 0;
    if ($restoredefault == TRUE) {
        $default= xarModGetVar('sitetools','default');
        xarModSetVar('sitetools','sitetooloptions',$default);
        xarResponseRedirect(xarModURL('sitetools', 'admin', 'modifyconfig'));
    }

    //use the default paths for the styles, rss and template paths now that we have relocatable var
    //for now just set here - TODO: go through code and remove redundant lines
    $stylecachepath = sys::varpath().'/cache/styles';
    $rsscachepath = sys::varpath().'/cache/rss';
    $templcachepath = sys::varpath().'/cache/templates';
    $linterm = "$lineterm";
    $varpath = sys::varpath();

    $backuppath = trim($backuppath);

    //backuppath might include ./var - replace it with real var path
    if (preg_match('/^var\//i',$backuppath)) {
        $backuppath = str_replace('var',$varpath,$backuppath);
    }
    //backuppath might include ./var - replace it with real var path
    if (preg_match('/^\.\/var\//i',$backuppath)) {
        $backuppath = str_replace('./var',$varpath,$backuppath);
    }
    //if it still contains a ./var then we need the real path
    if (preg_match('/^\.\/var\//i',$backuppath)) {
        $varpath = realpath('./var');
        $backuppath = str_replace('./var',$varpath,$backuppath);
    }

    //check the backup path
    $backuppath = trim(preg_replace('/\/$/', '', $backuppath));
    $defaultback = sys::varpath()."/uploads";
    if ($backuppath== '') {

        $msg = xarML('The backup path was invalid because it was set to empty and therefore was reset to the default. ');
        xarTplSetMessage($msg,'error');
        $errorcount++;
        $backuppath = $defaultback;
    } elseif (!file_exists($backuppath) || !is_dir($backuppath)) {

            $msg = xarML('Location [#(1)] for backup path either does not exist or is not a valid directory! The backup path was reset to default. ', $backuppath);
            xarTplSetMessage($msg,'error');
            $errorcount++;
            $backuppath = $defaultback;
    } elseif (!is_writable($backuppath)) {

           $msg = xarML('Location [#(1)] is not writeable by the server. Please change the permissions to make it writeable. ', $backuppath);
           xarTplSetMessage($msg,'error');
           $errorcount++;
            $backuppath = $defaultback;
    } else {
         xarModSetVar('sitetools', 'backuppath', $backuppath);
    }
    //now set the options array
    $checkboxlist = array('timestamp','createifnotexists','dbnameincreate','replaceinto','restoredefault','nohtml','hexblobs','disablemysqldump','useftpbackup','usesftpbackup');
    //ensure checkboxes are set
    foreach ( $checkboxlist as $boxname) {
        $$boxname = isset($$boxname)?$$boxname:0;
    }
    $vararray = array('nohtml','dbcompresstype','compresslevel','replaceinto',
                      'hexblobs','createifnotexists','dbnameincreate','neverbackupdbtypes','colnumber',
                      'defaultbktype','lineterm','timestamp','backuppath','stylecachepath','rsscachepath',
                      'templcachepath','backtickchar','quotechar','disablemysqldump');

    $sitetoolvars = array();
    foreach ($vararray as $varname) {
        $sitetoolvars[$varname] =   $$varname;
    }

    $sitetooloptions = serialize($sitetoolvars);
    xarModSetVar('sitetools','sitetooloptions',$sitetooloptions);

    //set this true for now as it is not fully supported
    //xarModSetVar('sitetools','disablemysqldump',TRUE);

    // FTP options
    xarModSetVar('sitetools','useftpbackup', $useftpbackup);
    xarModSetVar('sitetools','ftpserver', $ftpserver);
    xarModSetVar('sitetools','ftpuser', $ftpuser);
    xarModSetVar('sitetools','ftppw', $ftppw);
    xarModSetVar('sitetools','ftpdir', $ftpdir);
    // Secure FTP?
    xarModSetVar('sitetools','usesftpbackup', $usesftpbackup);

    if (xarModIsAvailable('scheduler')) {
        if (!xarVarFetch('interval', 'isset', $interval, array(), XARVAR_NOT_REQUIRED)) return;
        /* for each of the functions specified in the template */
        foreach ($interval as $func => $howoften) {
            /* see if we have a scheduler job running to execute this function */
            $job = xarModAPIFunc('scheduler','user','get',
                                 array('module' => 'sitetools',
                                       'type' => 'scheduler',
                                       'func' => $func));
            if (empty($job) || empty($job['interval'])) {
                if (!empty($howoften)) {
                    /* create a scheduler job */
                    xarModAPIFunc('scheduler','admin','create',
                                  array('module' => 'sitetools',
                                        'type' => 'scheduler',
                                        'func' => $func,
                                        'interval' => $howoften));
                }
            } elseif (empty($howoften)) {
                /* delete the scheduler job */
                xarModAPIFunc('scheduler','admin','delete',
                              array('module' => 'sitetools',
                                    'type' => 'scheduler',
                                    'func' => $func));
            } elseif ($howoften != $job['interval']) {
                /* update the scheduler job */
                xarModAPIFunc('scheduler','admin','update',
                              array('module' => 'sitetools',
                                    'type' => 'scheduler',
                                    'func' => $func,
                                    'interval' => $howoften));
            }
        }
    }

    xarModCallHooks('module','updateconfig','sitetools',
                   array('module' => 'sitetools'));
    if ($errorcount == 0) {
        $msg = xarML('Sitetools configuration settings were successfully updated.');
        xarTplSetMessage($msg,'status');
    } else {
        $msg = xarML('There were errors when trying to save your settings and some were NOT saved. Please review the errors.');
        xarTplSetMessage($msg,'alert');
    }

    xarResponseRedirect(xarModURL('sitetools', 'admin', 'modifyconfig'));

    /* Return */
    return true;
}
?>

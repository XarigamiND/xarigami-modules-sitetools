<?php
/**
 * Site Tools Modify Configuration
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
 * This is a standard function to modify the configuration parameters of the
 * module
 * @return array
 */
include_once('modules/sitetools/xarclass/backupDB.common.php');
function sitetools_admin_modifyconfig()
{
    /* Initialise the $data variable that will hold the data to be used in
     * the blocklayout template, and get the common menu configuration
     */
    $data = array();
    //common menu link
    $data['menulinks']= xarModAPIFunc('sitetools','admin','getmenulinks');
    /* Security check - important to do this as early as possible */
    if (!xarSecurityCheck('AdminSiteTools')) return;
    /* Generate a one-time authorisation code for this operation */
    $data['authid'] = xarSecGenAuthKey();

    /* Get database function settings */
    $default = xarModGetVar('sitetools','default');
    $sitetoolconfig = xarModGetVar('sitetools','sitetooloptions');
    $sitetoolconfig = unserialize($sitetoolconfig);
    $checkboxlist = array('timestamp','createifnotexists','dbnameincreate','replaceinto','restoredefault','nohtml','hexblobs','disablemysqldump');
    //ensure checkboxes are set
    foreach ( $checkboxlist as $boxname) {
        $$boxname = isset($$boxname)?$$boxname:0;
    }

    $vararray = array('nohtml','dbcompresstype','compresslevel','replaceinto',
                      'hexblobs','createifnotexists','dbnameincreate','neverbackupdbtypes','colnumber',
                      'defaultbktype','lineterm','timestamp','backuppath','stylecachepath','rsscachepath',
                      'templcachepath','backtickchar','quotechar','disablemysqldump');
    foreach ($vararray as $varname) {
        $data[$varname] = $sitetoolconfig[$varname];
    }

    //backuppath might include ./var - replace it with realpath
    if (preg_match('/^\.\/var\//i',$data['backuppath'])) {
        $varpath = realpath('./var');
        $data['backuppath'] = str_replace('./var',$varpath,$data['backuppath']);
    }
    $data['bkoptions'] = array('complete' =>xarML('*Full backup - complete inserts (default)'),
                               'standard'=>xarML('Full backup - standard inserts'),
                               'structure'=>xarML('Structure only'));
    $data['linetermoptions'] = array(
                                '\n'    => xarML('*nix (default)'),
                                '\n\r'  => xarML('Windows'),
                                '\r'    => xarML('Mac')
                                );
    $testdump = MySQLdumpVersion();
    $data['hasmysqldump'] = $testdump?xarML('is'):xarML('is not');
    $data['disablemysqldump'] = $testdump?$disablemysqldump : TRUE;

    $data['compressoptions'] = array('none'=>xarML('None'),'gzip'=>'gzip', 'bzip2'=>'bzip2');
    // Check for the FTP extension
    $data['ftpextension']  = extension_loaded('ftp');
    $data['useftpchecked'] = xarModGetVar('sitetools','useftpbackup') ? true : false;
    // Check for the SSL extension
    $data['sslextension']   = extension_loaded('openssl');
    $data['useftpbackup'] = xarModGetVar('sitetools','useftpbackup') ? true : false;
    $data['usesftpbackup'] = xarModGetVar('sitetools','usesftpbackup') ? true : false;
    $data['ftpserver']      = xarModGetVar('sitetools','ftpserver');
    $data['ftpuser']        = xarModGetVar('sitetools','ftpuser');
    $data['ftppw']          = xarModGetVar('sitetools','ftppw');
    $data['ftpdir']         = xarModGetVar('sitetools','ftpdir');
    //defaults
    $data['defstylepath']     = sys::varpath()."/cache/styles";
    $data['defrsspath']     = sys::varpath()."/cache/rss";
    $data['deftemplpath']        = sys::varpath()."/cache/templates";
    $data['restoredefault'] = FALSE;
    /* scheduler functions available in sitetools at the moment */
    $schedulerapi = array('optimize','backup');
    /* Define for each job type */
    $data['schedule']['optimize']=xarML('Run Optimize Job');
    $data['schedule']['backup']=xarML('Run Backup Job');

    if (xarModIsAvailable('scheduler')) {
        $data['intervals'] = xarModAPIFunc('scheduler','user','intervals');
        $data['interval'] = array();
        foreach ($schedulerapi as $func) {
            // see if we have a scheduler job running to execute this function
            $job = xarModAPIFunc('scheduler','user','get',
                                 array('module' => 'sitetools',
                                       'type' => 'scheduler',
                                       'func' => $func));
            if (empty($job) || empty($job['interval'])) {
                $data['interval'][$func] = '';
            } else {
                $data['interval'][$func] = $job['interval'];

            }
        }
    } else {
        $data['intervals'] = array();
        $data['interval'] = array();
    }

    $hooks = xarModCallHooks('module', 'modifyconfig', 'sitetools',
        array('module' => 'sitetools'));
    if (empty($hooks)) {
        $data['hooks'] = '';
    } elseif (is_array($hooks)) {
        $data['hooks'] = join('', $hooks);
    } else {
        $data['hooks'] = $hooks;
    }

   /*Return the template variables defined in this function */
 return $data;
}
?>
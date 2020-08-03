<?php
/**
 * Site Tools Scheduler for Backup
 *
 * @package modules
 * @subpackage Xarigami Sitetools Module
 * @copyright (C) 2003-2010 2skies.com
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @link http://xarigami.com/project/sitetools
 */
/**
 * Take a backup of the database(s) (executed by the scheduler module)
 *
 * @return bool true on success
 * @access private
 */
function sitetools_schedulerapi_backup($args)
{
    extract ($args);

    if (!isset($dbname) || ($dbname='') || (empty($dbname))){
        $dbconn = xarDBGetConn();
        $dbname= xarDBGetName();
        $dbtype= xarDBGetType();
    }
    $SelectedTables=''; //Todo: setup a default array of selected tables for partial backups

    //get the current options
    $sitetoolconfig = xarModGetVar('sitetools','sitetooloptions');
    $sitetoolconfig = unserialize($sitetoolconfig);

    foreach ($sitetoolconfig  as $varname =>$value) {
        $$varname = $value;
    }  
    $defaultbktype = xarModGetVar('sitetools','defaultbktype');
    $startbackup=$defaultbktype;

    if ((!isset($startbackup)) || (empty($startbackup))) {
      $startbackup='complete';
    }
   
    $data=array();

    $data = xarModAPIFunc('sitetools','admin','backupdb',
                               array ('dbcompresstype' => $dbcompresstype,
                                      'startbackup'    => $startbackup,
                                      'nohtml'         => TRUE,
                                      'SelectedTables' => $SelectedTables,
                                      'dbname'         => $dbname,
                                      'dbtype'         => $dbtype,
                                      'scheduler'      => TRUE));
    // $data holds an array with the backup data

    xarLogMessage('SITETOOLS: Scheduled backup complete');

    $useftp = xarModGetVar('sitetools','useftpbackup') ? true : false;
    if ($useftp) {
        xarModAPIFunc('sitetools','admin','ftpbackup', $data);
   }

 return true;
}

?>
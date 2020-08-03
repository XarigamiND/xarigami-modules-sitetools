<?php
/**
 * Site Tools Backup functions
 *
 * @package modules
 * @copyright (C) 2003-2010 2skies.com
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Sitetools Module
 * @link http://xarigami.com/project/sitetools
 * @author Jo Dalle Nogare <icedlava@2skies.com>
 */

/**
 * @Backup tables in your database
 * @Parameters
 * TODO: Add in multidatabase once multidatabase functionality and location decided
 * TODO: add in more customization of configurations
 */
function sitetools_admin_backup($args)
{
   if (!xarVarFetch('confirm',        'str:1:', $confirm,       '', XARVAR_NOT_REQUIRED)) return;
   if (!xarVarFetch('startbackup',    'str:2:', $startbackup,   '', XARVAR_NOT_REQUIRED)) return;
   if (!xarVarFetch('nohtml',        'checkbox',$nohtml,      false, XARVAR_NOT_REQUIRED)) return;
   if (!xarVarFetch('dbcompresstype', 'str:0:5',$dbcompresstype, 'bzip2', XARVAR_NOT_REQUIRED)) return;
   if (!xarVarFetch('dbname',         'str:1',  $dbname,        '' , XARVAR_NOT_REQUIRED)) return;
   if (!xarVarFetch('SelectedTables', 'array:', $SelectedTables, '', XARVAR_NOT_REQUIRED)) return;
   /* Security check */
    if (!xarSecurityCheck('AdminSiteTools')) return;

    $data=array();
    $data['menulinks'] = xarModAPIFunc('sitetools','admin','getmenulinks');
    /*setup variables */
    $data['returnurl'] = xarServerGetCurrentURL();
    $data['startbackup']=$startbackup;
    $data['nohtml'] = $nohtml;
    $data['dbcompresstype'] = $dbcompresstype;

    //get the current options
    $sitetoolconfig = xarModGetVar('sitetools','sitetooloptions');
    $sitetoolconfig = unserialize($sitetoolconfig);

    foreach ($sitetoolconfig  as $varname =>$value) {
        $$varname = $value;
    }

    $colnumber = $colnumber ? $colnumber :3;
    $data['colnumber'] = $colnumber;

    $backupabsolutepath= $backuppath;

    $data['warning']=0;
    $data['warningmessage']='<span class="xar-accent">'
                            .xarML('WARNING: directory does not exist or is not writeable: ').$backupabsolutepath.'</span><br /><br />'
                            .xarML(' Please ensure the backup directory exisits and is writeable');

    if ((!is_dir($backupabsolutepath)) || (!is_writeable($backupabsolutepath))) {
       $data['warning']=1;
       return $data;
    }
    $data['authid'] = xarSecGenAuthKey();
    /* Setup the current database for backup - until there is option to choose it TODO */
    if (($dbname='') || (empty($dbname))){
        $dbconn = xarDBGetConn();
            $dbname= xarDBGetName();
            $dbtype= xarDBGetType();
    }

    $data['confirm']=$confirm;
    $data['dbname']=$dbname;
    $data['dbtype']=$dbtype;

    //common menu link
    $data['menulinks']= xarModAPIFunc('sitetools','admin','getmenulinks');

    if (empty($startbackup)) {
       /* No confirmation yet - display a suitable form to obtain confirmation
        * of this action from the user
        * setup option links
        */
        $data['backupops']=array();
        $data['backupops']['complete'] = xarML('Full backup - complete inserts');
        $data['backupops']['standard'] = xarML('Full backup - standard inserts');
        $data['backupops']['partial'] =  xarML('Partial - select tables, complete inserts');
        $data['backupops']['structure'] = xarML('Full backup - Structure only');
        $data['dbcompresstype'] = $dbcompresstype;
        $data['compressoptions'] = array('none'=>xarML('None'),'gzip'=>'gzip', 'bzip2'=>'bzip2');
        $confirm='';

    /* Start actual backup for all types here */
    } elseif ($startbackup) {

        $confirm='';
        if ($startbackup =='partial'){
            $tabledata=array();
            //now using dbtype specific classes
            $tabledata=xarModAPIFunc('sitetools','admin','gettabledata');
            if ($tabledata == false) {
                /* Handle the user exceptions yourself */
                $status = xarML('Unable to access database table information');
                return $status;
            }
            /* set javascript header */
            xarModAPIfunc('base', 'javascript', 'modulefile', array('filename'=>'sitetools_admin_backup.js'));

            $data['dbtables']    = $tabledata['dbtables'];
            $tabletotal          = $tabledata['tabletotal'];
            $data['dbname']      = $tabledata['dbname'];
            $dbname              = $tabledata['dbname'];
            $data['checkboxname']= 'SelectedTables['.htmlentities($dbname, ENT_QUOTES).'][]';

            return $data;
        }

        if (!xarSecConfirmAuthKey()) {return;}

        @set_time_limit(600);

        $bkupdata=array();
        $bkupdata= xarModAPIFunc('sitetools','admin','backupdb',
                               array ('dbcompresstype' => $data['dbcompresstype'],
                                      'startbackup'    => $data['startbackup'],
                                      'nohtml'         => $data['nohtml'],
                                      'SelectedTables' => $SelectedTables,
                                      'dbname'         => $dbname,
                                      'dbtype'         => $dbtype));


        if (!is_array($bkupdata) || ($bkupdata == false)) {
            /* Handle the user exceptions yourself */
            $status = xarML('Unable to backup database');
            return $status;
        }

        $data['warning'] =$bkupdata['warning'];
        if ($nohtml==TRUE) {
           $data['runningstatus'] =array();
        } else {
            $data['runningstatus'] =$bkupdata['runningstatus'];
        }
        if ($data['warning'] != 1) {
            $data['deleteurl']    = $bkupdata['deleteurl'];
            $data['bkfiletype']   = $bkupdata['bkfiletype'];
            $data['bkfilename']   = $bkupdata['bkfilename'];
            $data['bkname']       = $bkupdata['bkname'];
            $data['bkfilesize']   = $bkupdata['bkfilesize'];
            $data['completetime'] = $bkupdata['completetime'];
            $data['backuptype']   = $bkupdata['backuptype'];
            $data['btype']        = $bkupdata['btype'];
           /*Generate download, view and delete URLS */

            $data['downloadurl']= xarModURL('sitetools','admin','downloadbkup',
                                         array('savefile' => $data['bkname']));
            $data['deleteurl']= xarModURL('sitetools','admin','downloaddel',
                                         array('savefile' => $data['bkname']));
            $msg = xarML('Database backup was completed successfully. Please check the table status messages for individual table issues.');
            xarTplSetMessage($msg,'status');
        }

    }
  return $data;

}

?>
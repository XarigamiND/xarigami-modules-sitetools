<?php
/**
 * @package modules
 * @copyright (C) 2003-2010 2skies.com
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Sitetools Module
 * @link http://xarigami.com/project/sitetools
 * @author Jo Dalle Nogare <icedlava@2skies.com>
 */
/**
 * Backup tables in your database
 *
 * @author jojodee
 * @return array ['bkfiletype']
                 ['bkfilename']=$backupabsolutepath.$partbackupfilename;
                 ['bkname']
 * @TODO: Add in multidatabase once multidatabase functionality and location decided
 * @TODO: Remove all the commented out code once classes fully tidied and tested
 */
function sitetools_adminapi_backupdb($args)
{
    extract($args);
    // Security check - allow scheduler api funcs to run as anon bug #2802
    //if (!xarSecurityCheck('AdminSiteTools')) return;

    if (!isset($startbackup)) return;
    $items=array();

    $items['startbackup'] = $startbackup;
    $items['dbcompresstype']= $dbcompresstype;
    $items['nohtml']= $nohtml;
      //Now we have the passed in vars in $items array already
    //so now e can get all the db options and we don't overwrite those passed in
    $bkvars = array();
    $sitetoolconfig = xarModGetVar('sitetools','sitetooloptions');
    $sitetoolconfig = unserialize($sitetoolconfig);

    foreach ($sitetoolconfig  as $varname =>$value) {
        $bkvars[''.$varname.''] = $value;
    }   
   //check directory exists and is writeable    
    if ((!is_dir($bkvars['backuppath'])) || (!is_writeable($bkvars['backuppath']))) {
       $items['warning']=1;
       return $items;
    }
    $items['warning']=0;   
    $items['warningmessage']='<span class="xar-accent">'
                            .xarML('WARNING: directory does not exist or is not writeable: ').$bkvars['backuppath'].'</span><br /><br />'
                            .xarML(' Please ensure the backup directory exisits and is writeable');
    $items['authid']     = xarSecGenAuthKey();    
     //get more vars
    if (!isset($dbname) || ($dbname='') || (empty($dbname))){
        $dbconn = xarDBGetConn();
            $dbname= xarDBGetName();
            $dbtype= xarDBGetType();
            $dbhost= xarDBGetHost();
    }
    $bkvars['startbackup'] = $startbackup;
    $bkvars['buffer_size']  = 32768;
    $bkvars['nohtml']       = $items['nohtml'];
    $bkvars['startbckup']   = isset($items['startbackup']) ? $items['startbackup'] : xarModGetVar('sitetools','defaultbktype');
    $bkvars['backuptype']   = $bkvars['startbackup'];
    $backuptype = $bkvars['backuptype'];
    $bkvars['lineterm']     = isset($bkvars['lineterm']) ? $bkvars['lineterm'] : "\n";
    $bkvars['dbcompresstype'] = isset( $items['dbcompresstype']) ?  $items['dbcompresstype'] : $bkvars['dbcompresstype'];
    $backupabsolutepath = $bkvars['backuppath'].'/';
    $bkvars['dbname']=$dbname;
    $bkvars['dbtype']=$dbtype;
    $bkvars['dbhost']=$dbhost;    


    //dbname as a configurable prefix 
    if (TRUE == $bkvars['dbnameincreate']) {
        $bkvars['thedbprefix']=$dbname.'.';
    } else {
        $bkvars['thedbprefix']='';
    }
    
    if ($bkvars['timestamp']==TRUE) {
        $bkvars['backuptimestamp'] = '.'.date('Y-m-d-THis');
    } else {
        $bkvars['backuptimestamp'] = '';
    }
  
    $backuptimestamp    = $bkvars['backuptimestamp'];
    
    switch ($bkvars['dbcompresstype']) {
        case 'gzip':
        case 'none':
            break;
        case 'bzip2':
            if (!function_exists('bzopen')) {
               //text default
               $bkvars['dbcompresstype'] = 'none';
            }
            break;
        default:
            $bkvars['dbcompresstype'] = 'none';
            break;
        }
      
    $fileextension      = (($bkvars['dbcompresstype'] == 'bzip2') ? '.bz2' : (($bkvars['dbcompresstype']== 'gzip') ? '.gz' : ''));
    
    $fullbackupfilename = $dbname.'.xar_backup'.$backuptimestamp.'.sql'.$fileextension;
    $partbackupfilename = $dbname.'.xar_backup_partial'.$backuptimestamp.'.sql'.$fileextension;
    $strubackupfilename = $dbname.'.xar_backup_structure'.$backuptimestamp.'.sql'.$fileextension;
    $tempbackupfilename = $dbname.'.xar_backup.temp.sql'.$fileextension;
    $modinfo =xarModGetInfo(xarModGetIDFromName('sitetools'));
    $xarbackupversion   = $modinfo['version']; 
    
    $bkvars['fullbackupfilename'] = $fullbackupfilename;
    $bkvars['partbackupfilename'] = $partbackupfilename;
    $bkvars['strubackupfilename'] = $strubackupfilename;
    $bkvars['tempbackupfilename'] = $tempbackupfilename;  
    $bkvars['xarbackupversion'] = $xarbackupversion;
              
    $runningstatus=array();
    if (!function_exists('getmicrotime')) {
        function getmicrotime()
        {
            list($usec, $sec) = explode(' ', microtime());
            return ((float) $usec + (float) $sec);
        }
    }
    
    // Instantiation of SiteTools class
    include_once("modules/sitetools/xarclass/dbSiteTools_".$dbtype.".php");
    $classname="dbSiteTools_".$dbtype;
    $bkitems= new $classname();
     
    if ($startbackup) {
       
       
        if ($startbackup == 'structure') {
            $backuptype = 'full';
            $btype=xarML('Structure Only');
        } elseif ($SelectedTables) {
            $btype=xarML('Selected Tables - Complete Inserts');
            $backuptype = 'partial';
        // headers for complete backup
        } else {
            $backuptype = 'full';
            if ($startbackup=='complete')
            {
              $btype=xarML('Full - Complete Inserts');
            } else {
            $btype=xarML('Full - Standard Inserts');
            }
        }

        $bkvars['starttime'] = getmicrotime();
        $bkvars['alltablesstructure'] = '';
     
        $bkvars['runningstatus']= $runningstatus;
        $bkvars['SelectedTables'] = $SelectedTables;
        //Switch to backup class
        //Pass all our vars in an array
        xarLogMessage("SITETOOLS: Entering main backup class call");
        $runningstatus = $bkitems-> backup($bkvars);

        if (count($runningstatus)>=1 || ($items['nohtml'] ==1)) {
            if ($startbackup == 'structure') {
                $items['bkfiletype']=xarML('Structure backup filename: ');
                $items['bkfilename']=$backupabsolutepath.$strubackupfilename;
                $items['bkname']=$strubackupfilename;
            } else if ($backuptype == 'full') {
                $items['bkfiletype']=xarML('Full backup filename: ');
                $items['bkfilename']=$backupabsolutepath.$fullbackupfilename;
                $items['bkname']=$fullbackupfilename;
            } else {
                $items['bkfiletype']=xarML('Partial backup filename: ');
                $items['bkfilename']=$backupabsolutepath.$partbackupfilename;
                $items['bkname']=$partbackupfilename;
            }

            $items['bkfilesize']=@FileSizeNiceDisplay(filesize($items['bkfilename']), 2);
            $items['completetime']= @FormattedTimeRemaining(getmicrotime() -  $bkvars['starttime'], 2);
            $items['deleteurl']=xarML('[Click to Delete]');
                        
        } else {
             $items['warning']=1;
        }
    } else {
        $items['warning']=1;
    }

    if ($items['nohtml'] = 1) $items['warning'] = 0;
    $items['runningstatus']=$runningstatus;
    $items['backuptype']=$backuptype;
    $items['btype']=$btype;

    // Log a message
    xarLogMessage('SITETOOLS: Created backup');

   //Return data for display
   return $items;
}

?>
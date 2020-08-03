<?php
/**
 * Site Tools Backup package
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
 * function to download the just completed backup
 * @param $bkfile is the name of the backup file
 *
 */
function sitetools_admin_downloadbkup ($args)
{
    if (!xarVarFetch('savefile', 'str:1', $savefile,'')) return;
    // Security check
    if (!xarSecurityCheck('AdminSiteTools')) return;


    if ((!isset($savefile)) || (empty($savefile))) {
        // Handle the user exceptions yourself
        $status = xarML('The file to download does not exist.');
        return $status;
    }
    //get the current options
    $sitetoolconfig = xarModGetVar('sitetools','sitetooloptions');
    $sitetoolconfig = unserialize($sitetoolconfig);

    foreach ($sitetoolconfig  as $varname =>$value) {
        $$varname = $value;
    }
    //check the file exists
    $pathtofile= $backuppath;

    $filetodownload = $pathtofile.'/'.$savefile;

  if (!file_exists($filetodownload)) {
       // Handle the user exceptions yourself
       $status = xarML('The backup file for download does not exist.');
       xarTplSetMessage($status,'error');
       return $status;
  }

//  $mimetp=mime_content_type ($filetodownload);

    ob_end_clean();
    // Setup headers for browser

    header("Cache-Control: no-cache, must-revalidate");
    header("Pragma: no-cache");
    header("Pragma: ");
    header("Cache-Control: ");
    header("Content-type: application/octetstream");
//    header("Content-type: ".$mimetp );

    header("Content-disposition: attachment; filename=\"".basename($filetodownload)."\"");
//    header("Content-length: $size");

    $fp = fopen($filetodownload,"rb");
    if( is_resource($fp) )
    {
        while( !feof($fp) )
        {
            echo fread($fp, 1024);
        }
    }
    fclose($fp);

 //ob_end_flush;

   exit();
}
?>
<?php
/**
 * @package modules
 * @copyright (C) 2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Sitetools Module
 * @copyright (C) 2006-2010 2skies.com
 * @link http://xarigami.com/project/sitetools
 */
/**
 * Backup tables in your database
 *
 * @author MichelV <michelv@xaraya.com>
 * @author jojodee <jojodee@xaraya.com>
 * @since 7 Nov 2006
 * @param array ['bkfiletype']
                 ['bkfilename']=$backupabsolutepath.$partbackupfilename;
                 ['bkname']
 * @return bool True on successful FTP action, false on failure
 * @TODO: Add in multidatabase once multidatabase functionality and location decided
 */
function sitetools_adminapi_ftpbackup($args)
{
    extract($args);
    // Security check - allow scheduler api funcs to run as anon bug #2802
    //if (!xarSecurityCheck('AdminSiteTools')) return;
    if (!extension_loaded('ftp')) {
        return false;
    }

    xarLogMessage('SITETOOLS: FTP attempt on file #(1)',$bkfilename);

    if(!isset($bkfilename) || empty($bkfilename)) {
        return false;
    }
    // open the connection
    $ftpserver = xarModGetVar('sitetools','ftpserver');
    $ftpuser   = xarModGetVar('sitetools','ftpuser');
    $ftppw     = xarModGetVar('sitetools','ftppw');
    $ftpdir    = xarModGetVar('sitetools','ftpdir');
    $usesftp   = xarModGetVar('sitetools','usesftpbackup');
    $compresstype = xarModGetVar('sitetools','dbcompresstype');
    $ftptype = $compresstype == 'none' ?FTP_ASCII : FTP_BINARY;
    // Connect and see if we use a secure connection
    if (extension_loaded('openssl') && $usesftp) {
        $conn = ftp_ssl_connect($ftpserver);
    } else {
        $conn = ftp_connect($ftpserver);
    }
    // Bail out if we cannot connect
    if(!$conn) {
        xarLogMessage('SITETOOLS: FTP connect failed, backup not transferred');
       return false;
    }
    ftp_pasv($conn, true);
    // Login
    if(!ftp_login($conn,$ftpuser,$ftppw)) {
        ftp_quit($conn);
        return false;
    }
    // Go to the path we want
    ftp_chdir($conn,$ftpdir);
    if(!ftp_put($conn,$ftpdir.'/'.$bkname,$bkfilename,$ftptype)) {
        return false;
    }

    ftp_quit($conn);

    // Log a message
    xarLogMessage('SITETOOLS: Excuted FTP of backup');

    return true;
}
?>
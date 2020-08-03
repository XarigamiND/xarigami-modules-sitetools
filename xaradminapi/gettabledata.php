<?php
/**
 * @package modules
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Sitetools Module
 * @copyright (C) 2003-2010 2skies.com
 * @link http://xarigami.com/project/sitetools
 * @author Jo Dalle Nogare <icedlava@2skies.com>
 */
/**
 * @Get table information from a database for selection of tables for partial backup
 * @author jojodee
 * @param database name, $dbname the physical database name (optional)
 * @param database type $dbtype (optional)
 * @return array $data - table id, table names, number of records
 */
function sitetools_adminapi_gettabledata($dbname='', $dbtype='')
{
    // Security check  - allow scheduler api funcs to run as anon bug #2802?
    //if (!xarSecurityCheck('AdminSiteTools')) return;

    if (($dbname='') || (empty($dbname))){
        $dbconn = xarDBGetConn();
        $dbname= xarDBGetName();
    }

    if ($dbtype=='' || !isset($dbtype)){
        $dbtype='mysql';
    }

    $rowinfo=array();
    // Instantiation of SiteTools class

     include_once("modules/sitetools/xarclass/dbSiteTools_".$dbtype.".php");

     $classname="dbSiteTools_".$dbtype;
     $tableitems= new $classname();
     
     if (!$rowdata= $tableitems->_selecttables($dbname,TRUE)) {return;}     
     //function moved to new class method
    
    $data['dbtables'] = $rowdata[$dbname];
    $data['tabletotal'] = count($data['dbtables']);
    $data['dbname']= $dbname;
   //Return data for display
   return $data;
}
?>
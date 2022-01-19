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
 * @Optimize a database
 * @author jojodee@xaraya.com
 * @author original MySQL_Tools by Michaelius (michaelius@nukeland.de)
 * @param database name, the physical database name (optional)
 * @param databaseType database type (optional)
 * @return array $data - table names, optimization state, saved, total save
 */
function sitetools_adminapi_optimizedb($dbname,$dbtype='')
{
    if ($dbtype=='' || !isset($dbtype)){
        $dbtype='mysqli';
    }

    // Security check  - allow scheduler api funcs to run as anon bug #2802
       $items=array();

    if (($dbname='') || (empty($dbname))){
        $dbconn = xarDBGetConn();
            $dbname= xarDBGetName();
    }

    $rowinfo=array();//bug #2595
  // Instantiation of SiteTools class

     include_once("modules/sitetools/xarclass/dbSiteTools_".$dbtype.".php");

     $classname="dbSiteTools_".$dbtype;
     $items= new $classname();
     if (!$rowdata= $items->_optimize($dbname)) {return;}
    // function moved to dbspecific classes

    //return
   return $rowdata;
}
?>

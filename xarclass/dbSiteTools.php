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
 * Database abstraction class for SiteTools
 *
 * @author Richard Cave <rcave@xaraya.com>
 * @author Jo Dalle Nogare <jojodee@xaraya.com>
 * @access private
 */
class dbSiteTools
{
    // initialize some vars
    var $_database_info;
    var $dbconn;
    var $dbtype;
    var $dbname;
    var $dbhost;

    function __construct ($dbname='',$dbtype='')
    {
        if (empty($this->dbconn)) {
            $this->dbconn = xarDBGetConn();
        }
        if (empty($this->dbtype)) {
            $this->dbtype = xarDBGetType();
        } else {
            $this->dbtype =$dbtype;
        }
        if (empty($this->dbname)) {
            $this->dbname = xarDBGetName();
        } else {
            $this->dbname=$dbname;
        }
        $this->dbhost = xarDBGetHost();
       $this->_database_info =array($this->dbtype,$this->dbconn,$this->dbname,$this->dbhost);
    }

    function selecttables($dbname='',$fulldata=FALSE)
    {
        $SelectedTables = $this->_selecttables($this->dbname,$fulldata);
         // Return items
        return $SelectedTables;
    }

    function checktables($SelectedTables)
    {
        $TableErrors = $this->_checktables($SelectedTables);

         // Return items
        return $TableErrors;
    }
    function bkcountoverallrows($SelectedTables,$number_of_cols='')
    {
        $overallrows = $this->_bkcountoverallrows($SelectedTables,$number_of_cols);

        return $overallrows;
    }

    function backup($bkvars)
    {
        $runningstatus = $this->_backup($bkvars);
        if (!is_array($runningstatus) || !$runningstatus) {return false;}

        // Return
        return $runningstatus;
    }
    
}

?>

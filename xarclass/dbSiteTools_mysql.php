<?php
/**
 * @package modules
 * @copyright (C) 2003-2011 2skies.com
 * @link http://xarigami.com/project/sitetools
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @author Jo Dalle Nogare <icedlava@2skies.com>
 */
/**
 * SiteTools Database abstraction class extension for mySQL
 *
 * @author Jo Dalle Nogare <jojodee@xaraya.com>
 * @access private
 */
sys::import('modules.sitetools.xarclass.dbSiteTools');
include_once('modules/sitetools/xarclass/backupDB.common.php');
class dbSiteTools_mysql extends dbSiteTools
{
    function _optimize()
    {
        $tot_data = 0;
        $tot_idx = 0;
        $tot_all = 0;
        $total_gain=0;
        $total_kbs =0;
        $gain=0;
        $rowinfo['total_gain']=0;
        $rowinfo['total_kbs']=0;
        $version=substr(mysql_get_server_info(),0,3);
        $local_query = 'SHOW TABLE STATUS FROM '.$this->dbname;
        $result      = @mysql_query($local_query);
        if (@mysql_num_rows($result)) {
            while ($row = mysql_fetch_array($result)) {
                if ($version>='4.1') {
                  $rowdata[]=array('rowname' => $row[0],
                                    'totaldata'  => $row[6],
                                    'totalidx'   => $row[8],
                                    'gain'       => $row[9]);
                } else {
                   $rowdata[]=array('rowname' => $row[0],
                                    'totaldata'  => $row[5],
                                    'totalidx'   => $row[7],
                                    'gain'       => $row[8]);
                }
                $local_query = 'OPTIMIZE TABLE '.$row[0];
                $resultat  = mysql_query($local_query);
           }
        }

        if (!$resultat) {return false;}

        $rowinfo = array();
        foreach ($rowdata as $datum) {
            $total = $datum['totaldata'] + $datum['totalidx'];
            $total = $total/1024;
            $total = round($total,3);
            $gain  = $datum['gain']/1024;
            $total_gain += $gain;
            $total_kbs  += $total;
            $gain  = round ($gain,3);
            $rowinfo['rowdata'][]=array('total' => $total,
                                        'gain'  => $gain,
                                        'tablename' => $datum['rowname']);
         }
        $rowinfo['total_gain']=$total_gain;
        $rowinfo['total_kbs']=$total_kbs;
        $rowinfo['dbname']=$this->dbname;

        return $rowinfo;

    }

    function _selecttables($dbname,$fulldata=FALSE)
    {
        //get the current options
        $sitetoolconfig = xarModGetVar('sitetools','sitetooloptions');
        $sitetoolconfig = unserialize($sitetoolconfig);

        foreach ($sitetoolconfig  as $varname =>$value) {
            $$varname = $value;
        }
        $query = 'SHOW TABLES FROM '.$this->dbname;
        $tables = mysql_query($query);//mysql_list_tables($this->dbname);
            if (is_resource($tables)) {
                $tablecounter = 0;
                    while (list($tablename) = mysql_fetch_array($tables)) {
                        $TableStatusResult = mysql_query('SHOW TABLE STATUS LIKE "'.mysql_escape_string($tablename).'"');
                        if ( $TableStatusRow = mysql_fetch_assoc($TableStatusResult)) {
                            if (isset($TypeEngineKey) && in_array(@$TableStatusRow[$TypeEngineKey], $neverbackupdbtypes)) {
                                //don't back up HEAP tables
                            } else {
                                $tablecounter++;
                                $SQLquery = 'SELECT COUNT(*) AS '.$backtickchar.'num'.$backtickchar.' FROM '.$backtickchar.mysql_escape_string($tablename).$backtickchar;
                               // $SelectedTables["$this->dbname"][] = $tablename;
                                $result = mysql_query($SQLquery);
                                $row = mysql_fetch_array($result);
                                if ($fulldata) {
                                    $SelectedTables["$this->dbname"][] = array('tablenum'=>$tablecounter, 'tablename'=>$tablename, 'tablerecs'=>$row['num']);
                                } else {
                                    $SelectedTables["$this->dbname"][] = $tablename;
                                }
                            }
                        }
                    }
            }
        $this->SelectedTables = $SelectedTables;
        return $SelectedTables;
    }

    function _checktables($SelectedTables)
    {
        foreach ($SelectedTables as $this->dbname => $selectedtablesarray) {
               mysql_select_db($this->dbname);
            foreach ($selectedtablesarray as $selectedtablename) {
                $result = mysql_query('CHECK TABLE '.$selectedtablename);
                while ($row = mysql_fetch_array($result)) {
                    if ($row['Msg_text'] == 'OK') {
                        mysql_query('OPTIMIZE TABLE '.$selectedtablename);
                    } else {
                         $TableErrors[] = $row['Table'].' ['.$row['Msg_type'].'] '.$row['Msg_text'];
                        if (!isset($TableErrorTables) || !is_array($TableErrorTables) || !in_array($this->dbname.'.'.$selectedtablename, $TableErrorTables)) {
                            $TableErrorDB[]     = $this->dbname;
                            $TableErrorTables[] = $selectedtablename;
                        }
                    }
                }
            }
        }


        if (isset($TableErrorTables) && is_array($TableErrorTables)) {
            for ($t = 0; $t < count($TableErrorTables); $t++) {
                mysql_select_db($TableErrorDB["$t"]);
                $fixresult = mysql_query('REPAIR TABLE '.$TableErrorTables["$t"].' EXTENDED');
                while ($fixrow = mysql_fetch_array($fixresult)) {
                   $TableErrors[] = $fixrow['Table'].' ['.$fixrow['Msg_type'].'] '.$fixrow['Msg_text'];
                }
            }
        return $TableErrors;

        }
    }

    function _bkcountoverallrows($SelectedTables,$number_of_cols)
    {
       $overallrows=0;
       foreach ($SelectedTables as $this->dbname => $value) {
            mysql_select_db($this->dbname);
            $tablecounter = 1;
            for ($t = 0; $t < count($SelectedTables["$this->dbname"]); $t++) {
                if ($tablecounter++ < $number_of_cols) {
                } else {
                    $tablecounter=1;
                }
                $SQLquery = 'SELECT COUNT(*) AS num FROM '.$SelectedTables["$this->dbname"]["$t"];
                $result = mysql_query($SQLquery);
                $row = mysql_fetch_array($result);
                $rows["$t"] = $row['num'];
                $overallrows += $rows["$t"];
            }
        }
        return $overallrows;
    }

    function _backup($bkvars)
    {
        //we could get many of these from default options
        //pass them in as we may want to allow setting these
        //in specific backup configs at a later time
        //now only compressiong type, db backup type and nohtml are available on backup
        //the rest is in default vars
        $dbcompresstype = $bkvars['dbcompresstype'];
        $colnumber      = $bkvars['colnumber'];
        $backtickchar   = $bkvars['backtickchar'];
        $quotechar      = $bkvars['quotechar'];
        $compresslevel  = $bkvars['compresslevel'];
        $createifnotexists  = $bkvars['createifnotexists'];
        $dbnameincreate     = $bkvars['dbnameincreate'];
        $neverbackupdbtypes = $bkvars['neverbackupdbtypes'];
        $nohtml         = $bkvars['nohtml'];
        $lineterm       = $bkvars['lineterm'];
        $buffer_size    = $bkvars['buffer_size'];
        $replaceinto    = $bkvars['replaceinto'];
        $timestamp      = $bkvars['timestamp'];
        $backuppath     = $bkvars['backuppath'];
        $hexblobs       = $bkvars['hexblobs'];
        $disablemysqldump = $bkvars['disablemysqldump'];

        //our key info already calculated
       // $overallrows =$bkvars['overallrows'];
        $thedbprefix =$bkvars['thedbprefix'];
        $alltablesstructure =$bkvars['alltablesstructure'];

        $startbackup =$bkvars['startbackup'];
        $SelectedTables =$bkvars['SelectedTables'];
        $runningstatus=$bkvars['runningstatus'];
        $starttime=$bkvars['starttime'];
        $dbname = $bkvars['dbname'];
        $dbhost = $bkvars['dbhost'];
        $backuptimestamp = $bkvars['backuptimestamp'];
        $fullbackupfilename = $bkvars['fullbackupfilename'];
        $partbackupfilename = $bkvars['partbackupfilename'];
        $strubackupfilename = $bkvars['strubackupfilename'];
        $tempbackupfilename = $bkvars['tempbackupfilename'];
        $xarbackupversion = $bkvars['xarbackupversion'];
        $backupabsolutepath = $backuppath.'/';
        $stats_interval = 500;
        $number_of_cols = $colnumber;
        $mysql_reconnect_interval = 100000;
        $TypeEngineKey = 'Engine';//we assume mysql >= 4.0
        $bkvars['TypeEngineKey'] = $TypeEngineKey;
        $ListOfDatabasesToMaybeBackUp[] = $dbname;

        //check if we have mysqldump and use that
        if ($startbackup == 'structure') {
            $newfullfilename = $backupabsolutepath.$strubackupfilename;
        } elseif (isset($SelectedTables) && is_array($SelectedTables)) {
            $newfullfilename = $backupabsolutepath.$partbackupfilename;
        } else {
            $newfullfilename = $backupabsolutepath.$fullbackupfilename;
        }
        $disablemysqldump = isset($disablemysqldump)?$disablemysqldump : 0;

        if ($startbackup && ($startbackup != 'partial') && ($disablemysqldump !=1) && MySQLdumpVersion()) {

            $commandline  = 'mysqldump';
            $commandline .= ' --databases '.$dbname;
            if ( $backtickchar  == '`') {
                $commandline .= ' --quote-names';
            }
            $commandline .= ($dbhost != 'localhost' ? ' --host='.$dbhost: '');
            $commandline .= ' --user='.$this->dbconn->user;
            $commandline .= ' --password='.$this->dbconn->password;
            if ($startbackup == 'structure') {
                $commandline .= ' --no-data';
            } elseif ($startbackup== 'complete') {
                $commandline .= ' --complete-insert';
            }
            switch ($dbcompresstype) {
                case 'bzip2':
                    if (!bzip2Version()) {
                        $runningstatus[]['message'] = xarML('ERROR: bzip2 does not appear to be installed');
                          return $runningstatus;
                    }
                    $commandline .= ' | bzip2 -cf'.$compresslevel.' > '.$newfullfilename;
                    break;
                case 'gzip':
                    if (!gzipVersion()) {
                       $runningstatus[]['message'] = xarML('ERROR: gzip does not appear to be installed');
                         return $runningstatus;
                    }
                    $commandline .= ' | gzip -cf'.$compresslevel.' > '.$newfullfilename;
                    break;
                case 'none':
                    $commandline .= ' > '.$newfullfilename;
                    break;
                default:
                    $runningstatus[]['message'] = xarML("ERROR: $dbcompresstype (#(1)) must be one of 'bzip2', 'gzip', 'none'", htmlentities($dbcompresstype));
                      return $runningstatus;
                    break;
            }
            set_time_limit(300); // shouldn't take nearly this long for anything, but just in case...
            echo SafeExec($commandline);
            $runningstatus[]['message'] = '<hr />';
        //No Mysqldump - then use php and iterate through the tables
        } else {

            switch ($dbcompresstype) {
                case 'gzip':
                case 'none':
                    // great
                    break;
                case 'bzip2':
                    if (!function_exists('bzopen')){

                         $runningstatus[]['message'] = xarML('ERROR: PHP-bzip2 support does not appear to be installed, please change $dbcompresstype to one of "gzip" or "none"');
                         return $runningstatus;
                    }
                    break;
                default:
                    $runningstatus[]['message'] = xarML("ERROR: $dbcompresstype (#(1)) must be one of 'bzip2', 'gzip', 'none'", htmlentities($dbcompresstype));
                     return $runningstatus;
                    break;
            }

            if ((($dbcompresstype == 'gzip')  && ($zp = @gzopen($backupabsolutepath.$tempbackupfilename, 'wb'.$compresslevel))) ||
                        (($dbcompresstype == 'bzip2') && ($bp = @bzopen($backupabsolutepath.$tempbackupfilename, 'w'))) ||
                        (($dbcompresstype == 'none')  && ($fp = @fopen($backupabsolutepath.$tempbackupfilename, 'wb')))) {
                $currenttime = xarLocaleGetFormattedDate('longdate');
                $fileheaderline  = "-- Sitetools v".$xarbackupversion." (http://xarigami.com) $lineterm";
                $fileheaderline .= "-- MySQL backup (".$currenttime.")$lineterm";
                $fileheaderline .= "-- Database: ".$dbname."$lineterm";
                $fileheaderline .= "-- Host: ".$dbhost."$lineterm";
                $fileheaderline .= "-- Type = ";
                if ($dbcompresstype == 'bzip2') {
                    bzwrite($bp, $fileheaderline, strlen($fileheaderline));
                } elseif ($dbcompresstype == 'gzip') {
                    gzwrite($zp, $fileheaderline, strlen($fileheaderline));
                } else {
                    fwrite($fp, $fileheaderline, strlen($fileheaderline));
                }

                if ($startbackup== 'structure') {

                    if ($dbcompresstype == 'bzip2') {
                        bzwrite($bp, 'Structure Only'.$lineterm.$lineterm, strlen('Structure Only'.$lineterm.$lineterm));
                    } elseif ($dbcompresstype == 'gzip') {
                        gzwrite($zp, 'Structure Only'.$lineterm.$lineterm, strlen('Structure Only'.$lineterm.$lineterm));
                    } else {
                        fwrite($fp, 'Structure Only'.$lineterm.$lineterm, strlen('Structure Only'.$lineterm.$lineterm));
                    }
                    $backuptype = 'full';
                    unset($SelectedTables);

                    $SelectedTables =  self::_selecttables($dbname);

                } elseif (isset($SelectedTables) && is_array($SelectedTables)) {

                    if ($dbcompresstype == 'bzip2') {
                        bzwrite($bp, 'Selected Tables Only'.$lineterm.$lineterm, strlen('Selected Tables Only'.$lineterm.$lineterm));
                    } elseif ($dbcompresstype == 'gzip') {
                        gzwrite($zp, 'Selected Tables Only'.$lineterm.$lineterm, strlen('Selected Tables Only'.$lineterm.$lineterm));
                    } else {
                        fwrite($fp, 'Selected Tables Only'.$lineterm.$lineterm, strlen('Selected Tables Only'.$lineterm.$lineterm));
                    }
                    $backuptype = 'partial';
                    $SelectedTables = $SelectedTables;

                } else {

                    if ($dbcompresstype == 'bzip2') {
                        bzwrite($bp, 'Complete'.$lineterm.$lineterm, strlen('Complete'.$lineterm.$lineterm));
                    } elseif ($dbcompresstype == 'gzip') {
                        gzwrite($zp, 'Complete'.$lineterm.$lineterm, strlen('Complete'.$lineterm.$lineterm));
                    } else {
                        fwrite($fp, 'Complete'.$lineterm.$lineterm, strlen('Complete'.$lineterm.$lineterm));
                    }
                    $backuptype = 'full';
                    unset($SelectedTables);
                    $SelectedTables = self::_selecttables($dbname);
                 }
                //Let's check the tables and return errors
                $TableErrors = array();
                $TableErrors =  self::_checktables($SelectedTables);

                if (count($TableErrors) > 0) {
                    $tableerrormsg=xarML('TABLE ERRORS!');
                    $runningstatus[]['message']='<strong>'.$tableerrormsg.'</strong><ul><li>'.implode('</li><li>', $TableErrors).'</li></ul>';
                }

                $runningstatus[]['message'] =xarML('<strong>Overall Progress:</strong><br/>');
                $overallrows = 0;
                $overallrows =  self::_bkcountoverallrows($SelectedTables,$number_of_cols);
                $runningstatus[]['message']=xarML('Creating table structures for ').'<strong>'.$dbname.'</strong>'.xarML(' database tables').'<br /><br />';
                $alltablesstructure = '';
                foreach ($SelectedTables as $dbname => $value) {
                    mysql_select_db($dbname);
                    for ($t = 0; $t < count($SelectedTables[$dbname]); $t++) {
                        set_time_limit(60);
                        $runningstatus[]['message']= xarML('Creating structure for <strong> #(1) #(2)</strong>',$dbname,$SelectedTables[$dbname][$t]);

                        $fieldnames     = array();
                        $structurelines = array();
                        $result = mysql_query('SHOW FIELDS FROM '.$backtickchar.$SelectedTables[$dbname][$t].$backtickchar);

                        while ($row = mysql_fetch_assoc($result)) {
                            $structureline  = $backtickchar.$row['Field'].$backtickchar;
                            $structureline .= ' '.$row['Type'];
                            $nulltype = ($row['Null'] =='NO') ? 'NOT NULL' : '';//'NULL';

                            preg_match('/^[a-z]+/i', $row['Type'], $matches);
                            $RowTypes[$dbname][$SelectedTables[$dbname][$t]][$row['Field']] = $matches[0];
                            $textblob =preg_match('/^(tiny|medium|long)?(text|blob)/i', $row['Type']);
                            $chartype=preg_match('/^(varchar)/i', $row['Type']);
                            $inttype=preg_match('/^(tiny|medium|long|)?(int)/i', $row['Type']);

                            if (!$textblob) {
                                $structureline .= ' '.$nulltype;
                            }
                            //if (@$row['Default']) {
                            if ($textblob) {
                                // no default values
                            } else { //make provision for empty '' or zero values
                                if (($row['Null'] =='NO') && $chartype && $chartype>0 && empty($row['Default'])) {
                                    $structureline .= '';//' default \'\'';
                                }elseif (($row['Null'] =='NO') && $inttype && ($inttype>0) && empty($row['Default']) && !empty($row['Extra'])) {
                                    $structureline .= '';//' default \'\'';
                                } elseif (($row['Null'] =='NO')  && empty($row['Default'])) {
                                    $structureline .= ' default \'0\'';
                                }elseif ($row['Null'] =='NO') {
                                    $structureline .= ' default \''.$row['Default'].'\'';
                                }
                            }
                           // }
                            $structureline .= ($row['Extra'] ? ' '.$row['Extra'] : '');
                            $structurelines[] = $structureline;

                            $fieldnames[] = $row['Field'];
                        }
                        mysql_free_result($result);

                        $tablekeys    = array();
                        $uniquekeys   = array();
                        $fulltextkeys = array();
                        $result = mysql_query('SHOW INDEX FROM '.$backtickchar.$SelectedTables[$dbname][$t].$backtickchar);
                        $INDICES = array();
                        while ($row = mysql_fetch_assoc($result)) {
                            $INDICES[$row['Key_name']][$row['Seq_in_index']] = $row;
                        }
                        mysql_free_result($result);
                        foreach ($INDICES as $index_name => $columndata) {
                            $structureline  = '';
                            if ($index_name == 'PRIMARY') {
                                $structureline .= 'PRIMARY ';
                            } elseif ((@$columndata[1]['Index_type'] == 'FULLTEXT') || ($columndata[1]['Comment'] == 'FULLTEXT')) {
                                $structureline .= 'FULLTEXT ';
                            } elseif (!$columndata[1]['Non_unique']) {
                                $structureline .= 'UNIQUE ';
                            }
                            $structureline .= 'KEY';
                            if ($index_name != 'PRIMARY') {
                                $structureline .= ' '.$backtickchar.$index_name.$backtickchar;
                            }
                            $structureline .= ' (';
                            $firstkeyname = true;
                            foreach ($columndata as $seq_in_index => $row) {
                                if (!$firstkeyname) {
                                    $structureline .= ',';
                                }
                                $structureline .= $backtickchar.$row['Column_name'].$backtickchar;
                                if ($row['Sub_part']) {
                                    $structureline .= '('.$row['Sub_part'].')';
                                }
                                $firstkeyname = false;
                            }
                            $structureline .= ')';
                            $structurelines[] = $structureline;
                        }

                        $TableStatusResult = mysql_query('SHOW TABLE STATUS LIKE "'.mysql_escape_string($SelectedTables[$dbname][$t]).'"');
                        if (!($TableStatusRow = mysql_fetch_assoc($TableStatusResult))) {
                            $runningstatus[]['message']= xarML("failed to execute 'SHOW TABLE STATUS' on #(1) #(2) ",$dbname,$tablename);
                        }

                        $tablestructure  = 'CREATE TABLE '.($createifnotexists ? 'IF NOT EXISTS ' : '').($dbnameincreate ? $backtickchar.$dbname.$backtickchar.'.' : '').$backtickchar.$SelectedTables[$dbname][$t].$backtickchar.' ('.$lineterm;
                        $tablestructure .= '  '.implode(','.$lineterm.'  ', $structurelines).$lineterm;
                        $tablestructure .= ') TYPE='.$TableStatusRow[$TypeEngineKey];
                        if ($TableStatusRow['Auto_increment'] !== null) {
                            $tablestructure .= ' AUTO_INCREMENT='.$TableStatusRow['Auto_increment'];
                        }
                        $tablestructure .= ';'.$lineterm.$lineterm;

                        $alltablesstructure .= str_replace(' ,', ',', $tablestructure);

                    } // end table structure backup
                }
                if ($dbcompresstype == 'bzip2') {
                    bzwrite($bp, $alltablesstructure.$lineterm, strlen($alltablesstructure) + strlen($lineterm));
                } elseif ($dbcompresstype == 'gzip') {
                    gzwrite($zp, $alltablesstructure.$lineterm, strlen($alltablesstructure) + strlen($lineterm));
                } else {
                    fwrite($fp, $alltablesstructure.$lineterm, strlen($alltablesstructure) + strlen($lineterm));
                }

                $datastarttime = getmicrotime();
                $runningstatus[]['message']= '';
                if ($startbackup != 'structure') {
                    $processedrows    = 0;
                    foreach ($SelectedTables as $dbname => $value) {
                        set_time_limit(60);
                        mysql_select_db($dbname);
                        for ($t = 0; $t < count($SelectedTables[$dbname]); $t++) {
                            $result = mysql_query('SELECT * FROM '.$SelectedTables[$dbname][$t]);
                            $rows[$t] = mysql_num_rows($result);
                            if ($rows[$t] > 0) {
                                $tabledatadumpline = '-- dumping data for '.$dbname.'.'.$SelectedTables[$dbname][$t].$lineterm;
                                if ($dbcompresstype == 'bzip2') {
                                    bzwrite($bp, $tabledatadumpline, strlen($tabledatadumpline));
                                } elseif ($dbcompresstype == 'gzip') {
                                    gzwrite($zp, $tabledatadumpline, strlen($tabledatadumpline));
                                } else {
                                    fwrite($fp, $tabledatadumpline, strlen($tabledatadumpline));
                                }
                            }
                            unset($fieldnames);
                            for ($i = 0; $i < mysql_num_fields($result); $i++) {
                                $fieldnames[] = mysql_field_name($result, $i);
                            }
                            if ($startbackup == 'complete') {
                                $insertstatement = ($replaceinto ? 'REPLACE' : 'INSERT').' INTO '.$backtickchar.$SelectedTables[$dbname][$t].$backtickchar.' ('.$backtickchar.implode($backtickchar.', '.$backtickchar, $fieldnames).$backtickchar.') VALUES (';
                            } else {
                                $insertstatement = ($replaceinto ? 'REPLACE' : 'INSERT').' INTO '.$backtickchar.$SelectedTables[$dbname][$t].$backtickchar.' VALUES (';
                            }
                            $currentrow       = 0;
                            $thistableinserts = '';
                            while ($row = mysql_fetch_array($result)) {
                                unset($valuevalues);
                                foreach ($fieldnames as $key => $val) {
                                    if ($row[$key] === null) {

                                        $valuevalues[] = 'NULL';

                                    } else {

                                        switch ($RowTypes[$dbname][$SelectedTables[$dbname][$t]][$val]) {
                                            // binary data dump, two hex characters per byte
                                            case 'tinyblob':
                                            case 'blob':
                                            case 'mediumblob':
                                            case 'longblob':
                                                $data = $row[$key];
                                                $data_len = strlen($data);
                                                if ($hexblobs && $data_len) {
                                                    $hexstring = '0x';
                                                    for ($i = 0; $i < $data_len; $i++) {
                                                        $hexstring .= str_pad(dechex(ord($data{$i})), 2, '0', STR_PAD_LEFT);
                                                    }
                                                    $valuevalues[] = $hexstring;
                                                } else {
                                                    $valuevalues[] = $quotechar.mysql_escape_string($data).$quotechar;
                                                }
                                                break;

                                            // just the (numeric) value, not surrounded by quotes
                                            case 'tinyint':
                                            case 'smallint':
                                            case 'mediumint':
                                            case 'int':
                                            case 'bigint':
                                            case 'float':
                                            case 'double':
                                            case 'decimal':
                                            case 'year':
                                                $valuevalues[] = mysql_escape_string($row[$key]);
                                                break;

                                            // value surrounded by quotes
                                            case 'varchar':
                                            case 'char':
                                            case 'tinytext':
                                            case 'text':
                                            case 'mediumtext':
                                            case 'longtext':
                                            case 'enum':
                                            case 'set':
                                            case 'date':
                                            case 'datetime':
                                            case 'time':
                                            case 'timestamp':
                                            default:
                                                $valuevalues[] = $quotechar.mysql_escape_string($row[$key]).$quotechar;
                                                break;
                                        }

                                    }
                                }
                                $thistableinserts .= $insertstatement.implode(', ', $valuevalues).');'.$lineterm;

                                if (strlen($thistableinserts) >= $buffer_size) {
                                    if ($dbcompresstype == 'bzip2') {
                                        bzwrite($bp, $thistableinserts, strlen($thistableinserts));
                                    } elseif ($dbcompresstype == 'gzip') {
                                        gzwrite($zp, $thistableinserts, strlen($thistableinserts));
                                    } else {
                                        fwrite($fp, $thistableinserts, strlen($thistableinserts));
                                    }
                                    $thistableinserts = '';
                                }
                                if ((++$currentrow % $stats_interval) == 0) {
                                    set_time_limit(60);
                                    //if ($nohtml != 1) {
                                        $runningstatus[]['message']= '<strong>'.$SelectedTables[$dbname][$t].' ('.number_format($rows[$t]).' records, ['.number_format(($currentrow / $rows[$t])*100).'%])</strong>';
                                        $elapsedtime = getmicrotime() - $datastarttime;
                                        $percentprocessed = ($processedrows + $currentrow) / $overallrows;
                                        $overallprogress = 'Overall Progress: '.number_format($processedrows + $currentrow).' / '.number_format($overallrows).' ('.number_format($percentprocessed * 100, 1).'% done) ['.FormattedTimeRemaining($elapsedtime).' elapsed';
                                        if (($percentprocessed > 0) && ($percentprocessed < 1)) {
                                            $overallprogress .= ', '.FormattedTimeRemaining(abs($elapsedtime - ($elapsedtime / $percentprocessed))).' remaining';
                                        }
                                        $overallprogress .= ']';
                                        $runningstatus[]['message'] = $overallprogress;

                                   // }
                                }
                            }
                          //  if ($nohtml !=1) {
                                $runningstatus[]['message']= $SelectedTables[$dbname][$t].' ('.number_format($rows[$t]).' records, [100%])';
                                $processedrows += $rows[$t];
                          //  }
                            if ($dbcompresstype == 'bzip2') {
                                bzwrite($bp, $thistableinserts.$lineterm.$lineterm, strlen($thistableinserts) + strlen($lineterm) + strlen($lineterm));
                            } elseif ($dbcompresstype == 'gzip') {
                                gzwrite($zp, $thistableinserts.$lineterm.$lineterm, strlen($thistableinserts) + strlen($lineterm) + strlen($lineterm));
                            } else {
                                fwrite($fp, $thistableinserts.$lineterm.$lineterm, strlen($thistableinserts) + strlen($lineterm) + strlen($lineterm));
                            }
                        }
                    }
                }
                if ($dbcompresstype == 'bzip2') {
                    bzclose($bp);
                } elseif ($dbcompresstype == 'gzip') {
                    gzclose($zp);
                } else {
                    fclose($fp);
                }

                if (file_exists($newfullfilename)) {
                    unlink($newfullfilename); // Windows won't allow overwriting via rename
                }
                rename($backupabsolutepath.$tempbackupfilename, $newfullfilename);

            } else {

               $runningstatus[]['message']='<strong>Warning:</strong> failed to open '.$backupabsolutepath.$tempbackupfilename.' for writing!<br/><br/>';
                if (is_dir($backupabsolutepath)) {
                    $runningstatus[]['message']= '<emphasis>CHMOD 777</emphasis> on the directory ('.htmlentities($backupabsolutepath).') should fix that.';
                } else {
                    $runningstatus[]['message']='The specified directory does not exist: "'.htmlentities($backupabsolutepath).'"';
                }

            }
        }

    if ($nohtml ==1) {
        $runningstatus = array();
        xarLogMessage("SITETOOLS: runningstatus array now empty");
    }

    return $runningstatus;

    }
}
?>
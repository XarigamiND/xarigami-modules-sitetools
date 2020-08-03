<?php
/**
 * Site Tools SQL Terminal
 *
 * @package modules
 * @copyright (C) 2002-2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Sitetools Module
 * @copyright (C) 2003-2010 2skies.com
 * @link http://xarigami.com/project/sitetools
 * @author Jo Dalle Nogare <icedlava@2skies.com>
 */

 /* A simple sql terminal for Sitetools
  * @author jojodee
  * @author Marcel van der Boom - supplied original script on which this is based
  */
function sitetools_admin_terminal()
{
    if(!xarVarFetch('term_input','str::',$term_input,'')) return;

   if (!xarSecurityCheck('AdminSiteTools')) return;
   $data = array();
    $output = array();
    if($term_input != '') {
        /* Pass verbatim to database; */
        $dbconn = xarDBGetConn();
        $result = $dbconn->Execute($term_input);
        if(!$result) {
            $output[] = array("Error" => $error->getShort());
        } else {
            if(is_object($result)) {
                while(!$result->EOF) {
                    $row = $result->GetRowAssoc(true);
                    $output[] = $row;
                    $result->MoveNext();
                }
            } else {
                $output[] = array(xarML("Success"));
            }
        }
    }

    /* $data['term_output'] = print_r($output,true); */
    $data['term_output'] = $output;
    $data['term_input'] = $term_input;

    //common menu link
    $data['menulinks']= xarModAPIFunc('sitetools','admin','getmenulinks');    
    return $data;
}
?>
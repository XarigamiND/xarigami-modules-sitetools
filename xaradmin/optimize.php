<?php
/**
 * Site Tools DB Optimization
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
 * @Optimize tables in your database
 * @Only for mySQL datbase at this time
 * @TODO: database abstraction classs
 */
function sitetools_admin_optimize()
{
   if (!xarVarFetch('confirm', 'str:1:', $confirm, '', XARVAR_NOT_REQUIRED)) return;

    /* Security check */
    if (!xarSecurityCheck('AdminSiteTools')) return;

    $data=array();

    //common menu link
    $data['menulinks']= xarModAPIFunc('sitetools','admin','getmenulinks');
    /* Check for confirmation. */
    if (empty($confirm)) {
        /* No confirmation yet - display a suitable form to obtain confirmation
         * of this action from the user
         */
        $data['optimized']=false;
        /* Generate a one-time authorisation code for this operation */
        $data['authid'] = xarSecGenAuthKey();
        /* Return the template variables defined in this function */
        return $data;
    }
    /* If we get here it means that the user has confirmed the action */


    /* Confirm authorisation code. */
    if (!xarSecConfirmAuthKey()) return;

    /* Start optimization api */
        $tabledata=array();
        $total_gain=0;
        $total_kbs=0;
        //optimize and get data for each table's result
        $tabledata= xarModAPIFunc('sitetools','admin','optimizedb');

        if ($tabledata == false) {
            /* Throw back any system exceptions (e.g. database failure) */
            /* Handle the user exceptions yourself */
            $status = xarML('Optimizing database failed');
            return $status;
        }

       $data['tabledat']=$tabledata['rowdata'];

       $total_gain=$tabledata['total_gain'];
       $total_kbs=$tabledata['total_kbs'];
       $data['totalgain'] = round ($total_gain,3);
       $data['totalkbs']  = round ($total_kbs,3);
       $data['dbname']    = $tabledata['dbname'];
       //Add this new optimization record to the database
       $optid = xarModAPIFunc('sitetools', 'admin','create',
                              array('totalgain' => $data['totalgain']));

      if (!isset($optid) && xarCurrentErrorType() != XAR_NO_EXCEPTION) return; // throw back

       /*get total number of times this script has run and total kbs */
      $items = xarModAPIFunc('sitetools', 'admin', 'getall');
       $gaintd=0;
       $runtimes=0;
       foreach ($items as $item) {
            $gaintd += $item['stgain'];
            $runtimes += 1;
       }

       $data['totalruns']=$runtimes;
       $data['gaintd']=$gaintd;
       $data['optimized']=true;
        $msg = xarML('Optimization was succesfully completed.');
        xarTplSetMessage($msg,'status');
    /* return */
return $data;
}
?>
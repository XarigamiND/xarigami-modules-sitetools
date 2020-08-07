<?php
/**
 * Site Tools Template Cache Management
 *
 * @package modules
 * @subpackage Xarigami Sitetools Module
 * @copyright (C) 2003-2010 2skies.com
 * @link http://xarigami.com/project/sitetools
 * @author Jo Dalle Nogare <icedlava@2skies.com>
 */
/**
 *
 * @ View Cache Files
 * @param  $ 'action' action taken on cache file
 * @param $ 'confirm' confirm action on delete
 */
function sitetools_admin_cacheview($args)
{

    /* Get parameters from whatever input we need. */
    if (!xarVarFetch('action', 'str:1', $action, false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('confirm', 'str:1:', $confirm, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('hashn', 'str:1:', $hashn, false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('templn', 'str:1:', $templn, false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('order', 'pre:trim:alpha:lower:enum:asc:desc',   $order, 'asc', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('sortby', 'str:0:4', $sortby, 'key',  XARVAR_GET_OR_POST)) return; //'name' template name or 'key'
     if (!xarVarFetch('message', 'str', $message, '',  XARVAR_NOT_REQUIRED)) return;

    /* Security check - important to do this as early as possible */
    if (!xarSecurityCheck('AdminSiteTools')) {
        return;
    }

    //get the current options
    $sitetoolconfig = xarModGetVar('sitetools','sitetooloptions');
    $sitetoolconfig = unserialize($sitetoolconfig);

    foreach ($sitetoolconfig  as $varname =>$value) {
        $$varname = $value;
    }

    $cachedir    = $templcachepath;
    $cachefile   = $templcachepath.'/CACHEKEYS';
    //Do not include the template for the current
    $scriptcache = $templcachepath.'/d4609360b2e77516aabf27c1f468ee33.php';
    $data=array();

    //common menu link
    $data['menulinks']= xarModAPIFunc('sitetools','admin','getmenulinks');
    $data['popup']=false;
    $data['message'] = $message;

    //check the cache is on
    $cacheon = xarConfigGetVar('Site.BL.CacheTemplates');
    if (!$cacheon) {
        $themeadmin = xarModURL('themes','admin','modifyconfig');

        $data['message'] = xarML('The Template cache is currently turned off. Please <a href="'.$themeadmin.'">turn on the template cache</a> to view cache files.');
        return $data;
    }
    /* Check for confirmation. */
    $data['authid'] = xarSecGenAuthKey();
    $data['return_url']=xarModURL('sitetools','admin','cacheview');
    $data['dummyimage'] = xarTplGetImage('blank.gif','base');
    if (empty($action)) {
        /* No action set yet - display cache file list and await action */
         $data['showfiles']=false;
        /* Generate a one-time authorisation code for this operation */
        $data['items']='';
        $cachelist=array();
        $cachenames=array();

        /* put all the names of the templates and hashed cache file into an array */
        umask();
        $count=0;
        $cachekeyfile=@file($cachefile);
        $fd = @fopen($cachefile,'r');
        foreach($cachekeyfile as $line_num => $line) {
              $cachelist[]=array(explode(": ", $line));
            ++$count;
        }
        $data['count']=$count;
        fclose($fd);

        /* generate all the URLS for cache file list */
        foreach($cachelist as $hashname) {
            foreach ($hashname as $filen) {
               $hashn=htmlspecialchars($filen[0]);
               $templn=htmlspecialchars($filen[1]);
               $fullnurl=xarModURL('sitetools','admin','cacheview',
                                  array('action'=>'show','templn'=>$templn,'hashn'=>$hashn));
               $cachenames[$templn]=array('hashn'=>$hashn,
                                   'templn'=>$templn,
                                   'fullnurl'=>$fullnurl);
            }
        }

        $uorder = strtoupper($order);
        if (isset($sortby) && $sortby == 'key') {  //sort by the hash name
            if (isset($uorder) && ($uorder == 'DESC')) {
                arsort($cachenames);
            } else {
                asort($cachenames);
            }
        }else  {
            //sort by the file name
            if (isset($uorder) && ($uorder == 'DESC')) {
                krsort($cachenames);

            } else {
                ksort($cachenames);
            }
        }
        if ($order == 'asc') {
            $data['sortimgclass'] = 'sorted-asc.png';
            $data['sortimglabel'] = xarML('Sorted Ascending');
        } else {
            $data['sortimgclass'] = 'sorted-desc.png';
             $data['sortimglabel'] = xarML('Sorted Descending');
        }

        $sortimage['key'] = false;
        $sortimage['name'] = false;
        if ($sortby == 'key') {
            $sortimage['key'] = true;
        } else {
            $sortimage['name'] = true;
        }
        $data['sortimage'] = $sortimage;
        $data['items']=$cachenames;
        $dorder = ($order == 'asc') ? 'desc' : 'asc';
        $data['hashsort'] = xarModURL('sitetools','admin','cacheview',array('sortby'=>'key','order'=>$dorder));
        $data['namesort'] = xarModURL('sitetools','admin','cacheview',array('sortby'=>'name','order'=>$dorder));
        $data['dorder'] = $dorder;
        /* Return the template variables defined in this function */
        return $data;

    } elseif ($action=='show'){
         $data['showfiles']= true;
        $hashfile=$cachedir.'/'.$hashn.'.php';
        $testpath = realpath(dirname($hashfile));
        if ($testpath != realpath($cachedir)) {
            $msg = xarML('You tried to browse a file outside of the cache directory. This action is not permitted.');
            xarResponseRedirect(xarModURL('sitetools','admin','cacheview',array('message' => $msg)));
        }
        $newfile=array();
        $filetxt=array();
        $newfile = file($hashfile);
        $i=0;
        foreach ($newfile as $line_num => $line) {
            ++$i;
            $filetxt[]=array('lineno' =>(int)$i,
                          'linetxt'=>htmlspecialchars($line));
        }
        $data['templn']=$templn;
        $data['hashfile']=$hashfile;
        $data['items']=$filetxt;
        return $data;
    }


    xarResponseRedirect(xarModURL('sitetools', 'admin', 'cacheview'));
    /*  Return */
    return true;
}
?>

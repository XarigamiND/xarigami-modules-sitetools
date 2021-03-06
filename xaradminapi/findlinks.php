<?php
/**
 * @package modules
 * @subpackage Xarigami Sitetools Module
 * @copyright (C) 2003-2010 2skies.com
 * @link http://xarigami.com/project/sitetools
 * @author Jo Dalle Nogare <icedlava@2skies.com>
 */

/**
 * Find links in fields from different modules/itemtypes
 * @author mikespub
 * @param $args['fields'] array of [module][itemtype] = fieldlist
 * @param $args['skiplocal'] bool optional flag to skip local links (default false) OPTIONAL
 * @return array. An array with the number of links found per module/itemtype
 * @throws DATABASE_ERROR
*/
function sitetools_adminapi_findlinks($args)
{
    extract($args);

    if (!isset($skiplocal)) $skiplocal = false;

    // load APIs for table names etc.
    xarModAPILoad('roles','user');
    xarModAPILoad('dynamicdata','user');
    if (xarModIsAvailable('articles')) {
        xarModAPILoad('articles','user');
    }

    $dbconn = xarDBGetConn();
    $xartable = xarDBGetTables();

    $linkstable = $xartable['sitetools_links'];

    // remove old links from the database
    $query = "DELETE FROM $linkstable";
    $result = $dbconn->Execute($query);
    if (!$result) return;

    $count = array();

    $server = xarServerGetHost();

    // find links for articles
    if (!empty($fields['articles']) && xarModIsAvailable('articles')) {
        $modid = xarModGetIDFromName('articles');
        $articlestable = $xartable['articles'];
        $pubtypes = xarModAPIFunc('articles','user','getpubtypes');

        foreach ($fields['articles'] as $ptid => $fieldlist) {
            $descr = $pubtypes[$ptid]['descr'];
            $count[$descr] = 0;
            $articlefields = array();
            $dynamicfields = array();
            // get the list of defined columns for articles
            $columns = array_keys($pubtypes[$ptid]['config']);
            foreach ($fieldlist as $field) {
                if (in_array($field,$columns)) {
                    $articlefields[] = $field;
                } else {
                    $dynamicfields[] = $field;
                }
            }
            if (count($articlefields) > 0) {
                $where = array();
                foreach ($articlefields as $field) {
                    $where[] = $field . " ne ''";
                }
                $whereclause = join(' or ',$where);
                $getfields = $articlefields;
                if (!in_array('aid',$getfields)) $getfields[] = 'aid';
                if (!in_array('title',$getfields)) $getfields[] = 'title';
                $items = xarModAPIFunc('articles','user','getall',
                                       array('ptid' => $ptid,
                                             'fields' => $getfields,
                                             'where' => $whereclause));
                $serialized = array();
                foreach ($articlefields as $field) {
                    if ($pubtypes[$ptid]['config'][$field]['format'] == 'urltitle') {
                        $serialized[$field] = 1;
                    }
                }
                foreach ($items as $item) {
                    $url = xarModURL('articles','user','display',
                                     array('aid' => $item['aid']));
                    foreach ($articlefields as $field) {
                        if (empty($item[$field])) continue;
                        if (!empty($serialized[$field])) {
                            $info = unserialize($item[$field]);
                            if (empty($info['link'])) continue;
                            $item[$field] = $info['link'];
                        }
                        if ($skiplocal &&
                            (!strstr($item[$field],'://') ||
                              preg_match("!://($server|localhost|127\.0\.0\.1)((:\d+)?/|$)!",$item[$field])) ) {
                            continue;
                        }
                        $id = $dbconn->GenId($linkstable);
                        $query = "INSERT INTO $linkstable (xar_id, xar_link, xar_status, xar_moduleid, xar_itemtype, xar_itemid, xar_itemtitle, xar_itemlink)
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                        $bindvars = array($id, $item[$field], 0, $modid, $ptid, $item['aid'], $item['title'], $url);
                        $result = $dbconn->Execute($query,$bindvars);
                        if (!$result) return;
                        $count[$descr]++;
                    }
                }
            }
            if (count($dynamicfields) > 0) {
                $where = array();
                foreach ($dynamicfields as $field) {
                    $where[] = $field . " ne ''";
                }
                $whereclause = join(' or ',$where);
                $object = new Dynamic_Object_List(array('moduleid' => $modid,
                                                        'itemtype' => $ptid,
                                                        'fieldlist' => $fieldlist,
                                                        'where' => $whereclause));
                $object->joinTable(array('table' => $articlestable,
                                         'key' => 'aid',
                                         'fields' => array('aid','title')));
                $items = $object->getItems();
                $serialized = array();
                foreach ($dynamicfields as $field) {
                    if ($object->properties[$field]->type == 41) { // urltitle
                        $serialized[$field] = 1;
                    }
                }
                foreach ($items as $item) {
                    $url = xarModURL('articles','user','display',
                                     array('aid' => $item['aid']));
                    foreach ($dynamicfields as $field) {
                        if (empty($item[$field])) continue;
                        if (!empty($serialized[$field])) {
                            $info = unserialize($item[$field]);
                            if (empty($info['link'])) continue;
                            $item[$field] = $info['link'];
                        }
                        if ($skiplocal &&
                            (!strstr($item[$field],'://') ||
                              preg_match("!://($server|localhost|127\.0\.0\.1)((:\d+)?/|$)!",$item[$field])) ) {
                            continue;
                        }
                        $id = $dbconn->GenId($linkstable);
                        $query = "INSERT INTO $linkstable (xar_id, xar_link, xar_status, xar_moduleid, xar_itemtype, xar_itemid, xar_itemtitle, xar_itemlink)
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                        $bindvars = array($id, $item[$field], 0, $modid, $ptid, $item['aid'], $item['title'], $url);
                        $result = $dbconn->Execute($query,$bindvars);
                        if (!$result) return;
                        $count[$descr]++;
                    }
                }
            }
        }
    }

    // find links for roles
    if (!empty($fields['roles'])) {
        $modid = xarModGetIDFromName('roles');
        $rolestable = $xartable['roles'];
        // only 1 itemtype for now, but groups might have separate DD fields later on
        $descr = array(0 => xarML('Users'),
                       1 => xarML('Groups'));
        foreach ($fields['roles'] as $itemtype => $fieldlist) {
            $where = array();
            foreach ($fieldlist as $field) {
                $where[] = $field . " ne ''";
            }
            $whereclause = join(' or ',$where);
            $object = new Dynamic_Object_List(array('moduleid' => $modid,
                                                    'itemtype' => $itemtype,
                                                    'fieldlist' => $fieldlist,
                                                    'where' => $whereclause));
            $object->joinTable(array('table' => $rolestable,
                                     'key' => 'uid',
                                     'fields' => array('uid','name')));
            $items = $object->getItems();
            $serialized = array();
            foreach ($fieldlist as $field) {
                if ($object->properties[$field]->type == 41) { // urltitle
                    $serialized[$field] = 1;
                }
            }
            $count[$descr[$itemtype]] = 0;
            foreach ($items as $itemid => $item) {
                $url = xarModURL('roles','user','display',
                                 array('uid' => $itemid));
                foreach ($fieldlist as $field) {
                    if (empty($item[$field])) continue;
                    if (!empty($serialized[$field])) {
                        $info = unserialize($item[$field]);
                        if (empty($info['link'])) continue;
                        $item[$field] = $info['link'];
                    }
                    if ($skiplocal &&
                        (!strstr($item[$field],'://') ||
                          preg_match("!://($server|localhost|127\.0\.0\.1)((:\d+)?/|$)!",$item[$field])) ) {
                        continue;
                    }
                    $id = $dbconn->GenId($linkstable);
                    $query = "INSERT INTO $linkstable (xar_id, xar_link, xar_status, xar_moduleid, xar_itemtype, xar_itemid, xar_itemtitle, xar_itemlink)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $bindvars = array($id, $item[$field], 0, $modid, $itemtype, $itemid, $item['name'], $url);
                    $result = $dbconn->Execute($query,$bindvars);
                    if (!$result) return;
                    $count[$descr[$itemtype]]++;
                }
            }
        }
    }
    // find links for xarpages

    if (!empty($fields['xarpages']) && xarModIsAvailable('xarpages')) {
        $modid = xarModGetIDFromName('xarpages');
        $xarpagestable = $xartable['xarpages_pages'];
        $pagetypes = xarModAPIFunc('xarpages','user','gettypes');
        $typearray = array();
        foreach ($pagetypes as $id=>$pagetype) {
            $typearray[$pagetype['ptid']] = $pagetype;
        }
        $pagetypes = $typearray;
        foreach ($fields['xarpages'] as $ptid => $fieldlist) {

            $pagename = $pagetypes[$ptid]['name'];
            $count[$pagename] = 0;
            $dynamicfields = array();
            foreach ($fieldlist as $field) {
                $dynamicfields[] = $field;
            }

            if (count($dynamicfields) > 0) {
                $where = array();
                foreach ($dynamicfields as $field) {
                    $where[] = $field . " ne ''";
                }

                $whereclause = join(' or ',$where);
                $object = new Dynamic_Object_List(array('moduleid' => $modid,
                                                        'itemtype' => $ptid,
                                                        'fieldlist' => $fieldlist,
                                                        'where' => $whereclause));
                                                        
                $object->joinTable(array('table' => $xarpagestable,
                                         'key' => 'pid',
                                         'fields' => array('pid','name')));
                $items = $object->getItems();

                $serialized = array();
                foreach ($dynamicfields as $field) {
                    if (isset($object->properties[$field]) && $object->properties[$field]->type == 41) { // urltitle
                        $serialized[$field] = 1;
                    }
                }
                foreach ($items as $item) {
                    $url = xarModURL('xarpages','user','display',
                                     array('pid' => $item['pid']));
                    foreach ($dynamicfields as $field) {
                        if (empty($item[$field])) continue;
                        if (!empty($serialized[$field])) {
                            $info = unserialize($item[$field]);
                            if (empty($info['link'])) continue;
                            $item[$field] = $info['link'];
                        }
                        if ($skiplocal &&
                            (!strstr($item[$field],'://') ||
                              preg_match("!://($server|localhost|127\.0\.0\.1)((:\d+)?/|$)!",$item[$field])) ) {
                            continue;
                        }
                        $id = $dbconn->GenId($linkstable);
                        $query = "INSERT INTO $linkstable (xar_id, xar_link, xar_status, xar_moduleid, xar_itemtype, xar_itemid, xar_itemtitle, xar_itemlink)
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                        $bindvars = array($id, $item[$field], 0, $modid, $ptid, $item['pid'], $item['name'], $url);
                        $result = $dbconn->Execute($query,$bindvars);
                        if (!$result) return;
                        $count[$pagename]++;
                    }
                }
            }
        }
    }
    // TODO: find links for ...
    return $count;
}
?>
<?php
/**
 * @package modules
 * @subpackage Xarigami Sitetools Module
 * @copyright (C) 2003-2010 2skies.com
 * @link http://xarigami.com/project/sitetools
 * @author Jo Dalle Nogare <icedlava@2skies.com>
 */

/**
 * Get fields containing links for different modules/itemtypes
 * @author mikespub
 * @returns array
 * @return array of module titles and their link fields
 * @throws DATABASE_ERROR
*/
function sitetools_adminapi_getlinkfields($args)
{
    extract($args);

    $modules = array();

    $proptypes = xarModAPIFunc('dynamicdata','user','getproptypes');

    // find relevant fields for articles
    if (xarModIsAvailable('articles')) {
        $pubtypes = xarModAPIFunc('articles','user','getpubtypes');
        $fieldformats = xarModAPIFunc('articles','user','getpubfieldformats');
        foreach ($pubtypes as $pubid => $pubtype) {
            $fields = array();
            foreach ($pubtype['config'] as $field => $info) {
                if (empty($info['label'])) continue;
                switch ($info['format'])
                {
                    case 'url':
                    case 'image':
                // skip imagelists here
                    //case 'imagelist':
                    case 'urltitle':
                        if (isset($fieldformats[$info['format']])) {
                            $type = $fieldformats[$info['format']];
                        } else {
                            $type = $info['format'];
                        }
                        $fields[] = array('name' => $info['label'],
                                          'field' => 'articles.' . $pubid . '.' . $field,
                                          'type' => $type);
                        break;
                    default:
                        break;
                }
            }
            $object = xarModAPIFunc('dynamicdata','user','getobject',
                                    array('module' => 'articles',
                                          'itemtype' => $pubid));
            if (!empty($object) && count($object->properties) > 0) {
                foreach ($object->properties as $name => $property) {
                    switch ($proptypes[$property->type]['name'])
                    {
                        case 'url':
                        case 'image':
                    // skip imagelists here
                        //case 'imagelist':
                        case 'urlicon':
                        case 'urltitle':
                            $fields[] = array('name' => $property->label,
                                              'field' => 'articles.' . $pubid . '.' . $name,
                                              'type' => $proptypes[$property->type]['label']);
                            break;
                        default:
                            break;
                    }
                }
            }
            if (count($fields) > 0) {
                $modules[$pubtype['descr']] = $fields;
            }
        }
    }

    // find relevant fields for roles
    // only 1 itemtype for now, but groups might have separate DD fields later on
    $rolesobject = xarModAPIFunc('dynamicdata','user','getobject',
                                 array('module' => 'roles'));
    if (!empty($rolesobject) && count($rolesobject->properties) > 0) {
        $fields = array();
        foreach ($rolesobject->properties as $name => $property) {
            switch ($proptypes[$property->type]['name'])
            {
                case 'url':
                case 'image':
            // skip imagelists here
                //case 'imagelist':
                case 'urlicon':
                case 'urltitle':
                    $fields[] = array('name' => $property->label,
                                      'field' => 'roles.0.' . $name,
                                      'type' => $proptypes[$property->type]['label']);
                    break;
                default:
                    break;
            }
        }
        if (count($fields) > 0) {
            $descr = xarML('Users');
            $modules[$descr] = $fields;
        }
    }
    // find relevant fields for xarpages
    if (xarModIsAvailable('xarpages')) {
        $pagetypes = xarModAPIFunc('xarpages','user','gettypes');
        foreach ($pagetypes as $id => $pagetype) {
            $fields = array();
            $object = xarModAPIFunc('dynamicdata','user','getobject',
                                    array('module' => 'xarpages',
                                          'itemtype' => $pagetype['ptid']));
            
            if (!empty($object) && count($object->properties) > 0) {
                foreach ($object->properties as $name => $property) {
                    switch ($proptypes[$property->type]['name'])
                    {
                        case 'url':
                        case 'image':
                        case 'urlicon':
                        case 'urltitle':
                            $fields[] = array('name' => $property->label,
                                              'field' => 'xarpages.' . $pagetype['ptid'] . '.' . $name,
                                              'type' => $proptypes[$property->type]['label']);
                            break;
                        default:
                            break;
                    }
                }

                if (count($fields) > 0) {
                    $modules[$pagetype['name']] = $fields;
                } 
           }
        }           
    }
    // TODO: find relevant fields for ...
    return $modules;
}

?>
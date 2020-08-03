<?php
/**
 * Site Tools Initialization
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Sitetools Module - Cumulus version
 * @copyright (C) 2003-2011 2skies.com
 * @link http://xarigami.com/project/sitetools
 * @author Jo Dalle Nogare <icedlava@2skies.com>
 */

$modversion['name']           = 'sitetools';
$modversion['id']             = '887';
$modversion['version']        = '1.0.2';
$modversion['displayname']    = 'SiteTools';
$modversion['description']    = 'Set of tools for site and database maintenance';
$modversion['credits']        = 'xardocs/credits.txt';
$modversion['help']           = 'xardocs/help.txt';
$modversion['changelog']      = 'xardocs/changelog.txt';
$modversion['license']        = 'xardocs/license.txt';
$modversion['official']       = 1;
$modversion['author']         = 'jojodee';
$modversion['homepage']       = 'http://xarigami.com/project/sitetools';
$modversion['contact']        = 'http://xarigami.com';
$modversion['admin']          = 1;
$modversion['user']           = 0;
$modversion['class']          = 'Complete';
$modversion['category']       = 'Tools';
$modversion['dependencyinfo']   = array(
                                    0 => array(
                                            'name' => 'core',
                                            'version_ge' => '1.4.0'
                                         )
                                );
if (false) { //Load and translate once
    xarML('SiteTools');
    xarML('Set of tools for site and database maintenance for Xarigami Cumulus');
}

?>
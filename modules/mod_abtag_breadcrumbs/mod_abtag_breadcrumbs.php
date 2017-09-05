<?php
/**
 * @package ABTag
 * @copyright Copyright (c) 2017 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

// Include the breadcrumbs functions only once
JLoader::register('ModAbtagBreadCrumbsHelper', __DIR__ . '/helper.php');

// Get the breadcrumbs
$list  = ModAbtagBreadCrumbsHelper::getList($params);
$count = count($list);

// Set the default separator
$separator = ModAbtagBreadCrumbsHelper::setSeparator($params->get('separator'));
$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx'), ENT_COMPAT, 'UTF-8');

require JModuleHelper::getLayoutPath('mod_abtag_breadcrumbs', $params->get('layout', 'default'));

<?php
/**
 * @package ABTag
 * @copyright Copyright (c) 2017 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die;

jimport('joomla.application.categories');

/**
 * Build the route for the com_abtag component
 *
 * @param	array	An array of URL arguments
 *
 * @return	array	The URL arguments to use to assemble the subsequent URL.
 */
function AbtagBuildRoute(&$query)
{
  $segments = array();

  if(isset($query['view'])) {
    $segments[] = $query['view'];
    unset($query['view']);
  }

  if(isset($query['id'])) {
    $segments[] = $query['id'];
    unset($query['id']);
  }

  if(isset($query['tagid'])) {
    $segments[] = $query['tagid'];
    unset($query['tagid']);
  }

  if(isset($query['layout'])) {
    unset($query['layout']);
  }

  return $segments;
}


/**
 * Parse the segments of a URL.
 *
 * @param	array	The segments of the URL to parse.
 *
 * @return	array	The URL attributes to be used by the application.
 */
function AbtagParseRoute($segments)
{
  $vars = array();

  switch($segments[0])
  {
    case 'tag':
	   $vars['view'] = 'tag';
	   $id = explode(':', $segments[1]);
	   $vars['id'] = (int)$id[0];
	   break;
    case 'entry':
	   $vars['view'] = 'entry';
	   $id = explode(':', $segments[1]);
	   $vars['id'] = (int)$id[0];
	   $tagid = explode(':', $segments[2]);
	   $vars['tagid'] = (int)$tagid[0];
	   break;
  }

  return $vars;
}


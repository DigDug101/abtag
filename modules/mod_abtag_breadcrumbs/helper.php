<?php
/**
 * @package ABTag
 * @copyright Copyright (c) 2017 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/**
 * Helper for mod_breadcrumbs
 *
 * @package     Joomla.Site
 * @subpackage  mod_breadcrumbs
 * @since       1.5
 */
class ModAbtagBreadCrumbsHelper
{
	/**
	 * Retrieve breadcrumb items
	 *
	 * @param   \Joomla\Registry\Registry  &$params  module parameters
	 *
	 * @return array
	 */
	public static function getList(&$params)
	{
		// Get the PathWay object from the application
		$app     = JFactory::getApplication();
		$pathway = $app->getPathway();
		$allItems   = $pathway->getPathWay();
		$lang    = JFactory::getLanguage();
		$menu    = $app->getMenu();

		// Look for the home menu
		if (JLanguageMultilang::isEnabled())
		{
			$home = $menu->getDefault($lang->getTag());
		}
		else
		{
			$home  = $menu->getDefault();
		}

		//ABTag note: Rules out the items linked to categories.
		$items = array();
		foreach($allItems as $key => $item) {
		  if(!preg_match('#view=category#', $item->link)) {
		    $items[] = $item;
		  }
		}

		$count = count($items);

		// Don't use $items here as it references JPathway properties directly
		$crumbs = array();

		for ($i = 0; $i < $count; $i ++)
		{
			$crumbs[$i]       = new stdClass;
			$crumbs[$i]->name = stripslashes(htmlspecialchars($items[$i]->name, ENT_COMPAT, 'UTF-8'));
			$crumbs[$i]->link = JRoute::_($items[$i]->link);
		}

		if ($params->get('showHome', 1))
		{
			$item       = new stdClass;
			$item->name = htmlspecialchars($params->get('homeText', JText::_('MOD_ABTAG_BREADCRUMBS_HOME')), ENT_COMPAT, 'UTF-8');
			$item->link = JRoute::_('index.php?Itemid=' . $home->id);
			array_unshift($crumbs, $item);
		}

		return $crumbs;
	}

	/**
	 * Set the breadcrumbs separator for the breadcrumbs display.
	 *
	 * @param   string  $custom  Custom xhtml complient string to separate the
	 * items of the breadcrumbs
	 *
	 * @return  string	Separator string
	 *
	 * @since   1.5
	 */
	public static function setSeparator($custom = null)
	{
		$lang = JFactory::getLanguage();

		// If a custom separator has not been provided we try to load a template
		// specific one first, and if that is not present we load the default separator
		if ($custom === null)
		{
			if ($lang->isRtl())
			{
				$_separator = JHtml::_('image', 'system/arrow_rtl.png', null, null, true);
			}
			else
			{
				$_separator = JHtml::_('image', 'system/arrow.png', null, null, true);
			}
		}
		else
		{
			$_separator     = htmlspecialchars($custom, ENT_COMPAT, 'UTF-8');
		}

		return $_separator;
	}
}

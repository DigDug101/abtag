<?php
/**
 * @package ABTag
 * @copyright Copyright (c) 2017 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


// No direct access
defined('_JEXEC') or die('Restricted access');
// Import the JPlugin class
jimport('joomla.plugin.plugin');


class plgContentAbtagplugins extends JPlugin
{
  public function onContentBeforeDisplay($context, &$row, &$params, $page = 0)
  {
		$app   = JFactory::getApplication();
		$view  = $app->input->get('view');
		$print = $app->input->getBool('print');

		if ($print)
		{
			return false;
		}

		if (($context === 'com_abtag.entry') && ($view === 'entry') && $params->get('show_item_navigation') && isset($row->tag_id))
		{
			$db       = JFactory::getDbo();
			$user     = JFactory::getUser();
			$lang     = JFactory::getLanguage();
			$nullDate = $db->getNullDate();

			$date = JFactory::getDate();
			$now  = $date->toSql();

			$uid        = $row->entry_id;
			$option     = 'com_content';
			$canPublish = $user->authorise('core.edit.state', $option .  '.article.' . $row->article_id);

			/**
			 * The following is needed as different menu items types utilise a different param to control ordering.
			 * For Blogs the `orderby_sec` param is the order controlling param.
			 * For Table and List views it is the `orderby` param.
			**/
			$params_list = $params->toArray();

			if (array_key_exists('orderby_sec', $params_list))
			{
				$order_method = $params->get('orderby_sec', '');
			}
			else
			{
				$order_method = $params->get('orderby', '');
			}

			// Additional check for invalid sort ordering.
			if ($order_method === 'front')
			{
				$order_method = '';
			}

			// Get the order code
			$orderDate = $params->get('order_date');
			$queryDate = $this->getQueryDate($orderDate);

			// Determine sort order.
			switch ($order_method)
			{
				case 'date' :
					$orderby = $queryDate;
					break;
				case 'rdate' :
					$orderby = $queryDate . ' DESC ';
					break;
				case 'alpha' :
					$orderby = 'a.title';
					break;
				case 'ralpha' :
					$orderby = 'a.title DESC';
					break;
				case 'hits' :
					$orderby = 'a.hits';
					break;
				case 'rhits' :
					$orderby = 'a.hits DESC';
					break;
				case 'order' :
					$orderby = 'etm.ordering';
					break;
				case 'author' :
					$orderby = 'a.created_by_alias, u.name';
					break;
				case 'rauthor' :
					$orderby = 'a.created_by_alias DESC, u.name DESC';
					break;
				case 'front' :
					$orderby = 'f.ordering';
					break;
				default :
					$orderby = 'etm.ordering';
					break;
			}

			$xwhere = ' AND (a.state = 1 OR a.state = -1)'
				. ' AND (publish_up = ' . $db->quote($nullDate) . ' OR publish_up <= ' . $db->quote($now) . ')'
				. ' AND (publish_down = ' . $db->quote($nullDate) . ' OR publish_down >= ' . $db->quote($now) . ')';

			// Array of articles in same category correctly ordered.
			$query = $db->getQuery(true);

			// Sqlsrv changes
			$case_when = ' CASE WHEN ' . $query->charLength('a.alias', '!=', '0');
			$a_id = $query->castAsChar('e.id');
			$case_when .= ' THEN ' . $query->concatenate(array($a_id, 'a.alias'), ':');
			$case_when .= ' ELSE ' . $a_id . ' END as slug';

			$case_when1 = ' CASE WHEN ' . $query->charLength('cc.alias', '!=', '0');
			$c_id = $query->castAsChar('cc.id');
			$case_when1 .= ' THEN ' . $query->concatenate(array($c_id, 'cc.alias'), ':');
			$case_when1 .= ' ELSE ' . $c_id . ' END as catslug';
			$query->select('itm.content_item_id, itm.tag_id AS tagid, e.id, a.title, a.catid, a.language,' . $case_when . ',' . $case_when1)
			      ->from('#__abtag_entry AS e')
			      ->join('LEFT','#__content AS a ON a.id=e.article_id')
			      ->join('LEFT','#__abtag_entry_tag_map AS etm ON etm.entry_id=e.id  AND etm.tag_id='.(int)$row->tag_id)
			      //Get only entries tagged with the same tag.
			      ->join('LEFT', '#__contentitem_tag_map AS itm ON itm.tag_id='.(int)$row->tag_id)
			      ->join('LEFT', '#__categories AS cc ON cc.id = a.catid')
			      ->where('itm.type_alias="com_abtag.entry" AND e.id=itm.content_item_id AND a.state = ' . (int) $row->state
					      . ($canPublish ? '' : ' AND a.access IN (' . implode(',', JAccess::getAuthorisedViewLevels($user->id)) . ') ') . $xwhere
				);
			$query->group('itm.content_item_id');
			$query->order($orderby);

			if ($app->isClient('site') && $app->getLanguageFilter())
			{
				$query->where('a.language in (' . $db->quote($lang->getTag()) . ',' . $db->quote('*') . ')');
			}

			$db->setQuery($query);
			$list = $db->loadObjectList('id');

			// This check needed if incorrect Itemid is given resulting in an incorrect result.
			if (!is_array($list))
			{
				$list = array();
			}

			reset($list);

			// Location of current content item in array list.
			$location = array_search($uid, array_keys($list));
			$rows     = array_values($list);

			$row->prev = null;
			$row->next = null;

			if ($location - 1 >= 0)
			{
				// The previous content item cannot be in the array position -1.
				$row->prev = $rows[$location - 1];
			}

			if (($location + 1) < count($rows))
			{
				// The next content item cannot be in an array position greater than the number of array postions.
				$row->next = $rows[$location + 1];
			}

			if ($row->prev)
			{
				$row->prev_label = ($this->params->get('display', 0) == 0) ? JText::_('JPREV') : $row->prev->title;
				$row->prev = JRoute::_(AbtagHelperRoute::getEntryRoute($row->prev->slug, array($row->prev->tagid), $row->prev->language));
			}
			else
			{
				$row->prev_label = '';
				$row->prev = '';
			}

			if ($row->next)
			{
				$row->next_label = ($this->params->get('display', 0) == 0) ? JText::_('JNEXT') : $row->next->title;
				$row->next = JRoute::_(AbtagHelperRoute::getEntryRoute($row->next->slug, array($row->next->tagid), $row->next->language));
			}
			else
			{
				$row->next_label = '';
				$row->next = '';
			}

			// Output.
			if ($row->prev || $row->next)
			{
				// Get the path for the layout file
				$path = JPluginHelper::getLayoutPath('content', 'pagenavigation');

				// Render the pagenav
				ob_start();
				include $path;
				$row->pagination = ob_get_clean();

				$row->paginationposition = $this->params->get('position', 1);

				// This will default to the 1.5 and 1.6-1.7 behavior.
				$row->paginationrelative = $this->params->get('relative', 0);
			}
		}

		//ABTag note: Check for vote displaying.
		if ($this->params->get('position', 'top') !== 'top')
		{
			return '';
		}

		return $this->displayVotingData($context, $row, $params, $page);
  }


	/**
	 * Displays the voting area when viewing an article and the voting section is displayed after the article
	 *
	 * @param   string   $context  The context of the content being passed to the plugin
	 * @param   object   &$row     The article object
	 * @param   object   &$params  The article params
	 * @param   integer  $page     The 'page' number
	 *
	 * @return  string|boolean  HTML string containing code for the votes if in com_content else boolean false
	 *
	 * @since   3.7.0
	 */
	public function onContentAfterDisplay($context, &$row, &$params, $page = 0)
	{
		if ($this->params->get('position', 'top') !== 'bottom')
		{
			return '';
		}

		return $this->displayVotingData($context, $row, $params, $page);
	}


	/**
	 * Translate an order code to a field for primary ordering.
	 *
	 * @param   string  $orderDate  The ordering code.
	 *
	 * @return  string  The SQL field(s) to order by.
	 *
	 * @since   3.3
	 */
	private static function getQueryDate($orderDate)
	{
		$db = JFactory::getDbo();

		switch ($orderDate)
		{
			// Use created if modified is not set
			case 'modified' :
				$queryDate = ' CASE WHEN a.modified = ' . $db->quote($db->getNullDate()) . ' THEN a.created ELSE a.modified END';
				break;

			// Use created if publish_up is not set
			case 'published' :
				$queryDate = ' CASE WHEN a.publish_up = ' . $db->quote($db->getNullDate()) . ' THEN a.created ELSE a.publish_up END ';
				break;

			// Use created as default
			case 'created' :
			default :
				$queryDate = ' a.created ';
				break;
		}

		return $queryDate;
	}


	/**
	 * Displays the voting area
	 *
	 * @param   string   $context  The context of the content being passed to the plugin
	 * @param   object   &$row     The article object
	 * @param   object   &$params  The article params
	 * @param   integer  $page     The 'page' number
	 *
	 * @return  string|boolean  HTML string containing code for the votes if in com_content else boolean false
	 *
	 * @since   3.7.0
	 */
	private function displayVotingData($context, &$row, &$params, $page)
	{
		$parts = explode('.', $context);

		if ($parts[0] !== 'com_abtag')
		{
			return false;
		}

		if (empty($params) || !$params->get('show_vote', null))
		{
			return '';
		}

		// Load plugin language files only when needed (ex: they are not needed if show_vote is not active).
		$this->loadLanguage();

		//ABTag note: The rating layout rendering has been removed as it is already
		//displayed via the event triggered as com_content.article in the view.html.php file

		if (JFactory::getApplication('site')->input->getString('view', '') === 'entry' && $row->state == 1)
		{
			// Get the path for the voting form layout file
			$path = JPluginHelper::getLayoutPath('content', 'abtagplugins', 'vote');

			// Render the layout
			ob_start();
			include $path;
			$html = ob_get_clean();
		}

		return $html;
	}
}


<?php
/**
 * @package ABTag
 * @copyright Copyright (c) 2017 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

/**
 * This models supports retrieving a category, the articles associated with the category,
 * sibling, child and parent categories.
 *
 * @since  1.5
 */
class AbtagModelTag extends JModelList
{
	/**
	 * Category items data
	 *
	 * @var array
	 */
	protected $_item = null;

	protected $_entries = null;

	protected $_siblings = null;

	protected $_children = null;

	protected $_parent = null;

	/**
	 * Model context string.
	 *
	 * @var		string
	 */
	protected $_context = 'com_abtag.tag';

	/**
	 * The category that applies.
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $_category = null;

	/**
	 * The list of other newfeed categories.
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $_categories = null;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since   1.6
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'e.id', 'e.article_id',
				'title', 'a.title',
				'alias', 'a.alias',
				'checked_out', 'a.checked_out',
				'checked_out_time', 'a.checked_out_time',
				'catid', 'a.catid', 'category_title',
				'state', 'a.state',
				'access', 'a.access', 'access_level',
				'created', 'a.created',
				'created_by', 'a.created_by',
				'modified', 'a.modified',
				'ordering', 'tm.ordering',
				'featured', 'a.featured',
				'language', 'a.language',
				'hits', 'a.hits',
				'publish_up', 'a.publish_up',
				'publish_down', 'a.publish_down',
				'author', 'a.author'
			);
		}

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   The field to order on.
	 * @param   string  $direction  The direction to order on.
	 *
	 * @return  void.
	 *
	 * @since   1.6
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		$app = JFactory::getApplication('site');
		$pk  = $app->input->getInt('id');

		$this->setState('tag.id', $pk);

		// Load the parameters. Merge Global and Menu Item params into new object
		$params = $app->getParams();
		$menuParams = new Registry;

		if ($menu = $app->getMenu()->getActive())
		{
			$menuParams->loadString($menu->params);
		}

		$mergedParams = clone $menuParams;
		$mergedParams->merge($params);

		$this->setState('params', $mergedParams);
		$user  = JFactory::getUser();

		$asset = 'com_content';

		if ($pk)
		{
			$asset .= '.category.' . $pk;
		}

		if ((!$user->authorise('core.edit.state', $asset)) &&  (!$user->authorise('core.edit', $asset)))
		{
			// Limit to published for people who can't edit or edit.state.
			$this->setState('filter.published', 1);
		}
		else
		{
			$this->setState('filter.published', array(0, 1, 2));
		}

		// Process show_noauth parameter
		if (!$params->get('show_noauth'))
		{
			$this->setState('filter.access', true);
		}
		else
		{
			$this->setState('filter.access', false);
		}

		$itemid = $app->input->get('id', 0, 'int') . ':' . $app->input->get('Itemid', 0, 'int');

		// Optional filter text
		$search = $app->getUserStateFromRequest('com_content.category.list.' . $itemid . '.filter-search', 'filter-search', '', 'string');
		$this->setState('list.filter', $search);

		// Filter.order
		$orderCol = $app->getUserStateFromRequest('com_content.category.list.' . $itemid . '.filter_order', 'filter_order', '', 'string');

		if (!in_array($orderCol, $this->filter_fields))
		{
			$orderCol = 'tm.ordering';
		}

		$this->setState('list.ordering', $orderCol);

		$listOrder = $app->getUserStateFromRequest('com_content.category.list.' . $itemid . '.filter_order_Dir', 'filter_order_Dir', '', 'cmd');

		if (!in_array(strtoupper($listOrder), array('ASC', 'DESC', '')))
		{
			$listOrder = 'ASC';
		}

		$this->setState('list.direction', $listOrder);

		$this->setState('list.start', $app->input->get('limitstart', 0, 'uint'));

		// Set limit for query. If list, use parameter. If blog, add blog parameters for limit.
		if (($app->input->get('layout') === 'blog') || $params->get('layout_type') === 'blog')
		{
			$limit = $params->get('num_leading_entries') + $params->get('num_intro_entries') + $params->get('num_links');
			$this->setState('list.links', $params->get('num_links'));
		}
		else
		{
			$limit = $app->getUserStateFromRequest('com_abtag.tag.list.' . $itemid . '.limit', 'limit', $params->get('display_num'), 'uint');
		}

		$this->setState('list.limit', $limit);

		// Set the depth of the category query based on parameter
		$showSubTags = $params->get('show_subtag_content', '0');

		if ($showSubTags)
		{
			$this->setState('filter.max_tag_levels', $params->get('show_subtag_content', '1'));
			$this->setState('filter.subtags', true);
		}

		$this->setState('filter.language', JLanguageMultilang::isEnabled());

		$this->setState('layout', $app->input->getString('layout'));

		// Set the featured articles state
		$this->setState('filter.featured', $params->get('show_featured'));
	}

	/**
	 * Get the articles in the category
	 *
	 * @return  mixed  An array of articles or false if an error occurs.
	 *
	 * @since   1.5
	 */
	public function getItems()
	{
		$limit = $this->getState('list.limit');

		if ($this->_entries === null && $tag = $this->getTag())
		{
			$model = JModelLegacy::getInstance('Entries', 'AbtagModel', array('ignore_request' => true));
			$model->setState('params', JFactory::getApplication()->getParams());
			$model->setState('filter.tag_id', $tag->id);
			$model->setState('filter.published', $this->getState('filter.published'));
			$model->setState('filter.access', $this->getState('filter.access'));
			$model->setState('filter.language', $this->getState('filter.language'));
			$model->setState('filter.featured', $this->getState('filter.featured'));
			$model->setState('list.ordering', $this->_buildContentOrderBy());
			$model->setState('list.start', $this->getState('list.start'));
			$model->setState('list.limit', $limit);
			$model->setState('list.direction', $this->getState('list.direction'));
			$model->setState('list.filter', $this->getState('list.filter'));

			// Filter.subtags indicates whether to include entries from subtags in the list or blog
			$model->setState('filter.subtags', $this->getState('filter.subtags'));
			$model->setState('filter.max_tag_levels', $this->getState('filter.max_tag_levels'));
			$model->setState('list.links', $this->getState('list.links'));

			if ($limit >= 0)
			{
				$this->_entries = $model->getItems();

				if ($this->_entries === false)
				{
					$this->setError($model->getError());
				}
			}
			else
			{
				$this->_entries = array();
			}

			$this->_pagination = $model->getPagination();
		}

		return $this->_entries;
	}

	/**
	 * Build the orderby for the query
	 *
	 * @return  string	$orderby portion of query
	 *
	 * @since   1.5
	 */
	protected function _buildContentOrderBy()
	{
		$app       = JFactory::getApplication('site');
		$db        = $this->getDbo();
		$params    = $this->state->params;
		$itemid    = $app->input->get('id', 0, 'int') . ':' . $app->input->get('Itemid', 0, 'int');
		$orderCol  = $app->getUserStateFromRequest('com_content.category.list.' . $itemid . '.filter_order', 'filter_order', '', 'string');
		$orderDirn = $app->getUserStateFromRequest('com_content.category.list.' . $itemid . '.filter_order_Dir', 'filter_order_Dir', '', 'cmd');
		$orderby   = ' ';

		if (!in_array($orderCol, $this->filter_fields))
		{
			$orderCol = null;
		}

		if (!in_array(strtoupper($orderDirn), array('ASC', 'DESC', '')))
		{
			$orderDirn = 'ASC';
		}

		if ($orderCol && $orderDirn)
		{
			$orderby .= $db->escape($orderCol) . ' ' . $db->escape($orderDirn) . ', ';
		}

		$articleOrderby   = $params->get('orderby_sec', 'rdate');
		$articleOrderDate = $params->get('order_date');
		$secondary        = AbtagHelperQuery::orderbySecondary($articleOrderby, $articleOrderDate) . ', ';

		$orderby .= $secondary . ' a.created ';

		return $orderby;
	}

	/**
	 * Method to get a JPagination object for the data set.
	 *
	 * @return  JPagination  A JPagination object for the data set.
	 *
	 * @since   12.2
	 */
	public function getPagination()
	{
		if (empty($this->_pagination))
		{
			return null;
		}

		return $this->_pagination;
	}


	//Get the current tag.
	public function getTag()
	{
	  $tagId = $this->getState('tag.id');

	  $db = $this->getDbo();
	  $query = $db->getQuery(true);
	  $query->select('*')
		->from('#__tags')
		->where('id='.(int)$tagId);
	  $db->setQuery($query);
	  $tag = $db->loadObject();

	  $this->setState('tag.level', $tag->level);

	  $images = new JRegistry;
	  $images->loadString($tag->images);
	  $tag->images = $images;

	  return $tag;
	}


	/**
	 * Get the child tags.
	 *
	 * @return  mixed  An array of tags or false if an error occurs.
	 *
	 */
	public function getChildren($onlyIds = false)
	{
	  $tagId = $this->getState('tag.id');
	  $user = JFactory::getUser();
	  $groups = implode(',', $user->getAuthorisedViewLevels());

	  //Add one to the start level as we don't want the current tag in the result.
	  $startLevel = $this->getState('tag.level', 1) + 1;
	  $endLevel = $this->getState('params')->get('tag_max_level', 0);

	  if($onlyIds) {
	    $endLevel = $this->getState('filter.max_tag_levels', 0);
	  }

	  if($endLevel > 0) { //Compute the end level from the start level.
	    $endLevel = $startLevel + $endLevel;
	  }
	  //Display all the subtags.
	  elseif($endLevel == -1) { 
	    $endLevel = 10;
	  }

	  //Ensure subcats are required.
	  if($endLevel) {
	    //Get the tag order type.
	    $tagOrderBy = $this->getState('params')->get('orderby_pri');
	    $orderBy = AbtagHelperQuery::orderbyPrimary($tagOrderBy);

	    $select = 'n.*';
	    if($onlyIds) {
	      $select = 'n.id';
	    }

	    $db = $this->getDbo();
	    $query = $db->getQuery(true);
	    $query->select($select)
		  ->from('#__tags AS n, #__tags AS p')
		  ->where('n.lft BETWEEN p.lft AND p.rgt')
		  ->where('n.level >= '.(int)$startLevel.' AND n.level <= '.(int)$endLevel)
		  ->where('n.access IN('.$groups.')')
		  ->where('n.published=1')
		  ->where('p.id='.(int)$tagId);

	    if(!empty($orderBy)) {
	      //Replace the c prefix used whith the query to get entries.
	      $orderBy = preg_replace('#^t#', 'n', $orderBy);
	      //Remove the comma and space from the string.
	      $orderBy = substr($orderBy, 0, -2);
	      $query->order($orderBy);
	    }
	    else {
	      $query->order('n.lft');
	    }

	    $db->setQuery($query);

	    if($onlyIds) {
	      return $db->loadColumn();
	    }

	    $children = $db->loadObjectList();

	    if(empty($children)) {
	      return $children;
	    }

	    if($this->getState('params')->get('show_tagged_num_entries', 0)) {
	      //Get the tag children ids.
	      $ids = array();
	      foreach($children as $child) {
		$ids[] = $child->id;
	      }

	      //Compute the number of entries for each tag.
	      $query->clear()
		    ->select('tm.tag_id, COUNT(*) AS numitems')
		    ->from('#__abtag_entry_tag_map AS tm')
		    ->join('LEFT', '#__abtag_entry AS e ON e.id=tm.entry_id')
		    ->join('LEFT', '#__content AS a ON a.id=e.article_id')
		    ->join('LEFT', '#__categories AS ca ON ca.id=a.catid')
		    ->where('a.access IN('.$groups.')')
		    ->where('ca.access IN('.$groups.')');

	      // Filter by state
	      $published = $this->getState('filter.published');
	      if(is_numeric($published)) {
		//Only published entries are counted when user is not Root.
		$query->where('a.state='.(int)$published);
	      }
	      elseif(is_array($published)) {
		//Entries with different states are also taken in account for super users.
		JArrayHelper::toInteger($published);
		$published = implode(',', $published);
		$query->where('a.state IN ('.$published.')');
	      }

	      //Do not count expired entries when user is not Root.
	      if($this->getState('filter.publish_date')) {
		// Filter by start and end dates.
		$nullDate = $db->quote($db->getNullDate());
		$nowDate = $db->quote(JFactory::getDate('now', JFactory::getConfig()->get('offset'))->toSql(true));

		$query->where('(a.publish_up = '.$nullDate.' OR a.publish_up <= '.$nowDate.')')
		      ->where('(a.publish_down = '.$nullDate.' OR a.publish_down >= '.$nowDate.')');
	      }

	      $query->where('tm.tag_id IN('.implode(',', $ids).') GROUP BY tm.tag_id');
	      $db->setQuery($query);
	      $tags = $db->loadObjectList('tag_id');

	      //Set the numitems attribute.
	      foreach($children as $child) {
		$child->numitems = 0;

		if(isset($tags[$child->id])) {
		  $child->numitems = $tags[$child->id]->numitems;
		}
	      }
	    }

	    return $children;
	  }

	  return array();
	}


	/**
	 * Increment the hit counter for the tag.
	 *
	 * @param   int  $pk  Optional primary key of the tag to increment.
	 *
	 * @return  boolean True if successful; false otherwise and internal error set.
	 *
	 * @since   3.2
	 */
	public function hit($pk = 0)
	{
	  $input = JFactory::getApplication()->input;
	  $hitcount = $input->getInt('hitcount', 1);

	  if($hitcount) {
	    $pk = (!empty($pk)) ? $pk : (int) $this->getState('tag.id');

	    $table = JTable::getInstance('Tag', 'JTable');
	    $table->load($pk);
	    $table->hit($pk);
	  }

	  return true;
	}
}

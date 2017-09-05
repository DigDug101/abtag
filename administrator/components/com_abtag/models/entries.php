<?php
/**
 * @package ABTag
 * @copyright Copyright (c) 2017 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.

jimport('joomla.application.component.modellist');



class AbtagModelEntries extends JModelList
{
  public function __construct($config = array())
  {
    if(empty($config['filter_fields'])) {
      $config['filter_fields'] = array('id', 'e.id', 'e.article_id',
				       'title', 'co.title', 
				       'alias', 'co.alias',
				       'created', 'co.created', 
				       'created_by', 'co.created_by',
				       'state', 'co.state', 
			               'access', 'co.access', 'access_level',
				       'user', 'user_id',
				       'ordering','co.ordering', 'tm.ordering', 'tm_ordering',
				       'featured', 'co.featured',
				       'language', 'co.language',
				       'hits', 'co.hits',
				       'catid', 'co.catid', 'category_id',
				       'tag'
				      );
    }

    parent::__construct($config);
  }


  protected function populateState($ordering = null, $direction = null)
  {
    // Initialise variables.
    $app = JFactory::getApplication();
    $session = JFactory::getSession();

    // Adjust the context to support modal layouts.
    if($layout = JFactory::getApplication()->input->get('layout')) {
      $this->context .= '.'.$layout;
    }

    //Get the state values set by the user.
    $search = $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
    $this->setState('filter.search', $search);

    $access = $this->getUserStateFromRequest($this->context.'.filter.access', 'filter_access');
    $this->setState('filter.access', $access);

    $userId = $app->getUserStateFromRequest($this->context.'.filter.user_id', 'filter_user_id');
    $this->setState('filter.user_id', $userId);

    $published = $this->getUserStateFromRequest($this->context.'.filter.published', 'filter_published', '');
    $this->setState('filter.published', $published);

    $categoryId = $this->getUserStateFromRequest($this->context.'.filter.category_id', 'filter_category_id');
    $this->setState('filter.category_id', $categoryId);

    $language = $this->getUserStateFromRequest($this->context . '.filter.language', 'filter_language');
    $this->setState('filter.language', $language);

    $tag = $this->getUserStateFromRequest($this->context . '.filter.tag', 'filter_tag');
    $this->setState('filter.tag', $tag);

    // List state information.
    parent::populateState('co.title', 'asc');

    // Force a language
    $forcedLanguage = $app->input->get('forcedLanguage');

    if(!empty($forcedLanguage)) {
      $this->setState('filter.language', $forcedLanguage);
      $this->setState('filter.forcedLanguage', $forcedLanguage);
    }
  }


  protected function getStoreId($id = '')
  {
    // Compile the store id.
    $id .= ':'.$this->getState('filter.search');
    $id .= ':'.$this->getState('filter.access');
    $id .= ':'.$this->getState('filter.published');
    $id .= ':'.$this->getState('filter.user_id');
    $id .= ':'.$this->getState('filter.category_id');
    $id .= ':'.$this->getState('filter.language');

    return parent::getStoreId($id);
  }


  protected function getListQuery()
  {
    //Create a new JDatabaseQuery object.
    $db = $this->getDbo();
    $query = $db->getQuery(true);
    $user = JFactory::getUser();

    // Select the required fields from the table.
    $query->select($this->getState('list.select', 'e.id,e.article_id,co.title,co.alias,co.state,co.catid,co.hits,co.access,'.
				   'co.featured,co.created,co.ordering,co.created_by,e.checked_out,e.checked_out_time,co.language'))
	  ->from('#__abtag_entry AS e')
	  // Join over the content articles.
	  ->join('LEFT', '#__content AS co ON co.id=e.article_id');

    //Get the user name.
    $query->select('us.name AS user')
	  ->join('LEFT', '#__users AS us ON us.id = co.created_by');

    // Join over the users for the checked out user.
    $query->select('uc.name AS editor')
	  ->join('LEFT', '#__users AS uc ON uc.id=e.checked_out');

    // Join over the categories.
    $query->select('ca.title AS category_title')
	  ->join('LEFT', '#__categories AS ca ON ca.id = co.catid');

    // Join over the language
    $query->select('lg.title AS language_title')
	  ->join('LEFT', $db->quoteName('#__languages').' AS lg ON lg.lang_code = co.language');

    // Join over the asset groups.
    $query->select('al.title AS access_level')
	  ->join('LEFT', '#__viewlevels AS al ON al.id = co.access');

    //Filter by component category.
    $categoryId = $this->getState('filter.category_id');
    if(is_numeric($categoryId)) {
      $query->where('co.catid = '.(int)$categoryId);
    }
    elseif(is_array($categoryId)) {
      JArrayHelper::toInteger($categoryId);
      $categoryId = implode(',', $categoryId);
      $query->where('co.catid IN ('.$categoryId.')');
    }

    //Filter by title search.
    $search = $this->getState('filter.search');
    if(!empty($search)) {
      if(stripos($search, 'id:') === 0) {
	$query->where('e.id = '.(int) substr($search, 3));
      }
      else {
	$search = $db->Quote('%'.$db->escape($search, true).'%');
	$query->where('(co.title LIKE '.$search.')');
      }
    }

    // Filter by access level.
    if($access = $this->getState('filter.access')) {
      $query->where('co.access='.(int) $access);
    }

    // Filter by access level on categories.
    if(!$user->authorise('core.admin')) {
      $groups = implode(',', $user->getAuthorisedViewLevels());
      $query->where('co.access IN ('.$groups.')');
      $query->where('ca.access IN ('.$groups.')');
    }

    //Filter by publication state.
    $published = $this->getState('filter.published');
    if(is_numeric($published)) {
      $query->where('co.state='.(int)$published);
    }
    elseif($published === '') {
      $query->where('(co.state IN (0, 1))');
    }

    //Filter by user.
    $userId = $this->getState('filter.user_id');
    if(is_numeric($userId)) {
      $type = $this->getState('filter.user_id.include', true) ? '= ' : '<>';
      $query->where('co.created_by'.$type.(int) $userId);
    }

    //Filter by language.
    if($language = $this->getState('filter.language')) {
      $query->where('co.language = '.$db->quote($language));
    }

    // Filter by a single tag.
    $tagId = $this->getState('filter.tag');

    if(is_numeric($tagId)) {
      $query->where($db->quoteName('tagmap.tag_id').' = '.(int)$tagId)
	    ->join('LEFT', $db->quoteName('#__contentitem_tag_map', 'tagmap').
		   ' ON '.$db->quoteName('tagmap.content_item_id').' = '.$db->quoteName('e.id').
		   ' AND '.$db->quoteName('tagmap.type_alias').' = '.$db->quote('com_abtag.entry'));
    }

    //Add the list to the sort.
    $orderCol = $this->state->get('list.ordering', 'co.title');
    $orderDirn = $this->state->get('list.direction'); //asc or desc

    //In case only the tag filter is selected we want the entries to be displayed according
    //to the mapping table ordering.
    if(is_numeric($tagId) && AbtagHelper::checkSelectedFilter('tag', true) && $orderCol == 'co.ordering') {
      //Join over the entry/tag mapping table.
      $query->select('ISNULL(tm.ordering), tm.ordering AS tm_ordering')
	    ->join('LEFT', '#__abtag_entry_tag_map AS tm ON e.id=tm.entry_id AND tm.tag_id='.(int)$tagId);

      //Switch to the mapping table ordering.
      //Note: Entries with NULL ordering are placed at the end of the list.
      $orderCol = 'ISNULL(tm.ordering) ASC, tm_ordering';
    }

    $query->order($db->escape($orderCol.' '.$orderDirn));

    return $query;
  }
}



<?php
/**
 * @package ABTag
 * @copyright Copyright (c) 2017 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die; //No direct access to this file.
require_once JPATH_ADMINISTRATOR.'/components/com_abtag/helpers/abtag.php';

use Joomla\Registry\Registry;


class AbtagModelEntry extends JModelItem
{

  protected $_context = 'com_abtag.entry';

  /**
   * Method to auto-populate the model state.
   *
   * Note. Calling getState in this method will result in recursion.
   *
   * @since   1.6
   *
   * @return void
   */
  protected function populateState()
  {
    $app = JFactory::getApplication('site');

    // Load state from the request.
    $pk = $app->input->getInt('id');
    $this->setState('entry.id', $pk);

    //Load the global parameters of the component.
    $params = $app->getParams();
    $this->setState('params', $params);

    $this->setState('filter.language', JLanguageMultilang::isEnabled());
  }


  //Returns a Table object, always creating it.
  public function getTable($type = 'Entry', $prefix = 'AbtagTable', $config = array()) 
  {
    return JTable::getInstance($type, $prefix, $config);
  }


  /**
   * Method to get a single record.
   *
   * @param   integer  $pk  The id of the primary key.
   *
   * @return  mixed    Object on success, false on failure.
   *
   * @since   12.2
   */
  public function getItem($pk = null)
  {
    $pk = (!empty($pk)) ? $pk : (int)$this->getState('entry.id');
    $user = JFactory::getUser();

    if($this->_item === null) {
      $this->_item = array();
    }

    if(!isset($this->_item[$pk])) {
      $db = JFactory::getDbo();
      $query = $db->getQuery(true);
      $query->select($this->getState('list.select', 'e.id AS entry_id,e.article_id,e.main_tag_id,a.*, a.state AS published'))
	    ->from($db->quoteName('#__abtag_entry').' AS e')
	    ->join('INNER', $db->quoteName('#__content').' AS a ON a.id=e.article_id')
	    ->where('e.id='.$pk);

      // Join on category table.
      $query->select('ca.title AS category_title, ca.alias AS category_alias, ca.access AS category_access')
	    ->join('LEFT', '#__categories AS ca on ca.id = a.catid');

      // Join on user table.
      $query->select('us.name AS author')
	    ->join('LEFT', '#__users AS us on us.id = a.created_by');

      // Join over the categories to get parent category titles
      $query->select('parent.title as parent_title, parent.id as parent_id, parent.path as parent_route, parent.alias as parent_alias')
	    ->join('LEFT', '#__categories as parent ON parent.id = ca.parent_id');

      // Join on voting table
      $query->select('ROUND(v.rating_sum / v.rating_count, 0) AS rating, v.rating_count as rating_count')
	      ->join('LEFT', '#__content_rating AS v ON a.id = v.content_id');

      // Filter by language
      if($this->getState('filter.language')) {
	$query->where('a.language in ('.$db->quote(JFactory::getLanguage()->getTag()).','.$db->quote('*').')');
      }

      if((!$user->authorise('core.edit.state', 'com_content')) && (!$user->authorise('core.edit', 'com_content'))) {
	// Filter by start and end dates.
	$nullDate = $db->quote($db->getNullDate());
	$nowDate = $db->quote(JFactory::getDate('now', JFactory::getConfig()->get('offset'))->toSql(true));
	$query->where('(a.publish_up = '.$nullDate.' OR a.publish_up <= '.$nowDate.')')
	      ->where('(a.publish_down = '.$nullDate.' OR a.publish_down >= '.$nowDate.')');
      }

      $db->setQuery($query);
      $data = $db->loadObject();

      if(is_null($data)) {
	JFactory::getApplication()->enqueueMessage(JText::_('COM_ABTAG_ERROR_ENTRY_NOT_FOUND'), 'error');
	return false;
      }

      // Convert parameter fields to objects.
      $registry = new JRegistry;
      $registry->loadString($data->attribs);

      $data->params = clone $this->getState('params');
      $data->params->merge($registry);

      $data->metadata = new Registry($data->metadata);

      $user = JFactory::getUser();
      // Technically guest could edit an article, but lets not check that to improve performance a little.
      if(!$user->get('guest')) {
	$userId = $user->get('id');
	$asset = 'com_content.article.'.$data->article_id;

	// Check general edit permission first.
	if($user->authorise('core.edit', $asset)) {
	  $data->params->set('access-edit', true);
	}

	// Now check if edit.own is available.
	elseif(!empty($userId) && $user->authorise('core.edit.own', $asset)) {
	  // Check for a valid user and that they are the owner.
	  if($userId == $data->created_by) {
	    $data->params->set('access-edit', true);
	  }
	}
      }

      // Compute view access permissions.
      if($access = $this->getState('filter.access')) {
	// If the access filter has been set, we already know this user can view.
	$data->params->set('access-view', true);
      }
      else {
	// If no access filter is set, the layout takes some responsibility for display of limited information.
	$user = JFactory::getUser();
	$groups = $user->getAuthorisedViewLevels();

	if($data->catid == 0 || $data->category_access === null) {
	  $data->params->set('access-view', in_array($data->access, $groups));
	}
	else {
	  $data->params->set('access-view', in_array($data->access, $groups) && in_array($data->category_access, $groups));
	}
      }

      //Get the current tag id.
      if($tagId = JFactory::getApplication('site')->input->getInt('tagid', 0)) {
	$data->tag_id = $tagId;
      }

      // Get the tags
      $data->tags = new JHelperTags;
      $data->tags->getItemTags('com_abtag.entry', $data->entry_id);


      $this->_item[$pk] = $data;
    }

    return $this->_item[$pk];
  }


  /**
   * Increment the hit counter for the article.
   *
   * @param   integer  $pk  Optional primary key of the entry.
   *
   * @return  boolean  True if successful; false otherwise and internal error set.
   */
  public function hit($pk = 0)
  {
    $input = JFactory::getApplication()->input;
    $hitcount = $input->getInt('hitcount', 1);

    if($hitcount) {
      $pk = (!empty($pk)) ? $pk : (int) $this->getState('entry.id');

      //Here we need the article id, not the entry id.
      $pk = AbtagHelper::getArticleId($pk);

      $table = JTable::getInstance('Content', 'JTable');
      $table->load($pk);
      $table->hit($pk);
    }

    return true;
  }
}


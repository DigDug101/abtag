<?php
/**
 * @package ABTag
 * @copyright Copyright (c) 2017 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.



class AbtagHelper
{
  //Create the tabs bar ($viewName = name of the active view).
  public static function addSubmenu($viewName)
  {
    JHtmlSidebar::addEntry(JText::_('COM_ABTAG_SUBMENU_ENTRIES'),
				      'index.php?option=com_abtag&view=entries', $viewName == 'entries');
  }


  //Get the list of the allowed actions for the user.
  public static function getActions($catIds = array())
  {
    $user = JFactory::getUser();
    $result = new JObject;

    $actions = array('core.admin', 'core.manage', 'core.create', 'core.edit',
		     'core.edit.own', 'core.edit.state', 'core.delete');

    //Get from the core the user's permission for each action.
    foreach($actions as $action) {
      //Check permissions against the component. 
      if(empty($catIds)) { 
	$result->set($action, $user->authorise($action, 'com_abtag'));
      }
      else {
	//Check permissions against the component categories.
	foreach($catIds as $catId) {
	  if($user->authorise($action, 'com_abtag.category.'.$catId)) {
	    $result->set($action, $user->authorise($action, 'com_abtag.category.'.$catId));
	    break;
	  }

	  $result->set($action, $user->authorise($action, 'com_abtag.category.'.$catId));
	}
      }
    }

    return $result;
  }

  //Build the user list for the filter.
  public static function getUsers($itemName)
  {
    // Create a new query object.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('u.id AS value, u.name AS text');
    $query->from('#__users AS u');
    //Get only the names of users who have created items, this avoids to
    //display all of the users in the drop down list.
    $query->join('INNER', '#__abtag_'.$itemName.' AS i ON i.created_by = u.id');
    $query->group('u.id');
    $query->order('u.name');

    // Setup the query
    $db->setQuery($query);

    // Return the result
    return $db->loadObjectList();
  }


  public static function checkSelectedFilter($filterName, $unique = false)
  {
    $post = JFactory::getApplication()->input->post->getArray();

    //Ensure the given filter has been selected.
    if(isset($post['filter'][$filterName]) && !empty($post['filter'][$filterName])) {
      //Ensure that only the given filter has been selected.
      if($unique) {
	$filter = 0;
	foreach($post['filter'] as $value) {
	  if(!empty($value)) {
	    $filter++;
	  }
	}

	if($filter > 1) {
	  return false;
	}
      }

      return true;
    }

    return false;
  }


  public static function mappingTableOrder($pks, $tagId, $limitStart)
  {
    //Check first the user can edit state.
    $user = JFactory::getUser();
    if(!$user->authorise('core.edit.state', 'com_abtag')) {
      return false;
    }

    //Start ordering from 1 by default.
    $ordering = 1;

    //When pagination is used set ordering from limitstart value.
    if($limitStart) {
      $ordering = (int)$limitStart + 1;
    }

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //Update the ordering values of the mapping table. 
    foreach($pks as $pk) {
      $query->clear();
      $query->update('#__abtag_entry_tag_map')
	    //Update the item ordering via the mapping table.
	    ->set('ordering='.$ordering)
	    ->where('entry_id='.(int)$pk)
	    ->where('tag_id='.(int)$tagId);
      $db->setQuery($query);
      $db->query();

      $ordering++;
    }

    return true;
  }


  public static function getArticleId($entryId)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('article_id')
	  ->from('#__abtag_entry')
	  ->where('id='.(int)$entryId);
    $db->setQuery($query);

    return $db->loadResult();
  }


  public static function addEntry($entry)
  {
    JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_abtag/tables');
    $table = JTable::getInstance('Entry', 'AbtagTable', array());

    // Bind data
    if(!$table->bind($entry)) {
      $table->getError();
      return false;
    }

    // Check the data.
    if(!$table->check()) {
      $table->getError();
      return false;
    }

    // Store the data.
    if(!$table->store()) {
      $table->getError();
      return false;
    }
  }


  public static function deleteEntries($entryIds)
  {
    if(!is_array($entryIds)) {
      if(ctype_digit($entryIds)) {
	$entryIds = array($entryIds);
      }
      else {
	return false;
      }
    }

    $model = JModelLegacy::getInstance('Entry', 'AbtagModel');
    $model->delete($entryIds);
  }


  public static function synchronize()
  {
    //Get the Joomla articles already handled by the component.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('article_id')
	  ->from('#__abtag_entry')
	  ->where('1');
    $db->setQuery($query);
    $syncArtIds = $db->loadColumn();

    $query->clear();
    $query->select('id')
	  ->from('#__content');

    if(!empty($syncArtIds)) {
      //Get the Joomla articles not handled yet by the component.
      $query->where('id NOT IN('.implode(',', $syncArtIds).')');
    }
    else {
      //Get all the Joomla articles.
      $query->where('1');
    }

    $db->setQuery($query);
    $newArtIds = $db->loadColumn();

    if(!empty($newArtIds)) {
      $nbNewArt = count($newArtIds);

      //Add the new Joomla articles to the component.
      foreach($newArtIds as $newArtId) {
	$entry = array('article_id' => $newArtId,
		       'title' => 'ABTag article '.$newArtId);

	self::addEntry($entry);
	//
	$syncArtIds[] = $newArtId;
      }

      $singular = '_1';
      if($nbNewArt > 1) {
	$singular = '';
      }

      JFactory::getApplication()->enqueueMessage(JText::sprintf('COM_ABTAG_N_NEW_ENTRIES_SYNCHRONIZED'.$singular, $nbNewArt), 'message');
    }
    elseif(empty($newArtIds) && empty($syncArtIds)) {
      JFactory::getApplication()->enqueueMessage(JText::_('COM_ABTAG_NO_ARTICLE_FOUND'), 'message');
      return;
    }

    //Get all the Joomla article ids.
    $query->clear();
    $query->select('id')
	  ->from('#__content')
	  ->where('1');
    $db->setQuery($query);
    $articleIds = $db->loadColumn();

    //Search for possible outdated article ids handled by the component.
    $outdatedIds = array_diff($syncArtIds, $articleIds);

    if(!empty($outdatedIds)) {
      //Get the corresponding ABTag item ids of the outdated article ids.
      $query->clear();
      $query->select('id')
	    ->from('#__abtag_entry')
	    ->where('article_id IN('.implode(',', $outdatedIds).')');
      $db->setQuery($query);
      $entryIds = $db->loadColumn();

      self::deleteEntries($entryIds);

      $nbOutdatedIds = count($outdatedIds);
      $singular = '_1';
      if($nbOutdatedIds > 1) {
	$singular = '';
      }

      JFactory::getApplication()->enqueueMessage(JText::sprintf('COM_ABTAG_N_ENTRIES_DELETED'.$singular, $nbOutdatedIds), 'message');
    }

    if(empty($newArtIds) && empty($outdatedIds)) {
      JFactory::getApplication()->enqueueMessage(JText::_('COM_ABTAG_ENTRIES_UP_TO_DATE'), 'message');
    }
  }
}



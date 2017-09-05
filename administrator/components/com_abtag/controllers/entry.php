<?php
/**
 * @package ABTag
 * @copyright Copyright (c) 2017 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.
 
jimport('joomla.application.component.controllerform');
 


class AbtagControllerEntry extends JControllerForm
{
  protected function allowEdit($data = array(), $key = 'id')
  {
    $itemId = $data['id'];
    $user = JFactory::getUser();

    //Get the item owner id of the article.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('co.id, co.created_by')
	  ->from('#__abtag_entry AS e')
	  ->join('INNER', '#__content AS co ON co.id=e.article_id')
	  ->where('e.id='.(int)$itemId);
    $db->setQuery($query);
    $results = $db->loadAssoc();

    //Compares against the article permission (not the entry).
    $canEdit = $user->authorise('core.edit', 'com_content.article.'.(int)$results['id']);
    $canEditOwn = $user->authorise('core.edit.own', 'com_content') && $results['created_by'] == $user->id;

    //Allow edition. 
    if($canEdit || $canEditOwn) {
      return true;
    }

    return false;
  }
}


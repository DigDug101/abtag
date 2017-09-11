<?php
/**
 * @package ABTag
 * @copyright Copyright (c) 2017 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.
jimport('joomla.application.component.modeladmin');
require_once JPATH_ADMINISTRATOR.'/components/com_abtag/helpers/abtag.php';


class AbtagModelEntry extends JModelAdmin
{
  //Prefix used with the controller messages.
  protected $text_prefix = 'COM_ABTAG';

  //Returns a Table object, always creating it.
  //Table can be defined/overrided in tables/itemname.php file.
  public function getTable($type = 'Entry', $prefix = 'AbtagTable', $config = array()) 
  {
    return JTable::getInstance($type, $prefix, $config);
  }


  public function getForm($data = array(), $loadData = true) 
  {
    $form = $this->loadForm('com_abtag.entry', 'entry', array('control' => 'jform', 'load_data' => $loadData));

    if(empty($form)) {
      return false;
    }

    return $form;
  }


  protected function loadFormData() 
  {
    // Check the session for previously entered form data.
    $data = JFactory::getApplication()->getUserState('com_abtag.edit.entry.data', array());

    if(empty($data)) {
      $data = $this->getItem();
    }

    return $data;
  }


  /**
   * Method to get a single record.
   *
   * @param   integer  $pk  The id of the primary key.
   *
   * @return  mixed  Object on success, false on failure.
   */
  public function getItem($pk = null)
  {
    if($item = parent::getItem($pk)) {
      $db = $this->getDbo();
      $query = $db->getQuery(true);
      //Get the attributes of the corresponding article.
      $query->select('c.title,c.alias,c.state,c.access,c.catid,c.introtext AS articletext,c.hits,c.language,'.
		     'c.created AS article_created,c.modified AS article_modified,u.name AS author_name,um.name AS modifier_name')
	    ->from('#__content AS c')
	    ->join('LEFT', '#__users AS u ON u.id=c.created_by')
	    ->join('LEFT', '#__users AS um ON um.id=c.modified_by')
	    ->where('c.id='.(int)$item->article_id);
      $db->setQuery($query);
      $article = $db->loadAssoc();

      //Adds the article attributes to the entry item.
      foreach($article as $key => $value) {
	$item->$key = $value;
      }

      //Get tags for this item.
      if(!empty($item->id)) {
	$item->tags = new JHelperTags;
	$item->tags->getTagIds($item->id, 'com_abtag.entry');
      }
    }

    return $item;
  }


  /**
   * Saves the manually set order of records.
   *
   * @param   array    $pks    An array of primary key ids.
   * @param   integer  $order  +1 or -1
   *
   * @return  mixed
   *
   * @since   12.2
   */
  public function saveorder($pks = null, $order = null)
  {
    //First ensure only the tag filter has been selected.
    if(AbtagHelper::checkSelectedFilter('tag', true)) {

      if(empty($pks)) {
	return JError::raiseWarning(500, JText::_($this->text_prefix.'_ERROR_NO_ITEMS_SELECTED'));
      }

      //Get the id of the selected tag and the limitstart value.
      $post = JFactory::getApplication()->input->post->getArray();
      $tagId = $post['filter']['tag'];
      $limitStart = $post['limitstart'];

      //Set the mapping table ordering.
      AbtagHelper::mappingTableOrder($pks, $tagId, $limitStart);

      return true;
    }

    //Hand over to the parent function.
    return parent::saveorder($pks, $order);
  }
}


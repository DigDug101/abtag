<?php
/**
 * @package ABTag
 * @copyright Copyright (c) 2017 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined( '_JEXEC' ) or die; // No direct access
 
jimport( 'joomla.application.component.view');
require_once JPATH_COMPONENT.'/helpers/abtag.php';
 

class AbtagViewEntries extends JViewLegacy
{
  protected $items;
  protected $state;
  protected $pagination;

  //Display the view.
  public function display($tpl = null)
  {
    $this->items = $this->get('Items');
    $this->state = $this->get('State');
    $this->pagination = $this->get('Pagination');
    $this->filterForm = $this->get('FilterForm');
    $this->activeFilters = $this->get('ActiveFilters');

    //Check for errors.
    if(count($errors = $this->get('Errors'))) {
      JError::raiseError(500, implode('<br />', $errors));
      return false;
    }

    //Display the tool bar.
    $this->addToolBar();

    $this->setDocument();
    $this->sidebar = JHtmlSidebar::render();

    //Display the template.
    parent::display($tpl);
  }


  //Build the toolbar.
  protected function addToolBar() 
  {
    //Display the view title and the icon.
    JToolBarHelper::title(JText::_('COM_ABTAG_ENTRIES_TITLE'), 'stack');

    //Get the allowed actions list
    $canDo = AbtagHelper::getActions();
    $user = JFactory::getUser();

    if($canDo->get('core.edit') || $canDo->get('core.edit.own') || 
       (count($user->getAuthorisedCategories('com_abtag', 'core.edit'))) > 0 || 
       (count($user->getAuthorisedCategories('com_abtag', 'core.edit.own'))) > 0) {
      JToolBarHelper::editList('entry.edit', 'JTOOLBAR_EDIT');
    }

    JToolBarHelper::custom('entries.synchronize', 'loop.png', 'loop_f2.png','COM_ABTAG_ACTION_SYNCHRONIZE', false);

    //Check for delete permission.
    if($canDo->get('core.delete') || count($user->getAuthorisedCategories('com_abtag', 'core.delete'))) {
      JToolBarHelper::divider();
      JToolBarHelper::deleteList('', 'entries.delete', 'JTOOLBAR_DELETE');
    }

    if($canDo->get('core.admin')) {
      JToolBarHelper::divider();
      JToolBarHelper::preferences('com_abtag', 550);
    }
  }


  protected function setDocument() 
  {
    //Include css file (if needed).
    //$doc = JFactory::getDocument();
    //$doc->addStyleSheet(JURI::base().'components/com_abtag/abtag.css');
  }
}



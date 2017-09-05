<?php
/**
 * @package ABTag 
 * @copyright Copyright (c) 2017 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; // No direct access.

jimport('joomla.application.component.controller');


class AbtagController extends JControllerLegacy
{
  public function display($cachable = false, $urlparams = false) 
  {
    require_once JPATH_COMPONENT.'/helpers/abtag.php';

    //Display the submenu.
    AbtagHelper::addSubmenu(JRequest::getCmd('view', 'entries'));

    //Set the default view.
    JRequest::setVar('view', JRequest::getCmd('view', 'entries'));

    //Display the view.
    parent::display();
  }
}



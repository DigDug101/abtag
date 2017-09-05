<?php
/**
 * @package ABTag
 * @copyright Copyright (c) 2017 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.
 
jimport('joomla.application.component.controlleradmin');
require_once JPATH_COMPONENT_ADMINISTRATOR.'/helpers/abtag.php';
 

class AbtagControllerEntries extends JControllerAdmin
{
  /**
   * Proxy for getModel.
   * @since 1.6
  */
  public function getModel($name = 'Entry', $prefix = 'AbtagModel', $config = array('ignore_request' => true))
  {
    $model = parent::getModel($name, $prefix, $config);
    return $model;
  }


  public function synchronize()
  {
    $cid = $this->input->get('cid', array());
    AbtagHelper::synchronize();
    $this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view='.$this->view_list, false));

    return true;
  }
}




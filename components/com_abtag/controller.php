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
  /**
   * Constructor.
   *
   * @param   array  $config  An optional associative array of configuration settings.
   * Recognized key values include 'name', 'default_task', 'model_path', and
   * 'view_path' (this list is not meant to be comprehensive).
   *
   * @since   12.2
   */
  public function __construct($config = array())
  {
    $this->input = JFactory::getApplication()->input;

    //Entry frontpage Editor entry proxying:
    if($this->input->get('view') === 'entries' && $this->input->get('layout') === 'modal') {
      JHtml::_('stylesheet', 'system/adminlist.css', array(), true);
      $config['base_path'] = JPATH_COMPONENT_ADMINISTRATOR;
    }

    parent::__construct($config);
  }


  public function display($cachable = false, $urlparams = false) 
  {

    // Set the default view name and format from the Request.
    // Note we are using e_id to avoid collisions with the router and the return page.
    // Frontend is a bit messier than the backend.
    $id = $this->input->getInt('e_id');
    //Set the view, (tag by default).
    $vName = $this->input->getCmd('view', 'tag');
    $this->input->set('view', $vName);

    //Make sure the parameters passed in the input by the component are safe.
    $safeurlparams = array(
	    'catid' => 'INT',
	    'tagid' => 'INT',
	    'id' => 'INT',
	    'cid' => 'ARRAY',
	    'year' => 'INT',
	    'month' => 'INT',
	    'limit' => 'UINT',
	    'limitstart' => 'UINT',
	    'showall' => 'INT',
	    'return' => 'BASE64',
	    'filter' => 'STRING',
	    'filter_order' => 'CMD',
	    'filter_order_Dir' => 'CMD',
	    'filter-search' => 'STRING',
	    'print' => 'BOOLEAN',
	    'lang' => 'CMD',
	    'Itemid' => 'INT');

    if($vName === 'entry') {
      // Get/Create the model
      if($model = $this->getModel($vName)) {
	$model->hit();
      }
    }

    //Display the view.
    parent::display($cachable, $safeurlparams);
  }
}



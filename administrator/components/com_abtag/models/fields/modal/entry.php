<?php
/**
 * @package ABTag
 * @copyright Copyright (c) 2017 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('JPATH_BASE') or die;

/**
 * Supports a modal entry picker.
 *
 */
class JFormFieldModal_Entry extends JFormField
{
  /**
   * The form field type.
   *
   * @var		string
   * @since   1.6
   */
  protected $type = 'Modal_Entry';

  /**
   * Method to get the field input markup.
   *
   * @return  string	The field input markup.
   * @since   1.6
   */
  protected function getInput()
  {
    $allowEdit = ((string) $this->element['edit'] == 'true') ? true : false;
    $allowClear	= ((string) $this->element['clear'] != 'false') ? true : false;

    // Load language
    JFactory::getLanguage()->load('com_abtag', JPATH_ADMINISTRATOR);

    // Load the modal behavior script.
    JHtml::_('behavior.modal', 'a.modal');

    // Build the script.
    $script = array();

    // Select button script
    $script[] = '	function jSelectEntry_'.$this->id.'(id, title, catid, object) {';
    $script[] = '		document.getElementById("'.$this->id.'_id").value = id;';
    $script[] = '		document.getElementById("'.$this->id.'_name").value = title;';

    if($allowEdit) {
      $script[] = '		jQuery("#'.$this->id.'_edit").removeClass("hidden");';
    }

    if($allowClear) {
      $script[] = '		jQuery("#'.$this->id.'_clear").removeClass("hidden");';
    }

    $script[] = '		SqueezeBox.close();';
    $script[] = '	}';

    // Clear button script
    static $scriptClear;

    if($allowClear && !$scriptClear) {
	    $scriptClear = true;

	    $script[] = '	function jClearEntry(id) {';
	    $script[] = '		document.getElementById(id + "_id").value = "";';
	    $script[] = '		document.getElementById(id + "_name").value = "'.htmlspecialchars(JText::_('COM_ABTAG_SELECT_A_ENTRY', true), ENT_COMPAT, 'UTF-8').'";';
	    $script[] = '		jQuery("#"+id + "_clear").addClass("hidden");';
	    $script[] = '		if (document.getElementById(id + "_edit")) {';
	    $script[] = '			jQuery("#"+id + "_edit").addClass("hidden");';
	    $script[] = '		}';
	    $script[] = '		return false;';
	    $script[] = '	}';
    }

    // Add the script to the document head.
    JFactory::getDocument()->addScriptDeclaration(implode("\n", $script));

    // Setup variables for display.
    $html = array();
    $link = 'index.php?option=com_abtag&amp;view=entries&amp;layout=modal&amp;tmpl=component&amp;function=jSelectEntry_'.$this->id; 

    if(isset($this->element['language'])) {
      $link .= '&amp;forcedLanguage='.$this->element['language'];
    }

    if((int) $this->value > 0) {
      $db = JFactory::getDbo();
      $query = $db->getQuery(true)
	      ->select($db->quoteName('title'))
	      ->from($db->quoteName('#__abtag_entry'))
	      ->where($db->quoteName('id').' = '.(int) $this->value);
      $db->setQuery($query);

      try {
	$title = $db->loadResult();
      }
      catch(RuntimeException $e) {
	JFactory::getApplication()->enqueueMessage($e->getMessage(), 'error');
      }
    }

    if(empty($title)) {
      $title = JText::_('COM_ABTAG_SELECT_A_ENTRY');
    }

    $title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

    // The active entry id field.
    if(0 == (int) $this->value) {
      $value = '';
    }
    else {
      $value = (int) $this->value;
    }

    // The current entry display field.
    $html[] = '<span class="input-append">';
    $html[] = '<input type="text" class="input-medium" id="'.$this->id.'_name" value="'.$title.'" disabled="disabled" size="35" />';
    $html[] = '<a class="modal btn hasTooltip" title="'.JHtml::tooltipText('COM_ABTAG_CHANGE_ENTRY').'"  href="'.$link.'&amp;'.JSession::getFormToken().'=1" rel="{handler: \'iframe\', size: {x: 800, y: 450}}"><i class="icon-file"></i> '.JText::_('JSELECT').'</a>';

    // Edit entry button
    //TODO: Set up the edit modal layout.
    /*if($allowEdit) {
      $html[] = '<a class="btn hasTooltip'.($value ? '' : ' hidden').'" href="index.php?option=com_abtag&layout=modal&tmpl=component&task=entry.edit&id=' . $value. '" target="_blank" title="'.JHtml::tooltipText('COM_CONTENT_EDIT_ARTICLE').'" ><span class="icon-edit"></span> ' . JText::_('JACTION_EDIT') . '</a>';
    }*/

    // Clear entry button
    if($allowClear) {
      $html[] = '<button id="'.$this->id.'_clear" class="btn'.($value ? '' : ' hidden').'" onclick="return jClearDocument(\''.$this->id.'\')"><span class="icon-remove"></span> ' . JText::_('JCLEAR') . '</button>';
    }

    $html[] = '</span>';

    // class='required' for client side validation
    $class = '';
    if($this->required) {
      $class = ' class="required modal-value"';
    }

    $html[] = '<input type="hidden" id="'.$this->id.'_id"'.$class.' name="'.$this->name.'" value="'.$value.'" />';

    return implode("\n", $html);
  }
}


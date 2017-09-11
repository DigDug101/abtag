<?php
/**
 * @package ABTag
 * @copyright Copyright (c) 2017 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined( '_JEXEC' ) or die; // No direct access

JHtml::_('behavior.formvalidator');
JHtml::_('behavior.keepalive');
JHtml::_('formbehavior.chosen', 'select');

//Prevent params layout (layouts/joomla/edit/params.php) to display twice some fieldsets.
$this->ignore_fieldsets = array('details', 'permissions', 'jmetadata');

//Get the needed article's fields.
$global = array(array('parent', 'parent_id'),
		array('published', 'state', 'enabled'),
		array('category', 'catid'),
		'featured', 'sticky', 'access',
		'language', 'note', 'version_note');

//Remove the hits field from the template as entrie don't use it. 
$publishingdata = array('publish_up', 'publish_down',
			array('created', 'created_time'),
			array('created_by', 'created_user_id'),
			'created_by_alias',
			array('modified', 'modified_time'),
			array('modified_by', 'modified_user_id'),
			'version', 'id');
?>

<script type="text/javascript">
Joomla.submitbutton = function(task)
{
  if(task == 'entry.cancel' || document.formvalidator.isValid(document.getElementById('entry-form'))) {
    Joomla.submitform(task, document.getElementById('entry-form'));
  }
}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_abtag&view=entry&layout=edit&id='.(int) $this->item->id); ?>" 
 method="post" name="adminForm" id="entry-form" enctype="multipart/form-data" class="form-validate">

  <?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>

  <div class="form-horizontal">

    <?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'tags')); ?>

      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'tags', JText::_('COM_ABTAG_TAB_TAGS')); ?>
	<div class="row-fluid span4">
	  <?php echo $this->form->getControlGroup('tags'); ?>
	  <?php echo $this->form->getControlGroup('main_tag_id'); ?>
	</div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'publishing', JText::_('JGLOBAL_FIELDSET_PUBLISHING', true)); ?>
      <div class="row-fluid form-horizontal-desktop">
	<div class="span6">
	  <?php
	        $this->fields = $publishingdata;
		echo JLayoutHelper::render('joomla.edit.publishingdata', $this);
	  ?>
	</div>
	<div class="span6">
	  <?php echo JLayoutHelper::render('joomla.edit.metadata', $this); ?>
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

    <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'details', JText::_('COM_ABTAG_TAB_ARTICLE_DETAILS')); ?>

      <div class="row-fluid">
	<div class="span9">
	    <div class="form-vertical">
	      <?php echo $this->form->getControlGroup('articletext'); ?>
	    </div>
	</div>
	<div class="span3">
	  <?php
	        $this->fields = $global;
		echo JLayoutHelper::render('joomla.edit.global', $this);
	  ?>
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

    <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'article_publishing', JText::_('COM_ABTAG_TAB_ARTICLE_PUBLISHING')); ?>
      <div class="row-fluid form-horizontal-desktop">
	<div class="span6">
	  <?php
		echo $this->form->getControlGroup('article_created'); 
		echo $this->form->getControlGroup('author_name'); 
		echo $this->form->getControlGroup('article_modified'); 
		echo $this->form->getControlGroup('modifier_name'); 
		echo $this->form->getControlGroup('hits'); 
		echo $this->form->getControlGroup('article_id'); 
	  ?>
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

      <?php echo JLayoutHelper::render('joomla.edit.params', $this); ?>
  </div>

  <input type="hidden" name="task" value="" />
  <?php echo JHtml::_('form.token'); ?>
</form>


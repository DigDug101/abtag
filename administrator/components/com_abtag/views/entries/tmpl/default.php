<?php
/**
 * @package ABTag
 * @copyright Copyright (c) 2017 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined( '_JEXEC' ) or die; // No direct access
JHtml::addIncludePath(JPATH_ROOT.'/administrator/components/com_content/helpers/html');

JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');

$user = JFactory::getUser();
$userId = $user->get('id');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));
//Rely on the com_content permissions not com_abtag.
$canOrder = $user->authorise('core.edit.state', 'com_content.category');
$saveOrder = $listOrder == 'co.ordering';

// Check if only the tag filter is selected.
$tagFilter = $tagId = 0;
if (AbtagHelper::checkSelectedFilter('tag', true)) {
  $post = JFactory::getApplication()->input->post->getArray();
  $tagId = $post['filter']['tag'];
  $tagFilter = true;
}

if($saveOrder && $tagFilter) {
  $saveOrderingUrl = 'index.php?option=com_abtag&task=entries.saveOrderAjax&tmpl=component';
  JHtml::_('sortablelist.sortable', 'entryList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}

?>

<script type="text/javascript">
Joomla.orderTable = function()
{
  table = document.getElementById("sortTable");
  direction = document.getElementById("directionTable");
  order = table.options[table.selectedIndex].value;

  if(order != '<?php echo $listOrder; ?>') {
    dirn = 'asc';
  }
  else {
    dirn = direction.options[direction.selectedIndex].value;
  }

  Joomla.tableOrdering(order, dirn, '');
}
</script>


<form action="<?php echo JRoute::_('index.php?option=com_abtag&view=entries');?>" method="post" name="adminForm" id="adminForm">

<?php if (!empty( $this->sidebar)) : ?>
  <div id="j-sidebar-container" class="span2">
	  <?php echo $this->sidebar; ?>
  </div>
  <div id="j-main-container" class="span10">
<?php else : ?>
  <div id="j-main-container">
<?php endif;?>

<?php
// Search tools bar 
echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this));
?>

  <div class="clr"> </div>
  <?php if (empty($this->items)) : ?>
	<div class="alert alert-no-items">
		<?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
	</div>
  <?php else : ?>
    <table class="table table-striped" id="entryList">
      <thead>
	<tr>
	<th width="1%" class="nowrap center hidden-phone">
	<?php echo JHtml::_('searchtools.sort', '', 'tm.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?>
	</th>
	<th width="1%" class="hidden-phone">
	<?php echo JHtml::_('grid.checkall'); ?>
	</th>
	<th width="1%" style="min-width:55px" class="nowrap center">
	<?php echo JHtml::_('searchtools.sort', 'JSTATUS', 'co.state', $listDirn, $listOrder); ?>
	</th>
	<th>
	<?php echo JHtml::_('searchtools.sort', 'JGLOBAL_TITLE', 'co.title', $listDirn, $listOrder); ?>
	</th>
	<th width="10%" class="nowrap hidden-phone">
	  <?php echo JHtml::_('searchtools.sort',  'JGRID_HEADING_ACCESS', 'co.access', $listDirn, $listOrder); ?>
	</th>
	<th width="10%" class="nowrap hidden-phone">
	<?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_CREATED_BY', 'user', $listDirn, $listOrder); ?>
	</th>
	<th width="5%" class="nowrap hidden-phone">
	  <?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_LANGUAGE', 'co.language', $listDirn, $listOrder); ?>
	</th>
	<th width="10%" class="nowrap hidden-phone">
	<?php echo JHtml::_('searchtools.sort', 'JDATE', 'co.created', $listDirn, $listOrder); ?>
	</th>
	<th width="1%" class="nowrap hidden-phone">
	  <?php echo JHtml::_('searchtools.sort', 'JGLOBAL_HITS', 'co.hits', $listDirn, $listOrder); ?>
	</th>
	<th width="1%" class="nowrap hidden-phone">
	<?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_ID', 'e.id', $listDirn, $listOrder); ?>
	</th>
      </tr>
      </thead>

      <tbody>
      <?php foreach ($this->items as $i => $item) :

      $ordering = ($listOrder == 'tm.ordering');
      $canCreate = $user->authorise('core.create', 'com_abtag.category.'.$item->catid);
      //Rely on the com_content permissions not com_abtag.
      $canEdit = $user->authorise('core.edit','com_content.article.'.$item->article_id);
      $canEditOwn = $user->authorise('core.edit.own', 'com_content.article.'.$item->article_id) && $item->created_by == $userId;
      $canCheckin = $user->authorise('core.manage','com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
      $canChange = ($user->authorise('core.edit.state','com_content.article.'.$item->article_id) && $canCheckin) || $canEditOwn; 

      // Set the sortable group id according to the
      // filter selection.

      // Group id by default.
      //ABTag note: Ordering feature is only active with tags.
      $sortableGroupId = $item->catid;
      if ($tagFilter) {
	// Group by tag id.
	$sortableGroupId = $tagId;
      }
      ?>

      <tr class="row<?php echo $i % 2; ?>" sortable-group-id="<?php echo $sortableGroupId; ?>">
	<td class="order nowrap center hidden-phone">
	  <?php
	  $iconClass = '';
	  if(!$canChange || !$tagFilter)
	  {
	    $iconClass = ' inactive';
	  }
	  elseif(!$saveOrder || !$tagFilter)
	  {
	    $iconClass = ' inactive tip-top hasTooltip" title="' . JHtml::tooltipText('JORDERINGDISABLED');
	  }
	  ?>
	  <span class="sortable-handler<?php echo $iconClass ?>">
		  <i class="icon-menu"></i>
	  </span>
	  <?php if($canChange && $saveOrder && $tagFilter) : ?>
	      <input type="text" style="display:none" name="order[]" size="5" value="<?php echo $item->tm_ordering;?>" class="width-20 text-area-order " />
	  <?php endif; ?>
	  </td>
	  <td class="center hidden-phone">
		  <?php echo JHtml::_('grid.id', $i, $item->id); ?>
	  </td>
	  <td class="center">
	    <div class="btn-group">
	      <?php echo JHtml::_('jgrid.published', $item->state, $i, 'entries.', false, 'cb'); ?>
	      <?php echo JHtml::_('contentadministrator.featured', $item->featured, $i, false); ?>
	    </div>
	  </td>
	  <td class="has-context">
	    <div class="pull-left">
	      <?php if ($item->checked_out) : ?>
		  <?php echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'entries.', $canCheckin); ?>
	      <?php endif; ?>
	      <?php if($canEdit || $canEditOwn) : ?>
		<a href="<?php echo JRoute::_('index.php?option=com_abtag&task=entry.edit&id='.$item->id);?>" title="<?php echo JText::_('JACTION_EDIT'); ?>"><?php echo $this->escape($item->title); ?></a>
	      <?php else : ?>
		<?php echo $this->escape($item->title); ?>
	      <?php endif; ?>
		<span class="small break-word">
		  <?php echo JText::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($item->alias)); ?>
		</span>
		<div class="small">
		  <?php echo JText::_('JCATEGORY') . ": ".$this->escape($item->category_title); ?>
		</div>
	    </div>
	  </td>
	  <td class="small hidden-phone">
	    <?php echo $this->escape($item->access_level); ?>
	  </td>
	  <td class="small hidden-phone">
	    <?php echo $this->escape($item->user); ?>
	  </td>
	  <td class="small hidden-phone">
	    <?php if ($item->language == '*'):?>
	      <?php echo JText::alt('JALL', 'language'); ?>
	    <?php else:?>
	      <?php echo $item->language_title ? $this->escape($item->language_title) : JText::_('JUNDEFINED'); ?>
	    <?php endif;?>
	  </td>
	  <td class="nowrap small hidden-phone">
	    <?php echo JHtml::_('date', $item->created, JText::_('DATE_FORMAT_LC4')); ?>
	  </td>
	  <td class="hidden-phone">
	    <?php echo (int) $item->hits; ?>
	  </td>
	  <td>
	    <?php echo $item->id; ?>
	  </td></tr>

      <?php endforeach; ?>
      <tr>
	  <td colspan="10"><?php echo $this->pagination->getListFooter(); ?></td>
      </tr>
      </tbody>
    </table>
  <?php endif; ?>

<input type="hidden" name="boxchecked" value="0" />
<input type="hidden" name="option" value="com_abtag" />
<input type="hidden" name="task" value="" />
<?php echo JHtml::_('form.token'); ?>
</form>


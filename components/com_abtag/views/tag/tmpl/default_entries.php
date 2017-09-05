<?php
/**
 * @package ABTag
 * @copyright Copyright (c) 2017 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;
JHtml::_('behavior.framework');

$params = &$this->item->params;
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));

// Check for at least one editable article
$isEditable = false;

if(!empty($this->items)) {
  foreach($this->items as $article) {
    if($article->params->get('access-edit')) {
      $isEditable = true;
      break;
    }
  }
}

$tableClass = $this->params->get('show_headings') != 1 ? ' table-noheader' : '';
?>

<table class="category table table-striped table-bordered table-hover<?php echo $tableClass; ?>">
  <caption class="hide"><?php echo JText::sprintf('COM_ABTAG_TAG_LIST_TABLE_CAPTION', $this->tag->title); ?></caption>
  <thead>
  <tr>
    <th id="categorylist_header_title">
      <?php echo JHtml::_('grid.sort', 'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?>
    </th>
    <?php if ($date = $this->params->get('list_show_date')) : ?>
	    <th scope="col" id="categorylist_header_date">
	    <?php if ($date === 'created') : ?>
		    <?php echo JHtml::_('grid.sort', 'COM_CONTENT_'.$date.'_DATE', 'a.created', $listDirn, $listOrder); ?>
	    <?php elseif ($date === 'modified') : ?>
		    <?php echo JHtml::_('grid.sort', 'COM_CONTENT_'.$date.'_DATE', 'a.modified', $listDirn, $listOrder); ?>
	    <?php elseif ($date === 'published') : ?>
		    <?php echo JHtml::_('grid.sort', 'COM_CONTENT_'.$date.'_DATE', 'a.publish_up', $listDirn, $listOrder); ?>
	    <?php endif; ?>
	    </th>
    <?php endif; ?>
    <?php if ($this->params->get('list_show_author')) : ?>
	    <th scope="col" id="categorylist_header_author">
		    <?php echo JHtml::_('grid.sort', 'JAUTHOR', 'author', $listDirn, $listOrder); ?>
	    </th>
    <?php endif; ?>
    <?php if ($this->params->get('list_show_hits')) : ?>
	    <th scope="col" id="categorylist_header_hits">
		    <?php echo JHtml::_('grid.sort', 'JGLOBAL_HITS', 'a.hits', $listDirn, $listOrder); ?>
	    </th>
    <?php endif; ?>
    <?php if ($this->params->get('list_show_votes', 0) && $this->vote) : ?>
	    <th scope="col" id="categorylist_header_votes">
		    <?php echo JHtml::_('grid.sort', 'COM_CONTENT_VOTES', 'rating_count', $listDirn, $listOrder); ?>
	    </th>
    <?php endif; ?>
    <?php if ($this->params->get('list_show_ratings', 0) && $this->vote) : ?>
	    <th scope="col" id="categorylist_header_ratings">
		    <?php echo JHtml::_('grid.sort', 'COM_CONTENT_RATINGS', 'rating', $listDirn, $listOrder); ?>
	    </th>
    <?php endif; ?>
    <?php if ($isEditable) : ?>
	    <th scope="col" id="categorylist_header_edit"><?php echo JText::_('COM_CONTENT_EDIT_ITEM'); ?></th>
    <?php endif; ?>
  </tr>
  </thead>

  <tbody>

    <?php foreach($this->items as $i => $item) : ?>
      <tr class="row<?php echo $i % 2; ?>" sortable-group-id="<?php echo $item->catid?>">
	<td>
	<?php  //Build the link to the login page for the user to login or register.
	      if(!$item->params->get('access-view')) : 
		$menu = JFactory::getApplication()->getMenu();
		$active = $menu->getActive();
		$itemId = $active->id;
		$link1 = JRoute::_('index.php?option=com_users&view=login&Itemid='.$itemId);
		$returnURL = JRoute::_(AbtagHelperRoute::getEntryRoute($item->slug, $item->tag_ids, $item->language));
		$link = new JUri($link1);
		$link->setVar('return', base64_encode($returnURL));
	      endif; ?>

	<?php if($item->params->get('access-view')) : //Set the link to the entry page.
	      $link = JRoute::_(AbtagHelperRoute::getEntryRoute($item->slug, $item->tag_ids, $item->language));
	  endif; ?>

	  <a href="<?php echo $link;?>"><?php echo $this->escape($item->title); ?></a>

	  <?php if (JLanguageAssociations::isEnabled() && $this->params->get('show_associations')) : ?>
		  <?php $associations = ContentHelperAssociation::displayAssociations($item->id); ?>
		  <?php foreach ($associations as $association) : ?>
			  <?php if ($this->params->get('flags', 1)) : ?>
				  <?php $flag = JHtml::_('image', 'mod_languages/' . $association['language']->image . '.gif', $association['language']->title_native, array('title' => $association['language']->title_native), true); ?>
				  &nbsp;<a href="<?php echo JRoute::_($association['item']); ?>"><?php echo $flag; ?></a>&nbsp;
			  <?php else : ?>
				  <?php $class = 'label label-association label-' . $association['language']->sef; ?>
				  &nbsp;<a class="' . <?php echo $class; ?> . '" href="<?php echo JRoute::_($association['item']); ?>"><?php echo strtoupper($association['language']->sef); ?></a>&nbsp;
			  <?php endif; ?>
		  <?php endforeach; ?>
	  <?php endif; ?>

	  <?php if ($item->state == 0) : ?>
		  <span class="list-published label label-warning">
					  <?php echo JText::_('JUNPUBLISHED'); ?>
				  </span>
	  <?php endif; ?>
	  <?php if (strtotime($item->publish_up) > strtotime(JFactory::getDate())) : ?>
		  <span class="list-published label label-warning">
					  <?php echo JText::_('JNOTPUBLISHEDYET'); ?>
				  </span>
	  <?php endif; ?>
	  <?php if ((strtotime($item->publish_down) < strtotime(JFactory::getDate())) && $item->publish_down != JFactory::getDbo()->getNullDate()) : ?>
		  <span class="list-published label label-warning">
					  <?php echo JText::_('JEXPIRED'); ?>
				  </span>
	  <?php endif; ?>
	  </td>
	  <?php if ($this->params->get('list_show_date')) : ?>
		  <td headers="categorylist_header_date" class="list-date small">
		    <?php echo JHtml::_('date', $item->displayDate, $this->escape($this->params->get('date_format', JText::_('DATE_FORMAT_LC3')))); ?>
		  </td>
	  <?php endif; ?>
	  <?php if($this->params->get('list_show_author')) : ?>
	    <td>
	      <?php echo $this->escape($item->author); ?>
	    </td>
	  <?php endif; ?>
	  <?php if ($this->params->get('list_show_hits', 1)) : ?>
		  <td headers="categorylist_header_hits" class="list-hits">
					  <span class="badge badge-info">
						  <?php echo JText::sprintf('JGLOBAL_HITS_COUNT', $item->hits); ?>
					  </span>
				  </td>
	  <?php endif; ?>
	  <?php if ($this->params->get('list_show_votes', 0) && $this->vote) : ?>
		  <td headers="categorylist_header_votes" class="list-votes">
			  <span class="badge badge-success">
				  <?php echo JText::sprintf('COM_CONTENT_VOTES_COUNT', $item->rating_count); ?>
			  </span>
		  </td>
	  <?php endif; ?>
	  <?php if ($this->params->get('list_show_ratings', 0) && $this->vote) : ?>
		  <td headers="categorylist_header_ratings" class="list-ratings">
			  <span class="badge badge-warning">
				  <?php echo JText::sprintf('COM_CONTENT_RATINGS_COUNT', $item->rating); ?>
			  </span>
		  </td>
	  <?php endif; ?>
	  <?php if ($isEditable) : ?>
		  <td headers="categorylist_header_edit" class="list-edit">
			  <?php if ($article->params->get('access-edit')) : ?>
				  <?php echo JHtml::_('icon.edit', $item, $params); ?>
			  <?php endif; ?>
		  </td>
	  <?php endif; ?>
	  </tr>
    <?php endforeach; ?>
    </table>

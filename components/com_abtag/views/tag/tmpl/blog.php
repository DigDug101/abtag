<?php
/**
 * @package ABTag
 * @copyright Copyright (c) 2017 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

JHtml::_('formbehavior.chosen', 'select');
JHtml::addIncludePath(JPATH_COMPONENT.'/helpers');
?>

<div class="blog<?php echo $this->pageclass_sfx;?>">
  <?php if($this->params->get('show_page_heading')) : ?>
	  <h1>
	    <?php echo $this->escape($this->params->get('page_heading')); ?>
	  </h1>
  <?php endif; ?>
  <?php if($this->params->get('show_tag_title', 1)) : ?>
	  <h2 class="category-title">
	      <?php echo JHtml::_('content.prepare', $this->tag->title, ''); ?>
	  </h2>
  <?php endif; ?>

  <?php if($this->params->get('show_tag_description') || $this->params->def('show_tag_image')) : ?>
	  <div class="category-desc">
		  <?php if($this->params->get('show_tag_image') && $this->tag->images->get('image_intro')) : ?>
			  <img src="<?php echo $this->tag->images->get('image_intro'); ?>"/>
		  <?php endif; ?>
		  <?php if($this->params->get('show_tag_description') && $this->tag->description) : ?>
			  <?php echo JHtml::_('content.prepare', $this->tag->description, ''); ?>
		  <?php endif; ?>
		  <div class="clr"></div>
	  </div>
  <?php endif; ?>

  <?php if(empty($this->lead_items) && empty($this->link_items) && empty($this->intro_items)) : ?>
    <?php if($this->params->get('show_no_tagged_entries')) : ?>
	    <p><?php echo JText::_('COM_ABTAG_NO_ENTRIES'); ?></p>
    <?php endif; ?>
  <?php endif; ?>

  <?php $leadingcount = 0; ?>
  <?php if(!empty($this->lead_items)) : ?>
	  <div class="items-leading clearfix">
	<?php foreach($this->lead_items as &$item) : ?>
		<div class="leading-<?php echo $leadingcount; ?><?php echo $item->state == 0 ? ' system-unpublished' : null; ?>"
			itemprop="blogPost" itemscope itemtype="http://schema.org/BlogPosting">
			<?php
			$this->item = & $item;
			echo $this->loadTemplate('item');
			?>
		</div>
		<?php $leadingcount++; ?>
	<?php endforeach; ?>
	  </div><!-- end items-leading -->
  <?php endif; ?>

  <?php
  $introcount = (count($this->intro_items));
  $counter = 0;
  ?>

  <?php if(!empty($this->intro_items)) : ?>
    <?php foreach($this->intro_items as $key => &$item) : ?>
	<?php $rowcount = ((int) $key % (int) $this->columns) + 1; ?>
	<?php if($rowcount == 1) : ?>
		<?php $row = $counter / $this->columns; ?>
		<div class="items-row cols-<?php echo (int) $this->columns; ?> <?php echo 'row-'.$row; ?> row-fluid clearfix">
	<?php endif; ?>
	<div class="span<?php echo round((12 / $this->columns)); ?>">
		<div class="item column-<?php echo $rowcount; ?><?php echo $item->state == 0 ? ' system-unpublished' : null; ?>"
		    itemprop="blogPost" itemscope itemtype="http://schema.org/BlogPosting">
		    <?php
		    $this->item = & $item;
		    echo $this->loadTemplate('item');
		    ?>
		</div>
		<!-- end item -->
		<?php $counter++; ?>
	</div><!-- end span -->
	<?php if(($rowcount == $this->columns) or ($counter == $introcount)) : ?>
		</div><!-- end row -->
	<?php endif; ?>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if(!empty($this->link_items)) : ?>
	  <div class="items-more">
	    <?php echo $this->loadTemplate('links'); ?>
	  </div>
  <?php endif; ?>

  <?php if(($this->params->def('show_pagination', 2) == 1  || ($this->params->get('show_pagination') == 2)) && ($this->pagination->pagesTotal > 1)) : ?>
  <div class="pagination">

	  <?php if ($this->params->def('show_pagination_results', 1)) : ?>
		  <p class="counter pull-right">
			  <?php echo $this->pagination->getPagesCounter(); ?>
		  </p>
	  <?php endif; ?>

	  <?php echo $this->pagination->getPagesLinks(); ?> </div>
  </div>
  <?php endif; ?>

  <?php if(!empty($this->children) && $this->tagMaxLevel != 0) : ?>
	  <div class="cat-children">
	    <h3><?php echo JTEXT::_('COM_ABTAG_SUBTAGS_TITLE'); ?></h3>
	    <?php echo $this->loadTemplate('children'); ?>
	  </div>
  <?php endif; ?>
</div><!-- blog -->


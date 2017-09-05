<?php
/**
 * @package ABTag
 * @copyright Copyright (c) 2017 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/**
 * ABTag Component Query Helper
 *
 * @static
 * @package     Joomla.Site
 * @subpackage  com_abtag
 * @since       1.5
 */
class AbtagHelperQuery
{
  /**
   * Translate an order code to a field for primary tag ordering.
   *
   * @param   string	$orderby	The ordering code.
   *
   * @return  string	The SQL field(s) to order by.
   * @since   1.5
   */
  public static function orderbyPrimary($orderby)
  {
    switch ($orderby)
    {
      case 'alpha' :
	      $orderby = 't.path, ';
	      break;

      case 'ralpha' :
	      $orderby = 't.path DESC, ';
	      break;

      case 'order' :
	      $orderby = 't.lft, ';
	      break;

      default :
	      $orderby = '';
	      break;
    }

    return $orderby;
  }

  /**
   * Translate an order code to a field for secondary category ordering.
   *
   * @param   string	$orderby	The ordering code.
   * @param   string	$orderDate	The ordering code for the date.
   *
   * @return  string	The SQL field(s) to order by.
   * @since   1.5
   */
  public static function orderbySecondary($orderby, $orderDate = 'created')
  {
    $queryDate = self::getQueryDate($orderDate);

    switch ($orderby)
    {
      case 'date' :
	      $orderby = $queryDate;
	      break;

      case 'rdate' :
	      $orderby = $queryDate.' DESC ';
	      break;

      case 'alpha' :
	      $orderby = 'a.title';
	      break;

      case 'ralpha' :
	      $orderby = 'a.title DESC';
	      break;

      case 'order' :
	      $orderby = 'tm.ordering';
	      break;

      case 'rorder' :
	      $orderby = 'tm.ordering DESC';
	      break;

      case 'author' :
	      $orderby = 'author';
	      break;

      case 'rauthor' :
	      $orderby = 'author DESC';
	      break;

      case 'front' :
	      $orderby = 'a.featured DESC, fp.ordering, ' . $queryDate . ' DESC ';
	      break;

      case 'random' :
	      $orderby = JFactory::getDbo()->getQuery(true)->Rand();
	      break;

      case 'vote' :
	      $orderby = 'article_id DESC ';
	      if (JPluginHelper::isEnabled('content', 'vote'))
	      {
		      $orderby = 'rating_count DESC ';
	      }
	      break;

      case 'rvote' :
	      $orderby = 'article_id ASC ';
	      if (JPluginHelper::isEnabled('content', 'vote'))
	      {
		      $orderby = 'rating_count ASC ';
	      }
	      break;

      case 'rank' :
	      $orderby = 'article_id DESC ';
	      if (JPluginHelper::isEnabled('content', 'vote'))
	      {
		      $orderby = 'rating DESC ';
	      }
	      break;

      case 'rrank' :
	      $orderby = 'article_id ASC ';
	      if (JPluginHelper::isEnabled('content', 'vote'))
	      {
		      $orderby = 'rating ASC ';
	      }
	      break;

      default :
	      $orderby = 'tm.ordering';
	      break;
    }

    return $orderby;
  }

  /**
   * Translate an order code to a field for primary category ordering.
   *
   * @param   string	$orderDate	The ordering code.
   *
   * @return  string	The SQL field(s) to order by.
   * @since   1.6
   */
  public static function getQueryDate($orderDate)
  {
    $db = JFactory::getDbo();

    switch($orderDate) {
      case 'modified' :
	      $queryDate = ' CASE WHEN a.modified = '.$db->quote($db->getNullDate()).' THEN a.created ELSE a.modified END';
	      break;

      // use created if publish_up is not set
      case 'published' :
	      $queryDate = ' CASE WHEN a.publish_up = '.$db->quote($db->getNullDate()).' THEN a.created ELSE a.publish_up END ';
	      break;

      case 'created' :
      default :
	      $queryDate = ' a.created ';
	      break;
    }

    return $queryDate;
  }

  /**
   * Method to order the intro entries array for ordering
   * down the columns instead of across.
   * The layout always lays the introtext entries out across columns.
   * Array is reordered so that, when entries are displayed in index order
   * across columns in the layout, the result is that the
   * desired entry ordering is achieved down the columns.
   *
   * @param   array    &$entries   Array of intro text entries
   * @param   integer  $numColumns  Number of columns in the layout
   *
   * @return  array  Reordered array to achieve desired ordering down columns
   *
   * @since   1.6
   */
  public static function orderDownColumns(&$entries, $numColumns = 1)
  {
    $count = count($entries);

    // Just return the same array if there is nothing to change
    if($numColumns == 1 || !is_array($entries) || $count <= $numColumns) {
      $return = $entries;
    }
    // We need to re-order the intro entries array
    else {
      // We need to preserve the original array keys
      $keys = array_keys($entries);

      $maxRows = ceil($count / $numColumns);
      $numCells = $maxRows * $numColumns;
      $numEmpty = $numCells - $count;
      $index = array();

      // Calculate number of empty cells in the array

      // Fill in all cells of the array
      // Put -1 in empty cells so we can skip later
      for($row = 1, $i = 1; $row <= $maxRows; $row++) {
	for($col = 1; $col <= $numColumns; $col++) {
	  if($numEmpty > ($numCells - $i)) {
	    // Put -1 in empty cells
	    $index[$row][$col] = -1;
	  }
	  else {
	    // Put in zero as placeholder
	    $index[$row][$col] = 0;
	  }

	  $i++;
	}
      }

      // Layout the entries in column order, skipping empty cells
      $i = 0;

      for($col = 1; ($col <= $numColumns) && ($i < $count); $col++) {
	for($row = 1; ($row <= $maxRows) && ($i < $count); $row++) {
	  if($index[$row][$col] != - 1) {
	    $index[$row][$col] = $keys[$i];
	    $i++;
	  }
	}
      }

      // Now read the $index back row by row to get entries in right row/col
      // so that they will actually be ordered down the columns (when read by row in the layout)
      $return = array();
      $i = 0;

      for($row = 1; ($row <= $maxRows) && ($i < $count); $row++) {
	for($col = 1; ($col <= $numColumns) && ($i < $count); $col++) {
	  $return[$keys[$i]] = $entries[$index[$row][$col]];
	  $i++;
	}
      }
    }

    return $return;
  }
}


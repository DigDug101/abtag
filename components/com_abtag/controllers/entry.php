<?php
/**
 * @package ABTag
 * @copyright Copyright (c) 2017 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die;
require_once JPATH_ROOT.'/administrator/components/com_abtag/helpers/abtag.php';

/**
 * @package     Joomla.Site
 * @subpackage  com_abtag
 */
class AbtagControllerEntry extends JControllerForm
{

  /**
   * Method to save a vote.
   *
   * @return  void
   *
   * @since   1.6
   */
  public function vote()
  {
    // Check for request forgeries.
    $this->checkToken();

    $user_rating = $this->input->getInt('user_rating', -1);

    if ($user_rating > -1)
    {
      $url = $this->input->getString('url', '');
      $id = $this->input->getInt('id', 0);
      $articleId = AbtagHelper::getArticleId($id);

      //Uses the storeVote function from the article model.
      JModelLegacy::addIncludePath(JPATH_SITE.'/components/com_content/models', 'ContentModel');
      $model = JModelLegacy::getInstance('Article', 'ContentModel');

      if ($model->storeVote($articleId, $user_rating))
      {
	$this->setRedirect($url, JText::_('COM_CONTENT_ARTICLE_VOTE_SUCCESS'));
      }
      else
      {
	$this->setRedirect($url, JText::_('COM_CONTENT_ARTICLE_VOTE_FAILURE'));
      }
    }
  }
}


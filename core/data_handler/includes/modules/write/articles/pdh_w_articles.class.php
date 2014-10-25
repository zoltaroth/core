<?php
/*
* Project:		EQdkp-Plus
* License:		Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
* Link:			http://creativecommons.org/licenses/by-nc-sa/3.0/
* -----------------------------------------------------------------------
* Began:		2010
* Date:			$Date$
* -----------------------------------------------------------------------
* @author		$Author$
* @copyright	2006-2011 EQdkp-Plus Developer Team
* @link			http://eqdkp-plus.com
* @package		eqdkpplus
* @version		$Rev$
*
* $Id$
*/

if(!defined('EQDKP_INC')) {
	die('Do not access this file directly.');
}

if(!class_exists('pdh_w_articles')) {
	class pdh_w_articles extends pdh_w_generic {
		
		public $arrLang = array(
			'title' 			=> "{L_HEADLINE}",
			'text'				=> "{L_DESCRIPTION}",
			'category'			=> "{L_CATEGORY}",
			'featured'			=> "{L_FEATURED}",
			'comments'			=> "{L_INFO_COMMENTS}",
			'votes'				=> "{L_INFO_VOTING}",
			'published'			=> "{L_PUBLISHED}",
			'show_from'			=> "{L_SHOW_FROM}",
			'show_to'			=> "{L_SHOW_TO}",
			'user_id'			=> "{L_USER}",
			'date'				=> "{L_DATE}",
			'previewimage'		=> "{L_PREVIEW_IMAGE}",
			'alias'				=> "{L_ALIAS}",
			'tags'				=> "{L_TAGS}",
			'page_objects'		=> "{L_PAGE_OBJECTS}",
			'hide_header'		=> "{L_HIDE_HEADER}",
		);

		public function __construct() {
			parent::__construct();
		}

		public function delete($id) {
			$arrOldData = $this->pdh->get('articles', 'data', array($id));
			$objQuery = $this->db->prepare("DELETE FROM __articles WHERE id =?")->execute($id);
			
			$this->pdh->put("comment", "delete_attach_id", array("articles", $id));

			$this->pdh->enqueue_hook('articles_update');
			
			$arrOld = array(
					'title' 			=> $arrOldData["title"],
					'text'				=> $arrOldData["text"],
					'category'			=> $arrOldData["category"],
					'featured'			=> $arrOldData["featured"],
					'comments'			=> $arrOldData["comments"],
					'votes'				=> $arrOldData["votes"],
					'published'			=> $arrOldData["published"],
					'show_from'			=> $arrOldData["show_from"],
					'show_to'			=> $arrOldData["show_to"],
					'user_id'			=> $arrOldData["user_id"],
					'date'				=> $arrOldData["date"],
					'previewimage'		=> $arrOldData["previewimage"],
					'alias'				=> $arrOldData["alias"],
					'tags'				=> implode(", ", unserialize($arrOldData["tags"])),
					'page_objects'		=> implode(", ", unserialize($arrOldData["page_objects"])),
					'hide_header'		=> $arrOldData["hide_header"],
			);
			
			//Logging			
			$arrChanges = $this->logs->diff(false, $arrOld, $this->arrLang);
			if ($arrChanges){
				$this->log_insert('action_article_deleted', $arrChanges, $id, $arrOldData["title"], 1, 'article');
			}
		}
		
		public function delete_category($intCategoryID){
			$arrArticles = $this->pdh->get('articles', 'id_list', $intCategoryID);
			foreach($arrArticles as $intArticleID){
				$arrOldData = $this->pdh->get('articles', 'data', array($intArticleID));
				$this->pdh->put("comment", "delete_attach_id", array("articles", $intArticleID));
					
				$arrOld = array(
						'title' 			=> $arrOldData["title"],
						'text'				=> $arrOldData["text"],
						'category'			=> $arrOldData["category"],
						'featured'			=> $arrOldData["featured"],
						'comments'			=> $arrOldData["comments"],
						'votes'				=> $arrOldData["votes"],
						'published'			=> $arrOldData["published"],
						'show_from'			=> $arrOldData["show_from"],
						'show_to'			=> $arrOldData["show_to"],
						'user_id'			=> $arrOldData["user_id"],
						'date'				=> $arrOldData["date"],
						'previewimage'		=> $arrOldData["previewimage"],
						'alias'				=> $arrOldData["alias"],
						'tags'				=> implode(", ", unserialize($arrOldData["tags"])),
						'page_objects'		=> implode(", ", unserialize($arrOldData["page_objects"])),
						'hide_header'		=> $arrOldData["hide_header"],
				);
				
				$arrChanges = $this->logs->diff(false, $arrOld, $this->arrLang);
				if ($arrChanges){
					$this->log_insert('action_article_deleted', $arrChanges, $intArticleID, $arrOldData["title"], 1, 'article');
				}
			}
			
			$objQuery = $this->db->prepare("DELETE FROM __articles WHERE category =?")->execute($intCategoryID);
			$this->pdh->enqueue_hook('articles_update');
		}
		
		public function add($strTitle, $strText, $arrTags, $strPreviewimage, $strAlias, $intPublished, $intFeatured, $intCategory, $intUserID, $intComments, $intVotes,$intDate, $strShowFrom, $strShowTo, $intHideHeader){
			if ($strAlias == ""){
				$strAlias = $this->create_alias($strTitle);
			} else {
				$strAlias = $this->create_alias($strAlias);
			}
			
			//Check Alias
			$blnAliasResult = $this->check_alias(0, $strAlias);			
			
			$strText = $this->bbcode->replace_shorttags($strText);
			$strText = $this->embedly->parseString($strText);
			
			$arrPageObjects = array();
			preg_match_all('#<p(.*)class="system-article"(.*) title="(.*)">(.*)</p>#iU', xhtml_entity_decode($strText), $arrTmpPageObjects, PREG_PATTERN_ORDER);
			if (count($arrTmpPageObjects[0])){
				foreach($arrTmpPageObjects[3] as $key=>$val){
					$arrPageObjects[] = $val;
				}
			}
			$objQuery = $this->db->prepare("INSERT INTO __articles :p")->set(array(
				'title' 			=> $strTitle,
				'text'				=> $strText,
				'category'			=> $intCategory,
				'featured'			=> $intFeatured,
				'comments'			=> $intComments,
				'votes'				=> $intVotes,
				'published'			=> $intPublished,
				'show_from'			=> $strShowFrom,
				'show_to'			=> $strShowTo,
				'user_id'			=> $intUserID,
				'date'				=> $intDate,
				'previewimage'		=> $strPreviewimage,
				'alias'				=> ($blnAliasResult) ? $strAlias : '',
				'hits'				=> 0,
				'sort_id'			=> 0,
				'tags'				=> serialize($arrTags),
				'votes_count'		=> 0,
				'votes_sum'			=> 0,
				'last_edited'		=> $this->time->time,
				'last_edited_user'	=> $this->user->id,
				'page_objects'		=> serialize($arrPageObjects),
				'hide_header'		=> $intHideHeader,
			))->execute();
			
			if ($objQuery){
				$id = $objQuery->insertId;
				
				if (!$blnAliasResult){
					$blnAliasResult = $this->check_alias(0, $strAlias.'-'.$id);
					if ($blnAliasResult){
						$objQuery = $this->db->prepare("UPDATE __articles :p WHERE id=?")->set(array(
							'alias' => $strAlias.'-'.$id,
						))->execute($id);
					} else {
						$this->db->prepare("DELETE FROM __articles WHERE id=?")->execute($id);
						return false;
					}
				}
						
				//Logging			
				$arrNew = array(
						'title' 			=> $strTitle,
						'text'				=> $strText,
						'category'			=> $intCategory,
						'featured'			=> $intFeatured,
						'comments'			=> $intComments,
						'votes'				=> $intVotes,
						'published'			=> $intPublished,
						'show_from'			=> $strShowFrom,
						'show_to'			=> $strShowTo,
						'user_id'			=> $intUserID,
						'date'				=> $intDate,
						'previewimage'		=> $strPreviewimage,
						'alias'				=> ($blnAliasResult) ? $strAlias : '',
						'tags'				=> implode(", ", $arrTags),
						'page_objects'		=> implode(", ", $arrPageObjects),
						'hide_header'		=> $intHideHeader,	
				);
					
				$arrChanges = $this->logs->diff(false, $arrNew, $this->arrLang);
				if ($arrChanges){
					$this->log_insert('action_article_added', $arrChanges, $id, $strTitle, 1, 'article');
				}
						
				$this->pdh->enqueue_hook('articles_update');
				$this->pdh->enqueue_hook('article_categories_update');
				return $id;
			}
			
			return false;
		}
		
		public function update_headline($id, $strTitle){
			$arrOldData = $this->pdh->get('articles', 'data', array($id));
			
			$objQuery = $this->db->prepare("UPDATE __articles :p WHERE id=?")->set(array(
					'title' 			=> $strTitle,
			))->execute($id);

			if ($objQuery){
				$this->pdh->enqueue_hook('articles_update');
				$this->pdh->enqueue_hook('article_categories_update');
			
				//Log changes
				$arrNew = array(
						'title' 			=> $strTitle,
				);
			
				$arrOld = array(
						'title' 			=> $arrOldData["title"],
				);
			
				$arrFlags = array(
						'text'			=> 1,
				);
			
				$arrChanges = $this->logs->diff($arrOld, $arrNew, $this->arrLang, $arrFlags);
				if ($arrChanges){
					$this->log_insert('action_article_updated', $arrChanges, $id, $arrOldData["title"], 1, 'article');
				}
			
				return $id;
			}
			return false;
		}
		
		public function update_article($id, $strText){
			$strText = $this->bbcode->replace_shorttags($strText);
			$strText = $this->embedly->parseString($strText);
				
			$arrPageObjects = array();
			preg_match_all('#<p(.*)class="system-article"(.*) title="(.*)">(.*)</p>#iU', xhtml_entity_decode($strText), $arrTmpPageObjects, PREG_PATTERN_ORDER);
			if (count($arrTmpPageObjects[0])){
				foreach($arrTmpPageObjects[3] as $key=>$val){
					$arrPageObjects[] = $val;
				}
			}
				
			$arrOldData = $this->pdh->get('articles', 'data', array($id));
			
			$objQuery = $this->db->prepare("UPDATE __articles :p WHERE id=?")->set(array(
					'text'				=> $strText,
					'page_objects'		=> serialize($arrPageObjects),
			))->execute($id);
			
			if ($objQuery){
				$this->pdh->enqueue_hook('articles_update');
				$this->pdh->enqueue_hook('article_categories_update');
			
				//Log changes
				$arrNew = array(
						'text'				=> $strText,
						'page_objects'		=> implode(", ", $arrPageObjects),
				);
			
				$arrOld = array(
						'text'				=> $arrOldData["text"],
						'page_objects'		=> implode(", ", unserialize($arrOldData["page_objects"])),
				);
			
				$arrFlags = array(
						'text'			=> 1,
				);
			
				$arrChanges = $this->logs->diff($arrOld, $arrNew, $this->arrLang, $arrFlags);
				if ($arrChanges){
					$this->log_insert('action_article_updated', $arrChanges, $id, $arrOldData["title"], 1, 'article');
				}
			
				return $id;
			}
				
			return false;
			
		}
		
		public function update($id, $strTitle, $strText, $arrTags, $strPreviewimage, $strAlias, $intPublished, $intFeatured, $intCategory, $intUserID, $intComments, $intVotes,$intDate, $strShowFrom, $strShowTo, $intHideHeader){
			if ($strAlias == ""){
				$strAlias = $this->create_alias($strTitle);
			} elseif($strAlias != $this->pdh->get('articles', 'alias', array($id))) {
				$strAlias = $this->create_alias($strAlias);
			}
			
			//Check Alias
			$blnAliasResult = $this->check_alias($id, $strAlias);
			if (!$blnAliasResult) return false;
			
			$strText = $this->bbcode->replace_shorttags($strText);
			$strText = $this->embedly->parseString($strText);
			
			$arrPageObjects = array();
			preg_match_all('#<p(.*)class="system-article"(.*) title="(.*)">(.*)</p>#iU', xhtml_entity_decode($strText), $arrTmpPageObjects, PREG_PATTERN_ORDER);
			if (count($arrTmpPageObjects[0])){
				foreach($arrTmpPageObjects[3] as $key=>$val){
					$arrPageObjects[] = $val;
				}
			}
			
			$arrOldData = $this->pdh->get('articles', 'data', array($id));
			
			$objQuery = $this->db->prepare("UPDATE __articles :p WHERE id=?")->set(array(
				'title' 			=> $strTitle,
				'text'				=> $strText,
				'category'			=> $intCategory,
				'featured'			=> $intFeatured,
				'comments'			=> $intComments,
				'votes'				=> $intVotes,
				'published'			=> $intPublished,
				'show_from'			=> $strShowFrom,
				'show_to'			=> $strShowTo,
				'user_id'			=> $intUserID,
				'date'				=> $intDate,
				'previewimage'		=> $strPreviewimage,
				'alias'				=> $strAlias,
				'tags'				=> serialize($arrTags),
				'last_edited'		=> $this->time->time,
				'last_edited_user'	=> $this->user->id,
				'page_objects'		=> serialize($arrPageObjects),
				'hide_header'		=> $intHideHeader,
			))->execute($id);
				
			if ($objQuery){
				$this->pdh->enqueue_hook('articles_update');
				$this->pdh->enqueue_hook('article_categories_update');
				
				//Log changes				
				$arrNew = array(
					'title' 			=> $strTitle,
					'text'				=> $strText,
					'category'			=> $intCategory,
					'featured'			=> $intFeatured,
					'comments'			=> $intComments,
					'votes'				=> $intVotes,
					'published'			=> $intPublished,
					'show_from'			=> $strShowFrom,
					'show_to'			=> $strShowTo,
					'user_id'			=> $intUserID,
					'date'				=> $intDate,
					'previewimage'		=> $strPreviewimage,
					'alias'				=> $strAlias,
					'tags'				=> implode(", ", $arrTags),
					'page_objects'		=> implode(", ", $arrPageObjects),
					'hide_header'		=> $intHideHeader,
				);
				
				$arrOld = array(
					'title' 			=> $arrOldData["title"],
					'text'				=> $arrOldData["text"],
					'category'			=> $arrOldData["category"],
					'featured'			=> $arrOldData["featured"],
					'comments'			=> $arrOldData["comments"],
					'votes'				=> $arrOldData["votes"],
					'published'			=> $arrOldData["published"],
					'show_from'			=> $arrOldData["show_from"],
					'show_to'			=> $arrOldData["show_to"],
					'user_id'			=> $arrOldData["user_id"],
					'date'				=> $arrOldData["date"],
					'previewimage'		=> $arrOldData["previewimage"],
					'alias'				=> $arrOldData["alias"],
					'tags'				=> implode(", ", unserialize($arrOldData["tags"])),
					'page_objects'		=> implode(", ", unserialize($arrOldData["page_objects"])),
					'hide_header'		=> $arrOldData["hide_header"],
				);
				
				$arrFlags = array(
					'text'			=> 1,
				);
								
				$arrChanges = $this->logs->diff($arrOld, $arrNew, $this->arrLang, $arrFlags);
				if ($arrChanges){
					$this->log_insert('action_article_updated', $arrChanges, $id, $arrOldData["title"], 1, 'article');
				}
				
				return $id;
			}
			
			return false;
		}
		
		public function reset_votes($id){
			$objQuery = $this->db->prepare("UPDATE __articles :p WHERE id=?")->set(array(
				'votes_count' 		=> 0,
				'votes_sum'			=> 0,
				'votes_users'		=> '',
			))->execute($id);
			
			if ($objQuery) {
				$this->log_insert('action_article_reset_votes', array(), $id, $this->pdh->get('articles', 'title', array($id)), 1, 'article');
				
				$this->pdh->enqueue_hook('articles_update');
				return true;
			}
			
			return false;
		}
		
		public function vote($intArticleID, $intVoting){
			$intSum = $this->pdh->get('articles', 'votes_sum', array($intArticleID));
			$intCount = $this->pdh->get('articles', 'votes_count', array($intArticleID));
			$arrVotedUsers = $this->pdh->get('articles', 'votes_users', array($intArticleID));
			$arrVotedUsers[] = $this->user->id;
			$intSum += $intVoting;
			$intCount++;
			
			$objQuery = $this->db->prepare("UPDATE __articles :p WHERE id=?")->set(array(
				'votes_count' 		=> $intCount,
				'votes_sum'			=> $intSum,
				'votes_users'		=> serialize($arrVotedUsers),
			))->execute($intArticleID);
			
			if ($objQuery) {
				$this->pdh->enqueue_hook('articles_update');
				return true;
			}
			
			return false;
		}
		
		public function delete_previewimage($id){
			$arrOld = array('previewimage' => $this->pdh->get('articles', 'previewimage', array($id)));
			
			$objQuery = $this->db->prepare("UPDATE __articles :p WHERE id=?")->set(array(
				'previewimage' 		=> '',
			))->execute($id);
			
			if ($objQuery) {	
				$arrNew = array('previewimage' => '');
				$log_action = $this->logs->diff($arrOld, $arrNew, $this->arrLang);
				if ($log_action) $this->log_insert('action_article_updated', $log_action, $id, $this->pdh->get('articles', 'title', array($id)), 1, 'article');
				$this->pdh->enqueue_hook('articles_update');
				return true;
			}
			
			return false;
		}
		
		public function update_featuredandpublished($id, $intFeatured, $intPublished){
			$arrOld = array(
				'featured' => $this->pdh->get('articles', 'featured', array($id)),
				'published'=> $this->pdh->get('articles', 'published', array($id))
			);
			
			$objQuery = $this->db->prepare("UPDATE __articles :p WHERE id=?")->set(array(
				'featured'		=> $intFeatured,
				'published'		=> $intPublished,
			))->execute($id);
			
			if ($objQuery){
				
				$arrNew = array(
					'featured'	=> $intFeatured,
					'published'	=> $intPublished,
				);
				$log_action = $this->logs->diff($arrOld, $arrNew, $this->arrLang);
				if ($log_action) $this->log_insert('action_article_updated', $log_action, $id, $this->pdh->get('articles', 'title', array($id)), 1, 'article');
				
				
				$this->pdh->enqueue_hook('articles_update');
				$this->pdh->enqueue_hook('article_categories_update');
				return $id;
			}
			return false;
		}
		
		public function set_published($arrIDs){
			
			foreach($arrIDs as $id){
				$arrOld = array(
						'published'=> $this->pdh->get('articles', 'published', array($id))
				);
				$arrNew = array(
						'published'	=> 1,
				);
				$log_action = $this->logs->diff($arrOld, $arrNew, $this->arrLang);
				if ($log_action) $this->log_insert('action_article_updated', $log_action, $id, $this->pdh->get('articles', 'title', array($id)), 1, 'article');
			}
			
			$objQuery = $this->db->prepare("UPDATE __articles :p WHERE id :in")->set(array(
					'published'		=> 1,
			))->in($arrIDs)->execute($id);
			
			$this->pdh->enqueue_hook('articles_update');
			$this->pdh->enqueue_hook('article_categories_update');
		}
		
		public function set_unpublished($arrIDs){			
			foreach($arrIDs as $id){
				$arrOld = array(
						'published'=> $this->pdh->get('articles', 'published', array($id))
				);
				$arrNew = array(
						'published'	=> 0,
				);
				$log_action = $this->logs->diff($arrOld, $arrNew, $this->arrLang);
				if ($log_action) $this->log_insert('action_article_updated', $log_action, $id, $this->pdh->get('articles', 'title', array($id)), 1, 'article');
			}
			$objQuery = $this->db->prepare("UPDATE __articles :p WHERE id :in")->set(array(
					'published'		=> 0,
			))->in($arrIDs)->execute($id);
			
			$this->pdh->enqueue_hook('articles_update');
			$this->pdh->enqueue_hook('article_categories_update');
		}
		
		public function change_category($arrIDs, $intCategoryID){
			$arrNew = array(
					'category'	=> $intCategoryID,
			);
			foreach($arrIDs as $id){
				$arrOld = array(
						'category'=> $this->pdh->get('articles', 'category', array($id))
				);
				$log_action = $this->logs->diff($arrOld, $arrNew, $this->arrLang);
				if ($log_action) $this->log_insert('action_article_updated', $log_action, $id, $this->pdh->get('articles', 'title', array($id)), 1, 'article');
			}
			
			$objQuery = $this->db->prepare("UPDATE __articles :p WHERE id :in")->set(array(
				'category' => $intCategoryID,	
			))->in($arrIDs)->execute();
			
			$this->pdh->enqueue_hook('articles_update');
			$this->pdh->enqueue_hook('article_categories_update');
		}
		
		private function check_alias($id, $strAlias){
			if (is_numeric($strAlias)) return false;
			
			if ($id){
				$strMyAlias = $this->pdh->get('articles', 'alias', array($id));
				if ($strMyAlias == $strAlias) return true;		
				$blnResult = $this->pdh->get('articles', 'check_alias', array($strAlias));
				return $blnResult;
				
			} else {
				$blnResult = $this->pdh->get('articles', 'check_alias', array($strAlias));
				return $blnResult;
				
			}
			return false;
		}
		
		private function create_alias($strTitle){
			$strAlias = utf8_strtolower($strTitle);
			$strAlias = str_replace(' ', '-', $strAlias);
			$a_satzzeichen = array("\"",",",";",".",":","!","?", "&", "=", "/", "|", "#", "*", "+", "(", ")", "%", "$", "´", "„", "“", "‚", "‘", "`", "^");
			$strAlias = str_replace($a_satzzeichen, "", $strAlias);
			return $strAlias;
		}
	
	}
}
?>
<?php
/**
 *@author: nicolaas[at]sunnysideup.co.nz
 *@description:
 * a log history and counted history of searches done (e.g. 100 people searched for "sunshine")
 * it allows gives the opportunity to link zero or more pages to a particular search phrase
 *
 *
 *
 **/

class SearchHistory Extends DataObject {

	private static $db = array(
		"Title" => "Varchar(255)",
		"RedirectTo" => "Varchar(255)"
	);

	private static $has_many = array(
		"LogEntries" => "SearchHistoryLog"
	);

	private static $many_many = array(
		"Recommendations" => "SiteTree"
	);

	private static $singular_name = 'Search History Phrase';

	private static $plural_name = 'Search History Phrases';

	private static $default_sort = 'Title';

	private static $separator = " | ";

	private static $minimum_length = 3;

	private static $number_of_keyword_repeats = 3;

	private static $searchable_fields = array(
		"Title",
		"RedirectTo"
	);

	private static $summary_fields = array(
		"Title", "RedirectTo"
	);

	private static $field_labels = array(
		"Title" => "Phrase Searched For",
		"RedirectTo" => "Redirect To Search Phrase (if any)",
		"Recommendations" => "Recommended Pages - must already be part of the natural result set",
	);

	public static function add_entry($KeywordString) {
		if($parent = self::find_entry($KeywordString)) {
			//do nothing
		}
		else {
			$parent = new SearchHistory();
			$parent->Title = $KeywordString;
			$parent->write();
		}
		if($parent) {
			$obj = new SearchHistoryLog();
			$obj->SearchedForID = $parent->ID;
			$obj->write();
			return $parent;
		}
	}

	public static function find_entry($KeywordString) {
		$KeywordString = self::clean_keywordstring($KeywordString);
		return SearchHistory::get()->filter(array("Title" => $KeywordString))->first();
	}

	static function clean_keywordstring($KeywordString) {
		Convert::raw2sql($KeywordString);
		$KeywordString = trim(eregi_replace(" +", " ", $KeywordString));
		return $KeywordString;
	}

	function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->removeByName("Title");
		$fields->addFieldToTab("Root.Main", new HeaderField($name = "TitleHeader", "search for: '".$this->Title."'", 1), "RedirectTo");
		$fields->removeByName("Recommendations");
		if(!$this->RedirectTo) {
			$source = SiteTree::get()
				->filter(array("ShowInSearch" => 1))
				->exclude(array("ClassName" => "SearchPlusPage"));
			$sourceArray = $sourc->map()->toArray();
			//$fields->addFieldToTab("Root.Main", new MultiSelectField($name = "Recommendations", $title = "Recommendations", $sourceArray));
			$fields->addFieldToTab("Root.Main", new TreeMultiselectField($name = "Recommendations", $title = "Recommendations", "SiteTree"));
		}
		else {
			$fields->addFieldToTab("Root.Main", new LiteralField($name = "Recommendations", '<p>This search phrase cannot have recommendations, because it redirects to <i>'.$this->RedirectTo.'</i></p>'));
		}
		$page = SearchPlusPage::get()->first();
		if(!$page) {
			user_error("Make sure to create a SearchPlusPage to make proper use of this module", E_USER_NOTICE);
		}
		else {
			$fields->addFieldToTab("Root.Main", new LiteralField(
				$name = "BackLinks",
				$content =
					'<p>
						Review a graph of all <a href="'.$page->Link().'popularsearchwords/100/10/">Popular Search Phrases</a> OR
						<a href="'.$page->Link().'results/?Search='.urlencode($this->Title).'&amp;action_results=Search&amp;redirect=1">try this search</a>.
					</p>'
			));
		}
		return $fields;
	}

	function onAfterWrite() {
		$bt = defined('DB::USE_ANSI_SQL') ? "\"" : "`";
		parent::onAfterWrite();
		//add recommendations that are not actually matching
		$combos = $this->Recommendations();
		if($combos) {
			$idArray = array();
			foreach($combos as $combo) {
				$idArray[$combo->SiteTreeID] = $combo->SiteTreeID;
			}
			if(count($idArray)) {
				$pages = SiteTree::get()
					->filter(array("ID" => $idArray));
				if($pages->count()) {
					foreach($pages as $page) {
						$changed = false;
						$title = Config::inst()->get("SearchHistory", "seperator").$this->getTitle();
						$multipliedTitle = Config::inst()->get("SearchHistory", "seperator").str_repeat($this->getTitle(), Config::inst()->get("SearchHistory", "number_of_keyword_repeats"));
						if(stripos($page->MetaKeywords." ", $multipliedTitle) === false) {
							$page->MetaKeywords. $multipliedTitle;
							$changed = true;
						}
						if(stripos($page->MetaKeywords." ", $multipliedTitle) === false) {
							$page->MetaKeywords = $page->MetaKeywords. $multipliedTitle;
							$changed = true;
						}
						if($changed) {
							$page->writeToStage('Stage');
							$page->Publish('Stage', 'Live');
							$page->Status = "Published";
						}
					}
				}
			}
		}
		//delete useless ones
		if(strlen($this->Title) < Config::inst()->get("SearchHistory", "minimum_length")) {
			$this->delete();
		}
	}

	function requireDefaultRecords() {
		parent::requireDefaultRecords();
		$dos = SearchHistory::get()
			->where("\"Title\" = '' OR \"Title\" IS NULL OR LENGTH(\"Title\") < ".Config::inst()->get("SearchHistory", "minimum_length"));
		if($dos) {
			foreach($dos as $do) {
				DB::alteration_message("deleting #".$do->ID." from SearchHistory as it does not have a search phrase", "deleted");
				$do->delete();
			}
		}
	}

}



class SearchHistoryLog Extends DataObject {

	private static $has_one = array(
		"SearchedFor" => "SearchHistory"
	);

	private static $singular_name = 'Search History Log Entry';

	private static $plural_name = 'Search History Log Entries';

	private static $default_sort = 'Created DESC';

}

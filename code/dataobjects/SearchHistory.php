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

	static $db = array(
		"Title" => "Varchar(255)",
		"RedirectTo" => "Varchar(255)"
	);

	static $has_many = array(
		"LogEntries" => "SearchHistoryLog"
	);

	static $many_many = array(
		"Recommendations" => "SiteTree"
	);

	static $singular_name = 'Search History Phrase';

	static $plural_name = 'Search History Phrases';

	static $default_sort = 'Title';

	protected static $separator = " | ";
		static function set_separator($v) { self::$separator = $v;}
		static function get_separator() { return self::$separator;}

	protected static $minimum_length = 3;
		static function set_minimum_length($v) { self::$minimum_length = intval($v) + 0;}
		static function get_minimum_length() { return self::$minimum_length;}

	protected static $number_of_keyword_repeats = 3;
		static function set_number_of_keyword_repeats($v) {if(!intval($v)) { $v = 1; } self::$number_of_keyword_repeats = intval($v) + 0;}
		static function get_number_of_keyword_repeats() { return self::$number_of_keyword_repeats;}

	public static $searchable_fields = array(
		"Title",
		"RedirectTo"
	);

	public static $summary_fields = array(
		"Title", "RedirectTo"
	);

	public static $field_labels = array(
		"Title" => "Phrase Searched For",
		"RedirectTo" => "Redirect To Search Phrase (if any)",
		"Recommendations" => "Recommended Pages - must already be part of the natural result set",
	);

	static function add_entry($KeywordString) {
		$bt = defined('DB::USE_ANSI_SQL') ? "\"" : "`";
		$KeywordString = self::clean_keywordstring($KeywordString);
		if($parent = DataObject::get_one("SearchHistory", "{$bt}Title{$bt} = '".$KeywordString."'")) {
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

	static function find_entry($KeywordString) {
		$bt = defined('DB::USE_ANSI_SQL') ? "\"" : "`";
		$KeywordString = self::clean_keywordstring($KeywordString);
		return DataObject::get_one("SearchHistory", "{$bt}Title{$bt} = '".$KeywordString."'");
	}

	static function clean_keywordstring($KeywordString) {
		Convert::raw2sql($KeywordString);
		$KeywordString = trim(eregi_replace(" +", " ", $KeywordString));
		return $KeywordString;
	}

	function getCMSFields() {
		$bt = defined('DB::USE_ANSI_SQL') ? "\"" : "`";
		$fields = parent::getCMSFields();
		$fields->removeByName("Title");
		$fields->addFieldToTab("Root.Main", new HeaderField($name = "TitleHeader", "search for: '".$this->Title."'", 1), "RedirectTo");
		$fields->removeByName("Recommendations");
		if(!$this->RedirectTo) {
			$source = DataObject::get("SiteTree", "{$bt}ShowInSearch{$bt} = 1 AND {$bt}ClassName{$bt} <> 'SearchPlusPage'");
			$sourceArray = $source->toDropdownMap();
			//$fields->addFieldToTab("Root.Main", new MultiSelectField($name = "Recommendations", $title = "Recommendations", $sourceArray));
			$fields->addFieldToTab("Root.Main", new TreeMultiselectField($name = "Recommendations", $title = "Recommendations", "SiteTree"));
		}
		else {
			$fields->addFieldToTab("Root.Main", new LiteralField($name = "Recommendations", '<p>This search phrase cannot have recommendations, because it redirects to <i>'.$this->RedirectTo.'</i></p>'));
		}
		$page = DataObject::get_one("SearchPlusPage");
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
				if($pages = DataObject::get("SiteTree", "{$bt}SiteTree{$bt}.{$bt}ID{$bt} IN (".implode(",", $idArray).")")) {
					foreach($pages as $page) {
						$changed = false;
						$title = self::get_separator().$this->getTitle();
						if(stripos($page->MetaTitle." ", $title) === false) {
							$page->MetaTitle = $page->MetaTitle . $title;
							$changed = true;
						}
						$multipliedTitle = self::get_separator().str_repeat($this->getTitle(), self::get_number_of_keyword_repeats());
						if(stripos($page->MetaKeywords." ", $multipliedTitle) === false) {
							$page->MetaKeywords = $page->MetaKeywords. $multipliedTitle;
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
		if(strlen($this->Title) < self::get_minimum_length()) {
			$this->delete();
		}
	}

	function requireDefaultRecords() {
		$bt = defined('DB::USE_ANSI_SQL') ? "\"" : "`";
		parent::requireDefaultRecords();
		$dos = DataObject::get("SearchHistory", "{$bt}Title{$bt} = '' OR {$bt}Title{$bt} IS NULL OR LENGTH({$bt}Title{$bt}) < ".self::get_minimum_length());
		if($dos) {
			foreach($dos as $do) {
				DB::alteration_message("deleting #".$do->ID." from SearchHistory as it does not have a search phrase", "deleted");
				$do->delete();
			}
		}
	}

}



class SearchHistoryLog Extends DataObject {

	static $has_one = array(
		"SearchedFor" => "SearchHistory"
	);

	static $singular_name = 'Search History Log Entry';

	static $plural_name = 'Search History Log Entries';

	static $default_sort = 'Created DESC';

}

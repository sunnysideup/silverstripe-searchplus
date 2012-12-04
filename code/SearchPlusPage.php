<?php
/*
 *@author: nicolaas [at] sunnysideup.co.nz
 *
 *
 **/

class SearchPlusPage extends Page {

	static $add_action = 'Search Plus Page';

	static $can_be_root = true;

	static $icon = 'searchplus/images/treeicons/SearchPlusPage';

	public static $db = array();

	public static $has_many = array(
		"RecommendedSearchPlusSections" => "RecommendedSearchPlusSection"
	);

	public function canCreate() {
		return !DataObject::get_one("SiteTree", "ClassName = 'SearchPlusPage'");
	}

	public function canDelete() {
		return false;
	}

	protected static $result_length = 10;
		static function set_result_length($v) { $v = intval($v); if($v < 1) {user_error("SearchPlusPage::set_result_length expects an integer greater than zero", E_USER_WARNING);} self::$result_length = $v; }
		static function get_result_length() { return self::$result_length; }

	public function getCMSFields($params = null) {
		$fields = parent::getCMSFields($params);
		$fields->addFieldToTab(
			"Root.Content.RecommendedSections",
			new HasManyComplexTableField(
				$controller = $this,
				$name = "RecommendedSearchPlusSections",
				$sourceClass = "RecommendedSearchPlusSection",
				$fieldList = array("Title" => "Title"),
				$detailFormFields = null,
				$sourceFilter = "",
				$sourceSort = "",
				$sourceJoin = ""
			)
		);
		$fields->addFieldToTab(
			"Root.Content.PopularSearchPhrases",
			new LiteralField(
				"PopularSearchPhrasesLink",
				'<p>Please make sure to regular <a href="'.$this->Link().'popularsearchwords/100/10">review the most popular search phrases</a> and to add recommendations for each</a>.</p>'
			)
		);
		return $fields;
	}




}

class SearchPlusPage_Controller extends Page_Controller {

	public function init() {
		parent::init();
		Requirements::javascript("searchplus/javascript/searchpluspage.js");
	}

	protected static $search_history_object = null;

	function Form() {
		return $this->SearchPlusForm("MainSearchForm", "MainSearch", "");
	}

	function results($data){
		if(isset($data["Search"]) || isset($data["MainSearch"])) {
			// there is a search
			Requirements::themedCSS("searchpluspage_searchresults");
			if(isset($data["MainSearch"]) || !isset($data["Search"])) {
				$data["Search"] = $data["MainSearch"];
			}
			//redirect if needed
			$data["Search"] = urldecode($data["Search"]);
			$form = $this->SearchPlusForm();
			if(!isset($_GET["redirect"])) {
				self::$search_history_object = SearchHistory::add_entry($data["Search"]);
				if(self::$search_history_object) {
					if(self::$search_history_object->RedirectTo && self::$search_history_object->RedirectTo != self::$search_history_object->Title) {
						Director::redirect(
							str_replace(
								"Search=".urlencode(self::$search_history_object->Title),
								"Search=".urlencode(self::$search_history_object->RedirectTo),
								HTTP::RAW_setGetVar('redirect', 1, null)
							)
						);
					}
				}
			}
			else {
				self::$search_history_object = SearchHistory::find_entry($data["Search"]);
			}
			//load data for recommended pages
			$recommendationsSet = $this->Recommendations();
			$matchArrayRecommended = array();
			$matchArrayResults = array();
			if($recommendationsSet) {
				foreach($recommendationsSet as $rec) {
					$matchArrayRecommended[$rec->ClassName.$rec->ID] = $rec->ClassName.$rec->ID;
				}
			}
			//work out positions
			$results = $form->getResults();
			$query = $form->getSearchQuery();
			$startingPosition = isset($_REQUEST["start"]) ? $_REQUEST["start"] : 0;
			$endingPosition = $startingPosition + SearchPlusPage::get_result_length();
			$startingPosition++;
			if($results) {
				$total = $results->TotalItems();
			}
			else {
				$total = 0;
			}
			if($endingPosition > $total) {
				$endingPosition = $total;
			}
			//highlight search text and check which ones are recommended
			if($total) {
				foreach($results as $result) {
					$title = $result->getTitle();
					$dbField = DBField::create($className = "Text", $title);
					$result->HighlightedTitle = $dbField->ContextSummary();
					$result->IsRecommended = false;
					$matchArrayResults[$result->ClassName.$result->ID] = $result->ClassName.$result->ID;
					if(isset($matchArrayRecommended[$result->ClassName.$result->ID])) {
						$result->IsRecommended = true;
					}
				}
			}
			$data = array(
				'Results' => $results,
				'Query' => $query,
				'From' => $startingPosition,
				'To' => $endingPosition,
				'Total' => $total,
				'HasResults' => $total ? true : false,
				'Recommendations' => $this->Recommendations(),
				'RecommendedSearchPlusSection' => $this->dataRecord->RecommendedSearchPlusSections(),
			);
			$this->Title = 'Search Results';
			$this->MetaTitle = 'Search: '.Convert::raw2att($query);
			$this->MenuTitle = 'Search Results';
			return $this->customise($data)->renderWith(array('SearchPlusPage_results', 'Page'));
		}
		return Array();
	}

	function Recommendations() {
		if(self::$search_history_object) {
			return self::$search_history_object->Recommendations();
		}
	}

	function HasPopularSearchWords() {
		return Permission::check("ADMIN");
	}

	function PopularSearchWordsForAllUsers($days = 100, $limit = 7) {
		$do = $this->getPopularSearchWords($days, $limit, $mergeRedirects = true);
		return $do->DataByCount;
	}

	function popularsearchwords(HTTPRequest $HTTPRequest) {
		if(!$this->HasPopularSearchWords()) {
			Security::permissionFailure($this, _t('Security.PERMFAILURE',' This page is secured and you need administrator rights to access it. Enter your credentials below and we will send you right along.'));
			return;
		}
		Requirements::themedCSS("popularsearches");
		$days = intval($HTTPRequest->param("ID"));
		if(!$days) {
			$days = 100;
		}
		$limit = intval($HTTPRequest->param("OtherID")+0);
		if(!$limit) $limit++;
		$do = $this->getPopularSearchWords($days, $limit);
		$page->MenuTitle = $do->Title;
		$do->MetaTitle = $do->Title;
		$do->Title = $do->Title;
		return $this->customise($do)->renderWith(array('SearchPlusPage_popularsearches', 'Page'));
	}

	protected function getPopularSearchWords($days, $limit, $mergeRedirects = false) {
		$bt = defined('DB::USE_ANSI_SQL') ? "\"" : "`";
		$extraWhere = '';
		if($mergeRedirects) {
			$extraWhere = " AND {$bt}RedirectTo{$bt} = '' OR {$bt}RedirectTo{$bt} IS NULL";
		}
		$data = DB::query("
			SELECT COUNT({$bt}SearchHistoryLog{$bt}.{$bt}ID{$bt}) count, {$bt}SearchHistory{$bt}.{$bt}RedirectTo{$bt} RedirectTo, {$bt}SearchHistory{$bt}.{$bt}Title{$bt} title, {$bt}SearchHistory{$bt}.{$bt}ID{$bt} id
			FROM {$bt}SearchHistoryLog{$bt}
				INNER JOIN {$bt}SearchHistory{$bt} ON {$bt}SearchHistory{$bt}.{$bt}ID{$bt} = {$bt}SearchHistoryLog{$bt}.{$bt}SearchedForID{$bt}
			WHERE {$bt}SearchHistoryLog{$bt}.{$bt}Created{$bt} > ( NOW() - INTERVAL $days DAY ) ".$extraWhere."
			GROUP BY {$bt}SearchHistory{$bt}.{$bt}ID{$bt}
			ORDER BY count DESC
			LIMIT 0, $limit
		");
		$do = new DataObject();
		$do->Title = "Search phrase popularity, $days days $limit entries";
		$do->DataByCount = new DataObjectSet();
		$do->DataByTitle = new DataObjectSet();
		$do->Limit = $limit;
		$do->Days = $days;
		$list = array();
		foreach($data as $key => $row) {
			if(!$key) {
				$max = $row["count"];
			}
			if($mergeRedirects) {
				$data = DB::query("
					SELECT COUNT({$bt}SearchHistoryLog{$bt}.{$bt}ID{$bt}) count
					FROM {$bt}SearchHistoryLog{$bt}
						INNER JOIN {$bt}SearchHistory{$bt}
							ON {$bt}SearchHistory{$bt}.{$bt}ID{$bt} = {$bt}SearchHistoryLog{$bt}.{$bt}SearchedForID{$bt}
					WHERE {$bt}SearchHistoryLog{$bt}.{$bt}Created{$bt} > ( NOW() - INTERVAL $days DAY )
						AND {$bt}SearchHistory{$bt}.{$bt}RedirectTo{$bt} = '".$row["title"]."'
					GROUP BY {$bt}SearchHistory{$bt}.{$bt}RedirectTo{$bt}
					ORDER BY count
					DESC LIMIT 1
				");
				if($data) {
					$extraCounts = $data->value();
				}
				$row["count"] += $extraCounts;
			}
			$percentage = floor(($row["count"]/$max)*100);
			$subDataSet = new ArrayData(
				array(
					"ParentID" => $row["id"],
					"Title" => $row["title"],
					"Width" => $percentage,
					"Count" => $row["count"],
					"Link" => $this->Link()."results/?Search=".urldecode($row["title"])."&amp;action_results=Search"
				)
			);
			$list[$row["title"]] = $subDataSet;
			$do->DataByCount->push($subDataSet );
		}
		ksort($list);
		foreach($list as $subDataSet ) {
			$do->DataByTitle->push($subDataSet);
		}
		return $do;
	}


}


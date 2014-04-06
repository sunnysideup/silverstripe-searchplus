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

class RecommendedSearchPlusSection Extends DataObject {

	private static $db = array(
		"Title" => "Varchar(255)",
		"Intro" => "Text",
		"Sort" => "Int"
	);

	private static $has_one = array(
		"ParentPage" => "Page",
		"Parent" => "SearchPlusPage"
	);

	private static $defaults = array(
		"Sort" => 100
	);

	private static $singular_name = 'Recommended SearchPlus Section';

	private static $plural_name = 'Recommended SearchPlus Sections';

	private static $default_sort = 'Sort, Title';

	private static $searchable_fields = array(
		"Title"
	);

	private static $summary_fields = array(
		"Title", "Sort"
	);

	private static $field_labels = array(
		"Sort" => "Sort Index"
	);

	function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->removeByName("ParentPageID");
		$fields->removeByName("ParentID");
		$fields->addFieldToTab("Root.Main", new TreeDropdownField($name = "ParentPageID", $title = "Parent Page (show all child pages as links for this recommended section)", $sourceObject = "SiteTree"));
		return $fields;
	}

	function onBeforeWrite() {
		parent::onBeforeWrite();
		if(!$this->ParentID) {
			if($page = SearchPlusPage::get()->first()) {
				$this->ParentID = $page->ID;
			}
			else{
				user_error("Make sure to create a SearchPlusPage", E_USER_NOTICE);
			}
		}
	}

}

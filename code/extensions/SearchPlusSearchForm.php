<?php

/*
 * @author: nicolaas[at]sunnysideup.co.nz
 * @description: adds a SearchForm to all Page Controller classes.
 *   so that you can add a search form to all pages
 *   the page is submitted to the SearchPlusPage.
 *   This is different from a "standard" search form,
 *   which is always submitted to the page it was submitted from.
 *
 *
 *
 **/

class SearchPlusSearchForm extends Extension {

	function SearchPlusForm($name = "SearchForm", $fieldName = "Search", $fieldLabel = '') {
		$action = Director::URLParam("Action");
		$page = DataObject::get_one("SearchPlusPage");
		if($page) {//!in_array($action, array("login", "logout")) &&
			if(!$fieldLabel) {
				if( isset($_REQUEST[$fieldName]) ) {
					$searchText = $_REQUEST[$fieldName];
					//we set $_REQUEST["Search"] below because it is also used by things like Text::ContextSummary
					$_REQUEST["Search"] = $_REQUEST[$fieldName];
				}
				elseif(isset($_REQUEST["Search"])) {
					$searchText = $_REQUEST["Search"];
				}
				else {
					$searchText = 'Search';
				}
			}
			else {
				$searchText = '';
			}
			$field = new TextField($fieldName, $fieldLabel, $searchText);
			$fields = new FieldSet($field);
			$actions = new FieldSet(
				new FormAction('results', 'Search')
			);
			$form = new SearchForm($this, $name, $fields, $actions);

			$form->setFormAction($page->Link()."results/");
			$form->setPageLength(SearchPlusPage::get_result_length());
			$form->unsetValidator();
			return $form;
		}
		elseif(!$page) {
			user_error("You need to create a SearchPlusPage to have a search box", E_USER_NOTICE);
		}
	}


 }
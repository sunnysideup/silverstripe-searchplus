<?php

/**
 * @author Nicolaas [at] sunnysideup.co.nz
 **/

class SearchPlusModelAdmin extends ModelAdmin {

	public static $managed_models = array("SearchHistory");

	public static function set_managed_models(array $array) {
		self::$managed_models = $array;
	}
	public static $url_segment = 'searchplus';

	public static $menu_title = 'Search';

}
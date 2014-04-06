<?php

/**
 * @author Nicolaas [at] sunnysideup.co.nz
 **/

class SearchPlusModelAdmin extends ModelAdmin {

	private static $managed_models = array("SearchHistory");

	private static $url_segment = 'searchplus';

	private static $menu_title = 'Search';

}

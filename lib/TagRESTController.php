<?php
class TagRESTController extends WPAPIRESTController {
	protected function __construct() {}
	
	protected function getTags() {
		$tags = get_tags('hide_empty=0&orderby=count&order=ASC');
		
		// Return tags usgin WordPress native function. (This can be replaced with a custom function)
		if(isset($tags[1]))
			return $this->_return($tags);
		else
			return $this->_return($tags[0]);
		
	}
	
	protected function getTag($tag = 0) {
		$tags = get_tags('hide_empty=0&orderby=count&order=ASC&include='.$tag);
		if(isset($tags[1]))
			return $this->_return($tags);
		else
			return $this->_return($tags[0]);
	}
	
	/**
	 * Apply request filter
	 * 
	 * @since 0.1
	 * 
	 * @return (mixed) filtered content
	 */
	private function _return($content) {
		return wpr_filter_content($content,wpr_get_filter("Tags"));
	}
}
?>
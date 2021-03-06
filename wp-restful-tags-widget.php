<?php
if(in_array("wp-restful-tags-plugin/wp-restful-tags.php",get_option('active_plugins'))) {
/*
Copyright 2010  Jos� P. Airosa  (email : me@joseairosa.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
	
//========================================
// Create Tags Widget
//========================================
class wpr_widget_top_tags {
	
	function activate() {
		// Instructions to run when the plugin is activated
		$data = array( 'return_type' => 'xml', 'max_tags' => 3);
	    if ( ! get_option('wpr_widget_top_tags')){
	      add_option('wpr_widget_top_tags' , $data);
	    } else {
	      update_option('wpr_widget_top_tags' , $data);
	    }
	}
	
	function deactivate() {
		// Instructions to run when the plugin is activated
		delete_option('wpr_widget_top_tags');
	}
	
	function control() {
		global $wpdb;
		require_once WPR_PLUGIN_FOLDER_PATH.'lib/OAuthStore.php';
		// Init the database connection
		$store = OAuthStore::instance ( 'MySQL', array ('conn' => $wpdb->dbh ) );
		$servers = $store->listServerTokens();
		
		$data = get_option('wpr_widget_top_tags'); ?>
		<p>
			<label>Return Type 
				<select name="wpr_widget_top_tags_return_type" id="wpr_widget_top_tags_return_type">
					<option value="json" <?php echo ($data ['return_type'] == "json" ? 'selected="selected"' : '' )?>>JSON</option>
					<option value="xml" <?php echo ($data ['return_type'] == "xml" ? 'selected="selected"' : '' )?>>XML</option>
				</select>
			</label>
		</p>
		<p>
			<label>Max Tags
				<input style="width: 35px; text-align: right;" type="text" name="wpr_widget_top_tags_max" id="wpr_widget_top_tags_max" value="<?php echo $data ['max_tags'] ?>" />
			</label>
		</p>
		<p>
			<label>Used API Servers
				<select multiple="multiple" size="5" name="wpr_widget_top_tags_server[]" style="height: 100px;overflow: auto;">
					<?php 
						foreach($servers as $server): 
						$server_url = str_replace(array("/api"),array(""),$server['server_uri']);
					?>
						<option <?php echo ((!isset($data ['servers']) || !is_array($data ['servers'])) ? '' : ((in_array($server ['consumer_key'],$data ['servers'])) ? 'selected="selected"' : '' ) )?> value="<?php echo $server ['consumer_key']?>"><?php echo $server_url?></option>
					<?php endforeach;?>
				</select>
			</label>
		</p>
		<?php
		if (isset ( $_POST ['wpr_widget_top_tags_return_type'] )) {
			$data ['return_type'] = attribute_escape ( $_POST ['wpr_widget_top_tags_return_type'] );
			$data ['max_tags'] = attribute_escape ( $_POST ['wpr_widget_top_tags_max'] );
			$data ['servers'] = $_POST ['wpr_widget_top_tags_server'];
			update_option ( 'wpr_widget_top_tags', $data );
		}
	}
	
	function widget($args) {
		global $wpdb,$wp_query;
		$reserved_requests = array("request-token","auth","access-token","register");
		
		if(!isset($wp_query->query_vars['request']))
			$wp_query->query_vars['request'] = "";
			
		if(!in_array($wp_query->query_vars['request'],$reserved_requests)) {
			$data = get_option('wpr_widget_top_tags');
			echo $args ['before_widget'];
			echo $args ['before_title'] . 'Network Tags' . $args ['after_title'];
			require_once WPR_PLUGIN_FOLDER_PATH.'lib/OAuthStore.php';
			require_once WPR_PLUGIN_FOLDER_PATH.'lib/consumer/WP-API.php';
			require_once WPR_PLUGIN_FOLDER_PATH.'lib/consumer/OAuth.php';
			require_once WPR_PLUGIN_FOLDER_PATH.'lib/jsonwrapper/jsonwrapper.php';
			// Init the database connection
			$store = OAuthStore::instance ( 'MySQL', array ('conn' => $wpdb->dbh ) );
			$servers = $store->listServerTokens();
			
			echo '<div id="wpr-top-tags-wrapper">';
			
			foreach($servers as $server) {
				if(is_array($data ['servers']) && in_array($server ['consumer_key'],$data ['servers'])) {
					$server_url = str_replace(array("/api"),array(""),$server['server_uri']);
					$response = "";
					$to = new WPOAuth ( $server ['consumer_key'], $server ['consumer_secret'], $server ['token'], $server ['token_secret'], $server ['server_uri'] );
					
					if($data ['return_type'] == "json") {
						$response = json_decode($to->OAuthRequest ( $to->TO_API_ROOT.'tags.json', array (), 'POST' ));
					} elseif($data ['return_type'] == "xml") {
						$response = json_decode($to->OAuthRequest ( $to->TO_API_ROOT.'tags.xml', array (), 'POST' ));
					}
					
					if(is_array($response)) {
						$response = array_reverse($response);
						
						if(count($response) > 0) {
							echo '<p>'.$server_url.'</p>';
							$x = 1;
							echo '<ul>';
							foreach($response as $single_tag) {
								if($x <= $data['max_tags']) {
									echo '<li><a href="'.$server_url.'?s='.$single_tag->name.'">'.$single_tag->name.'</a></li>';
								}
								$x++;
							}
							echo '</ul>';
						}
					}
				}
			}
			
			echo '</div>';
			
			
			echo $args ['after_widget'];
		}
	}
	
	function register() {
		register_sidebar_widget ( 'Get API Tags', array ('wpr_widget_top_tags', 'widget' ) );
		register_widget_control ( 'Get API Tags', array ('wpr_widget_top_tags', 'control' ) );
	}
}

//========================================
// Tell WordPress to load this widget
//========================================
add_action ( "widgets_init", array ('wpr_widget_top_tags', 'register' ) );
register_activation_hook ( __FILE__, array ('wpr_widget_top_tags', 'activate' ) );
register_deactivation_hook ( __FILE__, array ('wpr_widget_top_tags', 'deactivate' ) );

}
?>
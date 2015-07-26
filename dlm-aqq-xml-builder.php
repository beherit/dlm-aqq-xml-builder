<?php
/*
Plugin Name: DLM AQQ XML Builder
Plugin URI: http://beherit.pl/en/aqq/other/dlm-aqq-xml-builder
Description: Helps to build an XML files for update system in AQQ IM.
Version: 1.0
Author: Krzysztof Grochocki
Author URI: http://beherit.pl/
Text Domain: dlm_axb
Domain Path: /languages
License: GPLv3
*/

/*
	Copyright (C) 2015 Krzysztof Grochocki

	This file is part of DLM AQQ XML Builder.

	DLM AQQ XML Builder is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3, or
	(at your option) any later version.

	DLM AQQ XML Builder is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with GNU Radio. If not, see <http://www.gnu.org/licenses/>.
*/

//Translate plugin metadata
__('http://beherit.pl/en/aqq/other/dlm-aqq-xml-builder', 'dlm_axb');
__('Helps to build an XML files for update system in AQQ IM.', 'dlm_axb');

//Define translations
function ejabat_textdomain() {
	load_plugin_textdomain('dlm_axb', false, dirname(plugin_basename(__FILE__)).'/languages');
}
add_action('init', 'ejabat_textdomain');

//Admin init
function dlm_axb_admin_init() {
	//Register settings
	register_setting('dlm_axb_settings', 'dlm_axb_plugins_category');
	register_setting('dlm_axb_settings', 'dlm_axb_plugins_xml_url');
	register_setting('dlm_axb_settings', 'dlm_axb_themes_category');
	register_setting('dlm_axb_settings', 'dlm_axb_themes_xml_url');
	//Add link to the settings on plugins page
	add_filter('plugin_action_links', 'dlm_axb_plugin_action_links', 10, 2);
}
add_action('admin_init', 'dlm_axb_admin_init');

//Link to the settings on plugins page
function dlm_axb_plugin_action_links($action_links, $plugin_file) {
	if(plugin_basename(__FILE__) == $plugin_file) {
		$action_links[] = '<a href="edit.php?post_type=dlm_download&page=dlm_axb_settings">'.__('Settings', 'dlm_axb').'</a>';
	}
	return $action_links;
}

//Create admin menu
function dlm_axb_admin_menu() {
	//Global variable
	global $dlm_axb_options_page_hook;
	//Add options page
	$dlm_axb_options_page_hook = add_submenu_page('edit.php?post_type=dlm_download', 'AQQ XML Builder Add-on', 'AQQ XML Builder', 'manage_options', 'dlm_axb_settings', 'dlm_axb_settings_page');
}
add_action('admin_menu', 'dlm_axb_admin_menu');

//Add meta boxes
function dlm_axb_add_dlm_download_meta_boxes() {
	//Get category slug from terms
	$terms = get_the_terms($post_id, 'dlm_download_category');
	$term_slugs = array();
	if($terms && ! is_wp_error($terms)) {
		foreach ($terms as $term) {
			$term_slugs[] = $term->slug;
		}
	}
	//Check post category slug
	if((in_array(get_option('dlm_axb_plugins_category'), $term_slugs)) || (in_array(get_option('dlm_axb_themes_category'), $term_slugs))) {
		//Add post meta box
		add_meta_box(
			'dlm_axb_post_meta_box',
			__('File information', 'dlm_axb'),
			'dlm_axb_post_meta_box',
			'dlm_download',
			'normal',
			'default'
		);
	}
}
add_action('add_meta_boxes_dlm_download', 'dlm_axb_add_dlm_download_meta_boxes');
function dlm_axb_add_meta_boxes() {
	//Global variable
	global $dlm_axb_options_page_hook;
	//Add plugins meta box
	add_meta_box(
		'dlm_axb_plugins_meta_box',
		__('Plugins', 'dlm_axb'),
		'dlm_axb_plugins_meta_box',
		$dlm_axb_options_page_hook,
		'normal',
		'default'
	);
	//Add themes meta box
	add_meta_box(
		'dlm_axb_themes_meta_box',
		__('Themes', 'dlm_axb'),
		'dlm_axb_themes_meta_box',
		$dlm_axb_options_page_hook,
		'normal',
		'default'
	);
}
add_action('add_meta_boxes', 'dlm_axb_add_meta_boxes');

//Show post meta box
function dlm_axb_post_meta_box() {
	//Get category slug from terms
	$terms = get_the_terms($post->ID, 'dlm_download_category');
	$term_slugs = array();
	if($terms && ! is_wp_error($terms)) {
		foreach ($terms as $term) {
			$term_slugs[] = $term->slug;
		}
	}
	//Get set categories
	$themes_category = get_option('dlm_axb_themes_category');
	//Get saved meta data
	$values = get_post_custom($post->ID);
	$changelog = isset($values['dlm_download_changelog']) ? $values['dlm_download_changelog'][0] : '';
	$version_type = isset($values['dlm_download_version_type']) ? $values['dlm_download_version_type'][0] : 0;
	if(!in_array($themes_category, $term_slugs)) {
		$platform = isset($values['dlm_download_platform']) ? $values['dlm_download_platform'][0] : 2;
		$dll_name = isset($values['dlm_download_dll_name']) ? $values['dlm_download_dll_name'][0] : get_the_title();
	}
	$supported_core = isset($values['dlm_download_supported_core']) ? $values['dlm_download_supported_core'][0] : '';

	//Set nonce field for saving meta data
	wp_nonce_field('dlm_axb_post_meta_box', '_dlm_axb_nonce');
	//Print meta boxes ?>
	<p>
		<label for="dlm_download_changelog"><?php _e('Changelog', 'dlm_axb'); ?>:</label>
		<textarea name="dlm_download_changelog" id="dlm_download_changelog" style="width:100%; min-height:200px;"><?php echo $changelog; ?></textarea>
	</p>
	<p>
		<label for="dlm_download_version_type"><?php _e('Version type', 'dlm_axb'); ?>:</label>
		<select name="dlm_download_version_type" id="dlm_download_version_type">
			<option value="0" <?php selected($version_type, 0); ?>><?php _e('stable', 'dlm_axb'); ?></option>
			<option value="1" <?php selected($version_type, 1); ?>><?php _e('beta', 'dlm_axb'); ?></option>
		</select>
	</p>
	<?php if(!in_array($themes_category, $term_slugs)) { ?>
	<p>
		<label for="dlm_download_platform"><?php _e('Platform', 'dlm_axb'); ?>:</label>
		<select name="dlm_download_platform" id="dlm_download_platform">
			<option value="0" <?php selected($platform, 0); ?>>x86</option>
			<option value="1" <?php selected($platform, 1); ?>>x64</option>
			<option value="2" <?php selected($platform, 2); ?>>x86/x64</option>
		</select>
	</p>
	<p>
		<label for="dlm_download_dll_name"><?php _e('Addon ID', 'dlm_axb'); ?>:</label>
		<input type="text" name="dlm_download_dll_name" id="dlm_download_dll_name" value="<?php echo $dll_name; ?>" />
	</p>
	<?php } ?>
	<p>
		<label for="dlm_download_supported_core"><?php _e('Required AQQ version', 'dlm_axb'); ?>:</label>
		<input type="text" name="dlm_download_supported_core" id="dlm_download_supported_core" value="<?php echo $supported_core; ?>" />
	</p>
	<?php
}

//Show plugins meta box
function dlm_axb_plugins_meta_box() {
	$plugins_category = get_option('dlm_axb_plugins_category'); ?>
	<ul>
		<li>
			<?php $tax_terms = get_terms('dlm_download_category'); ?>
			<label for="dlm_axb_plugins_category"><?php _e('Files category', 'dlm_axb'); ?>:</label>
			<select name="dlm_axb_plugins_category" id="dlm_axb_plugins_category">
				<option value="" <?php selected($plugins_category, ''); ?>></option>
				<?php foreach ($tax_terms as $tax_term) {
					echo '<option value="' . $tax_term->slug . '" ' . selected($plugins_category, $tax_term->slug, false) . '>' . $tax_term->name . '</option>';
				} ?>
			</select>
			</br><small><?php _e('When generating the XML file will only be included files in the specified category.', 'dlm_axb'); ?></small>
		</li>
		<li>
			<label for="dlm_axb_plugins_xml_url"><?php _e('XML file path', 'dlm_axb')?>:</label>
			</br><small><?php echo ABSPATH; ?></small><input type="text" name="dlm_axb_plugins_xml_url" id="dlm_axb_plugins_xml_url" value="<?php echo get_option('dlm_axb_plugins_xml_url', 'aqq_update/plugins.xml'); ?>" />
		</li>
	</ul>
<? }

//Show themes meta box
function dlm_axb_themes_meta_box() {
	$themes_category = get_option('dlm_axb_themes_category'); ?>
	<ul>
		<li>
			<?php $tax_terms = get_terms('dlm_download_category'); ?>
			<label for="dlm_axb_themes_category"><?php _e('Files category', 'dlm_axb'); ?>:</label>
			<select name="dlm_axb_themes_category" id="dlm_axb_themes_category">
				<option value="" <?php selected($themes_category, ''); ?>></option>
				<?php foreach ($tax_terms as $tax_term) {
					echo '<option value="' . $tax_term->slug . '" ' . selected($themes_category, $tax_term->slug, false) . '>' . $tax_term->name . '</option>';
				} ?>
			</select>
			</br><small><?php _e('When generating the XML file will only be included files in the specified category.', 'dlm_axb'); ?></small>
		</li>
		<li>
			<label for="dlm_axb_themes_xml_url"><?php _e('XML file path', 'dlm_axb')?>:</label>
			</br><small><?php echo ABSPATH; ?></small><input type="text" name="dlm_axb_themes_xml_url" id="dlm_axb_themes_xml_url" value="<?php echo get_option('dlm_axb_themes_xml_url', 'aqq_update/themes.xml'); ?>" />
		</li>
	</ul>
<?php }

//Display settings page
function dlm_axb_settings_page() {
	//Global variable
	global $dlm_axb_options_page_hook;
	//Enable add_meta_boxes function
	do_action('add_meta_boxes', $dlm_axb_options_page_hook); ?>
	<div class="wrap">
		<h2>AQQ XML Builder Add-on</h2>
		<?php //Generate XML
		if(isset($_POST['generate_xml'])) {
			//Get saved options
			$plugins_category = get_option('dlm_axb_plugins_category');
			$themes_category = get_option('dlm_axb_themes_category');
			//Generate XML for plugins files
			if(!empty($plugins_category)) {
				if(dlm_axb_generate_plugins_xml())
					echo '<div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible"><p><strong>'.__('The XML file for the plugins has been successfully generated.', 'dlm_axb').'</strong></p></div>';
				else
					echo '<div class="error settings-error notice is-dismissible" id="setting-error-settings_updated"><p><strong>'.__('Errors occurred while saving XML file for plugins. Check the plugin settings.', 'dlm_axb').'Wystąpiły problemy przy zapisie pliku XML dla wtyczek. Sprawdź ustawienia wtyczki.</strong></p></div>';
			}
			//Generate XML for themes files
			if(!empty($themes_category)) {
				if(dlm_axb_generate_themes_xml())
					echo '<div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible"><p><strong>'.__('The XML file for the themes has been successfully generated.', 'dlm_axb').'</strong></p></div>';
				else
					echo '<div class="error settings-error notice is-dismissible" id="setting-error-settings_updated"><p><strong>'.__('Errors occurred while saving XML file for themes. Check the plugin settings.', 'dlm_axb').'Wystąpiły problemy przy zapisie pliku XML dla kompozycji. Sprawdź ustawienia wtyczki.</strong></p></div>';
			}
		} ?>
		<div id="poststuff">
			<div id="post-body" class="metabox-holder">
				<div id="postbox-container" class="postbox-container">
					<form id="dlm_axb-form" method="post" action="options.php">
						<?php settings_fields('dlm_axb_settings');
						do_meta_boxes($dlm_axb_options_page_hook, 'normal', null); ?>
						<input id="submit" class="button button-primary" type="submit" name="submit" value="<?php _e('Save changes', 'dlm_axb')?>" />
						<input id="generate_xml" class="button button-secondary" type="submit" formaction="?post_type=dlm_download&page=dlm_axb_settings" name="generate_xml" value="<?php _e('Generate XML files', 'dlm_axb')?>" />
					</form>
				</div>
			</div>
		</div>
	</div>
	<?php
}

//Saving data on post update and rebuild XML
function dlm_axb_save_post($post_id) {
	//End on doing an auto save
	if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
	//Our nonce isn't there or we can't verify it
	if(!isset($_POST['_dlm_axb_nonce']) || !wp_verify_nonce($_POST['_dlm_axb_nonce'], 'dlm_axb_post_meta_box')) {
		//Rebuild XML files on custom post type
		if(get_post_type($post_id) == 'dlm_download') {
			//Get category slug from terms
			$terms = get_the_terms($post_id, 'dlm_download_category');
			$term_slugs = array();
			if($terms && ! is_wp_error($terms)) {
				foreach ($terms as $term) {
					$term_slugs[] = $term->slug;
				}
			}
			//Add post meta boxe for custom post type and category
			if((in_array(get_option('dlm_axb_plugins_category'), $term_slugs)) || (in_array(get_option('dlm_axb_themes_category'), $term_slugs))) {
				dlm_axb_generate_plugins_xml();
				dlm_axb_generate_themes_xml();
			}
		}
		//End
		return;
	}
	//Make sure data is set before trying to save it
	if(isset($_POST['dlm_download_changelog']))
		update_post_meta($post_id, 'dlm_download_changelog', $_POST['dlm_download_changelog']);
	if(isset($_POST['dlm_download_version_type']))
		update_post_meta($post_id, 'dlm_download_version_type', $_POST['dlm_download_version_type']);
	if(isset($_POST['dlm_download_platform']))
		update_post_meta($post_id, 'dlm_download_platform', $_POST['dlm_download_platform']);
	if(isset($_POST['dlm_download_dll_name']))
		update_post_meta($post_id, 'dlm_download_dll_name', $_POST['dlm_download_dll_name']);
	if(isset($_POST['dlm_download_supported_core']))
		update_post_meta($post_id, 'dlm_download_supported_core', $_POST['dlm_download_supported_core']);
	//Rebuild XML files
	dlm_axb_generate_plugins_xml();
	dlm_axb_generate_themes_xml();
}
add_action('save_post', 'dlm_axb_save_post');

//XML class
class SimpleXMLExtended extends SimpleXMLElement {
	public function addCData($cdata_text) {
		$node = dom_import_simplexml($this);
		$no = $node->ownerDocument;
		$node->appendChild($no->createCDATASection($cdata_text));
	}
}

//Generate XML for plugins files
function dlm_axb_generate_plugins_xml() {
	//Get category name
	$category = get_option('dlm_axb_plugins_category');
	//If category not set
	if(empty($category)) return false;
	//Create XML file
	$plugins_xml = new SimpleXMLExtended("<?xml version=\"1.0\" encoding=\"utf-8\" ?><plugins/>");
	$plugins_xml->addAttribute('update', current_time('Y-m-d H:i:s'));
	//Query items
	$args = array(
		'post_type' => 'dlm_download',
		'post_status' => 'publish',
		'posts_per_page' => -1,
		'tax_query' => array(
			array(
				'taxonomy' => 'dlm_download_category',
				'field' => 'slug',
				'terms' => $category,
				'operator' => 'IN'
			)
		),
		'orderby' => 'date',
		'order' => 'DESC'
	);
	$query = new WP_Query($args);
	//Start query
	if($query->have_posts()) {
		//Download monitor variable
		global $dlm_download;
		//Loop
		while($query->have_posts()) {
			$query->the_post();
			$item = $plugins_xml->addChild('item');
			$item->addAttribute('id', $query->post->ID);
			$item->addChild('name', get_the_title());
			$item->addChild('version', $dlm_download->get_the_version_number());
			$version_type = get_post_meta($query->post->ID, 'dlm_download_version_type', true);
			if($version_type == 1) $version_type = 'beta';
			else $version_type = 'stable';
			$item->addChild('version-type', $version_type);
			$item->addChild('supported-core', get_post_meta($query->post->ID, 'dlm_download_supported_core', true));
			$item->addChild('updated-date', get_the_date('Y.m.d, H:i'));
			$item->addChild('download-url', $dlm_download->get_the_download_link());
			$item->addChild('file-name', $dlm_download->get_the_filename());
			$platforms = $item->addChild('platforms');
			$platform = get_post_meta($query->post->ID, 'dlm_download_platform', true);
			if(empty($platform)) $platform = 2;
			if($platform == 0) $platforms->addChild('x86');
			if($platform == 1) $platforms->addChild('x64');
			if($platform == 2) {
				$platforms->addChild('x86');
				$platforms->addChild('x64');
			}
			$dll_name = get_post_meta($query->post->ID, 'dlm_download_dll_name', true);
			if(empty($dll_name)) $dll_name = get_the_title();
			$item->addChild('dll-name', $dll_name . '.dll');
			$item->lastchanges = null;
			$item->lastchanges->addCData(str_replace("\r", "", get_post_meta($query->post->ID, 'dlm_download_changelog', true)));
		}
	}
	//End query
	wp_reset_postdata();
	//Get XML url for plugins
	$plugins_xml_url = ABSPATH . get_option('dlm_axb_plugins_xml_url', 'aqq_update/plugins.xml');
	//Save XML to file
	if($plugins_xml->saveXML($plugins_xml_url)) $success = true;
	else $success = false;
	//Return success or fail
	return $success;
}

//Generate XML for themes files
function dlm_axb_generate_themes_xml() {
	//Get category name
	$category = get_option('dlm_axb_themes_category');
	//If category not set
	if(empty($category)) return false;
	//Create XML file
	$themes_xml = new SimpleXMLExtended("<?xml version=\"1.0\" encoding=\"utf-8\" ?><themes/>");
	$themes_xml->addAttribute('update', current_time('Y-m-d H:i:s'));
	//Query items
	$args = array(
		'post_type' => 'dlm_download',
		'post_status' => 'publish',
		'posts_per_page' => -1,
		'tax_query' => array(
			array(
				'taxonomy' => 'dlm_download_category',
				'field' => 'slug',
				'terms' => $category,
				'operator' => 'IN'
			)
		),
		'orderby' => 'date',
		'order' => 'DESC'
	);
	$query = new WP_Query($args);
	//Start query
	if($query->have_posts()) {
		//Download monitor variable
		global $dlm_download;
		//Loop
		while($query->have_posts()) {
			$query->the_post();
			$item = $themes_xml->addChild('item');
			$item->addAttribute('id', $query->post->ID);
			$item->addChild('name', get_the_title());
			$item->addChild('version', $dlm_download->get_the_version_number());
			$version_type = get_post_meta($query->post->ID, 'dlm_download_version_type', true);
			if($version_type == 1) $version_type = 'beta';
			else $version_type = 'stable';
			$item->addChild('version-type', $version_type);
			$item->addChild('supported-core', get_post_meta($query->post->ID, 'dlm_download_supported_core', true));
			$item->addChild('updated-date', get_the_date('Y.m.d, H:i'));
			$item->addChild('download-url', $dlm_download->get_the_download_link());
			$item->addChild('file-name', $dlm_download->get_the_filename());
			$item->lastchanges = null;
			$item->lastchanges->addCData(str_replace("\r", "", get_post_meta($query->post->ID, 'dlm_download_changelog', true)));
		}
	}
	//End query
	wp_reset_postdata();
	//Get XML url for themes
	$themes_xml_url = ABSPATH . get_option('dlm_axb_themes_xml_url', 'aqq_update/themes.xml');
	//Save XML to file
	if($themes_xml->saveXML($themes_xml_url)) $success = true;
	else $success = false;
	//Return success or fail
	return $success;
}

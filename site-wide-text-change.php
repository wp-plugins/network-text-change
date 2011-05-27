<?php
/*
Plugin Name:Site Wide Text Change Lite
Version: 2.0.2
Plugin URI: http://premium.wpmudev.org/project/site-wide-text-change
Description: Would you like to be able to change any wording, anywhere in the entire admin area on your whole site? Without a single hack? Well, if that's the case then this plugin is for you!
Author: Barry at caffeinatedb.com, Ulrich Sossou (incsub)
Author URI: http://premium.wpmudev.org/
Network: true
Text Domain: sitewidetext
WDP ID: 227
*/

/*
Copyright 2007-2011 Incsub (http://incsub.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

// Un comment for full belt and braces replacements, warning:
// 1. TEST TEST TEST
// define( 'SWTC-BELTANDBRACES', 'yes' );

require_once( 'sitewidetextincludes/classes/functions.php' );

// Set up my location
set_swt_url( __FILE__ );
set_swt_dir( __FILE__ );

/**
 * Plugin main class
 **/
class Site_Wide_Text_Change {

	/**
	 * Current version of the plugin
	 **/
	var $build = '2.0.2';

	/**
	 * Stores translation tables
	 **/
	var $translationtable = false;

	/**
	 * Stores translations
	 **/
	var $translationops = false;

	/**
	 * PHP 4 constructor
	 **/
	function Site_Wide_Text_Change() {
		$this->__construct();
	}

	/**
	 * PHP 5 constructor
	 **/
	function __construct() {
		add_action('admin_init', array(&$this, 'add_admin_header_sitewide'));

		add_action( 'admin_menu', array( &$this, 'pre_3_1_network_admin_page' ) );
		add_action( 'network_admin_menu', array( &$this, 'network_admin_page' ) );

		add_filter('gettext', array(&$this, 'replace_text'), 10, 3);

		add_action( 'plugins_loaded', array(&$this, 'load_textdomain'));

		if( defined('SWTC-BELTANDBRACES') ) {
			add_action('init', array(&$this, 'start_cache'), 1);
			add_action('admin_print_footer_scripts', array(&$this, 'end_cache'), 999);
		}

	}

	/**
	 * Show admin warning
	 **/
	function warning() {
		echo '<div id="update-nag">' . __('Warning, this page is not loaded with the full replacements processed.','sitewidetext') . '</div>';
	}

	/**
	 * Load text domain
	 **/
	function load_textdomain() {

		// load text domain
		if ( defined( 'WPMU_PLUGIN_DIR' ) && file_exists( WPMU_PLUGIN_DIR . '/site-wide-text-change.php' ) ) {
			load_muplugin_textdomain( 'sitewidetext', 'sitewidetextincludes' );
		} else {
			load_plugin_textdomain( 'sitewidetext', false, dirname( plugin_basename( __FILE__ ) ) . '/sitewidetextincludes' );
		}

	}

	/**
	 * Run before admin page display
	 *
	 * Enqueue scripts, remove output buffer and save settings
	 **/
	function add_admin_header_sitewide() {
		global $plugin_page;

		if( 'sitewidetext_admin' !== $plugin_page )
			return;

		wp_enqueue_style('sitewidecss', swt_url('sitewidetextincludes/styles/sitewide.css'), array(), $this->build);
		wp_enqueue_script('sitewidejs', swt_url('sitewidetextincludes/js/sitewideadmin.js'), array('jquery', 'jquery-form', 'jquery-ui-sortable'), $this->build);

		if(defined('SWTC-BELTANDBRACES')) {
			add_action('admin_notices', array(&$this, 'warning'));

			//remove other actions
			remove_action('init', array(&$this, 'start_cache'));
			remove_action('admin_print_footer_scripts', array(&$this, 'end_cache'));
		}

		$this->update_admin_page();
	}

	/**
	 * Add network admin page
	 **/
	function network_admin_page() {
		add_submenu_page( 'settings.php', __( 'Text Change', 'sitewidetext' ), __( 'Text Change', 'sitewidetext' ), 'manage_network_options', 'sitewidetext_admin', array( &$this, 'handle_admin_page' ) );
	}

	/**
	 * Add network admin page the old way
	 **/
	function pre_3_1_network_admin_page() {
		add_submenu_page( 'ms-admin.php', __( 'Text Change', 'sitewidetext' ), __( 'Text Change', 'sitewidetext' ), 'manage_network_options', 'sitewidetext_admin', array( &$this, 'handle_admin_page' ) );
	}

	/**
	 * Individual replace table output
	 **/
	function show_table($key, $table) {

		echo '<div class="postbox " id="swtc-' . $key . '">';

		echo '<div title="Click to toggle" class="handlediv"><br/></div><h3 class="hndle"><input type="checkbox" name="deletecheck[]" class="deletecheck" value="' . $key . '" /><span>' . $table['title'] . '</span></h3>';
		echo '<div class="inside">';

		echo "<table width='100%'>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Find this text','sitewidetext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='swtble[$key][find]' value='" . esc_attr(stripslashes($table['find'])) . "' class='long find' />";
		echo "<br/>";
		echo "<input type='checkbox' name='swtble[$key][ignorecase]' class='case' value='1' ";
		if($table['ignorecase'] == '1') echo "checked='checked' ";
		echo "/>&nbsp;<span>" . __('Ignore case when replacing text.','sitewidetext') . "</span>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('in this text domain','sitewidetext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='swtble[$key][domain]' value='" . esc_attr(stripslashes($table['domain'])) . "' class='short domain' />";
		echo "&nbsp;<span>" . __('( leave blank for global changes )','sitewidetext') , '</span>';
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('and replace it with','sitewidetext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='swtble[$key][replace]' value='" . esc_attr(stripslashes($table['replace'])) . "' class='long replace' />";
		echo "</td>";
		echo "</tr>";

		echo "</table>";

		echo '</div>';

		echo '</div>';

	}

	/**
	 * Individual replace table output for javascript use
	 **/
	function show_table_template() {

		echo '<div class="postbox blanktable" id="blanktable" style="display: none;">';

		echo '<div title="Click to toggle" class="handlediv"><br/></div><h3 class="hndle"><input type="checkbox" name="deletecheck[]" class="deletecheck" value="" /><span>New Text Change Rule</span></h3>';
		echo '<div class="inside">';

		echo "<table width='100%'>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('Find this text','sitewidetext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='swtble[][find]' value='' class='long find' />";
		echo "<br/>";
		echo "<input type='checkbox' name='swtble[][ignorecase]' class='case' value='1' ";
		echo "/>&nbsp;<span>" . __('Ignore case when finding text.','sitewidetext') . "</span>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('in this text <abbr title="A text domain is related to the internationisation of the text, you should leave this blank unless you know what it means.">domain</abbr>','sitewidetext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='swtble[][domain]' value='' class='short domain' />";
		echo "&nbsp;<span>" . __('( leave blank for global changes )','sitewidetext') , '</span>';
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td valign='top' class='heading'>";
		echo __('and replace it with','sitewidetext');
		echo "</td>";
		echo "<td valign='top' class=''>";
		echo "<input type='text' name='swtble[][replace]' value='' class='long replace' />";
		echo "</td>";
		echo "</tr>";

		echo "</table>";

		echo '</div>';

		echo '</div>';

	}

	/**
	 * Save admin settings
	 **/
	function update_admin_page() {

		if(isset($_POST['action']) && addslashes($_POST['action']) == 'sitewide') {

			check_admin_referer('sitewidetext');

			if(!empty($_POST['delete'])) {
				$deletekeys = (array) $_POST['deletecheck'];
			} else {
				$deletekeys = array();
			}

			if (empty($deletekeys)) {
				// Check for limit
				$ops = get_site_option('translation_table');
				if (count($ops) > 4 || count($_POST['swtble']) > 4) {
					wp_redirect( add_query_arg( array( 'warn' => 1), remove_query_arg(array('msg'), wp_get_referer()) ) );
					exit();
				}
			}

			if(!empty($_POST['swtble'])) {
				$save = array();
				$op = array();
				foreach($_POST['swtble'] as $key => $table) {
					if(!in_array($key, $deletekeys) && !empty($table['find'])) {
						$save[addslashes($key)]['title'] = 'Text Change : ' . htmlentities($table['find'],ENT_QUOTES, 'UTF-8');
						$save[addslashes($key)]['find'] = $table['find'];
						$save[addslashes($key)]['ignorecase'] = $table['ignorecase'];
						$save[addslashes($key)]['domain'] = $table['domain'];
						$save[addslashes($key)]['replace'] = $table['replace'];

						if($table['ignorecase'] == '1') {
							$op['domain-' . $table['domain']]['find'][] = '/' . stripslashes($table['find']) . '/i';
						} else {
							$op['domain-' . $table['domain']]['find'][] = '/' . stripslashes($table['find']) . '/';
						}
						$op['domain-' . $table['domain']]['replace'][] = stripslashes($table['replace']);

					}

				}

				if(!empty($op)) {
					update_site_option('translation_ops',$op);
					update_site_option('translation_table',$save);
				} else {
					update_site_option('translation_ops', 'none');
					update_site_option('translation_table', 'none');
				}
			}

			wp_safe_redirect( add_query_arg( array( 'msg' => 1), wp_get_referer() ) );

		}

	}

	/**
	 * Admin page output
	 **/
	function handle_admin_page() {

		$messages = array();
		$messages[1] = __('Your Settings have been updated.', 'sidewidetext');

		$translations = $this->get_translation_table(true);

		echo "<div class='wrap'>";

		// Show the heading
		echo '<div class="icon32" id="icon-tools"><br/></div>';
		echo "<h2>" . __('Text Change Settings','sitewidetext') . "</h2>";

		if ( isset($_GET['msg']) ) {
			echo '<div id="message" class="updated"><p>' . $messages[(int) $_GET['msg']];
			echo '</p></div>';
			$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
		}

		if (isset($_GET['warn'])) {
			echo "<div class='error'><p><a title='Upgrade Now' href='http://premium.wpmudev.org/project/site-wide-text-change'>You have reached the limit for this version. Upgrade to Pro version for unlimited number of text change rules.</a></p></div>";
			$_SERVER['REQUEST_URI'] = remove_query_arg(array('warn'), $_SERVER['REQUEST_URI']);
		}
		echo "<div class='error'><p><a title='Upgrade Now' href='http://premium.wpmudev.org/project/site-wide-text-change'>In this version, the number of text change rules is limited. Upgrade to Pro version for unlimited number of text change rules.</a></p></div>";

		echo "<form action='' method='post'>";
		echo "<input type='hidden' name='action' value='sitewide' />";

		wp_nonce_field( 'sitewidetext' );

		echo '<div class="tablenav">';
		echo '<div class="alignleft">';
		echo '<input class="button-secondary delete save" type="submit" name="save" value="' . __('Save all settings', 'sitewidetext') . '" />';
		echo '<input class="button-secondary del" type="submit" name="delete" value="' . __('Delete selected', 'sitewidetext') . '" />';
		echo '</div>';

		echo '<div class="alignright">';
		echo '<input class="button-secondary addnew" type="submit" name="add" value="' . __('Add New', 'sitewidetext') . '" />';
		echo '</div>';

		echo '</div>';

		echo "<div id='entryholder'>";

		if($translations && is_array($translations)) {

			foreach($translations as $key => $table) {

				$this->show_table($key, $table);

			}

		} else {

			echo "<p style='padding: 10px;' id='holdingtext'>";

			echo __('You do not have any text change rules entered. Click on the Add New button on the right hand side to add a new rule.','sitewidetext');

			echo "</p>";

		}

		echo "</div>";	// Entry holder

		echo '<div class="tablenav">';
		echo '<div class="alignleft">';
		echo '<input class="button-secondary delete save" type="submit" name="save" value="' . __('Save all settings', 'sitewidetext') . '" />';
		echo '<input class="button-secondary del" type="submit" name="delete" value="' . __('Delete selected', 'sitewidetext') . '" />';
		echo '</div>';

		echo '<div class="alignright">';
		echo '<input class="button-secondary addnew" type="submit" name="add" value="' . __('Add New', 'sitewidetext') . '" />';
		echo '</div>';

		echo '</div>';

		echo "</form>";

		$this->show_table_template();

		echo "</div>";	// wrap

	}

	/**
	 * Cache translation tables
	 **/
	function get_translation_table($reload = false) {

		if($this->translationtable && !$reload) {
			return $this->translationtable;
		} else {
			$this->translationtable = get_site_option('translation_table', array());
			return $this->translationtable;
		}

	}

	/**
	 * Cache translations
	 **/
	function get_translation_ops($reload = false) {

		if($this->translationops && !$reload) {
			return $this->translationops;
		} else {
			$this->translationops = get_site_option( 'translation_ops', array() );
			return $this->translationops;
		}

	}

	/**
	 * Replace text
	 **/
	function replace_text( $transtext, $normtext, $domain ) {
		$tt = $this->get_translation_ops();

		if( !is_array( $tt ) )
			return $transtext;

		$toprocess = array();
		if( isset( $tt['domain-' . $domain]['find'] ) && isset( $tt['domain-']['find'] ) )
			$toprocess =  (array) $tt['domain-' . $domain]['find'] + (array) $tt['domain-']['find'];
		elseif( isset( $tt['domain-' . $domain]['find'] ) )
			$toprocess =  (array) $tt['domain-' . $domain]['find'];
		elseif( isset( $tt['domain-']['find'] ) )
			$toprocess =  (array) $tt['domain-']['find'];

		$toreplace = array();
		if( isset( $tt['domain-' . $domain]['replace'] ) && isset( $tt['domain-']['replace'] ) )
			$toreplace =  (array) $tt['domain-' . $domain]['replace'] + (array) $tt['domain-']['replace'];
		elseif( isset( $tt['domain-' . $domain]['replace'] ) )
			$toreplace =  (array) $tt['domain-' . $domain]['replace'];
		elseif( isset( $tt['domain-']['replace'] ) )
			$toreplace =  (array) $tt['domain-']['replace'];

		$transtext = preg_replace( $toprocess, $toreplace, $transtext );

		return $transtext;
	}

	/**
	 * Start output buffer
	 **/
	function start_cache() {
		ob_start();
	}

	/**
	 * End output buffer
	 **/
	function end_cache() {
		$tt = $this->get_translation_ops();

		if( !is_array( $tt ) ) {
			ob_end_flush();
		} else {
			$content = ob_get_contents();

			$toprocess = (array) $tt['domain-']['find'];
			$toreplace = (array) $tt['domain-']['replace'];

			$content = preg_replace( $toprocess, $toreplace, $content );

			ob_end_clean();
			echo $content;
		}
	}

}

if( is_admin() )
	$swtc =& new Site_Wide_Text_Change();

/**
 * Show notification if WPMUDEV Update Notifications plugin is not installed
 **/
if ( !function_exists( 'wdp_un_check' ) ) {
	add_action( 'admin_notices', 'wdp_un_check', 5 );
	add_action( 'network_admin_notices', 'wdp_un_check', 5 );

	function wdp_un_check() {
		if ( !class_exists( 'WPMUDEV_Update_Notifications' ) && current_user_can( 'edit_users' ) )
			echo '<div class="error fade"><p>' . __('Please install the latest version of <a href="http://premium.wpmudev.org/project/update-notifications/" title="Download Now &raquo;">our free Update Notifications plugin</a> which helps you stay up-to-date with the most stable, secure versions of WPMU DEV themes and plugins. <a href="http://premium.wpmudev.org/wpmu-dev/update-notifications-plugin-information/">More information &raquo;</a>', 'wpmudev') . '</p></div>';
	}
}

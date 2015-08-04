<?php
/**
 * Plugin Name: BBConnect Quicklinks
 * Plugin URI: n/a
 * Description: An addon to provide a quicklink framework for BBConnect
 * Version: 0.0.1
 * Author: Brown Box
 * Author URI: http://brownbox.net.au
 * License: Proprietary Brown Box
 */
include_once('classes/abstract.class.php');

function bbconnect_quicklinks_init() {
    if (!defined('BBCONNECT_VER')) {
        add_action('admin_init', 'bbconnect_quicklinks_deactivate');
        add_action('admin_notices', 'bbconnect_quicklinks_deactivate_notice');
    } else {
        $class_dir = dirname(__FILE__).'/classes/';
        bbconnect_quicklinks_recursive_include($class_dir);
    }
}
add_action('plugins_loaded', 'bbconnect_quicklinks_init');

function bbconnect_quicklinks_deactivate() {
    deactivate_plugins(plugin_basename( __FILE__ ));
}

function bbconnect_quicklinks_deactivate_notice() {
    echo '<div class="updated"><p><strong>BBConnect Quicklinks</strong> has been <strong>deactivated</strong> as it requires BB Connect.</p></div>';
    if (isset( $_GET['activate']))
        unset( $_GET['activate']);
}

function bbconnect_quicklinks_recursive_include($dir_name, $parent_dir = '') {
    $dir = opendir($dir_name);
    $files = array();
    while (false !== ($filename = readdir($dir))) {
        if ($filename == '.' || $filename == '..') {
            continue;
        }

        if (is_dir($dir_name.$filename)) {
            bbconnect_quicklinks_recursive_include($dir_name.$filename.'/', $dir_name);
        }

        if (strpos($filename, '.php') !== false) {
            include_once($dir_name.$filename);
            if (strpos($filename, 'abstract') === false) {
                $quicklink_prefix = !empty($dir_name) ? str_replace('/', '_', str_replace(dirname(__FILE__).'/classes/', '', $dir_name)) : '';
                $quicklink_name = $quicklink_prefix.array_shift(explode('.', $filename)).'_quicklink';
                add_action('wp_ajax_'.$quicklink_name.'_submit', array($quicklink_name, 'post_submission'));
            }
        }
    }
}

function bbconnect_show_quicklinks($dirname, array $user_ids, array $args = array()) {
?>
<style>
/* Quicklinks */
#quicklinks-wrapper { background: #fff none repeat scroll 0 0; border: 1px solid rgba(0, 0, 0, 0.2); display: block; margin-bottom: 0.5rem; min-height: 1.5rem; min-width: 100px; padding: 1.375rem 0.375rem 0.25rem; position: relative; right: 0;	top: 0; width: 98%;}
#quicklinks-wrapper > strong { box-sizing: border-box; float: left; font-size: 8px; left: 0; padding-left: 5px; position: absolute; top: 0; width: 100%;}
#bbconnect #quicklinks-wrapper ul {white-space: nowrap;}
#bbconnect #quicklinks-wrapper li a { background: #eee none repeat scroll 0 0; color: #222; font-size: 0.675rem !important; font-weight: bold; margin: 0.25rem 0; padding: 0.125rem 0.25rem; text-decoration: none; width: auto;}
#bbconnect #quicklinks-wrapper li { display: inline; float: none;}
#bbconnect #quicklinks-wrapper li a.s-quicklinks:hover { background: #ccc none repeat scroll 0 0;}
</style>
<?php

$current_user = wp_get_current_user();
$roles = $current_user->roles;
if( $roles[0] !== 'content_manager' ) {

?>
		<div id="quicklinks-wrapper"><strong>QUICKLINKS</strong>
			<ul>
<?php
    $class_dir = dirname(__FILE__).'/classes/'.$dirname.'/';
    $dir = opendir($class_dir);
    while (false !== ($filename = readdir($dir))) {
        if ($filename == '.' || $filename == '..') continue;
        $files[] = $filename;
    }
    closedir($dir);
    sort($files);

    foreach ($files as $filename) {
        if (strpos($filename, '.php') !== false) {
            $quicklink_name = $dirname.'_'.array_shift(explode('.', $filename)).'_quicklink';
            if (class_exists($quicklink_name)) {
                $quicklink = new $quicklink_name();
                $quicklink->show_link($user_ids, $args);
            }
        }
    }
?>
	  		</ul>
		</div>

<?php
    }
}

function bbconnect_report_quicklinks(array $user_ids, array $args = array()) {
    return bbconnect_show_quicklinks('reports', $user_ids, $args);
}

function bbconnect_profile_quicklinks($user_id) {
    return bbconnect_show_quicklinks('profile', array($user_id));
}
<?php

/**
 * The plugin bootstrap file
 *
 * @package pluginette-todo-list
 * @link    https://pluginette.com
 * @since   1.0
 *
 * @wordpress-plugin
 * Plugin Name:       ToDo Block
 * Description:       ToDo List Block for Gutenberg.
 * Requires at least: 6.3
 * Requires PHP:      7.0
 * Version:           1.1.1
 * Author:            David Towoju
 * Author URI:        https://pluginette.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       pluginette-todo-list
 */

if (!defined('TDB_NAME')) {
  define('TDB_NAME', 'pluginette/todo-block-item');
}

if (!defined('TDB_VERSION')) {
  define('TDB_VERSION', '1.1.1');
}

if (!defined('TDB_CHECKLIST_BLOCK')) {
  define('TDB_CHECKLIST_BLOCK', 'pluginette/todo-block-list');
}

if (!defined('TDB_PATH')) {
  define('TDB_PATH', plugin_dir_path(__FILE__));
}

if (!defined('PGNT_META_NAME')) {
  define('PGNT_META_NAME', 'pgnt_checklist_'.get_current_user_id());
}

if (!defined('PGNT_META_NAME')) {
  define('PGNT_META_NAME', 'pgnt_checklist_'.get_current_user_id());
}

// Load the core plugin class that contains all hooks.
require plugin_dir_path(__FILE__) . 'class-db.php';
require plugin_dir_path(__FILE__) . 'class-todo-block.php';

$todo = new TDBCore();
$todo->run_hooks();

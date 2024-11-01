<?php

/**
 * This file defines the core plugin class
 *
 * @link       davidtowoju@gmail.com
 * @since      1.0
 *
 * @package pluginette-todo-list
 */

/**
 * The core plugin class.
 *
 * @since   1.0
 * @package pluginette-todo-list
 * @author  David Towoju <hello@pluginette.com>
 */
class TDBCore extends TDBDatabase
{

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	public $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string    $version    The current version of the plugin.
	 */
	public $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since 1.0
	 */
	public function __construct()
	{
		global $wpdb;

		$this->version     = TDB_VERSION;
		$this->plugin_name = 'pluginette-todo-list';
		$this->table_name  = $wpdb->prefix . 'todo_block';
		$this->primary_key = 'id';
	}


	/**
	 * Register all of the hooks related to both admin & public area
	 *
	 * @since  1.0
	 * @access private
	 */
	public function run_hooks()
	{
		add_action('plugins_laoded', array($this, 'set_locale'));
		add_action('admin_init', array($this, 'create_table'));
		add_action('init', array($this, 'todo_list_block_init'));
		add_filter('render_block', array($this, 'add_input_to_list_items'), 10, 2);
		add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
		add_action('wp_ajax_update_checkbox_state', [$this, 'update_checkbox_state_callback']);
		add_action('rest_api_init', [$this, 'register_custom_endpoint']);

	}

	function register_custom_endpoint() {
    register_rest_route('custom/v1', '/process-uuids', array(
			'methods' => 'POST',
			'callback' => [$this, 'process_uuids_callback'],
			'permission_callback' => function () {
				return current_user_can('edit_posts');
			},
    ));
	}

	function process_uuids_callback($request) {
    // $fields = $request->get_json_params(); // Get the UUIDs sent in the request
    $fields = $request->get_param('fields'); // Get the UUIDs sent in the request
    // Process the UUIDs here (example: save to database, perform actions, etc.)
    // Return a response if needed
    return rest_ensure_response('UUIDs received successfully');
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * @since  1.0
	 * @access private
	 */
	private function set_locale()
	{
		load_plugin_textdomain(
			'pluginette-todo-list',
			false,
			plugin_dir_path(__FILE__) . '/languages/'
		);
	}

	/**
	 * Registers the block using the metadata loaded from the `block.json` file.
	 *
	 * @return void
	 */
	public function todo_list_block_init()
	{
		register_block_type(plugin_dir_path(__FILE__) . 'blocks/todo-item/');
		register_block_type(plugin_dir_path(__FILE__) . 'blocks/todo-list/');
	}

	/**
	 * Add input checkbox to the list on the frontend
	 *
	 * @param  array $block_content the block conent.
	 * @param  array $block the block array.
	 * @return array
	 */
	public function add_input_to_list_items($block_content, $block)
	{
		if (!is_admin() && TDB_CHECKLIST_BLOCK === $block['blockName']) {

			if (!isset($block['innerBlocks']) || !is_array($block['innerBlocks'])) {
				return;
			}

			$enableSave   = isset($block['attrs']['enableSave']) && true === $block['attrs']['enableSave'];
			$fieldName   = isset($block['attrs']['fieldName']) ? sanitize_key($block['attrs']['fieldName']) : '';
			$meta = '';

			// try to get meta
			$post_id = get_the_ID();
			if (absint($post_id) > 1) {
				$meta = get_post_meta($post_id, 'tdb_' . $post_id . '_' . get_current_user_id(), true);
			} else {
				$enableSave = false;
			}

			$block_content = '<div>';
			foreach ($block['innerBlocks'] as $key => $block) {
				$rows = $this->get_where([
					'post_id' => $post_id,
					'field' => $block['attrs']['uuid'],
					'checked' => 'true'
				]);
				$block_content .= $this->render_list_item($block['innerHTML'], $block, $enableSave, $fieldName, $meta, $rows);
			}
			$block_content .= '<input type="hidden" id="tdb_nonce_input_id" value="' . wp_create_nonce('update_checkbox_state_nonce') . '">';
			$block_content .= '<input type="hidden" id="tdb_post_id" value="' . get_the_ID() . '">';

			$block_content .= '</div>';
		}

		return $block_content;
	}

	/**
	 * Add input checkbox to the list on the frontend
	 *
	 * @param  array $block_content the block conent.
	 * @param  array $block the block array.
	 * @return array
	 */
	public function render_list_item($block_content, $block, $enableSave, $fieldName, $meta, $rows)
	{
		$checked   = isset($block['attrs']['checked']) && true === $block['attrs']['checked'] ? true : false;
		$read_only = isset($block['attrs']['toggleReadOnly']) && true === $block['attrs']['toggleReadOnly'] ? 'true' : 'false';
		$disabled  = isset($block['attrs']['toggleDisable']) && true === $block['attrs']['toggleDisable'] ? 'true' : 'false';

		if ($enableSave) {
			$input = sprintf(
				'<input class="wp-block-pluginette-todo-input" type="checkbox" value="%s" x-model="checked" @change="toggle" />',
				$block['attrs']['uuid']
			);
			$content  = '<div class="wp-block-pluginette-todo-block-item-wrapper" x-data="todo" x-init="checked = ' . (!empty($rows) ? 'true' : 'false') . '">';
		} else {
			$input = sprintf(
				'<input class="wp-block-pluginette-todo-input" type="checkbox" value="1" %s %s %s />',
				checked(1, $checked, false),
				'true' === $read_only ? 'data-readonly="true" onclick="this.checked=!this.checked;"' : '',
				'true' === $disabled ? 'disabled=disabled' : ''
			);
			$content  = '<div class="wp-block-pluginette-todo-block-item-wrapper">';
		}

		$content .= $input;
		$content .= $block_content;
		$content .= '</div>';

		$block_content = apply_filters('todolists_add_checkbox', $content, $block_content, $checked);
		// }
		return $block_content;
	}

	public function update_checkbox_state_callback()
	{
		global $post;

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			wp_send_json_error('Bad request');
		}

		check_ajax_referer('update_checkbox_state_nonce', 'nonce');

		$post_id = isset($_POST['post_id']) ? wp_unslash(absint($_POST['post_id'])) : '';
		$checkbox_name = isset($_POST['value']) ? wp_unslash(sanitize_key($_POST['value'])) : '';
		$status = isset($_POST['checked']) ? wp_unslash(sanitize_text_field($_POST['checked'])) : 'false';
		$user_id = get_current_user_id();

		if (empty($user_id) || empty($post_id) || empty($checkbox_name)) {
			wp_send_json_error();
		}

		$blog_id = get_current_blog_id();

		// does record exists
		$record = $this->get_where([
			// 'user_id' => $user_id,
			'post_id' => $post_id,
			'blog_id' => $blog_id,
			'field' => $checkbox_name,
		]);

		if($record){
			// only author can edit
			if($record->user_id != $user_id){
				wp_send_json_error();
			}
			$this->update($record->id, ['checked' => $status]);
			wp_send_json_success();
		}

		$this->insert([
			'user_id' => $user_id,
			'post_id' => $post_id,
			'blog_id' => $blog_id,
			'field' => $checkbox_name,
			'checked' => $status
		]);

		wp_send_json_success();
	}

	public function enqueue_scripts()
	{
		if (is_singular() && has_block(TDB_NAME)) {
			wp_enqueue_script('alpine', plugins_url('/assets/alpine.js', __FILE__), [], 3, [
				'strategy'  => 'defer',
			]);
			wp_enqueue_script('todo-block', plugins_url('/assets/script.js', __FILE__), ['wp-util']);
			// wp_enqueue_script( 'wp-util' );
		}
	}

	/**
	 * Get default column values
	 *
	 * @access  public
	 * @since   1.0
	 */
	public function get_column_defaults()
	{
		return array(
			'id' => 0,
			'post_id'    => '',
			'user_id'       => '',
			'blog_id'       => 0,
			'field'      => '',
			'checked'      => true,
			'created_at'        => gmdate('Y-m-d H:i:s'),
		);
	}

	/**
	 * Get columns and formats
	 *
	 * @access  public
	 * @since   1.0
	 */
	public function get_columns()
	{
		return array(
			'id'    => '%d',
			'post_id' => '%d',
			'user_id' => '%d',
			'blog_id' => '%d',
			'field'    => '%s',
			'checked'    => '%s',
			'created_at' => '%s',
		);
	}

	/**
	 * Create the table
	 *
	 * @access  public
	 * @since   1.0
	 */
	public function create_table()
	{
		global $wpdb;
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		// delete_option( $this->table_name . '_db_version' );
		$db_version = get_option($this->table_name . '_db_version');

		if ($db_version >= TDB_VERSION) {
			return;
		}

		$sql = "CREATE TABLE " . $this->table_name . " (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		user_id bigint(20),
		post_id bigint(20) NOT NULL,
		blog_id bigint(20) NOT NULL,
		field varchar(30) NOT NULL,
		checked varchar(30) NOT NULL,
		created_at datetime NOT NULL,
		PRIMARY KEY  (id),
		KEY `field` (`field`),
		KEY `user_id` (`user_id`),
		KEY `checked` (`checked`),
		KEY `post_id` (`post_id`)
		) CHARACTER SET utf8 COLLATE utf8_general_ci;";

		dbDelta($sql);

		update_option($this->table_name . '_db_version', $this->version);
	}

	/**
	 * The name of the plugin
	 *
	 * @since  1.0
	 * @return string    The name of the plugin.
	 */
	public function get_plugin_name()
	{
		return $this->plugin_name;
	}


	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since  1.0
	 * @return string    The version number of the plugin.
	 */
	public function get_version()
	{
		return $this->version;
	}
}

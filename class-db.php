<?php

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * DB base class
 *
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.1
 */
abstract class TDBDatabase
{

  /**
   * The name of our database table
   *
   * @access  public
   * @since   2.1
   */
  public $table_name;

  /**
   * The version of our database table
   *
   * @access  public
   * @since   2.1
   */
  public $version;

  /**
   * The name of the primary column
   *
   * @access  public
   * @since   2.1
   */
  public $primary_key;

  /**
   * Get things started
   *
   * @access  public
   * @since   2.1
   */
  public function __construct()
  {
  }

  /**
   * Whitelist of columns
   *
   * @access  public
   * @since   2.1
   * @return  array
   */
  public function get_columns()
  {
    return array();
  }

  /**
   * Default column values
   *
   * @access  public
   * @since   2.1
   * @return  array
   */
  public function get_column_defaults()
  {
    return array();
  }

  /**
   * Retrieve a row by the primary key
   *
   * @access  public
   * @since   2.1
   * @return  object
   */
  public function get($row_id)
  {
    global $wpdb;
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM $this->table_name WHERE $this->primary_key = %s LIMIT 1;", $row_id));
  }

  /**
   * Retrieve a row by a specific column / value
   *
   * @access  public
   * @since   2.1
   * @return  object
   */
  public function get_by($column, $row_id)
  {
    global $wpdb;
    $column = esc_sql($column);
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM $this->table_name WHERE $column = %s LIMIT 1;", $row_id));
  }

  /**
   * Retrieve a specific column's value by the primary key
   *
   * @access  public
   * @since   2.1
   * @return  string
   */
  public function get_column($column, $row_id)
  {
    global $wpdb;
    $column = esc_sql($column);
    return $wpdb->get_var($wpdb->prepare("SELECT $column FROM $this->table_name WHERE $this->primary_key = %s LIMIT 1;", $row_id));
  }

  /**
   * Retrieve a specific column's value by the the specified column / value
   *
   * @access  public
   * @since   2.1
   * @return  string
   */
  public function get_column_by($column, $column_where, $column_value)
  {
    global $wpdb;
    $column_where = esc_sql($column_where);
    $column       = esc_sql($column);
    return $wpdb->get_var($wpdb->prepare("SELECT $column FROM $this->table_name WHERE $column_where = %s LIMIT 1;", $column_value));
  }

  /**
   * Retrieve a row by multiple columns / values
   *
   * @access  public
   * @return  object
   */
  public function get_where($conditions)
  {
    global $wpdb;

    // Prepare the WHERE clause
    $where = '';
    $params = array();
    foreach ($conditions as $column => $value) {
      $column = esc_sql($column);
      $value = esc_sql($value);
      $where .= "$column = %s AND ";
      $params[] = $value;
    }
    $where = rtrim($where, ' AND ');

    // Prepare the SQL query
    $sql = $wpdb->prepare("SELECT * FROM $this->table_name WHERE $where", $params);

    return $wpdb->get_row($sql);
  }

  /**
   * Insert a new row
   *
   * @access  public
   * @since   2.1
   * @return  int
   */
  public function insert($data, $type = '')
  {
    global $wpdb;

    // Set default values
    $data = wp_parse_args($data, $this->get_column_defaults());

    do_action('tdb_pre_insert_' . $type, $data);

    // Initialise column format array
    $column_formats = $this->get_columns();

    // Force fields to lower case
    $data = array_change_key_case($data);

    // White list columns
    $data = array_intersect_key($data, $column_formats);

    // Reorder $column_formats to match the order of columns given in $data
    $data_keys = array_keys($data);
    $column_formats = array_merge(array_flip($data_keys), $column_formats);

    $wpdb->insert($this->table_name, $data, $column_formats);

    do_action('tdb_post_insert_' . $type, $wpdb->insert_id, $data);

    return $wpdb->insert_id;
  }

  /**
   * Update a row
   *
   * @access  public
   * @since   2.1
   * @return  bool
   */
  public function update($row_id, $data = array(), $where = '')
  {

    global $wpdb;

    // Row ID must be positive integer
    $row_id = absint($row_id);

    if (empty($row_id)) {
      return false;
    }

    if (empty($where)) {
      $where = $this->primary_key;
    }

    // Initialibippise column format array
    $column_formats = $this->get_columns();

    // Force fields to lower case
    $data = array_change_key_case($data);

    // White list columns
    $data = array_intersect_key($data, $column_formats);

    // Reorder $column_formats to match the order of columns given in $data
    $data_keys = array_keys($data);
    $column_formats = array_merge(array_flip($data_keys), $column_formats);

    if (false === $wpdb->update($this->table_name, $data, array($where => $row_id), $column_formats)) {
      return false;
    }

    return true;
  }

  /**
   * Delete a row identified by the primary key
   *
   * @access  public
   * @since   2.1
   * @return  bool
   */
  public function delete($row_id = 0)
  {

    global $wpdb;

    // Row ID must be positive integer
    $row_id = absint($row_id);

    if (empty($row_id)) {
      return false;
    }

    if (false === $wpdb->query($wpdb->prepare("DELETE FROM $this->table_name WHERE $this->primary_key = %d", $row_id))) {
      return false;
    }

    return true;
  }

  /**
   * Check if the given table exists
   *
   * @since  2.4
   * @param  string $table The table name
   * @return bool          If the table name exists
   */
  public function table_exists($table)
  {
    global $wpdb;
    $table = sanitize_text_field($table);

    return $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE '%s'", $table)) === $table;
  }
}

<?php

/**
 * @author    Hotshopper <info@hotshopper.nl>
 * @license   GPL-2.0+
 * @date 2016.
 * @link      https://www.hotshopper.nl
 */
class CashBackStatistics extends WP_List_Table {
	private static $instance;

	/** Class constructor */
	public function __construct() {

		parent::__construct( [
			'singular' => __( 'Statistic', 'sp' ), //singular name of the listed records
			'plural'   => __( 'Statistics', 'sp' ), //plural name of the listed records
			'ajax'     => false //should this table support ajax?

		] );

	}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function no_items() {
		_e( 'No statistics available.', 'cashback' );
	}

	function column_name( $item ) {

		// create a nonce
		$title = '<strong>' . $item['page'] . '</strong>';

		return $title;
	}

	public function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	function get_columns() {
		$columns = [
			'cb'      => '',
			'page'    => __( 'Page', 'cashback' ),
			'leads'   => __( 'Leads', 'cashback' ),
			'revenue' => __( 'Revenue', 'cashback' )
		];

		return $columns;
	}

	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'page'    => array( 'name', true ),
			'leads'   => array( 'city', false ),
			'revenue' => array( 'revenue', false )
		);

		return $sortable_columns;
	}

	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();


		$per_page     = $this->get_items_per_page( 'statistics_per_page', 5 );
		$current_page = $this->get_pagenum();
		$total_items  = self::record_count();

		$this->set_pagination_args( [
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page //WE have to determine how many items to show on a page
		] );


		$this->items = self::getStatistics( $per_page, $current_page );
	}

	public static function record_count() {
		global $wpdb;

		$sql = "SELECT COUNT(*) FROM " . $wpdb->prefix . "c247_statistics";

		return $wpdb->get_var( $sql );
	}

	public static function getStatistics( $per_page = 5, $page_number = 1 ) {
		global $wpdb;

		$sql = "SELECT `page`, leads, revenue FROM " . $wpdb->prefix . "c247_statistics ";
		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
		}
		$sql .= " LIMIT $per_page";

		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;
		$result = $wpdb->get_results( $sql, 'ARRAY_A' );

		return $result;
	}
}
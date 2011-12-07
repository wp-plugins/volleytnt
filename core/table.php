<?php 
class VolleyTNT_Table {
	private $columns = array();
	private $data = array();
	public $id = '';
	private $actions = array();
	private $column_actions = array();
	private $get = array();
	private $pagination = 20;
	private $filters = array();

	public function __construct( $id, $columns, $data ) {
		global $wpdb;
		$this->id = $id;
		$this->columns = $columns;
		if ( is_string( $data ) ) {
			$this->data = $wpdb->get_results( $data, ARRAY_A );
		} else {
			$this->data = $data;
		}
		$this->parse_get();
		if ( isset( $this->columns[ $this->get['sb'] ] ) ) usort( $this->data, array( $this, 'sorter' ) );
	}
	
	public function add_filter( $column, $callback ) {
		if ( isset( $this->columns[ $column ] ) and is_callable( $callback ) ) {
			$this->filters[ $column ] = $callback;
			return true;
		} else return false;
	}

	private function sorter( $a, $b ) {
		if ( !isset( $a[ $this->get['sb'] ], $b[ $this->get['sb'] ] ) ) return 0;
		if ( $a[ $this->get['sb'] ] == $b[ $this->get['sb'] ] ) return 0;
		if ( 'asc' == $this->get['so'] ) {
			return $a[ $this->get['sb'] ] > $b[ $this->get['sb'] ] ? 1 : -1;
		} else {
			return $a[ $this->get['sb'] ] < $b[ $this->get['sb'] ] ? 1 : -1;
		}
	}

	private function parse_get() {
		$defaults = array( 	'sb' => array_shift( array_keys( $this->columns ) ), // sort by
							'so' => 'asc', // sort order, "asc" or "desc"
							'p'  => 0 // pagination (page #)
								);
		$get = ( isset( $_GET[ $this->id ] ) ) ? (array) @json_decode( @base64_decode( $_GET[ $this->id ] ) ) : array();
		foreach ( $defaults as $k => $v ) {
			$this->get[ $k ] = isset( $get[ $k ] ) ? $get[ $k ] : $v;
		}
		$this->get['so'] = strtolower( $this->get['so'] );
		if ( $this->get['so'] != 'asc' and $this->get['so'] != 'desc' ) $this->get['so'] = $defaults['so'];
		$this->get['p'] = intval( $this->get['p'] );
		if ( $this->get['p'] < 0 ) $this->get['p'] = $defaults['p'];
	}

	public function add_action( $url_tpl, $label, $class = 'standard', $msg_js_confirm = '' ) {
		$this->actions[] = array( 'url' => $url_tpl, 'label' => $label, 'class' => $class, 'jsconfirm' => $msg_js_confirm );
	}

	public function add_column_action( $column, $url_tpl ) {
		$this->column_actions[ $column ] = $url_tpl;
	}
	
	public function row_per_page( $num ) {
		$this->pagination = absint( $num );
	}

	private function url( $params, $merge = true ) {
		if ( $merge ) $params = array_merge( $this->get, $params );
		return add_query_arg( $this->id, base64_encode( json_encode( $params ) ) );
	}
	
	private function url_from_tpl( $template, $data ) {
		$s = $r = array();
		foreach ( $data as $k => $v ) {
			$s[] = '%' . $k . '%';
			$r[] = $v;
		}
		return str_replace( $s, $r, $template );
	}

	private function headers() {
		echo '<tr>';
		foreach ( $this->columns as $slug => $label ) {
			if ( $this->get['sb'] == $slug ) {
				$sorted = ' sorted ' . $this->get['so'];
				$nextsort = ( 'asc' == $this->get['so'] ) ? 'desc' : 'asc';
			} else {
				$sorted = ' sortable desc';
				$nextsort = 'asc';
			}
			echo '<th class="manage-column column-' . $slug . $sorted . '" scope="col">';
			echo '<a href="' . $this->url( array( 'so' => $nextsort, 'sb' => $slug ) ) . '">';
			echo '<span>' . $label . '</span><span class="sorting-indicator"></span></a></th>';
		}
		echo '</tr>';
	}
	
	private function td( $column, $data ) {
		echo isset( $this->filters[ $column ] ) ? call_user_func( $this->filters[ $column ], $data ) : $data;
	}
	
	private function body() {
		if ( $this->data ) {
			$counter = 0;
			$data = array_slice( $this->data, $this->get['p'] * $this->pagination, $this->pagination );
			foreach ( $data as $row ) {
				$class = ( ++$counter % 2 ) ? ' class="alternate"' : '';
				echo '<tr valign="top"' . $class . '>';
				
				$num_col = 0;
				foreach ( $this->columns as $slug => $col_label ) {
					$num_col++;
					$data = isset( $row[ $slug ] ) ? $row[ $slug ] : false;
					echo '<td class="' . $slug . '">';
					if ( $num_col === 1 ) echo '<strong>';
					if ( isset( $this->column_actions[ $slug ] ) ) {
						echo '<a href="' . $this->url_from_tpl( $this->column_actions[ $slug ], $row ) . '">';
						$this->td( $slug, $data );
						echo '</a>';
					} else {
						$this->td( $slug, $data );
					}
					if ( $num_col === 1 ) echo '</strong>';
					if ( $num_col === 1 and $this->actions ) {
						$tot = count( $this->actions );
						$act = 0;
						echo '<div class="row-actions">';
						foreach ( $this->actions as $action ) {
							echo '<span class="' . $action['class'] . '">';
							$onclk = $action['jsconfirm'] ? ' onClick="return confirm(volleytnt.' . $action['jsconfirm'] . ');"' : '';
							echo '<a href="' . $this->url_from_tpl( $action['url'], $row ) . '"' . $onclk . '>' . $action['label'] . '</a>';
							if ( ++$act !== $tot ) echo ' | ';
							echo '</span>';
						}
						echo '</div>';
					}
					echo '</td>';
				}
				
				echo '</tr>';
			}
		} else {
			
		}
	}

	private function pagination() {
		$total_items = count( $this->data );
		if ( $total_items <= $this->pagination ) return;
		echo '<div class="tablenav-pages">';
		
		$current = $this->get['p'];
		$html_total_pages = $total_pages = round( $total_items / $this->pagination );
				
		$output = '<span class="displaying-num">' . sprintf( _n( '1 elemento', '%s elementi', $total_items, 'volleytnt' ), number_format_i18n( $total_items ) ) . '</span>';

		$page_links = array();

		$disable_first = $disable_last = '';
		if ( $current == 0 )
			$disable_first = ' disabled';
		if ( $current == ( $total_pages - 1 ) )
			$disable_last = ' disabled';

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'first-page' . $disable_first,
			esc_attr__( 'Vai alla prima pagina', 'volleytnt' ),
			esc_url( $this->url( array( 'p' => 0 ) ) ),
			'&laquo;'
		);

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'prev-page' . $disable_first,
			esc_attr__( 'Vai alla pagina precedente', 'volleytnt' ),
			esc_url( $this->url( array( 'p' => $this->get['p'] - 1 ) ) ),
			'&lsaquo;'
		);

		$html_current_page = $this->get['p'] + 1;


		$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
		$page_links[] = '<span class="paging-input">' . sprintf( _x( '%1$s di %2$s', 'paging' ), $html_current_page, $html_total_pages ) . '</span>';

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'next-page' . $disable_last,
			esc_attr__( 'Vai alla prossima pagina', 'volleytnt' ),
			esc_url( $this->url( array( 'p' => $this->get['p'] + 1 ) ) ),
			'&rsaquo;'
		);

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'last-page' . $disable_last,
			esc_attr__( "Vai all'ultima pagina", 'volleytnt' ),
			esc_url( $this->url( array( 'p' => $total_pages ) ) ),
			'&raquo;'
		);

		$output .= "\n<span class='pagination-links'>" . join( "\n", $page_links ) . '</span>';
		
		echo $output;
		

		echo '</div>';
	}

	public function show() {
		if ( !$this->columns ) return;
		echo '<div class="tablenav top">';
		$this->pagination();
		echo '</div>';
		echo '<table class="volleytnt_table widefat fixed" cellspacing="0" id="' . $this->id . '">';
		echo '<thead>';
		$this->headers();
		echo '</thead>';
		echo '<tbody>';
		$this->body();
		echo '</tbody>';
		echo '<tfoot>';
		$this->headers();
		echo '</tfoot>';
		echo '</table>';
		echo '<div class="tablenav bottom">';
		$this->pagination();
		echo '</div>';
	}
	
	public function debug() {
		pr( $this->get );
	}
}
?>

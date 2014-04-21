<?php
/**
 * Plugin Name: SQL Query Exec
 * Plugin URI: https://github.com/blocknotes/wordpress_sql_query_exec
 * Description: Execute SQL queries (admin only) - Installed in: Tools \ SQL Query Exec
 * Version: 1.0.4
 * Author: Mattia Roccoberton
 * Author URI: http://blocknot.es
 * License: GPL3
 */

class sql_query_exec
{
	function __construct()
	{
	// --- Actions --------------------------------------------------------- //
		add_action( 'admin_init', array( &$this, 'init' ) );
		add_action( 'admin_menu', array( &$this, 'menu' ) );
	}

	function init()
	{
		wp_register_style( 'sql-query-exec', plugins_url( 'sqe-styles.css', __FILE__ ) );
		wp_enqueue_style( 'sql-query-exec' );
	}

	function menu()
	{
		add_management_page( 'SQL Query Exec', 'SQL Query Exec', 'manage_options', 'sql-query-exec', array( &$this, 'page_tool' ) );
	}

	function page_tool()
	{
		global $wpdb;
		if( !current_user_can( 'manage_options' ) ) wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		$sqe_cut = !isset( $_POST['sqe_query'] ) ? TRUE : !empty( $_POST['sqe_cut'] );
		$sqe_cnt = isset( $_POST['sqe_last_cnt'] ) ? ( intval( $_POST['sqe_last_cnt'] ) > 1 ? 1 : 2 ) : 1;
?>
		<div class="wrap">
			<div id="sqe-credits">by <a href="http://blocknot.es" target="_blank">Mat</a></div>
			<h2>SQL Query Exec</h2>
			<div id="sqe-warning"><b>Warning:</b> manipulating the database can be dangerous, be careful. Do a backup before going on.<br />The author of this plugin is not responsible for the consequences of use of this software, no matter how awful, even if they arise from flaws in it.</div>
			<form method="post" id="form-sql-query-exec" name="form-sql-query-exec">
				<input type="hidden" name="sqe_show_tables" id="sqe-show-tables" value="0" />
				<input type="hidden" name="sqe_last_cnt" value="<?php echo $sqe_cnt; ?>" />
				<input type="hidden" name="sqe_last_query1" value="<?php echo htmlentities( stripslashes( ( $sqe_cnt == 1 ) ? $_POST['sqe_query'] : $_POST['sqe_last_query1'] ) ); ?>" />
				<input type="hidden" name="sqe_last_query2" value="<?php echo htmlentities( stripslashes( ( $sqe_cnt == 2 ) ? $_POST['sqe_query'] : $_POST['sqe_last_query2'] ) ); ?>" />
				<div>
					<label for="sqe-query">SQL query (press Enter to execute):</label>
					<textarea id="sqe-query" name="sqe_query" autofocus="autofocus" cols="80" rows="3" onkeypress="Javascript:if(event.keyCode===13){document.getElementById('form-sql-query-exec').submit();return false;}"><?php echo isset( $_POST['sqe_query'] ) ? stripslashes( $_POST['sqe_query'] ) : ''; ?></textarea>
				</div>
				<div style="margin-top: 5px">
					<label for="sqe_prev_query">Previous query:</label>
					<input type="text" id="sql-prev-query" readonly="readonly" value="<?php echo htmlentities( stripslashes( ( $sqe_cnt == 2 ) ? $_POST['sqe_last_query1'] : $_POST['sqe_last_query2'] ) ); ?>" />
				</div>
				<div style="margin-top: 10px">
					<label class="selectit"><input type="checkbox" id="sqe_cut" name="sqe_cut" <?php echo $sqe_cut ? 'checked="checked"' : ''; ?> />Cut long values (over 40 chars)</label> &nbsp; 
					<input type="button" class="button" value="Copy previous query" onclick="Javascript:document.getElementById('sqe-query').value=document.getElementById('sql-prev-query').value;document.getElementById('sqe-query').focus();" /> &nbsp; 
					<input type="button" class="button" value="SHOW TABLES" onclick="Javascript:document.getElementById('sqe-show-tables').value=1;document.getElementById('form-sql-query-exec').submit();" /> &nbsp; 
					<input type="submit" class="button" value="Execute SQL query" style="font-weight: bold" />
				</div>
			</form>
<?php
		if( isset( $_POST['sqe_show_tables'] ) && $_POST['sqe_show_tables'] == '1' )
		{
			$result = $wpdb->query( 'SHOW TABLES' );
			if( $result !== FALSE )
			{
				echo "<hr />\n";
				$results = $wpdb->last_result;
				echo '<div style="text-align: center"><b>', count( $results ), "</b> tables</div>\n";
				echo '<div id="sqe-results-wrapper"><table id="sqe-results">', "\n";
				foreach( $results as $result )
				{
					$vars = get_object_vars( $result );
					//var_dump( current( $vars ) );
					$cnt++;
					echo '<tr class="', ( $cnt % 2 == 0 ) ? 'even' : 'odd', '">';
					//echo "<td class=\"c1\">$cnt</td>";
					echo '<td>&nbsp;<input type="button" class="button button-small" value="SELECT * FROM" onclick="Javascript:document.getElementById(\'sqe-query\').value=\'SELECT * FROM ', current( $vars ), '\';document.getElementById(\'sqe-query\').focus();" /> ', current( $vars ), '</td>';
					echo "</tr>\n";
				}
				echo "</table></div>\n";
				$wpdb->flush();
			}
			else
			{
				echo '<p id="sqe-message">Query error</p>';
				$wpdb->show_errors();
				$wpdb->print_error();
				$wpdb->hide_errors();
			}
		}
		else if( isset( $_POST['sqe_query'] ) && !empty( $_POST['sqe_query'] ) )
		{
			$result = $wpdb->query( stripslashes( $_POST['sqe_query'] ) );
			if( $result !== FALSE )
			{
				echo "<hr />\n";
				$results = $wpdb->last_result;
				$cnt = 0;
				echo '<div style="text-align: center"><b>', count( $results ), "</b> results</div>\n";
				echo '<div id="sqe-results-wrapper"><table id="sqe-results">', "\n";
				foreach( $results as $result )
				{
					$vars = get_object_vars( $result );
					if( $cnt == 0 )
					{
						echo '<tr><th class="c1">#</th>';
						foreach( $vars as $key => $value ) echo '<th>', $key, '</th>';
						echo "</tr>\n";
					}
					$cnt++;
					echo '<tr class="', ( $cnt % 2 == 0 ) ? 'even' : 'odd', '">';
					echo "<td class=\"c1\">$cnt</td>";
					foreach( $vars as $key => $value )
					{
						if( $sqe_cut )
						{
							if( strlen( $value ) < 40 ) $value_ = htmlentities( $value );
							else $value_ = htmlentities( substr( $value, 0, 40 ) ) . ' &hellip;';
						}
						else $value_ = htmlentities( $value );
						echo '<td>', $value_, '</td>';
					}
					echo "</tr>\n";
				}
				echo "</table></div>\n";
				$wpdb->flush();
			}
			else
			{
				echo '<p id="sqe-message">Query error</p>';
				$wpdb->show_errors();
				$wpdb->print_error();
				$wpdb->hide_errors();
			}
		}
		echo "</div>\n";
	}
}

new sql_query_exec();

<?php
/**
 * Plugin Name:       bbPress User Stats
 * Plugin URI:        @TODO
 * Description:       Display daily number of posts by a user
 * Version:           1.0.0
 * Author:            Muhammad Haris
 * Author URI:        http://mharis.net
 * Text Domain:       mhar_bbp_user_stats
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * GitHub Plugin URI: https://github.com/mharis/bbpress-user-stats
 */


/**
 * Add view stats link to the users list
 */
function mhar_add_view_stats_link( $actions, $user_object ) {
	$actions['mhar_bb_user_stats'] = "<a class='mhar_view_stats' href='" . admin_url( "options.php?page=bbp-user-stats&amp;user=$user_object->ID") . "'>" . __( 'View Stats', 'mhar_bbp_user_stats' ) . "</a>";

	return $actions;
}
add_filter( 'user_row_actions', 'mhar_add_view_stats_link', 10, 2 );

/**
 * Add hidden stats page to WP-Admin
 */
function mhar_add_stats_page() {
	add_submenu_page(
		'options.php', // hidden page
		__( 'bbPress User Stats', 'mhar_bbp_user_stats' ),
		__( 'bbPress User Stats', 'mhar_bbp_user_stats' ),
		'manage_options',
		'bbp-user-stats',
		'mhar_stats_view'
	);
}
add_action( 'admin_menu', 'mhar_add_stats_page' );

/**
 * get user posts
 */
function mhar_get_posts( $author_id ) {
	global $wpdb;

	$query = $wpdb->get_results("
		SELECT post_date FROM " . $wpdb->prefix . "posts
		WHERE post_author = " . $author_id . "
		AND (
			post_type = 'topic' OR post_type = 'reply'
		)
		AND (
			post_status = 'publish' OR post_status = 'closed'
		)
	");

	return $query;
}

/**
 * Sort posts
 */
function mhar_sort_posts( $author_id, $month, $year ) {
	global $wpdb;

	$query = $wpdb->get_results("
		SELECT post_date, COUNT(*) as number_posts FROM " . $wpdb->prefix . "posts
		WHERE post_author = " . $author_id . "
		AND (
			post_type = 'topic' OR post_type = 'reply'
		)
		AND (
			post_status = 'publish' OR post_status = 'closed'
		)
		AND (post_date BETWEEN '" . $year . "-" . $month . "-01 00:00:00' AND '" . $year . "-" . $month . "-31 23:59:59')
		GROUP BY DAY(post_date)
	");

	return $query;
}

/**
 * Render Stats
 */
function mhar_stats_view() { ?>
<div class="wrap">

	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
	
	<?php
	if( ! $_GET['user']) {
		return;
	}

	$posts = mhar_get_posts($_GET['user']);

	if( isset( $_POST['month'] ) && $_POST['month'] != -1 ) {
		$selected_month = $_POST['month'];
	} else {
		$selected_month = date('m');
	}

	if( isset( $_POST['year'] ) && $_POST['year'] != -1 ) {
		$selected_year = $_POST['year'];	
	} else {
		$selected_year = date('Y');
	}

	$sorted_posts = mhar_sort_posts( $_GET['user'], $selected_month, $selected_year );

	$months = array(
		'01' => 'January',
		'02' => 'February',
		'03' => 'March',
		'04' => 'April',
		'05' => 'May',
		'06' => 'June',
		'07' => 'July',
		'08' => 'August',
		'09' => 'September',
		'10' => 'October',
		'11' => 'November',
		'12' => 'December'
	);
	?>

	<form action="" method="POST">
		<div class="tablenav top">
			<div class="alignleft actions">
				<select name="month">
					<option value="-1" selected="selected"><?php _e('Select Month', 'mhar_bbp_user_stats'); ?></option>
					<?php foreach( $months as $month_in_number => $month_in_text ): ?>
					<option value="<?php echo $month_in_number; ?>" <?php selected($month_in_number, $selected_month, true) ?>><?php echo $month_in_text; ?></option>
					<?php endforeach; ?>
				</select>
				<input type="submit" name="" id="doaction" class="button action" value="<?php _e('Apply', 'mhar_bbp_user_stats'); ?>">
			</div>
			<div class="alignleft actions">
				<select name="year">
					<option value="-1" selected="selected"><?php _e('Select Year', 'mhar_bbp_user_stats'); ?></option>
					<?php foreach(range(date('Y', strtotime($posts[0]->post_date)), date('Y')) as $year): ?>
					<option value="<?php echo $year; ?>" <?php selected($year, $selected_year, true) ?>><?php echo $year; ?></option>
					<?php endforeach; ?>
				</select>
				<input type="submit" name="" id="doaction" class="button action" value="<?php _e('Apply', 'mhar_bbp_user_stats'); ?>">
			</div>
		</div>
	</form>
	<?php if( $sorted_posts ): ?>
	<table class="wp-list-table widefat fixed posts">
		<thead>
			<tr>
				<th><?php _e('Day', 'mhar_bbp_user_stats'); ?></th>
				<th><?php _e('Number of Posts', 'mhar_bbp_user_stats'); ?></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th><?php _e('Day', 'mhar_bbp_user_stats'); ?></th>
				<th><?php _e('Number of Posts', 'mhar_bbp_user_stats'); ?></th>
			</tr>
		</tfoot>
		<tbody>
			<?php foreach( $sorted_posts as $post ): ?>
			<tr>
				<td><?php echo date( 'd', strtotime($post->post_date) ); ?></td>
				<td><?php echo $post->number_posts; ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php else: ?>
	<p><?php _e('No stats found.', 'mhar_bbp_user_stats'); ?></p>
	<?php endif; ?>
</div>
<?php }
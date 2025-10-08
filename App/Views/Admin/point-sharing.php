<?php
/**
 * @author      Wployalty (Alagesan)
 * @license     http://www.gnu.org/licenses/gpl-3.0.html
 * @link        https://www.wployalty.net
 * */

defined( 'ABSPATH' ) or die;
$woocommerce    = \Wlr\App\Helpers\Woocommerce::getInstance();
$base_url       = isset( $base_url ) ? $base_url : '';
$app_url        = isset( $app_url ) ? $app_url : '#';
$back           = ( isset( $back ) && ! empty( $back ) ) ? $back : '';
$search         = isset( $search ) && ! empty( $search ) ? $search : '';
$search_email   = ( isset( $search_email ) && ! empty( $search_email ) ) ? $search_email : '';
$wp_date_format = isset( $wp_date_format ) && ! empty( $wp_date_format ) ? $wp_date_format : 'Y-m-d';
?>

<div id="wlps-expire-points">
    <div class="wlps-expire-points-content-holder">
        <form action="<?php echo esc_url( $base_url ); ?>" method="post"
              id="manage_customer_point_sharing_form"
              name="manage_customer_point_sharing">
            <div class="content-header">
                <div class="heading"><p><?php esc_html_e( 'MANAGE POINTS SHARING', 'wp-loyalty-rules' ) ?></p></div>
                <div class="wlps-search-filter-block">
                    <div class="wlps-back-to-apps">
                        <a class="button" target="_self"
                           href="<?php echo esc_url( $app_url ); ?>">
                            <img src="<?php echo esc_url( $back ); //phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage?>"
                                 alt="<?php esc_attr_e( "Back", "wp-loyalty-rules" ); ?>">
							<?php esc_html_e( 'Back to WPLoyalty', 'wp-loyalty-rules' ); ?></a>
                    </div>
                    <div class="search">
                        <input type="text" name="search"
                               placeholder="<?php esc_attr_e( 'Search by customer email address', 'wp-loyalty-rules' ) ?>"
                               value="<?php echo esc_attr( $search ); ?>"/>
                        <a onclick="wlps_jquery('#manage_customer_point_sharing_form').submit();"
                           class="wlps-email-search">
                            <img src="<?php echo esc_url( $search_email ); //phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage?>"
                                 alt="search">
                        </a>
                    </div>
                    <div class="wlps-filter" id="wlps-filter-status-block"
                         onclick="wlps.showFilter()">
						<?php if ( isset( $filter_status ) && ! empty( $filter_status ) && isset( $point_sort ) && ! empty( $point_sort ) ): ?>
							<?php foreach ( $filter_status as $key => $status ): ?>
                                <div class="wlps-filter-status">
                                    <button
                                            type="button" <?php echo $key === $point_sort ? 'class="active-filter"' : '' ?>
                                            onclick="wlps.filterPoints('#wlps-main #manage_customer_point_sharing_form','<?php echo esc_js( $key ); ?>')"
                                            value="<?php echo esc_attr( $key ); ?>"><?php esc_html_e( $status, 'wp-loyalty-rules' ) //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText ?>
                                    </button>
                                </div>
							<?php endforeach; ?>
						<?php endif; ?>
                    </div>
                </div>
            </div>
            <input type="hidden" name="point_sort"
                   value="<?php echo isset( $point_sort ) ? esc_attr( $point_sort ) : 'all'; ?>"/>
            <input type="hidden" name="page" value="<?php echo esc_attr( WLPS_PLUGIN_SLUG ); ?>"/>
            <input type="hidden" name="view" value="expire_points"/>
            <input type="hidden" name="sort_order" id="user_expire_point_filter_order"
                   value="<?php echo isset( $filter_order ) ? esc_attr( $filter_order ) : 'id'; ?>"/>
            <input type="hidden" name="sort_order_dir" id="user_expire_point_filter_order_dir"
                   value="<?php echo isset( $filter_order_dir ) ? esc_attr( $filter_order_dir ) : 'ASC'; ?>"/>
        </form>
		<?php if ( empty( $items ) ):
			$no_points_yet = ( isset( $no_points_yet ) && ! empty( $no_points_yet ) ) ? $no_points_yet : '';
			?>
            <div class="wlps-no-points">
                <div>
                    <img src="<?php echo esc_url( $no_points_yet ); //phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
					?>" alt="">
                </div>
                <div class="no-points-label">
					<?php esc_html_e( 'No transactions yet. You will see points and their expiry here after you have enabled this feature.', 'wp-loyalty-rules' ) ?>
                </div>
            </div>
		<?php else: ?>
            <div class="wlps-body-content">
                <div class="wlps-body-header">
                    <div><b><?php esc_html_e( 'No', 'wp-loyalty-rules' ); ?></b></div>
                    <div><b><?php esc_html_e( 'Senders Email', 'wp-loyalty-rules' ); ?></b></div>
                    <div><b><?php esc_html_e( 'Recipient Email', 'wp-loyalty-rules' ); ?></b></div>
                    <div><b><?php esc_html_e( 'Points Shared', 'wp-loyalty-rules' ); ?></b></div>
                    <div><b><?php esc_html_e( 'Status', 'wp-loyalty-rules' ); ?></b></div>
                    <div><b><?php esc_html_e( 'Created At', 'wp-loyalty-rules' ); ?></b></div>
                </div>

                <div class="wlps-body-data">
					<?php if ( isset( $items ) && ! empty( $items ) && is_array( $items ) ): ?>
						<?php foreach ( $items as $item ): ?>
                            <div class="wlps-data-row">
                                <div class="record-id">
                                    <p><?php echo ! empty( $item->id ) ? intval( $item->id ) : '-'; ?></p>
                                </div>
                                <div class="sender-email">
                                    <p><?php echo ! empty( $item->sender_email ) ? esc_html( $item->sender_email ) : '-'; ?></p>
                                </div>
                                <div class="recipient-email">
                                    <p><?php echo ! empty( $item->recipient_email ) ? esc_html( $item->recipient_email ) : '-'; ?></p>
                                </div>
                                <div class="points-shared">
                                    <p><?php echo ! empty( $item->points ) ? intval( $item->points ) : '0'; ?></p>
                                </div>
                                <div class="point-status">
                                    <p class="<?php echo esc_attr( $item->status ?? '' ); ?>">
										<?php echo ! empty( $item->status ) ? esc_html( ucfirst( $item->status ) ) : '-'; ?>
                                    </p>
                                </div>
                                <div class="created-at">
                                    <p><?php echo ! empty( $item->created_at ) ? esc_html( $item->created_at ) : '-'; ?></p>
                                </div>
                            </div>
						<?php endforeach; ?>
					<?php else: ?>
                        <div class="wlps-no-points">
                            <p><?php esc_html_e( 'No point sharing records found.', 'wp-loyalty-rules' ); ?></p>
                        </div>
					<?php endif; ?>
                </div>
            </div>
			<?php if ( isset( $pagination ) ): ?>
                <div class="wlps-pagination">
					<?php echo wp_kses_post( $pagination->createLinks() ); ?>
                </div>
			<?php endif; ?>
		<?php endif; ?>
    </div>

</div>

<?php
/**
 * @author      Wployalty (Alagesan)
 * @license     http://www.gnu.org/licenses/gpl-3.0.html
 * @link        https://www.wployalty.net
 * */

defined( 'ABSPATH' ) or die;
$wlps_base_url     = isset( $base_url ) ? $base_url : '';
$wlps_app_url       = isset( $app_url ) ? $app_url : '#';
$wlps_back         = ( isset( $back ) && ! empty( $back ) ) ? $back : '';
$wlps_search       = isset( $search ) && ! empty( $search ) ? $search : '';
$wlps_search_email = ( isset( $search_email ) && ! empty( $search_email ) ) ? $search_email : '';
?>

<div id="wlps-point-sharing">
    <div class="wlps-point-sharing-content-holder">
        <form action="<?php echo esc_url( $wlps_base_url ); ?>" method="post"
              id="manage_customer_point_sharing_form"
              name="manage_customer_point_sharing">
            <div class="content-header">
                <div class="heading"><p><?php esc_html_e( 'MANAGE POINTS SHARING', 'wp-loyalty-point-sharing' ) ?></p>
                </div>
                <div class="wlps-search-filter-block">
                    <div class="wlps-back-to-apps">
                        <a class="button" target="_self"
                           href="<?php echo esc_url( $wlps_app_url ); ?>">
                            <?php //phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
                            <img src="<?php echo esc_url( $wlps_back );?>"
                                 alt="<?php esc_attr_e( "Back", "wp-loyalty-point-sharing" ); ?>">
							<?php esc_html_e( 'Back to WPLoyalty', 'wp-loyalty-point-sharing' ); ?></a>
                    </div>
                    <div class="search">
                        <input type="text" name="search"
                               placeholder="<?php esc_attr_e( 'Search by customer email address', 'wp-loyalty-point-sharing' ) ?>"
                               value="<?php echo esc_attr( $wlps_search ); ?>"/>
                        <a onclick="wlps_jquery('#manage_customer_point_sharing_form').submit();"
                           class="wlps-email-search">
                            <?php //phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
                            <img src="<?php echo esc_url( $wlps_search_email );?>"
                                 alt="search">
                        </a>
                    </div>
                    <div class="wlps-filter" id="wlps-filter-status-block">
						<?php if ( isset( $filter_status ) && ! empty( $filter_status ) && isset( $status_sort ) && ! empty( $status_sort ) ): ?>
							<?php foreach ( $filter_status as $wlps_key => $status ): ?>
                                <div class="wlps-filter-status">
                                    <button
                                            type="button" <?php echo $wlps_key === $status_sort ? 'class="active-filter"' : '' ?>
                                            onclick="wlps.filterPoints('#wlps-main #manage_customer_point_sharing_form','<?php echo esc_js( $wlps_key ); ?>')"
                                            value="<?php echo esc_attr( $wlps_key ); ?>"><?php echo esc_html( $status )  ?>
                                    </button>
                                </div>
							<?php endforeach; ?>
						<?php endif; ?>
                    </div>
                </div>
            </div>
            <input type="hidden" name="status_sort"
                   value="<?php echo isset( $status_sort ) ? esc_attr( $status_sort ) : 'all'; ?>"/>
            <input type="hidden" name="page" value="<?php echo esc_attr( WLPS_PLUGIN_SLUG ); ?>"/>
            <input type="hidden" name="view" value="expire_points"/>
            <input type="hidden" name="sort_order" id="user_point_share_filter_order"
                   value="<?php echo isset( $filter_order ) ? esc_attr( $filter_order ) : ''; ?>"/>
            <input type="hidden" name="sort_order_dir" id="user_point_share_filter_order_dir"
                   value="<?php echo isset( $filter_order_dir ) ? esc_attr( $filter_order_dir ) : ''; ?>"/>
        </form>
		<?php if ( empty( $items ) ):
			$wlps_no_transactions_yet = ( isset( $no_transactions_yet ) && ! empty( $no_transactions_yet ) )
				? esc_url( $no_transactions_yet )
				: '';
			?>
            <div class="wlps-no-points">
                <div>
                    <?php  //phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage?>
                    <img src="<?php echo esc_url( $wlps_no_transactions_yet );
					?>" alt="">
                </div>
                <div class="no-points-label">
					<?php esc_html_e( 'No transactions yet. You will see points and their transactions here after you have enabled this feature.', 'wp-loyalty-point-sharing' ) ?>
                </div>
            </div>
		<?php else: ?>
            <div class="wlps-body-content">
                <div class="wlps-body-header">
                    <div><b><?php esc_html_e( 'No', 'wp-loyalty-point-sharing' ); ?></b></div>
                    <div><b><?php esc_html_e( 'Senders Email', 'wp-loyalty-point-sharing' ); ?></b></div>
                    <div><b><?php esc_html_e( 'Recipient Email', 'wp-loyalty-point-sharing' ); ?></b></div>
                    <div><b><?php esc_html_e( 'Points Shared', 'wp-loyalty-point-sharing' ); ?></b></div>
                    <div><b><?php esc_html_e( 'Status', 'wp-loyalty-point-sharing' ); ?></b></div>
                    <div><b><?php esc_html_e( 'Created At', 'wp-loyalty-point-sharing' ); ?></b></div>
                </div>

                <div class="wlps-body-data">
					<?php if ( isset( $items ) && ! empty( $items ) && is_array( $items ) ): ?>
						<?php foreach ( $items as $wlps_item ): ?>
                            <div class="wlps-data-row">
                                <div class="record-id">
                                    <p><?php echo ! empty( $wlps_item->id ) ? intval( $wlps_item->id ) : '-'; ?></p>
                                </div>
                                <div class="sender-email">
                                    <p><?php echo ! empty( $wlps_item->sender_email ) ? esc_html( $wlps_item->sender_email ) : '-'; ?></p>
                                </div>
                                <div class="recipient-email">
                                    <p><?php echo ! empty( $wlps_item->recipient_email ) ? esc_html( $wlps_item->recipient_email ) : '-'; ?></p>
                                </div>
                                <div class="points-shared">
                                    <p><?php echo ! empty( $wlps_item->points ) ? intval( $wlps_item->points ) : '0'; ?></p>
                                </div>
                                <div class="point-status">
                                    <p class="<?php echo esc_attr( $wlps_item->status ?? '' ); ?>">
										<?php echo ! empty( $wlps_item->status ) ? esc_html( ucfirst( $wlps_item->status ) ) : '-'; ?>
                                    </p>
                                </div>
                                <div class="created-at">
                                    <p><?php
	                                    echo ! empty( $wlps_item->created_at )
		                                    ? esc_html( \Wlps\App\Helpers\Util::beforeDisplayDate( strtotime( $wlps_item->created_at ) ) )
		                                    : '-';
	                                    ?>
                                    </p>
                                </div>
                            </div>
						<?php endforeach; ?>
					<?php else: ?>
                        <div class="wlps-no-points">
                            <p><?php esc_html_e( 'No point sharing records found.', 'wp-loyalty-point-sharing' ); ?></p>
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

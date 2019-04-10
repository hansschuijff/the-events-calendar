<?php
/**
 * Facilitates smoother integration with the Freemius.
 *
 * @since TBD
 */
class Tribe__Events__Integrations__Freemius {
	/**
	 * Stores the instance for the Freemius
	 *
	 * @since  TBD
	 *
	 * @var Freemius
	 */
	private $instance;

	/**
	 * Stores the ID for the Freemius application
	 *
	 * @since  TBD
	 *
	 * @var string
	 */
	private $freemius_id = '3069';

	/**
	 * Stores the slug for the Freemius application
	 *
	 * @since  TBD
	 *
	 * @var string
	 */
	private $slug = 'the-events-calendar';

	/**
	 * Performs setup for the Freemius integration singleton.
	 *
	 * @since  TBD
	 *
	 * @return void
	 */
	public function __construct() {
		$page = tribe_get_request_var( 'page' );

		$valid_page = [
			Tribe__Settings::$parent_slug,
			Tribe__App_Shop::MENU_SLUG,
			Tribe__Events__Aggregator__Page::$slug,
			'tribe-help',
		];

		if ( ! in_array( $page, $valid_page ) ) {
			return;
		}

		// if the common that loaded doesn't include Freemius, let's bail
		if ( ! tribe()->offsetExists( 'freemius' ) ) {
			return;
		}

		/**
		 * Allows third-party disabling of The Events Calendar integration
		 *
		 * @since  TBD
		 *
		 * @param  bool  $should_load
		 */
		$should_load = apply_filters( 'tribe_events_integrations_should_load_freemius', true );

		if ( ! $should_load ) {
			return;
		}

		$this->instance = tribe( 'freemius' )->initialize(
			$this->slug,
			$this->freemius_id,
			'pk_e32061abc28cfedf231f3e5c4e626',
			[
				'menu' => [
					'slug' => $page,
					'account' => true,
					'support' => false,
				],
				'is_premium' => false,
				'has_addons' => false,
				'has_paid_plans' => false,
			]
		);

		tribe_asset( Tribe__Events__Main::instance(), 'tribe-events-freemius', 'freemius.css', [], 'admin_enqueue_scripts' );

		add_action( 'admin_init', [ $this, 'action_skip_activation' ] );

		$this->instance->add_filter( 'connect_message_on_update', [ $this, 'filter_connect_message_on_update' ], 10, 6 );

		add_action( 'admin_init', [ $this, 'maybe_remove_activation_complete_notice' ] );
	}

	/**
	 * Action to skip activation since freemius doesnt do their job correctly hre
	 *
	 * @since  TBD
	 *
	 * @return bool|void
	 */
	public function action_skip_activation() {
		$fs_action = tribe_get_request_var( 'fs_action' );

		// Prevent Fatals
		if ( ! function_exists( 'fs_redirect' ) || ! function_exists( 'fs_is_network_admin' ) ) {
			return false;
		}

		// Actually do the Skipping of connection, since their code doesnt
		if ( $this->slug . '_skip_activation' !== $fs_action ) {
			return false;
		}

		check_admin_referer( $this->slug . '_skip_activation' );

		$this->instance->skip_connection( null, fs_is_network_admin() );

		fs_redirect( $this->instance->get_after_activation_url( 'after_skip_url' ) );
	}

	/**
	 * Filter the content for the Freemius Popup
	 *
	 * @since  TBD
	 *
	 * @param  string $message
	 * @param  string $user_first_name
	 * @param  string $product_title
	 * @param  string $user_login
	 * @param  string $site_link
	 * @param  string $freemius_link
	 *
	 * @return string
	 */
	public function filter_connect_message_on_update(
		$message,
		$user_first_name,
		$product_title,
		$user_login,
		$site_link,
		$freemius_link
	) {
		$plugin_name = 'The Events Calendar';
		$title = '<h3>' . sprintf( esc_html__( 'We hope you love %1$s', 'the-events-calendar' ), $plugin_name ) . '</h3>';
		$html = '';

		$html .= '<p>';
		$html .= sprintf(
			esc_html__( 'Hi, %1$s! This is an invitation to help %2$s community. If you opt-in, some data about your usage of %2$s will be shared with our teams (so they can work their butts off to improve). We will also share some helpful info on events management. WordPress, and our products from time to time.', 'the-events-calendar' ),
			$user_first_name,
			$plugin_name
		);
		$html .= '</p>';

		$html .= '<p>';
		$html .= sprintf(
			esc_html__( 'And if you skip this, that\'s okay! %1$s will still work just fine.', 'the-events-calendar' ),
			$plugin_name
		);
		$html .= '</p>';

		// Powered by HTML
		$html .= '<div class="tribe-powered-by-freemius">' . esc_html__( 'Powered by', 'the-events-calendar' ) . '</div>';

		return $title . $html;
	}

	/**
	 * Returns The Events Calendar instance of Freemius plugin
	 *
	 * @since  TBD
	 *
	 * @return Freemius
	 */
	public function get() {
		return $this->instance;
	}

	/**
	 * Method to remove the sticky message when the plugin is active for freemius
	 *
	 * @since  TBD
	 *
	 * @return void
	 */
	public function maybe_remove_activation_complete_notice() {

		// Bail if the is_pending_activation() method doesn't exist
		if ( ! method_exists( $this->instance, 'is_pending_activation' ) ) {
			return;
		}

		// Bail if it's still pending activation
		if ( $this->instance->is_pending_activation() ) {
			return;
		}

		$admin_notices = FS_Admin_Notices::instance(
				$this->slug,
				'The Events Calendar',
				$this->instance->get_unique_affix()
			);

		// Bail if it doesn't have the activation complete notice
		if ( ! $admin_notices->has_sticky( 'activation_complete' ) ) {
			return;
		}

		// Remove the sticky notice for activation complete
		$admin_notices->remove_sticky( 'activation_complete' );

	}
}
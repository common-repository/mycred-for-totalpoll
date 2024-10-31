<?php
/**
 * Plugin Name: myCred for TotalPoll
 * Plugin URI: https://www.mycred.me/store/mycred-for-totalpoll
 * Description: Allows you to reward users points for participating in polls.
 * Version: 1.1
 * Tags: mycred, points, vote, poll
 * Author: myCRED
 * Author URI: https://www.mycred.me
 * Author Email: support@mycred.me
 * Requires at least: WP 4.0
 * Tested up to: WP 6.6.1
 * Text Domain: mycred_totalpoll
 * Domain Path: /lang
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! class_exists( 'myCRED_TotalPoll' ) ) :
	final class myCRED_TotalPoll {

		// Plugin Version
		public $version             = '1.1';

		// Instnace
		protected static $_instance = NULL;

		// Current session
		public $session             = NULL;

		public $slug                = '';
		public $domain              = '';
		public $plugin              = NULL;
		public $plugin_name         = '';

		/**
		 * Setup Instance
		 * @since 1.0
		 * @version 1.0
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Not allowed
		 * @since 1.0
		 * @version 1.0
		 */
		public function __clone() { _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.0' ); }

		/**
		 * Not allowed
		 * @since 1.0
		 * @version 1.0
		 */
		public function __wakeup() { _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.0' ); }

		/**
		 * Define
		 * @since 1.0
		 * @version 1.0
		 */
		private function define( $name, $value, $definable = true ) {
			if ( ! defined( $name ) )
				define( $name, $value );
		}

		/**
		 * Require File
		 * @since 1.0
		 * @version 1.0
		 */
		public function file( $required_file ) {
			if ( file_exists( $required_file ) )
				require_once $required_file;
		}

		/**
		 * Construct
		 * @since 1.0
		 * @version 1.0
		 */
		public function __construct() {

			$this->slug        = 'mycred-totalpoll';
			$this->plugin      = plugin_basename( __FILE__ );
			$this->domain      = 'mycred_totalpoll';
			$this->plugin_name = 'myCRED for TotalPoll';

			$this->define_constants();

			add_filter( 'mycred_setup_hooks',    array( $this, 'register_hook' ) );
			add_action( 'mycred_init',           array( $this, 'load_textdomain' ) );
			add_action( 'mycred_all_references', array( $this, 'add_badge_support' ) );
			add_action( 'mycred_load_hooks',    'mycred_total_poll_load_hook' );

		}

		/**
		 * Define Constants
		 * @since 1.0
		 * @version 1.0
		 */
		public function define_constants() {

			$this->define( 'MYCRED_TOTAL_POLL_VER',   $this->version );
			$this->define( 'MYCRED_TOTAL_POLL_SLUG',  $this->slug );
			$this->define( 'MYCRED_DEFAULT_TYPE_KEY', 'mycred_default' );

		}

		/**
		 * Includes
		 * @since 1.0
		 * @version 1.0
		 */
		public function includes() { }

		/**
		 * Load Textdomain
		 * @since 1.0
		 * @version 1.0
		 */
		public function load_textdomain() {

			// Load Translation
			$locale = apply_filters( 'plugin_locale', get_locale(), $this->domain );

			load_textdomain( $this->domain, WP_LANG_DIR . '/' . $this->slug . '/' . $this->domain . '-' . $locale . '.mo' );
			load_plugin_textdomain( $this->domain, false, dirname( $this->plugin ) . '/lang/' );

		}

		/**
		 * Register Hook
		 * @since 1.0
		 * @version 1.0
		 */
		public function register_hook( $installed ) {

			$installed['totalpoll'] = array(
				'title'       => __( 'TotalPoll', 'mycred_totalpoll' ),
				'description' => __( 'Awards %_plural% for voting in polls.', 'mycred_totalpoll' ),
				'callback'    => array( 'myCRED_Hook_Total_Polls' )
			);

			return $installed;

		}

		/**
		 * Add Badge Support
		 * @since 1.0
		 * @version 1.0
		 */
		public function add_badge_support( $references ) {

			$references['poll_voting'] = __( 'Voting in Polls (TotalPoll)', 'mycred_totalpoll' );

			return $references;

		}

	}
endif;

function mycred_totalpoll_plugin() {
	return myCRED_TotalPoll::instance();
}
mycred_totalpoll_plugin();

/**
 * Total Poll Hook
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_total_poll_load_hook' ) ) :
	function mycred_total_poll_load_hook() {

		if ( class_exists( 'myCRED_Hook_Total_Polls' ) ) return;

		class myCRED_Hook_Total_Polls extends myCRED_Hook {

			/**
			 * Construct
			 */
			function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

				parent::__construct( array(
					'id'       => 'totalpoll',
					'defaults' => array(
						'creds'    => 1,
						'log'      => '%plural% for voting',
						'limit'    => '0/x'
					)
				), $hook_prefs, $type );

			}

			/**
			 * Run
			 * This class method is fired of by myCRED when it's time to load all hooks.
			 * It should be used to "hook" into the plugin we want to add support for or the
			 * appropriate WordPress instances. Anything that must be loaded for this hook to work
			 * needs to be called here.
			 * @since 1.0
			 * @version 1.0
			 */
			public function run() {

				add_action( 'tp_add_vote',                 array( $this, 'vote_poll_backward_comp_v3' ) ); // for totalpoll version 3.x
				add_action( 'totalpoll/actions/poll/vote', array( $this, 'vote_poll_backward_comp_v3' ) ); // for totalpoll version 3.x
				add_action( 'totalpoll/actions/after/poll/command/log', array( $this, 'vote_poll' ), 10, 2 ); // for totalpoll version 4.x

			}

			/**
			 * Poll Voting
			 * @since 1.0
			 * @version 1.0
			 */
			public function vote_poll( $log, $poll ) {

				$user_id = get_current_user_id();
				if ( $user_id == 0 || $poll->getError() ) return;

				if ( $this->core->exclude_user( $user_id ) ) return;

				// Limit
				if ( ! $this->over_hook_limit( '', 'poll_voting', $user_id ) )
					$this->core->add_creds(
						'poll_voting',
						$user_id,
						$this->prefs['creds'],
						$this->prefs['log'],
						$poll->getId(),
						array( 'ref_type' => 'post' ),
						$this->mycred_type
					);


			}

			/**
			 * Poll Voting
			 * Backward compatibility for version 3.x
			 * @since 1.0
			 * @version 1.0
			 */
			public function vote_poll_backward_comp_v3( $poll_id ) {

				$user_id = get_current_user_id();
				if ( $user_id == 0 ) return;

				if ( $this->core->exclude_user( $user_id ) ) return;

				// Limit
				if ( ! $this->over_hook_limit( '', 'poll_voting', $user_id ) )
					$this->core->add_creds(
						'poll_voting',
						$user_id,
						$this->prefs['creds'],
						$this->prefs['log'],
						$poll_id,
						array( 'ref_type' => 'post' ),
						$this->mycred_type
					);
			}

			/**
			 * Preferences
			 * If the hook has settings, it has to be added in using this class method.
			 * @since 1.0
			 * @version 1.0
			 */
			public function preferences() {

				$prefs = $this->prefs;

?>
<div class="hook-instance">
	<label class="subheader"><?php _e( 'New Vote', 'mycred_totalpoll' ); ?></label>
	<div class="row">
		<div class="col-lg-2 col-md-6 col-sm-6 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr( $this->field_id( 'creds' ) ); ?>"><?php echo esc_html( $this->core->plural() ); ?></label>
				<input type="text" name="<?php echo esc_attr( $this->field_name( 'creds' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'creds' ) ); ?>" value="<?php echo esc_attr( $this->core->number( $prefs['creds'] ) ); ?>" size="8" class="form-control" />
			</div>
		</div>
		<div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr( $this->field_id( array( 'giving' => 'limit' ) ) ); ?>"><?php esc_html_e( 'Limit', 'mycred' ); ?></label>
				<?php
				echo wp_kses( $this->hook_limit_setting( $this->field_name( 'limit' ), $this->field_id( 'limit' ), $prefs['limit'] ),
						array(
							'div' => array(
								'class' => array()
							),
							'input' => array(
								'type' => array(),
								'size' => array(),
								'class' => array(),
								'name' => array(),
								'id' => array(),
								'value' => array()
							),
							'select' => array(
								'name' => array(),
								'id' => array(),
								'class' => array()
							),
							'option' => array(
								'value' => array(),
								'selected' => array()
							)
						)
					); ?>
			</div>
		</div>
		<div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
			<div class="form-group">
				<label class="subheader"><?php esc_html_e( 'Log Template', 'mycred_totalpoll' ); ?></label>
				<input type="text" name="<?php echo esc_attr( $this->field_name( 'log' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['log'] ); ?>" class="long" />
				<span class="description"><?php echo wp_kses_post( $this->available_template_tags( array( 'general' ) ) ); ?></span>
			</div>
		</div>
	</div>
</div>
<?php

			}

			/**
			 * Sanitise Preferences
			 * While myCRED does some basic sanitization of the data you submit in the settings,
			 * we do need to handle our hook limits since 1.6. If your settings contain a checkbox (or multiple)
			 * then you should also use this method to handle the submission making sure the checkbox values are
			 * taken care of.
			 * @since 1.0
			 * @version 1.0
			 */
			function sanitise_preferences( $data ) {

				if ( isset( $data['limit'] ) && isset( $data['limit_by'] ) ) {
					$limit = sanitize_text_field( $data['limit'] );
					if ( $limit == '' ) $limit = 0;
					$data['limit'] = $limit . '/' . $data['limit_by'];
					unset( $data['limit_by'] );
				}

				return $data;

			}

		}

	}
endif;

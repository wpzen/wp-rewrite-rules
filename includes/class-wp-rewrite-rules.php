<?php

/**
 * WP_Rewrite_Rules setup
 *
 * @package WP_Rewrite_Rules
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main WP_Rewrite_Rules Class.
 *
 * @class WP_Rewrite_Rules
 */
final class WP_Rewrite_Rules {

	/**
	 * The single instance of the class.
	 *
	 * @since 	 1.0.0
	 * @access   private
	 * @var 	 WP_Rewrite_Rules
	 */
	private static $_instance = null;

	/**
	 * Rewrite rules.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $rules    Rewrite  rules.
	 */
	private static $rules = array();

	/**
	 * Custom query vars.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $vars    Query vars.
	 */
	private static $vars = array();

	/**
	 * Main WP_Rewrite_Rules Instance.
	 *
	 * Ensures only one instance of WP Rewrite Rules is loaded or can be loaded.
	 *
	 * @since    1.0.0
	 * @static
	 * @see      wp_rewrite_rules()
	 * @return   WP_Rewrite_Rules - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * WP_Rewrite_Rules Constructor.
	 * Hook into actions and filters.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'add_rewrite_rules' ), 0 );
		add_action( 'parse_request', array( $this, 'parse_request' ), 0 );
		add_action( 'query_vars', array( $this, 'register_query_vars' ), 0 );
	}

	/**
	 * Adds rewrite rules during WordPress initialization.
 	 *
	 * @since    1.0.0
	 */
	public function add_rewrite_rules() {
		foreach ( self::$rules as $rule ) {
			add_rewrite_rule( $rule['regex'], $rule['query'], $rule['after'] );
		}
	}

	/**
	 * Call a matched regular expression controller.
	 *
	 * @since    1.0.0
	 * @param    $request    The current request object.
	 */
	public function parse_request( WP $request ) {
		$this->matched_regex( $request->matched_rule );
	}

	/**
	 * Register custom query vars
	 *
	 * @since    1.0.0
	 * @param    array    $vars    The array of available query variables.
	 */
	public function register_query_vars( $vars ) {
		$vars = array_merge( $vars, self::$vars );
		return $vars;
	}

	/**
	 * Adds a rewrite rule that transforms a URL structure to a set of query vars.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Rule parameters.
	 */
	public static function add_rule( $args ) {
		if( empty( $args['regex'] ) ) {
			return;
		}

		$defaults = array(
			'query' 	 => 'index.php',
			'controller' => '',
			'template' 	 => '',
			'redirect'	 => '',
			'access'	 => '',
			'after'		 => 'top',
		);

		$r = wp_parse_args( $args, $defaults );

		self::$rules[] = $r;
	}

	/**
	 * Get the rewrite rule for a given regex.
	 *
	 * @param     string    $regex    Regular expression to match request against.
	 * @return    array     Rewtite rule.
	 */
	public static function get_rule_by_regex( $regex ) {
		return array_search( $regex, array_column( self::$rules, 'regex' ) );
	}

	/**
	 * Get rewrite rules.
	 *
	 * @since    1.0.0
	 * @return   array    All rewrite rules.
	 */
	public static function get_rules() {
		return self::$rules;
	}

	/**
	 * Add custom query vars.
	 *
	 * @since    1.0.0
	 * @param    array    $vars    The array of custom query variables.
	 */
	public static function add_query_vars( $vars ) {
		self::$vars = array_merge( self::$vars, (array) $vars );
	}

	/**
	 * Get custom query vars.
	 *
	 * @since    1.0.0
	 * @return   array    All custom query vars.
	 */
	public static function get_query_vars() {
		return self::$vars;
	}

	/**
	 * Call the controller by regular expression.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    $matched_regex    The current matched regex.
	 */
	private function matched_regex( $matched_regex ) {
		
		// Get the rewrite rule.
		$key = self::get_rule_by_regex( $matched_regex );

		if( ! is_int( $key ) ) {
			return;
		}

		extract( self::$rules[ $key ] );

		if ( ! $this->check_access( $access ) ) {
			$this->access_denied();
			return;
		}

		/**
		 * 301 redirect, if necessary.
		 */
		if( is_callable( $redirect ) ) {
			add_action( 'template_redirect', function( $r ) use ( $redirect ) {
				$redirect = call_user_func_array( $redirect, array( $r ) );
				wp_redirect( esc_url( $redirect ), 301 );
				exit;
			} );
		}

		/**
		 * Override the template, if necessary.
		 */
		if( ! empty( $template ) ) {
			add_action( 'template_include', function( $t ) use ( $template ) {
				if( $template_path = locate_template( $template ) ) {
					return $template_path;
				}
				return get_404_template();
			} );
		}

		if( is_callable( $controller ) ) {
			$controller();
		} else if( class_exists( $controller ) ) {
			new $controller();
		}

	}

	/**
	 * Verify user access.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   void
	 */
	private function check_access( $access ) {
		if( is_callable( $access ) ) {
			return call_user_func( $access );
		}
		return true;
	}

	/**
	 * Choose an action based on logged-in status when denied access
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   void
	 */
	private function access_denied() {
		if ( is_user_logged_in() ) {
			$this->error_403();
		} else {
			$this->login_redirect();
		}
	}

	/**
	 * Display a 403 error page
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return void
	 */
	private function error_403() {
		$message = apply_filters( 'wp_rewrite_rules_access_denied_message', __( 'You do not have permission to access this page.', 'wp-rewrite-rules' ) );
		$title = apply_filters( 'wp_rewrite_rule_denied_title', __( 'Access Denied', 'wp-rewrite-rules' ) );
		$args = apply_filters( 'wp_rewrite_rule_denied_args', array( 'response' => 403 ) );
		wp_die( $message, $title, $args );
		exit;
	}

	/**
	 * Redirect to the login page
	 * 
	 * @since    1.0.0
	 * @access   private
	 * @return   void
	 */
	private function login_redirect() {
		$url = wp_login_url( $_SERVER['REQUEST_URI'] );
		wp_redirect( $url );
		exit;
	}

}

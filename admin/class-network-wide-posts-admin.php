<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://syllogic.in
 * @since      1.0.0
 *
 * @package    Network_Wide_Posts
 * @subpackage Network_Wide_Posts/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Network_Wide_Posts
 * @subpackage Network_Wide_Posts/admin
 * @author     Aurovrata V. <vrata@syllogic.in>
 */
class Network_Wide_Posts_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;
	
	/**
	 * The isntance of Network_Wide_Posts_Terms for creating network-wide terms.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      object    $network_terms    The isntance of Network_Wide_Posts_Terms class.
	 */
	private $network_terms;
	
	/**
	 * Constant to determine the location.
	 *
	 * @since 1.0.0
	 * @access static
	 * @var int The order where the post sub-menu sits.  (-ve to order from end).
	 */
	const POST_MENU_ORDER = -1;
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->load_dependencies();
	}
	/**
	 * Load the required dependencies for admin functionality.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Network_Wide_Posts_Terms. Functionality for creating network-wide terms of the plugin.
	 * 
	 * Creates an instance of Network_Wide_Posts_Terms for creating network-wide terms in each child site
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for Functionality for creating network-wide terms of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-network-wide-posts-terms-base.php';
		//default implementation of the base abstract class
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-network-wide-posts-terms.php';
		//functionality for polylang enabled sites
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-network-wide-posts-terms-polylang.php';
	}
	
	/**
	 * Adds a link to the plugin settings page
	 *
	 * @since 		1.0.0
	 * @param 		array 		$links 		The current array of links
	 * @return 		array 					The modified array of links
	 */
	public function settings_link( $links ) {

		$settings_link = sprintf( '<a href="%s">%s</a>', admin_url( 'options-general.php?page=' . $this->plugin_name ), __( 'Settings', 'network-wide-posts' ) );

		array_unshift( $links, $settings_link );

		return $links;

	} // settings_link()

	/**
	 * Adds links to the plugin links row
	 *
	 * @since 		1.0.0
	 * @param 		array 		$links 		The current array of row links
	 * @param 		string 		$file 		The name of the file
	 * @return 		array 					The modified array of row links
	 */
	public function plugin_row_links( $links, $file ) {

		if ( $file == $this->plugin_name ) {

			$link = '<a href="http://twitter.com/aurovrata">Twitter</a>';

			array_push( $links, $link );

		}

		return $links;

	} // plugin_row_links()
	
	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles($hook_sufix) {

		/**
		 * Let's load the admin css only if the current page is part of our plugin.
		 * We create plugin dashboard pages with the plugin name embeded.
		 */
		
		if(strstr($hook_sufix,$this->plugin_name)){
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/network-wide-posts-admin.css', array(), $this->version, 'all' );
			//error_log("NWT: CSS Enqueue ".$hook_sufix);
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts($hook_sufix) {
		//we only need to load the js for the sortable list
		if('posts_page_'.$this->plugin_name . '-order' == $hook_sufix){
			wp_enqueue_script( 'jquery-ui-sortable');
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/network-wide-posts-admin.js', array( 'jquery' ), $this->version, false );
		}
	}
	
	/**
	 * Initiliase the main class for handling network-wide terms
	 *
	 * This is called by a hook on 'plugins_loaded' so that we can detect correctly the dependent plugins.
	 *
	 * @since    1.0.0
	 */
	public function initialise_network_wide_terms(){
		//create class for terms creation
		if(isset($this->network_terms)) return;
		switch(true){
			case (class_exists('Polylang')):
				$this->network_terms = new Network_Wide_Posts_Terms_Polylang($this->plugin_name, $this->version);
				break;
			default:
				$this->network_terms = new Network_Wide_Posts_Terms_Default($this->plugin_name, $this->version);
				break;
		}
	}

	/**
	 * Adds a settings page link to a menu
	 * This function is called by an admin hook
	 * @since 		1.0.0
	 * @return 		void
	 */
	public function add_menu() {
		$blog_id = get_current_blog_id();
		if(1!=$blog_id) return;
		// add_options_page( $page_title, $menu_title, $capability, $menu_slug, $callback );
		
		add_options_page(
			apply_filters( $this->plugin_name . '-settings-page-title', __( 'Network Wide Post Settings', 'network-wide-posts' ) ),
			apply_filters( $this->plugin_name . '-settings-menu-title', __( 'Network Wide Post', 'network-wide-posts' ) ),
			'manage_options',
			$this->plugin_name,
			array( $this, 'options_page' )
		);

	} // add_menu()
	
	/**
	 * Hook referenced function to add a sub-menu to the Posts section
	 *
	 * @since 		1.0.0
	 */
	public function add_post_sub_menu(){
		$blog_id = get_current_blog_id();
		if(1!=$blog_id) return;
		$term_name = $this->network_terms->get_term_name();
		//add_posts_page( $page_title, $menu_title, $capability, $menu_slug, $function) simple wrapper for add_submenu_page to post menu
		add_posts_page('Order '. $term_name .' Posts', $term_name, 'manage_categories', $this->plugin_name . '-order', array($this,'show_network_wide_posts'));
	}
	
	/**
	 * Hook referenced function to change the order of the sub menu added in add_post_sub_menu()
	 *
	 * @since 		1.0.0
	 * @param 		array 		$menu_ord 		Empty array, only used when changing section menu order, Dashboard, Posts, Media and so on
	 * @return 		array 									In this case it is empty as we are changing the sub menu using the global $submenu.
	 */
	public function order_post_sub_menu($menu_ord ) {
		if(is_network_admin()) return; //not applicable in this case
		
		$blog_id = get_current_blog_id();
		if(1!=$blog_id) return;  // the menu only appears on the first site
		
    global $submenu;

    $arr = array();
		$menu_order = array();
		foreach($submenu['edit.php'] as $order => $menu){
			$menu_order[] = $order;
			if(isset($menu[2]) && $this->plugin_name . '-order' == $menu[2]) $nwp_key = $order;
		}
		
    $idx = array_search($nwp_key,$menu_order);
		//remove our menu form the current order
		array_splice($menu_order, $idx,1);
		
		//insert our menu in the order we want.
		if(self::POST_MENU_ORDER>0) array_splice($menu_order,self::POST_MENU_ORDER-1, 0, $nwp_key);
		else array_splice($menu_order,sizeof($menu_order) - self::POST_MENU_ORDER+1, 0, $nwp_key);
		
		//let's re-order the menus
		foreach($menu_order as $order ){
			$arr[] = $submenu['edit.php'][$order];
		}
    $submenu['edit.php'] = $arr;

    return $menu_ord;
	}
	
	/**
	 * Registers plugin settings, sections, and fields
	 * This function is called by an admin hook
	 *
	 * @since 		1.0.0
	 * @return 		void
	 */
	public function register_settings() {
		$blog_id = get_current_blog_id();
		if(1!=$blog_id) return;
		// register_setting( $option_group, $option_name, $sanitize_callback );

		register_setting(
			$this->plugin_name . '-options',
			$this->plugin_name . '-options',
			array( $this, 'validate_options' )
		);

		// add_settings_section( $id, $title, $callback, $menu_slug );

		add_settings_section(
			$this->plugin_name . '_options' ,
			 __( 'Select your network-wide taxonomies', 'network-wide-posts' ) ,
			array( $this, 'display_options_section' ),
			$this->plugin_name
		);

		// add_settings_field( $id, $title, $callback, $menu_slug, $section, $args );

		add_settings_field(
			'display-taxonomy',
			 __( 'What taxonmies do you want to use?', 'network-wide-posts' ) ,
			array( $this, 'display_taxonomy_selection_field' ),
			$this->plugin_name,
			$this->plugin_name . '_options' 
		);

		add_settings_field(
			'term-name',
			__( 'Name', 'network-wide-posts' ),
			array( $this, 'network_wide_term_name_field' ),
			$this->plugin_name,
			$this->plugin_name . '_options' 
		);
		add_settings_field(
			'term-slug',
			__( 'Slug', 'network-wide-posts' ) ,
			array( $this, 'network_wide_term_slug_field' ),
			$this->plugin_name,
			$this->plugin_name . '_options' 
		);
		
		/*
		 *  Additional settings sections: Site Aliases
		 */
		// register_setting( $option_group, $option_name, $sanitize_callback );

		register_setting(
			$this->plugin_name . '-options',
			$this->plugin_name . '-options-aliases',
			array( $this, 'validate_options_aliases' )
		);
		
		// add_settings_section( $id, $title, $callback, $menu_slug );

		add_settings_section(
			$this->plugin_name . '_options_alias' ,
			 __( 'Alias your child sites', 'network-wide-posts' ) ,
			array( $this, 'display_alias_section' ),
			$this->plugin_name
		);
		
		// add_settings_field( $id, $title, $callback, $menu_slug, $section, $args );

		add_settings_field(
			'display-sites-alias',
			 __( 'Aliases', 'network-wide-posts' ) ,
			array( $this, 'display_sites_alias_field' ),
			$this->plugin_name,
			$this->plugin_name . '_options_alias' 
		);

	} // register_settings()

	
	
	/**
	 * Creates the options page
	 *
	 * @since 		1.0.0
	 * @return 		void
	 */
	public function options_page() {
		
		?><h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
		<form method="post" action="options.php"><?php

		settings_fields( $this->plugin_name . '-options' );

		do_settings_sections( $this->plugin_name );

		submit_button( 'Save Settings' );

		?></form><?php

	} // options_page()

	/**
	 * Validates saved options
	 *
	 * @since 		1.0.0
	 * @param 		array 		$input 			array of submitted plugin options
	 * @return 		array 						array of validated plugin options
	 */
	public function validate_options( $input ) {

		$valid = array();
		$term_name="";
		$term_slug="";
		$validTax = false;
		$validName = false;
		$validSlug = false;
		
		//error_log("NWT: Validating options");
		//error_log("NWT: Inputs ".print_r($input,true));
		
		if ( isset( $input['display-taxonomy'] ) ) {

			$valid['display-taxonomy']	= $input['display-taxonomy'] ;
			$validTax =true;
			//error_log("NWT: display-network-taxonomy: ".$valid['display-taxonomy']);
		}//else error_log("NWT: display-network-taxonomy not set");

		if ( isset( $input['term-name'] ) && isset( $input['term-slug'] ) ) {

			$term_name 			= trim( $input['term-name'] );
			$valid['term-name'] 	= sanitize_text_field( $term_name );

			if ( $valid['term-name'] != $input['term-name'] ) {

				add_settings_error( 'term-name', 'network_wide_term_name', __( 'Term name error.', 'network-wide-posts' ), 'error' );

			}else $validName=true;

			$term_slug 			= trim( $input['term-slug'] );
			$valid['term-slug'] 	= sanitize_title_with_dashes( $term_slug );

			if ( $valid['term-slug'] != $input['term-slug'] ) {

				add_settings_error( 'term-slug', 'network_wide_term_slug', __( 'Term slug error.', 'network-wide-posts' ), 'error' );

			}else $validSlug=true;
			

		}else add_settings_error( 'term-name', 'network_wide_term_name', __( 'Please fill in a term name and slug.', 'network-wide-posts' ), 'error' );
		if ( isset( $input['post-order'] ) ) {

			$valid['post-order'] 	= $input['post-order'] ;

		}
		
		//setup terms in blogs
		if($validSlug && $validName && $validTax)
			$this->network_terms->set_network_wide_terms( $valid['display-taxonomy'], array($term_slug=>$term_name) );

		return $valid;

	} // validate_options()

	/**
	 * Validates saved aliases options
	 *
	 * @since 		1.0.0
	 * @param 		array 		$input 			array of submitted plugin options
	 * @return 		array 						array of validated plugin options
	 */
	public function validate_options_aliases( $input ) {
		//there is nothing to validate here, its optional setting
		return $input;

	} // validate_options_aliases()
	/**
	 * Creates a settings section
	 *
	 * @since 		1.0.0
	 * @param 		array 		$params 		Array of parameters for the section
	 * @return 		mixed 						The settings section
	 */
	public function display_options_section( $params ) {

		echo '<p>';
		_e("If you have an existing term you want to use, copy and paste the slug in the slug settings field, the plugin will automatically replicate it on the sites which does not have this term.","network-wide-posts");
		echo '</br>';
		_e("In a future version of this plugin, we will allow categories to be created/selected.","network-wide-posts");
		echo '</p>';

	} // display_options_section()

	/**
	 * Creates the alias settings section
	 *
	 * @since 		1.0.0
	 * @param 		array 		$params 		Array of parameters for the section
	 * @return 		mixed 						The settings section
	 */
	public function display_alias_section( $params ) {

		echo '<p>';
		_e("Setting up aliases for child sites are useful when manually ordering networ-wide posts.","network-wide-posts");
		echo '</p>';

	} // display_alias_section()
	/**
	 * Creates a settings field
	 *
	 * @since 		1.0.0
	 * @return 		mixed 			The settings field
	 */
	public function display_taxonomy_selection_field() {

		$options 	= get_option( $this->plugin_name . '-options' );
		$option 	= '';

		if ( ! empty( $options['display-taxonomy'] ) ) {

			$option = $options['display-taxonomy'];

		}

		?>
		<input type="radio" id="<?php echo $this->plugin_name; ?>-options[display-taxonomy]"
					name="<?php echo $this->plugin_name; ?>-options[display-taxonomy]"
					value="<?php echo Network_Wide_Posts_Terms::AUTOMATIC_TAG; ?>"
					checked="" <?php checked( Network_Wide_Posts_Terms::AUTOMATIC_TAG, $option, false ); ?> /> <?php _e("Create a tag automatically in each child site","network-wide-posts");?></br>
		<?php

	} // display_taxonomy_selection_field()
	
	/**
	 * Creates a settings field
	 *
	 * @since 		1.0.0
	 * @return 		mixed 			The settings field
	 */
	public function display_sites_alias_field(){
		global $wpdb;
		
		$options 	= get_option( $this->plugin_name . '-options-aliases' );
		//error_log("NWP: Aliases options \n".print_r($options, true));
		$blogs = $wpdb->get_results("SELECT blog_id, domain, path FROM $wpdb->blogs");
		
		foreach ($blogs as $blog){
			$alias = 'blog('.$blog->blog_id.')';
			if(isset($options['site-'.$blog->blog_id])) $alias = $options['site-'.$blog->blog_id];
			?>
		<input type="text" id="<?php echo $this->plugin_name; ?>-options-aliases[site-<?php echo $blog->blog_id;?>]"
					name="<?php echo $this->plugin_name; ?>-options-aliases[site-<?php echo $blog->blog_id;?>]"
					value="<?php echo $alias; ?>" />
		<span><?php _e("Site","network-wide-posts");?>: <?php echo $blog->domain.$blog->path;?></span></br>
		<?php
			
		}
		
	}

	/**
	 * Creates a settings field
	 *
	 * @since 		1.0.0
	 * @return 		mixed 			The settings field
	 */
	public function network_wide_term_name_field() {

		$options  	= get_option( $this->plugin_name . '-options' );
		$option 	= '';

		if ( ! empty( $options['term-name'] ) ) {

			$option = $options['term-name'];

		}else $option = $this->network_terms->get_term_name();

		?>
		<input type="text" id="<?php echo $this->plugin_name; ?>-options[term-name]" name="<?php echo $this->plugin_name; ?>-options[term-name]" value="<?php echo esc_attr( $option ); ?>"></br>
		<?php

	} // network_wide_term_name_field()
	
	/**
	 * Creates a settings field
	 *
	 * @since 		1.0.0
	 * @return 		mixed 			The settings field
	 */
	public function network_wide_term_slug_field() {

		$options  	= get_option( $this->plugin_name . '-options' );
		$option 	= '';

		if ( ! empty( $options['term-slug'] ) ) {

			$option = $options['term-slug'];

		}else $option = $this->network_terms->get_term_slug();

		?>
		<input type="text" id="<?php echo $this->plugin_name; ?>-options[term-slug]" name="<?php echo $this->plugin_name; ?>-options[term-slug]" value="<?php echo esc_attr( $option ); ?>"></br>
		<?php

	} // network_wide_term_slug_field()
	
	/*
	 *  Function called when a new blog is created.
	 *
	 *This function is hooked to 'wpmu_new_blog'
	 *
	 * @since 		1.0.0
	 * @param 		int 		$blog_id 		blog id
	 * @param 		int 		$user_id 		user id who created the blog
	 * @param 		string 		$domain 	domain name
	 * @param 		string 		$path 		path
	 * @param 		int 		$site_id 		site id
	 * @param 		array 		$meta 		array of additional site parameters
	 */
	public function add_new_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta ){
		$this->network_terms->initialise_new_blog($blog_id);
	}
	
	/**
	 * Hook referenced function to display network-wide posts page in the Posts section sub menu
	 *
	 * @since 		1.0.0
	 */
	public function show_network_wide_posts(){
		$showLanguages = false;
		$languages = array();
		$posts = $this->network_terms->get_network_wide_posts();
		if (class_exists('Polylang')){
			$languages = $this->network_terms->get_languages();
			$default_lang = $this->network_terms->get_default_language();
			//error_log("NWP: Languages (default:".$default_lang.") \n".print_r($languages,true));
			include( plugin_dir_path( __FILE__ ) . 'partials/network-wide-posts-admin-polylang-display.php');
		}else{
			include( plugin_dir_path( __FILE__ ) . 'partials/network-wide-posts-admin-display.php');
		}
	}
	
	/**
	 * Hook referenced function to capture Ajax calls from the network-wide post page
	 *
	 * @since 		1.0.0
	 * @param 		array 		$menu_ord 		Empty array, only used when changing section menu order, Dashboard, Posts, Media and so on
	 * @return 		array 									In this case it is empty as we are changing the sub menu using the global $submenu.
	 */
	public function network_wide_post_ordering(){
		$this->network_terms->save_posts_order();
	}
}

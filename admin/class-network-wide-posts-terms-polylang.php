<?php

/**
 * The file extends the core server side action of this plugin to use Polylang's translation services
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://syllogic.in
 * @since      1.0.0
 *
 * @package    Network_Wide_Posts
 * @subpackage Network_Wide_Posts/admin
 */

/**
 * The Network-wide taxonomy terms used to identify the posts we want on the main site.
 *
 * This is the implementation for the PolyLang multi-language management of posts on a network.
 *
 * @package    Network_Wide_Posts
 * @subpackage Network_Wide_Posts/admin
 * @author     Aurovrata V. <vrata@syllogic.in>
 */
 
class Network_Wide_Posts_Terms_Polylang extends Network_Wide_Posts_Terms{
  
  /**
	 *  Default language in which the base class is set up with..
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $default_lang    A language code set by polylang.
	 */
  protected $default_lang;
	
	/**
	 *  An array of language which we find in the network sites
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      array    $languages    Language code => langauge name.
	 */
	protected $languages;
  
  /**
	 * Initialize the class and set its properties.
	 *
	 * When this contstructor is called we are assuming that the Polylang plugin is enabled and therfore
	 * dont's need to verify it anymore.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
  public function __construct( $plugin_name, $version) {
    $this->initialise( $plugin_name, $version);
		$this->default_lang = pll_default_language();
		$this->languages = get_option($this->plugin_name."-languages",array());
	}
  
	/**
	 *  Record a language in use.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @param    string    $code    Language code such as 'en'
	 * @param    string    $name    Full name of the language
	 */
	protected function set_language($code, $name){
		$this->languages[$code]=$name;
	}
	
	/**
	 *  Record a language in use.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @return   array     Languages found on the network with key/value pair: code=>language name
	 */
	public function get_languages(){
		return $this->languages;
	}
	
	/**
	 *  Get the default language of Polylang content.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @return   string    A language code set by polylang.
	 */
	public function get_default_language(){
		return $this->default_lang;
	}
	
  /**
	 * Function to create network-wide term in a given site.
	 *
	 * This function will switch the given blog_id, create the term and then switch back to the current blog.
	 *
	 * @since    1.0.0
	 * @param    string    $blog_id       the id of the blog in which to set the therm.
	 */
	
	protected function create_blog_term($blog_id, $term_name = null, $term_slug = null , $term_description='Network-wide -- DO NOT DELETE'){
		if(!isset($term_name))  $term_name = $this->term_name;
		if(!isset($term_slug))  $term_slug = $this->term_slug;
		
		$current_blog_id = get_current_blog_id();	
		switch_to_blog($blog_id);
		
		//let's get the languages used on that site
		$translations = pll_languages_list(array('hide_empty'=>0,'fields'=>'slug'));
		$language_name = pll_languages_list(array('hide_empty'=>0,'fields'=>'name'));
		
		//if we found some languages, let's cache them
		for($idx = 0; $idx< sizeof($language_name);$idx++){
			$this->set_language($translations[$idx],$language_name[$idx]);
		}
		
		//else we assume we are dealing with the default language
		if(empty($translations)){
			$translations = array($this->default_lang);
			//error_log("Warning (Network-wide-posts) No Polylang languages set in blog: ".$blog_id.", assuming default language (".$this->default_lang.")");
		}
		$term_ids = array(); //term id storage array
		
		foreach($translations as $lang){
			$term_slug = $this->term_slug . "-" . $lang;
			$term_name = $this->term_name . " (". $lang .")";
			if($this->default_lang == $lang){
				$term_slug = $this->term_slug;
				$term_name = $this->term_name;	
			}
			$args = array(
					'description'=>$term_description,
					'slug' => $term_slug,
				);
			switch( true ){
				case ( self::AUTOMATIC_TAG === $this->term_type ):
					$taxonomy = 'post_tag';
					
					$term = get_term_by( 'slug', $args['slug'], $taxonomy);
					if( !$term ) {
						//create tag
						$term = wp_insert_term( $term_name, $taxonomy, $args);
						if(is_array($term)) $term_id = $term['term_id']; 
						else $term_id = -1;
					}else $term_id=$term->term_id;
					//associate the new term with the correct language
					pll_set_term_language($term_id, $lang);
					
					//let's keep a cache for quicker loading.
					$term_ids[$lang]=$term_id;
					$this->set_blog_term_id( $blog_id, $term_ids);
					break;
				case (self::AUTOMATIC_CAT === $this->term_type ):
					$taxonomy = 'category';
					$term_ids = array();
					$term = get_term_by( 'slug', $args['slug'], $taxonomy);
					if( !$term ) {
						//create tag
						$term = wp_insert_term( $term_name, $taxonomy, $args);
						if(is_array($term)) $term_id = $term['term_id']; 
						else $term_id = -1;
					}else $term_id=$term->term_id;
					
					pll_set_term_language($term_id, $lang['slug']);
					$term_ids[$lang['slug']]=$term_id;
					
					$this->set_blog_term_id( $blog_id, $term_id);
					break;
				case (self::SELECTED_TAX === $this->term_type ):
				default:
					//TODO, nothing?
					break;
			}
		}
		//switch back to current blog
		restore_current_blog();
	}
	/**
	 * Function to create the DB view of network-wide posts.
	 *
	 * @since    1.0.0
	 */
	protected function create_network_wide_posts_view(){
		global $wpdb;
		//let's save the languages
		update_option($this->plugin_name."-languages",$this->languages);
							 
		switch( true ){
			case ( self::AUTOMATIC_TAG === $this->term_type ):
			case ( self::AUTOMATIC_CAT === $this->term_type ):
				
				//view for posts wp_network_wide_posts
				$blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
				$sql_view = "CREATE OR REPLACE VIEW " . $wpdb->prefix . NWP_VIEW_POSTS_NAME . " AS ";
				$sql_union=' ';
				$sql_values = array();
				foreach ($blogids as $blog_id){
					$lang_terms = $this->get_blog_term_id($blog_id);
					foreach($lang_terms as $lang => $term_id){
						$sql_view .=  $sql_union . $this->sql_blog_posts_select( $blog_id, array('lang'=>$lang));
						$sql_union=" UNION ";
						$sql_values[]=$term_id;
					}
				}
				$wpdb->query( $wpdb->prepare( $sql_view ,$sql_values) );
				//error_log("Network-wide Posts: built view --- \n".$wpdb->last_query);
				
				//view for thumbs wp_network_wide_posts_thumbs
				$sql_view = "CREATE OR REPLACE VIEW " . $wpdb->prefix . NWP_VIEW_POSTS_NAME . "_thumbs AS ";
				$sql_union=' ';
				$sql_values = array();
				foreach ($blogids as $blog_id){
					$sql_view .=  $sql_union . $this->sql_blog_posts_thumbs_select( $blog_id);
					$sql_union=" UNION ";
					$sql_values[]=$blog_id;
				}
				$wpdb->query( $wpdb->prepare( $sql_view , $sql_values) );
				//error_log("Network-wide Posts: built view --- \n".$wpdb->last_query);
				break;
			case (self::SELECTED_TAX === $this->term_type):
				//TODO
				//set up user selected terms
				break;
			default:
				break;
		}
	}
  /**
	 * Function to get the the sql select for given blog_id.
	 *
	 * @since    1.0.0
	 * @var       $blog_id  blog id for which the sql string needs to be built 
	 * @return    string     the sql select for given blog_id.
	 */
	protected function sql_blog_posts_select($blog_id, $args=null){
		global $wpdb;
		$lang = $args['lang'];
		$table_prefix = $wpdb->prefix;
		if($blog_id>1) $table_prefix = $wpdb->prefix . $blog_id . "_";
		return "SELECT
			CAST(CONCAT('".$blog_id."',ID) AS UNSIGNED) AS nwp_id,
			post_title AS nwp_title,
			post_name AS nwp_name,
			post_date AS nwp_date,
			post_content AS nwp_content,
			post_excerpt AS nwp_excerpt,
			".$table_prefix."postmeta.meta_value as nwp_thumb_id,
			'".$blog_id."' AS blog_id,
			'". $lang ."' as nwp_lang
		FROM ".$table_prefix."posts
			INNER JOIN ".$table_prefix."term_relationships ON ".$table_prefix."posts.ID = ".$table_prefix."term_relationships.object_id
			      AND ".$table_prefix."term_relationships.term_taxonomy_id = %d
			LEFT JOIN ".$table_prefix."postmeta ON ".$table_prefix."posts.ID = ".$table_prefix."postmeta.post_id
						AND ".$table_prefix."postmeta.meta_key LIKE '_thumbnail_id'
		WHERE ".$table_prefix."posts.post_status LIKE 'publish'";
	}
	/**
	 * Function to retrieve the post from the view of network-wide posts.
	 *
	 * @since    1.0.0
	 */
	public function get_network_wide_posts($args=''){
		global $wpdb;
		$sql_query = "SELECT posts.nwp_id, posts.nwp_title, posts.nwp_name, posts.blog_id, posts.nwp_lang
       FROM ". $wpdb->prefix . NWP_VIEW_POSTS_NAME . " as posts, " . $wpdb->prefix . NWP_VIEW_POSTS_NAME . "_thumbs as thumbs 
        WHERE posts.nwp_thumb_id = thumbs.nwp_thumb_id
         AND posts.blog_id = thumbs.blog_id ";
				 
		if(isset($args['lang']) ){
			$sql_query .= "AND posts.nwp_lang = '".$args['lang']."' ORDER BY ";
		}else $sql_query .= "ORDER BY posts.nwp_lang, ";
		switch($this->nwp_order_type){
			case 'manual':
				if(empty($this->nwp_manual_order)){
					$sql_query .= "nwp_date DESC";
				}else{
					if($this->nwp_new_post_top)
						$sql_query .= "FIELD(nwp_id, ". implode(",",$this->nwp_manual_order) . "), nwp_date DESC";
					else
						$sql_query .= "if(FIELD(nwp_id, ". implode(",",$this->nwp_manual_order) . ")=0,1,0), FIELD(nwp_id, ". implode(",",$this->nwp_manual_order) . "), nwp_date DESC";
				}
				break;
			case 'slug':
				$sql_query .= "nwp_name";
				break;
			case 'time':
			default:
				$sql_query .= "nwp_date DESC";
				break;
		}
		
		$posts = $wpdb->get_results($sql_query);
		return $posts;
	}
}
<?php

namespace gokabam_api;

/**
 * Class VirtualThemedPages
 * @link https://gist.githubusercontent.com/brianoz/9105004/raw/34c0953e84c63304478ece26d1860bd19409f1ba/class-virtualthemedpage-bc.php
 */
class VirtualThemedPages {
	public $title = '';
	public $body = '';
	public $slug = '';
	private $vpages = array();  // the main array of virtual pages
	private $mypath = '';
	public $blankcomments = "blank-comments.php";

	public $template;
	public $subtemplate;


	function __construct( $plugin_path = null, $blankcomments = null ) {
		if ( empty( $plugin_path ) ) {
			$plugin_path = dirname( __FILE__ );
		}
		$this->mypath = $plugin_path;

		if ( ! empty( $blankcomments ) ) {
			$this->blankcomments = $blankcomments;
		}

		// Virtual pages are checked in the 'parse_request' filter.
		// This action starts everything off if we are a virtual page
		add_action( 'parse_request', array( &$this, 'vtp_parse_request' ) );
	}

	function add( $virtual_regexp, $name, $contentfunction,$post_function = null ) {
		$this->vpages[ $virtual_regexp ] = ['name'=>$name,'get'=>$contentfunction, 'post'=>$post_function];
	}


	// Check page requests for Virtual pages
	// If we have one, call the appropriate content generation function
	//
	/**
	 * @param $wp
	 *
	 * @return \WP|false
	 *
	 */
	function vtp_parse_request( &$wp ) {
		//global $wp;


		//$p = $wp->query_vars['pagename'];
		$request_uri = $_SERVER['REQUEST_URI'];

		$matched      = 0;
		$post_function = $get_function  = $name =  null;
		$is_post = false;
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$is_post = true;
		}
		foreach ( $this->vpages as $regexp => $functions ) {
			if ( preg_match( $regexp, $request_uri ) ) {
				$matched      = 1;
				if ($is_post) {
					$post_function = $functions['post'];
				} else {
					$get_function = $functions['get'];
				}
				$name = $functions['name'];
				break;
			}
		}
		// Do nothing if not matched
		if ( ! $matched ) {
			return false;
		}

		$data_from_post = [];
		if ($post_function) {
			call_user_func_array( $post_function, array($name,  &$data_from_post, $request_uri ) );
		}

		if (!$get_function) {
			return false;
		}


		// setup hooks and filters to generate virtual movie page
		add_action( 'template_redirect', array( &$this, 'template_redir' ) );
		add_filter( 'the_posts', array( &$this, 'vtp_createdummypost' ) );
		add_filter( 'page_link', array( &$this, 'vtp_createpagelink' ), 1, 2 );

		// we also force comments removal; a comments box at the footer of
		// a page is rather meaningless.
		// This requires the blank_comments.php file be provided
		add_filter( 'comments_template', array( &$this, 'disable_comments' ), 11 );

		// Call user content generation function
		// Called last so it can remove any filters it doesn't like
		// It should set:
		//    $this->body   -- body of the virtual page
		//    $this->title  -- title of the virtual page
		//    $this->template  -- optional theme-provided template
		//          eg: page
		//    $this->subtemplate -- optional subtemplate (eg movie)
		// Docs is unclear whether call by reference works for call_user_func()
		// so using call_user_func_array() instead, where it's mentioned.
		// See end of file for example code.
		$this->template = $this->subtemplate = null;
		$this->title    = null;
		unset( $this->body );
		call_user_func_array( $get_function, array( $name, &$this,$request_uri,$data_from_post ) );

		if ( ! isset( $this->body ) ) //assert
		{
			wp_die( "Virtual Themed Pages: must save ->body [VTP07]" );
		}

		return $wp;
	}

	function vtp_createpagelink( $link, $postID ) {
		if ( $postID == - 1 ) {
			$link = home_url( $_SERVER['REQUEST_URI'] );
		}

		return $link;
	}

	// Setup a dummy post/page
	// From the WP view, a post == a page
	//
	function vtp_createdummypost( $posts ) {

		// have to create a dummy post as otherwise many templates
		// don't call the_content filter
		global $wp, $wp_query;

//		( count( $posts ) == 0 ) &&
//		(
//			(strpos( $wp->request, $this->slug ) !== false) ||
//			( array_key_exists('page_id',$wp->query_vars) && ($wp->query_vars['page_id'] == $this->slug) )
//		)

		if (true) {

			//create a fake post instance
			$p = new \stdClass;
			// fill $p with everything a page in the database would have
			$p->ID                    = - 1;
			$p->post_author           = 1;
			$p->post_date             = current_time( 'mysql' );
			$p->post_date_gmt         = current_time( 'mysql', $gmt = 1 );
			$p->post_content          = $this->body;
			$p->post_title            = $this->title;
			$p->post_excerpt          = '';
			$p->post_status           = 'publish';
			$p->ping_status           = 'closed';
			$p->post_password         = '';
			$p->post_name             = $this->slug; // slug
			$p->to_ping               = '';
			$p->pinged                = '';
			$p->modified              = $p->post_date;
			$p->modified_gmt          = $p->post_date_gmt;
			$p->post_content_filtered = '';
			$p->post_parent           = 0;
			$p->guid                  = get_home_url( '/' . $p->post_name ); // use url instead?
			$p->menu_order            = 0;
			$p->post_type             = 'page';
			$p->post_mime_type        = '';
			$p->comment_status        = 'closed';
			$p->comment_count         = 0;
			$p->filter                = 'raw';
			$p->ancestors             = array(); // 3.6

			// reset wp_query properties to simulate a found page
			$wp_query->is_page     = true;
			$wp_query->is_singular = true;
			$wp_query->is_home     = false;
			$wp_query->is_archive  = false;
			$wp_query->is_category = false;
			unset( $wp_query->query['error'] );
			$wp->query                     = array();
			$wp_query->query_vars['error'] = '';
			$wp_query->is_404              = false;

			$wp_query->current_post  = $p->ID;
			$wp_query->found_posts   = 1;
			$wp_query->post_count    = 1;
			$wp_query->comment_count = 0;
			// -1 for current_comment displays comment if not logged in!
			$wp_query->current_comment = null;
			$wp_query->is_singular     = 1;
			$wp_query->is_attachment   = false;

			$wp_query->post              = $p;
			$wp_query->posts             = array( $p );
			$wp_query->queried_object    = $p;
			$wp_query->queried_object_id = $p->ID;
			$wp_query->current_post      = $p->ID;
			$wp_query->post_count        = 1;

			return array( $p );
		}
		return $posts;
	}


	// Virtual Movie page - tell wordpress we are using the given
	// template if it exists; otherwise we fall back to page.php.
	//
	// This func gets called before any output to browser
	// and exits at completion.
	//
	function template_redir() {
		//    $this->body   -- body of the virtual page
		//    $this->title  -- title of the virtual page
		//    $this->template  -- optional theme-provided template eg: 'page'
		//    $this->subtemplate -- optional subtemplate (eg movie)
		//

		if ( ! empty( $this->template ) && ! empty( $this->subtemplate ) ) {
			// looks for in child first, then master:
			//    template-subtemplate.php, template.php
			get_template_part( $this->template, $this->subtemplate );
		} elseif ( ! empty( $this->template ) ) {
			// looks for in child, then master:
			//    template.php
			get_template_part( $this->template );
		} elseif ( ! empty( $this->subtemplate ) ) {
			// looks for in child, then master:
			//    template.php
			get_template_part( $this->subtemplate );
		} else {
			get_template_part( 'page' );
		}

		// It would be possible to add a filter for the 'the_content' filter
		// to detect that the body had been correctly output, and then to
		// die if not -- this would help a lot with error diagnosis.

		exit;
	}


	// Some templates always include comments regardless, sigh.
	// This replaces the path of the original comments template with a
	// empty template file which returns nothing, thus eliminating
	// comments reliably.
	function disable_comments( $file ) {
		if ( file_exists( $this->blankcomments ) ) {
			return ( $this->mypath . '/' . $this->blankcomments );
		}

		return ( $file );
	}


} // class


// Example code - you'd use something very like this in a plugin
//
if ( 0 ) {
	// require 'BC_Virtual_Themed_pages.php';
	// this code segment requires the WordPress environment

	$vp = new VirtualThemedPages();
	$vp->add( '#/mypattern/unique#i', 'mytest_contentfunc' );

	// Example of content generating function
	// Must set $this->body even if empty string
	function mytest_contentfunc( $v, $url ) {
		// extract an id from the URL
		$id = 'none';
		if ( preg_match( '#unique/(\d+)#', $url, $m ) ) {
			$id = $m[1];
		}
		// could wp_die() if id not extracted successfully...

		$v->title       = "My Virtual Page Title";
		$v->body        = "Some body content for my virtual page test - id $id\n";
		$v->template    = 'page'; // optional
		$v->subtemplate = 'billing'; // optional
		$v->slug        = 'go-kabam-versions';
	}
}




// end
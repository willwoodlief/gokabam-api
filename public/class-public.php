<?php

namespace gokabam_api;
require_once 'pages.php';
require_once  'gokabam.goodies.php';

/**
 * @var $GokabamGoodies GoKabamGoodies
 * <p>
 *   Nice Stuff
 * </p>
 */
global $GokabamGoodies;
$GokabamGoodies = null;


/**
 * The public-facing functionality of the plugin.
 *
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 */
class Plugin_Public
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $plugin_name The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private $version;

	/**
	 * @var Pages pages
	 */
    private $pages;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string $plugin_name The name of the plugin.
     * @param      string $version The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->pages = null;

    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {

	    $b_check = strpos($_SERVER['REQUEST_URI'], strtolower( PLUGIN_NAME));
	    if ($b_check) {
		    wp_enqueue_style('bootstrap', PLUGIN_URL . 'node_modules/bootstrap/dist/css/bootstrap.min.css', array(), '3.3.7', 'all');
		    wp_enqueue_style('bootstrap-theme' , PLUGIN_URL . 'node_modules/bootstrap/dist/css/bootstrap-theme.min.css', array(), '3.3.7', 'all');
		    wp_enqueue_style( 'fontawesome', PLUGIN_URL . 'node_modules/@fortawesome/fontawesome-free/css/all.min.css', array(), '5.3.1', 'all');
		    wp_enqueue_style( 'bootstrap-dialog', PLUGIN_URL . 'node_modules/bootstrap3-dialog/dist/css/bootstrap-dialog.min.css', array('bootstrap'), '1.35.4', 'all');

	    }

        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/public.css', array(), $this->version, 'all');
	    $this->pages->enqueue_styles();
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {

	    $b_check = strpos($_SERVER['REQUEST_URI'], strtolower( PLUGIN_NAME));
    	if ($b_check) {
		    wp_enqueue_script('moment', PLUGIN_URL . 'node_modules/moment/min/moment-with-locales.min.js', array('jquery'), '2.22.2', false);
		    wp_enqueue_script('jquery-bootstrap', PLUGIN_URL . 'node_modules/bootstrap/dist/js/bootstrap.min.js', array('jquery'), '3.3.7', false);
		    wp_enqueue_script('bootstrap-dialog', PLUGIN_URL . 'node_modules/bootstrap3-dialog/dist/js/bootstrap-dialog.min.js', array('jquery-bootstrap'), '1.35.4', false);
    	}
        wp_enqueue_script($this->plugin_name. 'a', plugin_dir_url(__FILE__) . 'js/public.js', array('jquery'), $this->version, false);
        $title_nonce = wp_create_nonce(strtolower( PLUGIN_NAME) . 'public_nonce');
        wp_localize_script($this->plugin_name. 'a', strtolower( PLUGIN_NAME) . '_frontend_ajax_obj', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'action' => strtolower( PLUGIN_NAME) . '_submit_chart_step',
            'nonce' => $title_nonce,
            'plugins_url' => plugins_url()
        ));

        $this->pages->enqueue_scripts();

    }


    public function send_survey_ajax_handler() {


	    check_ajax_referer( strtolower( PLUGIN_NAME) . 'public_nonce' );

	    if (array_key_exists( 'method',$_POST) && $_POST['method'] == 'survey_answer') {

		    try {
			    $response_id = null;
			    wp_send_json(['is_valid' => true, 'data' => $response_id, 'action' => 'updated_survey_answer']);
			    die();
		    } catch (\Exception $e) {
			    wp_send_json(['is_valid' => false, 'message' => $e->getMessage(), 'trace'=>$e->getTrace(), 'action' => 'stats' ]);
			    die();
		    }
	    }

	    else {
		    //unrecognized
		    wp_send_json(['is_valid' => false, 'message' => "unknown action"]);
		    die();
	    }
    }

    //JSON


    public function shortcut_code()
    {
    	global $is_wp_init_called;
	    $is_wp_init_called = true;
	    global $GokabamGoodies;
	    $GokabamGoodies = new GoKabamGoodies();
        add_shortcode($this->plugin_name, array($this, 'manage_shortcut'));

    }

    /**
     * @param array $attributes - [$tag] attributes
     * @param null $content - post content
     * @param string $tag
     * @return string - the html to replace the shortcode
     */
    public
    function manage_shortcut($attributes = [], $content = null, $tag = '')
    {
        global $shortcut_content;
// normalize attribute keys, lowercase
        $atts = array_change_key_case((array)$attributes, CASE_LOWER);

        // override default attributes with user attributes
	    /** @noinspection PhpUnusedLocalVariableInspection */
	    $our_atts = shortcode_atts([
            'border' => 1,
            'results' => 0,
        ], $atts, $tag);

        // start output
        $o = '';

        $shortcut_content = '';
        // enclosing tags
        if (!is_null($content)) {

            // run shortcode parser recursively
            $expanded__other_shortcodes = do_shortcode($content);
            // secure output by executing the_content filter hook on $content, allows site wide auto formatting too
            $shortcut_content .= apply_filters('the_content', $expanded__other_shortcodes);

        }

	    /** @noinspection PhpIncludeInspection */
	    require_once plugin_dir_path(dirname(__FILE__)) . 'public/partials/shortcode-gui.php';


        // return output
        return $o;
    }


	/**
	 * @throws \Exception
	 */
	public function virtual_pages() {

		$this->pages = new Pages($this->plugin_name,$this->version);
	}

}

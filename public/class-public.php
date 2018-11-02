<?php

namespace gokabam_api;
require_once 'pages.php';
require_once    PLUGIN_PATH.'public/gateway/gokabam.goodies.php';
require_once PLUGIN_PATH . 'public/gateway/api-gateway.php';
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
		    wp_enqueue_style( 'fontawesome', PLUGIN_URL . 'node_modules/@fortawesome/fontawesome-free/css/all.min.css', array(), '5.4.2', 'all');
		    wp_enqueue_style( 'bootstrap-dialog', PLUGIN_URL . 'node_modules/bootstrap3-dialog/dist/css/bootstrap-dialog.min.css', array('bootstrap'), '1.35.4', 'all');
		    wp_enqueue_style( 'tokenfield', PLUGIN_URL . 'node_modules/tokenfield/dist/tokenfield.css', array(), '1.1.0', 'all');


		    wp_enqueue_style($this->plugin_name. '_public', plugin_dir_url(__FILE__) . 'css/public.css', array(), $this->version, 'all');
		    wp_enqueue_style($this->plugin_name . '_gokabam_roots', plugin_dir_url(__FILE__) . 'css/gokabam_roots.css', array(), $this->version, 'all');
	    }


	    $this->pages->enqueue_styles();
    }

    protected function quick_enqueue($prefix,$relative) {
	    wp_enqueue_script(
	    	$this->plugin_name. '_' . $prefix,
		    plugin_dir_url(__FILE__) . $relative,
		    array(),
		    $this->version, false
	    );

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
		    wp_enqueue_script('moment',
			    PLUGIN_URL . 'node_modules/moment/min/moment-with-locales.min.js', array('jquery'), '2.22.2', false);


		    wp_enqueue_script('jquery-bootstrap',
			    PLUGIN_URL . 'node_modules/bootstrap/dist/js/bootstrap.min.js', array('jquery'), '3.3.7', false);


		    wp_enqueue_script('bootstrap-dialog',
			    PLUGIN_URL . 'node_modules/bootstrap3-dialog/dist/js/bootstrap-dialog.min.js',
			    array('jquery-bootstrap'), '1.35.4', false);

		    wp_enqueue_script('notify-browser',
			    PLUGIN_URL . 'node_modules/notifyjs-browser/dist/notify.js',
			    array('jquery-bootstrap'), '0.4.2', false);

		    wp_enqueue_script('tokenfield',
			    PLUGIN_URL . 'node_modules/tokenfield/dist/tokenfield.min.js',
			    array(), '1.1.0', false);

		    wp_enqueue_script('popup-overlay',
			    PLUGIN_URL . 'jslib/my.jquery.popupoverlay.js',
			    array(), '1.7.13', false);

		    wp_enqueue_script('jquery-ui',
			    PLUGIN_URL . 'jslib/jquery-ui/jquery-ui.js',
			    array(), '1.12.1', false);





		    // wp_enqueue_script('gokabam-family', PLUGIN_URL . 'public/js/gokabam.family.js', array('jquery'), '0.0.1', false);

		    $this->quick_enqueue('public','js/public.js');
		    $this->quick_enqueue('typedefs','js/gokabam.typedefs.js');
		    $this->quick_enqueue('heartbeat','js/gokabam.heartbeat.js');
		    $this->quick_enqueue('editor_callbacks','js/gokabam_editor/KabamEditorCallbacks.js');


			//displays
		    $this->quick_enqueue('display_base','js/gokabam_display/kabam.display.base.js');
		    $this->quick_enqueue('display_word_minimal','js/gokabam_display/kabam.display.word.minimal.js');
		    $this->quick_enqueue('display_word_wide','js/gokabam_display/kabam.display.word.wide.js');
		    $this->quick_enqueue('display_word_compact','js/gokabam_display/kabam.display.word.compact.js');
		    $this->quick_enqueue('display_tag_wide','js/gokabam_display/kabam.display.tag.wide.js');
		    $this->quick_enqueue('display_journal_wide','js/gokabam_display/kabam.display.journal.wide.js');
		    $this->quick_enqueue('display_journal_compact','js/gokabam_display/kabam.display.journal.compact.js');
		    $this->quick_enqueue('display_version_wide','js/gokabam_display/kabam.display.version.wide.js');


		    //containers
		    $this->quick_enqueue('container_base','js/gokabam_container/kabam.container.base.js');
		    $this->quick_enqueue('container_minimal','js/gokabam_container/kabam.container.minimal.js');
		    $this->quick_enqueue('container_word_wide','js/gokabam_container/kabam.container.word.wide.js');
		    $this->quick_enqueue('container_word_compact','js/gokabam_container/kabam.container.word.compact.js');
		    $this->quick_enqueue('container_tag_wide','js/gokabam_container/kabam.container.tag.wide.js');
		    $this->quick_enqueue('container_journal_wide','js/gokabam_container/kabam.container.journal.wide.js');
		    $this->quick_enqueue('container_journal_compact','js/gokabam_container/kabam.container.journal.compact.js');
		    $this->quick_enqueue('container_version_wide','js/gokabam_container/kabam.container.version.wide.js');

		    //editors
		    $this->quick_enqueue('editor_base','js/gokabam_editor/kabam.editor.base.js');
		    $this->quick_enqueue('editor_base_bs_dialog','js/gokabam_editor/kabam.editor.bsdialog.base.js');
		    $this->quick_enqueue('editor_word_single','js/gokabam_editor/kabam.editor.word.single.js');
		    $this->quick_enqueue('editor_journal_single','js/gokabam_editor/kabam.editor.journal.single.js');
		    $this->quick_enqueue('editor_version_single','js/gokabam_editor/kabam.editor.version.single.js');

		    //central library

		    $this->quick_enqueue('gokabam_core','js/gokabam.js');




		    remove_filter( 'the_content', 'wpautop' );
		    remove_filter( 'the_excerpt', 'wpautop' );
    	}

	    $title_nonce = wp_create_nonce(strtolower( PLUGIN_NAME) . 'public_nonce');
        wp_localize_script($this->plugin_name. '_public', strtolower( PLUGIN_NAME) . '_frontend_ajax_obj', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'action' => strtolower( PLUGIN_NAME) . '_submit_chart_step',
            'nonce' => $title_nonce,
            'plugins_url' => plugins_url()
        ));

        $this->pages->enqueue_scripts();

    }



    public function send_survey_ajax_handler() {
		global $GokabamGoodies;
		require_once PLUGIN_PATH . 'lib/Input.php';

	    check_ajax_referer( strtolower( PLUGIN_NAME) . 'public_nonce' );

	    if (array_key_exists( 'method',$_POST) && $_POST['method'] == 'echo') {

		    try {
		    	$echo = Input::get('data', Input::THROW_IF_MISSING);
		    	$data = [];
		    	$data['data'] = $echo;
		    	$data['handler_says'] = 'Echoed Data, have a nice day';
		    	$data['server_time'] = date('M d Y h:i:s a', time());
		    	$data['server_timezone'] = date_default_timezone_get();
			    wp_send_json(['is_valid' => true, 'data' => $data, 'handler_says' => 'Thank you echo service!']);
			    die();
		    } catch (\Exception $e) {
			    wp_send_json(['is_valid' => false, 'message' => $e->getMessage(), 'trace'=>$e->getTrace(), 'action' => 'stats' ]);
			    die();
		    }
	    } elseif (array_key_exists( 'method',$_POST) && $_POST['method'] == 'gokabam_api' ) {
	    	try {
			    $user_map = $this->create_user_map();
			    $gateway  = new ApiGateway( $GokabamGoodies->get_mydb(), $GokabamGoodies->get_current_version_id(), $user_map );
			    $response = $gateway->all();
			    wp_send_json( [ 'is_valid' => true, 'data' => $response, 'handler_says' => 'GoKabam API Response' ] );
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


	/**
	 * creates a user map
	 * @return GKA_User[]
	 * @throws ApiParseException
	 */
    protected function create_user_map() {
    	global $GokabamGoodies;

		$ret = [];
		$users = get_users();
		foreach ($users as $user) {
			$node = new GKA_User();
			$node->user_email = $user->user_email;
			$node->user_name = $user->user_nicename;
			$node->user_id = $GokabamGoodies->get_kid_talk()->generate_string_id('user',$user->ID);
			$node->ts_since= strtotime($user->user_registered);
			$ret[$user->ID] = $node;
		}

		return $ret;
    }


    public function shortcut_code()
    {
    	global $is_wp_init_called;
	    $is_wp_init_called = true;
	    global $GokabamGoodies;
	    $GokabamGoodies = new GoKabamGoodies(); //put here, because its the right timing
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

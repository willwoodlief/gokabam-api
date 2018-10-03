<?php
namespace gokabam_api;
require_once 'virtual-pages.php';


class Pages {

	protected $plugin_version = 0;
	protected $plugin_name = null;
	protected $vp = null;
	protected $page_def_classes = [];
	protected $page_class_lookup = [];


	/**
	 * Pages constructor.
	 * @param string $plugin_name
	 * @param float $plugin_version
	 * @throws \Exception
	 */
	public function __construct($plugin_name,$plugin_version) {
		$this->plugin_version = $plugin_version;
		$this->plugin_name = $plugin_name;
		$this->page_def_classes = [];
		$this->auto_load_virtual_pages();
		$this->vp = new VirtualThemedPages();
		foreach ($this->page_class_lookup as $name=>$class) {
			$regex =  call_user_func($class . "::get_regex");
			$this->vp->add($regex,$name,
				function( $name,$virtual_page, $request_uri,$data_from_post ) {
					$class =        $this->page_class_lookup[$name];
					$virtual_page->title       = call_user_func($class . "::get_title");

					//get_page(&$virtual_page,$url,array $data_from_post = null)
					$virtual_page->body        = call_user_func_array( $class . "::get_page", array(  &$data_from_post, $request_uri ) );
					$virtual_page->template       = call_user_func($class . "::get_template");

					//	$v->subtemplate = 'billing'; // optional
					$virtual_page->slug        = call_user_func($class . "::get_slug");
				},
				function($name,  &$data_from_post, $request_uri ) {
					$class =        $this->page_class_lookup[$name];
					call_user_func_array( $class . "::post_page", array( &$this, &$data_from_post, $request_uri ) );
				}
			);
		}

	}


	/**
	 * @throws \Exception
	 */
	public  function auto_load_virtual_pages() {

		$dir = realpath(dirname(__FILE__)) . '/pages';


		$files = self::rsearch($dir,'/.*entry.php/');

		foreach ($files as $file) {
			try {
				/** @noinspection PhpIncludeInspection */
				require_once($file);
				$this->page_def_classes[] = self::get_class_name_from_file($file);

			} catch (\Exception $e) {
				continue;
			}
		}

		$checks = [];
		foreach ($this->page_def_classes as $page_def_class) {
			$class = $page_def_class['class'];
			$namespace = $page_def_class['namespace'];

			$fullname = "$namespace\\$class";
			$name = call_user_func($fullname . "::get_name");

			$this->page_class_lookup[$name] = $fullname;
			//check to make sure unique action names in the code, throw an exception if not
			if (isset($checks[$name])) {
				throw new \Exception("Duplicate Action name of [$name]");
			}
			$checks[$name] = true;
		}

	}


	private static function rsearch($folder, $pattern) {
		$dir = new \RecursiveDirectoryIterator($folder);
		$ite = new \RecursiveIteratorIterator($dir);
		$files = new \RegexIterator($ite, $pattern, \RegexIterator::GET_MATCH);
		$fileList = array();
		foreach($files as $file) {
			$fileList = array_merge($fileList, $file);
		}
		return $fileList;
	}

	/**
	 * Gets the string class name, if more than one class defined in the file, then returns the last class defined
	 * @param $file
	 * @return array  [class=>'',namespace=>'']
	 * @throws \Exception if class cannot be found
	 */
	private  static function get_class_name_from_file($file)
	{
		$fp = fopen($file, 'r');
		$class = $namespace = $buffer = '';
		$i = 0;
		while (!$class) {
			if (feof($fp)) break;

			$buffer .= fread($fp, 512);
			$tokens = @token_get_all($buffer);

			if (strpos($buffer, '{') === false) continue;

			for (;$i<count($tokens);$i++) {
				if ($tokens[$i][0] === T_NAMESPACE) {
					for ($j=$i+1;$j<count($tokens); $j++) {
						if ($tokens[$j][0] === T_STRING) {
							$namespace .= '\\'.$tokens[$j][1];
						} else if ($tokens[$j] === '{' || $tokens[$j] === ';') {
							break;
						}
					}
				}

				if ($tokens[$i][0] === T_CLASS) {
					for ($j=$i+1;$j<count($tokens);$j++) {
						if ($tokens[$j] === '{') {
							$class = $tokens[$i+2][1];
						}
					}
				}
			}
		}
		if (!$class) {
			throw new \Exception("Cannot find a class in $file");
		}
		return ['class' => $class, 'namespace' => $namespace];
	}

	public function enqueue_styles() {
		$request_uri = $_SERVER['REQUEST_URI'];

		foreach ($this->page_def_classes as $page_def_class) {
			$class     = $page_def_class['class'];
			$namespace = $page_def_class['namespace'];

			$fullname = "$namespace\\$class";
			$regexp =  call_user_func($fullname . "::get_regex");
			if ( preg_match( $regexp, $request_uri ) ) {
				call_user_func_array( $fullname . "::enqueue_styles", array( $this->plugin_name,$this->plugin_version ) );
				break;
			}
		}

	}

	public function enqueue_scripts() {

		$request_uri = $_SERVER['REQUEST_URI'];

		foreach ($this->page_def_classes as $page_def_class) {
			$class     = $page_def_class['class'];
			$namespace = $page_def_class['namespace'];

			$fullname = "$namespace\\$class";
			$regexp =  call_user_func($fullname . "::get_regex");
			if ( preg_match( $regexp, $request_uri ) ) {
				call_user_func_array( $fullname . "::enqueue_scripts", array( $this->plugin_name,$this->plugin_version ) );
				break;
			}
		}
	}

}
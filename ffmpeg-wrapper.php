<?php

require_once("ffmpeg-parameter.php");
require_once("ffmpeg-video.php");
require_once("ffmpeg-audio.php");
require_once("ffmpeg-video-filter.php");
require_once("ffmpeg-filter-complex.php");

class ffmpeg_wrapper {

	// *************************************************************************************************** //
	// ** ffmpeg_wrapper :: data members																** //
	// *************************************************************************************************** //

	// ffmpeg version
	public $version;

	// ffmpeg executable path
	public $path;
	
	// the installed ffmpeg configuration
	public $configuration;

	// array of input files, output file
	private $input;
	private $output;

	// ffmepg parameters array
	private $global_attribs;
	public $video;
	public $audio;

	// ffmpeg filters
	public $video_filter;
	public $video_filter_complex;
	public $video_filter_complex_flag;
	// execute related members
	public $execute;
	public $response_string;
	public $response_val;

	// flag & helpers
	public $simulate;
	public $loglevel;
	private $response_junk_lines;

	// *************************************************************************************************** //
	// ** ffmpeg_wrapper :: private functions 															** //
	// *************************************************************************************************** //

	private function set_configuration() {
		exec( $this->path . " -version" , $ret);
		$conf_string = $ret[2];
		$config_arr = explode("configuration: ", $conf_string);
		$this->configuration = explode("--", $config_arr[1]);
		$this->response_junk_lines = count($ret)-1;
	}

	public function __construct($path) {

		$this->path = $path;

		// deal with version
		$version = $this->get_vertion();
		if (ffmpeg_wrapper::supported_version($version)) {
			$this->version = "1.0";
		} else {
			throw new Exception("ffmpeg_wrapper :: __construct() :: unsupported version: " . $version, 1);
		}

		// get the configure parameters
		$this->set_configuration();

		// init input array
		$this->input = array();

		// init atributes arrays
		$this->video 				= new ffmpeg_video($this);
		$this->audio 				= new ffmpeg_audio($this);
		$this->video_filter 		= new ffmpeg_video_filter($this);
		$this->video_filter_complex = new ffmpeg_video_filter_complex($this);

		$this->global_attribs = $this->default_global_attribs();

		$this->video_filter_complex_flag = false;
	}

	private function default_global_attribs() {
		$attribs = array();
		array_push($attribs, new ffmpeg_parameter("y"));
		array_push($attribs, new ffmpeg_parameter("shortest"));

		return $attribs;
	}

	// verify that the command is executable
	private function verify() {
		
		if (count($this->input) == 0) {
			throw new Exception("ffmpeg_wrapper :: missing input file", 1);	
		}

		if (is_null($this->output)) {
			throw new Exception("ffmpeg_wrapper :: missing output file", 1);
		}

		foreach ($this->input as $input_param) {
			if (!file_exists($input_param->value)) {
				throw new Exception("ffmpeg_wrapper :: input file does not exist (" . $input_param->value . ")", 1);
			}
		}

		return true;
	}

	private function build() {
		
		$this->verify();

		// build the execution string
		$str = $this->path . " ";

		// global attribs
		foreach ($this->global_attribs as $param) {
			$str .= $param->get_string() . " ";
		}

		// input files:
		foreach ($this->input as $input_param) {
			$str .= $input_param->get_string() . " ";
		}

		if ($this->video_filter_complex_flag) {
			$str .= $this->video_filter_complex->get_string();
		}

		$str .= $this->video_filter->get_string();

		// video attribs:
		$str .= $this->video->get_string();
		$str .= $this->audio->get_string();
		
		// set outputs
		$str .= $this->output . " ";
		$str .= "2>&1";

		return $str;

	}

	// *************************************************************************************************** //
	// ** ffmpeg_wrapper :: public functions 															** //
	// *************************************************************************************************** //

	public function get_vertion() {
		exec( $this->path . " -version" , $ret);
		return $ret[0];
	}

	static public function supported_version($version) {

		if ($version == "ffmpeg version 1.0") {
			return true;
		}

		if ($version == "ffmpeg version 1.1") {
			return true;
		}

		if ($version == "ffmpeg version N-47767-g0f23634") {
			return true;
		}

		return false;
	}

	public function add_global_param($param, $value = "") {
		array_push($this->global_attribs, new ffmpeg_parameter($param,$value));
	}

	public function add_input($input_file) {
		array_push($this->input, new ffmpeg_parameter("i",$input_file));
	}

	public function add_concatenate_input($input_file) {
		$this->video_filter_complex_flag = true;
		$this->video_filter_complex->concat_add($input_file);
		$this->add_input($input_file);
	}

	public function set_output($output_file) {
		$this->output = $output_file;
	}

	public function run() {
		$this->execute = $this->build();

		if ($this->simulate) {
			echo $this->execute . PHP_EOL;
			return;
		}

		exec(	$this->execute,
				$this->response_string,
				$this->response_val
		);

		if ( !$this->is_successful() ) {
			return $this->response(true);
		} else {
			return $this->response();
		}
	}

	public function is_successful() {

		if (is_null($this->response_val)) {
			return false;
		}

		if ($this->response_val != 0) {
			return false;
		}

		return true;
	}

	public function response($showCommand = false) {

		if ($this->simulate) {
			$response = "ffmpeg_wrapper :: response() :: Simulation" . PHP_EOL;
			return $response;
		}

		if ($this->is_successful()) {
			$response = "ffmpeg_wrapper :: response() :: Successful run" . PHP_EOL;
		} else {
			$response = "ffmpeg_wrapper :: response() :: Failed run" . PHP_EOL;
		}
		
		$response .= PHP_EOL . "response:" . PHP_EOL;
		if (!is_null($this->response_string)) {
			foreach ($this->response_string as $line_num => $line) {
				if ($line_num > $this->response_junk_lines) {
					$response .= "\t" . $line . PHP_EOL;
				}
			}
		}

		if ($showCommand) {
			$response .= PHP_EOL . "For command: " . PHP_EOL . $this->execute . PHP_EOL;
		}

		return $response;
	}
	
	// Per-file configuration:
	//----------------------
	public function set_length($time) {
		$this->add_global_param("t",$time);		
	}

	public function set_start_from($time) {
		$this->add_global_param("ss",$time);
	}

	public function set_input_format($fmt) {
		$this->add_global_param("f",$fmt);
	}

	public function set_pixcel_format($fmt) {
		$this->add_global_param("pix_fmt",$fmt);
	}

	public function set_log_level($level) {

		if (
			$level === "quiet"  	||
			$level === "panic"  	||
			$level === "fatal"  	||
			$level === "error"  	||
			$level === "warning"  	||
			$level === "info"  		||
			$level === "verbose"  	||
			$level === "debug"		   ) 
		{
			$this->loglevel = $level;
			$this->add_global_param("loglevel",$level);
		}
	}
}

?>
<?php

require_once("ffmpeg-utils.php");
require_once("ffmpeg-parameter.php");
require_once("ffmpeg-video.php");
require_once("ffmpeg-audio.php");
require_once("ffmpeg-video-filter.php");
require_once("ffmpeg-filter-complex.php");
require_once("ffmpeg-presets.php");

define("DEFAULT_FFMPEG_PATH", "/usr/local/bin/ffmpeg");
define("DEFAULT_FFPROBE_PATH", "/usr/local/bin/ffprobe");
define("FFMPEG_WRAPPER_DEBUG_PRINTS", true);

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
	public $output;

	// ffmepg parameters array
	private $global_attribs;
	public $video;
	public $audio;
	public $last_input_info_video;
	public $last_input_info_audio;

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
		$this->response_junk_lines = count($ret);
	}

	public function __construct($path = DEFAULT_FFMPEG_PATH) {

		$this->path = $path;

		// deal with multipel version
		ffmpeg_wrapper::verify_supported_version($this->get_vertion());

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
			$str .= $input_param->get_string_for_path() . " ";
		}

		if ($this->video_filter_complex_flag) {
			$str .= $this->video_filter_complex->get_string();
		}

		$str .= $this->video_filter->get_string();

		// video attribs:
		$str .= $this->video->get_string();
		$str .= $this->audio->get_string();
		
		// set outputs
		$str .= "\"" . $this->output . "\"" . " ";
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

	public function verify_supported_version($version) {
		return true;

		// set the version
		$this->$version = $version;

		if ($version == "ffmpeg version 1.0") {
			return true;
		}

		if ($version == "ffmpeg version 1.1") {
			return true;
		}

		if ($version == "ffmpeg version 1.1.2") {
			return true;
		}

		throw new Exception("unsupported version: " . $version, 1);

		return false;
	}

	public function add_global_param($param, $value = "") {
		array_push($this->global_attribs, new ffmpeg_parameter($param,$value));
	}

	public function add_input($input_file) {
		array_push($this->input, new ffmpeg_parameter("i", $input_file));
		$this->last_input_info_video = ffmpeg_wrapper::video_stream_info($input_file);
		$this->last_input_info_audio = ffmpeg_wrapper::audio_stream_info($input_file);
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

	public function get_input_length() {
		throw new Exception("Can not get input length. not implemented.", 1);
	}

	public function trim($from,$length) {
		$this->set_start_from($from);
		$this->video->length($length);
	}

	public function trim_range($head,$tail) {

		if ($tail < $head) {
			throw new Exception("Can not trim. tail parameter ($tail) is smaller than head parameter ($head)", 1);
		}

		$this->set_start_from($head);
		$this->video->length($tail - $head);
	}


	// *************************************************************************************************** //
	// ** ffmpeg_wrapper :: public static functions 													** //
	// *************************************************************************************************** //

	public static function streams_info($file) {

		if (!file_exists(DEFAULT_FFPROBE_PATH)) {
			throw new Exception("Can't locate ffprobe, using default location (" . DEFAULT_FFPROBE_PATH . ")", 1);
		}

		$toexec = DEFAULT_FFPROBE_PATH . " -show_streams -print_format json -v quiet " . $file;
		exec($toexec, $ret, $val);

		$stream_arr = json_decode(implode($ret), true);
		if (!empty($stream_arr)) {
			return json_decode(implode($ret), true);
		} else {
			return NULL;
		}
	}

	public static function video_stream_info($file) {
		if (!file_exists(DEFAULT_FFPROBE_PATH)) {
			throw new Exception("Can't locate ffprobe, using default location (" . DEFAULT_FFPROBE_PATH . ")", 1);
		}

		$toexec = DEFAULT_FFPROBE_PATH . " -show_streams -select_streams v -print_format json -v quiet " . $file;
		exec($toexec, $ret, $val);

		$stream_arr = json_decode(implode($ret), true);
		if (!empty($stream_arr["streams"]["0"])) {
			return $stream_arr["streams"]["0"];
		} else {
			return NULL;
		}

	}

	public static function audio_stream_info($file) {
		if (!file_exists(DEFAULT_FFPROBE_PATH)) {
			throw new Exception("Can't locate ffprobe, using default location (" . DEFAULT_FFPROBE_PATH . ")", 1);
		}

		$toexec = DEFAULT_FFPROBE_PATH . " -show_streams -select_streams a -print_format json -v quiet " . $file;
		exec($toexec, $ret, $val);

		$stream_arr = json_decode(implode($ret), true);
		if (!empty($stream_arr["streams"]["0"])) {
			return $stream_arr["streams"]["0"];
		} else {
			return NULL;
		}
	}

	public static function analyze_letterbox_video($file) {

		$info = ffmpeg_wrapper::video_stream_info($file);
		$duration = $info["duration"];

		$ffmpeg = new ffmpeg_wrapper();
		// $ffmpeg->add_global_param("ss",floor($duration/2));
		$ffmpeg->add_input($file);

		$ffmpeg->audio->disable();
		$ffmpeg->video->length(50);
		$ffmpeg->video->format("rawvideo");
		$ffmpeg->video_filter->cropdetect(16,2,0);
		$ffmpeg->set_output("/dev/null");
		$ffmpeg->run();

		$crop_string_array = array();		
		foreach ($ffmpeg->response_string as $string) {
			
			// if the string include
			if (strpos($string,'crop') !== false) {
				$splitString = explode(" crop=", $string);
				array_push($crop_string_array, $splitString[1]);
			}
		}

		// find most common crop recommendation
		$crop_map = array();
		$common_crop = $crop_string_array[0];
		$common_crop_count = 1;

		for ($i = 0 ; $i < sizeof($crop_string_array) ; $i++) {
			$current = $crop_string_array[$i];
			if (!isset($crop_map[$current])) {
				$crop_map[$current] = 1;
			} else {
				$crop_map[$current]++;
			}
			if ($crop_map[$current] > $common_crop_count) {
				$common_crop = $current;
				$common_crop_count = $crop_map[$current];
			}
		}

		if (FFMPEG_WRAPPER_DEBUG_PRINTS) {
			// echo "most common crop: " . $common_crop . PHP_EOL;
		}

		// check if crop is needed:
		$frame_size = explode(":", $common_crop);
		$aspect_ratio = explode(":", $info["display_aspect_ratio"]);
		
		$ratio = $aspect_ratio[0] / $aspect_ratio[1];
		$crop_ratio = $frame_size[0] / $frame_size[1];

		if (FFMPEG_WRAPPER_DEBUG_PRINTS) {
			// echo "input - width  = " . $info["width"] . PHP_EOL;
			// echo "input - height = " . $info["height"] . PHP_EOL;
			// echo "input - aspect ratio = " . $ratio . PHP_EOL;
			// echo "crop frame - width  = " . $frame_size[0] . PHP_EOL;
			// echo "crop frame - heigth = " . $frame_size[1] . PHP_EOL;
			// echo "crop frame - aspect ratio = " . $crop_ratio . PHP_EOL;
		}

		if ($frame_size[0] != $info["width"] || $frame_size[1] != $info["height"]) {
			return explode(":", $common_crop);
		}

		return null;
	}

	public static function analyze_rotation($file) {

		$info = ffmpeg_wrapper::video_stream_info($file);
		if (isset($info["tags"]["rotate"])) {
			return intval($info["tags"]["rotate"]);
		}
		return intval(-1);
	}

	// *************************************************************************************************** //
	// ** ffmpeg_wrapper :: batch functions 		 													** //
	// *************************************************************************************************** //

	public function fix_letterbox($file,$new_width,$new_height) {
		$letterbox_array = ffmpeg_wrapper::analyze_letterbox_video($file);
		if ($this->video_filter->fix_letterbox($letterbox_array, $new_width, $new_height)) {
			if (FFMPEG_WRAPPER_DEBUG_PRINTS) {
				echo ">> letterbox needed" . PHP_EOL;
			}
		} else {
			if (FFMPEG_WRAPPER_DEBUG_PRINTS) {
				echo ">> no letterbox needed" . PHP_EOL;
			}
		}		
	}

	public function fix_vertical($file) {
		$transpose_degree = ffmpeg_wrapper::analyze_rotation($file);
		if ($this->video_filter->transpose($transpose_degree)) {
			if (FFMPEG_WRAPPER_DEBUG_PRINTS) {
				echo ">> transpose needed" . PHP_EOL;
			}
		} else {
			if (FFMPEG_WRAPPER_DEBUG_PRINTS) {
				echo ">> no transpose needed" . PHP_EOL;
			}
		}
	}

	public function fix_aspect_ratio_blur($aspect_ratio, $new_aspect_ratio) {
		if ($aspect_ratio != $new_aspect_ratio) {
			echo ">> fix_aspect_ratio_blur" . PHP_EOL;
		}
	}

	public function fix_aspect_ratio_normal($aspect_ratio, $new_aspect_ratio) {
		if ($aspect_ratio != $new_aspect_ratio) {
			if (FFMPEG_WRAPPER_DEBUG_PRINTS) {
				echo ">> aspect ratio padding needed" . PHP_EOL;
				echo ">>> in aspect: " . round($aspect_ratio,3) . PHP_EOL;
				echo ">>> new aspect: " . round($new_aspect_ratio,3) . PHP_EOL;				
			}

			// 4:3 to 16:9
			if ($aspect_ratio == 1.333 && $new_aspect_ratio == 1.778) {
				echo ">>> will convert 4x3 to 16x9" . PHP_EOL;
				$new_wide_width = intval($input_width * floatval(1+1/3));
				$this->video_filter->pad_center($new_wide_width,$input_heigth);
			}

			if ($aspect_ratio == 1.5 && $new_aspect_ratio == 1.778) {
				echo ">>> will convert 3x2 to 16x9" . PHP_EOL;
			}
		}		
	}

}

?>
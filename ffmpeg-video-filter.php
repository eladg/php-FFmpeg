<?php

require_once("ffmpeg-filter.php");

class ffmpeg_video_filter {

	public $ffmpeg_wrapper;

	public $filters;
	public $string;
	public $input_files;
	public $last_stream;

	public function __construct($wrapper) {

		$this->ffmpeg_wrapper = $wrapper;

		$this->filters = array();
		$this->input_files = array();
		$this->last_stream = 0;
	}

	private function new_id() {
		$stream_id = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz"),0,5);
		return $stream_id;
	}

	private function new_input_id() {
		$stream_id = "_" . substr(str_shuffle("abcdefghijklmnopqrstuvwxyz"),0,3);
		return $stream_id;
	}

	private function stream_prefix() {
		return "t";
	}

	private function push_filter($new_filter) {

		if ($this->ffmpeg_wrapper->video_filter_complex_flag) {
			throw new Exception("Can't add filter over complex filter", 1);
		}

		array_push($this->filters, $new_filter);
	}

	public function add_input($file, $next_filter = null) {
		$stream_id = $this->new_input_id();

		$input_filter = new ffmpeg_filter();
		$input_filter->id = $stream_id;
		$input_filter->name = "movie";
		$input_filter->values = array($file);

		$input_filter->input_streams = array();
		$input_filter->output_streams = array($stream_id);

		if ($next_filter != null) {
			$input_filter->filter = $next_filter;
		}

		$this->push_filter($input_filter);

		return $stream_id;
	}

	public function get_string() {

		$this->string = "";

		if (count($this->filters) == 0) {
			return;
		}

		return $this->build();
	}

	public function add_simple_filter_with_filter($name,$values,$next_filter) {
		$stream_id = $this->new_id();

		$filter = new ffmpeg_filter();
		$filter->id = $stream_id;
		$filter->name = $name;
		$filter->values = $values;
		$filter->filter = $next_filter;

		// chain algorithem
		$filter->input_streams = array($this->last_stream);
		$filter->output_streams = array($this->new_stream());

		$this->push_filter($filter);
	}

	public function add_simple_filter($name,$values) {
	
		$stream_id = $this->new_id();

		$filter = new ffmpeg_filter();
		$filter->id = $stream_id;
		$filter->name = $name;
		$filter->values = $values;

		// chain algorithem
		$filter->input_streams = array($this->last_stream);
		$filter->output_streams = array($this->new_stream());
		
		$this->push_filter($filter);
	}

	private function stream_str($s_id) {
		return "[" . $this->stream_prefix() . $s_id . "]";
	}

	private function new_stream() {
		$s_id = $this->last_stream + 1;
		$this->last_stream = $s_id;
		return $s_id;
	}

	private function build() {

		$this->string .= "-vf" . " " . "'";

		// add input files
		foreach ($this->input_files as $file) {
			$this->string .= $file->get_string();

			foreach ($file->output_streams as $out_stream) {
				$this->string .= $this->stream_str($out_stream);
			}
			$this->string .= ";";
		}

		// filter files
		$this->string .= "[in]fifo" . $this->stream_str(0) . ";";

		foreach ($this->filters as $filter) {
			
			foreach ($filter->input_streams as $in_stream) {
				$this->string .= $this->stream_str($in_stream);
			}

			$this->string .= $filter->get_string();

			foreach ($filter->output_streams as $out_stream) {
				$this->string .= $this->stream_str($out_stream);
			}

			$this->string .= ";";

		}

		$this->string .= $this->stream_str($this->last_stream) . "fifo" . "[out]";
		$this->string .= "'" . " ";

		return $this->string;
	}

	// *************************************************************************************************** //
	// ** ffmpeg-video-filter :: public functions 														** //
	// *************************************************************************************************** //

	public function scale($to_width,$to_height) {
		$this->add_simple_filter("scale",array($to_width,$to_height));
	}

	public function pad_center($output_width,$output_height) {
		$this->add_simple_filter("pad",array($output_width,$output_height,"(ow-iw)/2","(oh-ih)/2"));
	}

	public function crop($crop_width,$crop_heigth,$x,$y) {
		$this->add_simple_filter("crop",array($crop_width,$crop_heigth,$x,$y));
	}

	public function cropdetect($limit = 24,$round = 2,$reset = 0) {
		$this->add_simple_filter("cropdetect",array($limit,$round,$reset));
	}

	public function select_iframes() {
		$this->add_simple_filter("select",array("select=eq(pict_type\,I)"));
	}

	public function select_scence_frames($val) {
		$this->add_simple_filter("select",array("gt(scene\," . $val . ")"));
	}

	public function hflip() {
		$this->add_simple_filter("hflip",array());
	}

	public function vflip() {
		$this->add_simple_filter("vflip",array());
	}

	public function fix_letterbox($letterbox_array,$width,$height) {
		
		$flag = false;
		if (!empty($letterbox_array)) {	
			
			// add crop filter to filter-out the letterbox
			//
			// Multiple bugs here...

			if ($width == 1280 && $height == 720) { 
				$flag = true;			
				$this->crop($letterbox_array["0"],
							$letterbox_array["1"],
							$letterbox_array["2"],
							$letterbox_array["3"]
				);
			}

		}

		return $flag;
	}

	public function transpose($transpose_degree) {
		switch ($transpose_degree) {
			case 0:
				return false;
				break;
			case 90: case 270:

				$info = $this->ffmpeg_wrapper->last_input_info_video;
				$currnet_width = $info["width"];
				$current_height = $info["height"];

				$new_width = intval($current_height * ($current_height / $currnet_width));
				$new_heigth = intval($current_height);

				$padcenter_filter = ffmpeg_filter::filter_with_params(
					"pad",
					array($currnet_width,$current_height,"(ow-iw)/2","(oh-ih)/2"),
					array(),
					array()
				);
				$scale_filter = ffmpeg_filter::filter_with_params(
					"scale",
					array($new_width,$new_heigth),
					array(),
					array(),
					$padcenter_filter
				);

				$this->add_simple_filter_with_filter("transpose",array(1),$scale_filter);

				if ($transpose_degree == 270) {
					$this->hflip();				
				}
				return true;
				break;
			case 180:
				$this->hflip();
				$this->vflip();
				return true;
				break;

			default:
				return false;
				break;
		}
		return false;
	}

	public function overlay($movie,$x,$y) {

		$overlay_stream_id = $this->add_input($movie);

		$overlay = new ffmpeg_filter();
		$overlay->id = $this->new_id();
		$overlay->name = "overlay";
		$overlay->values = array($x,$y);

		// chain algorithem
		$overlay->input_streams = array($this->last_stream, $overlay_stream_id); // 2 inputs
		$overlay->output_streams = array($this->new_stream());

		$this->push_filter($overlay);
	}

	public function overlay_with_filters($movie,$x,$y,$input_filter = null,$overlay_filter = null) {

		if ($input_filter != null) {
			$overlay_stream_id = $this->add_input($movie,$input_filter);
		} else {
			$overlay_stream_id = $this->add_input($movie);
		}

		$overlay = new ffmpeg_filter();
		$overlay->id = $this->new_id();
		$overlay->name = "overlay";
		$overlay->values = array($x,$y);
		
		if ($overlay_filter != null) {
			$overlay->filter = $overlay_filter;
		}

		// chain algorithem
		$overlay->input_streams = array($this->last_stream, $overlay_stream_id); // 2 inputs
		$overlay->output_streams = array($this->new_stream());

		$this->push_filter($overlay);
	}

}

?>
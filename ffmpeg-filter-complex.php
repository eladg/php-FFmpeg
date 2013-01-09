<?php

require_once("ffmpeg-filter.php");

class ffmpeg_video_filter_complex {

	public $ffmpeg_wrapper;

	public $filters;
	public $string;
	public $input_files;
	public $last_stream;

	private $concat_flag;

	public function __construct($wrapper) {

		$this->ffmpeg_wrapper = $wrapper;

		$this->filters = array();
		$this->input_files = array();
	}

	public function get_string() {
		return $this->build();
	}

	private function stream_prefix() {
		return "";
	}

	private function stream_str($s_id) {
		return "[" . $this->stream_prefix() . $s_id . "]";
	}

	private function new_id() {
		$stream_id = "_cat_" . substr(str_shuffle("abcdefghijklmnopqrstuvwxyz"),0,5);
		return $stream_id;
	}

	private function build() {
		$this->string = "-filter_complex" . " " . "'";

		// add the filters
		foreach ($this->filters as $filter) {
			
			foreach ($filter->input_streams as $in_stream) {
				$this->string .= $this->stream_str($in_stream);
			}

			$this->string .= $filter->get_string();

			foreach ($filter->output_streams as $out_stream) {
				$this->string .= $this->stream_str($out_stream);
			}

		}

		$this->string .= "'" . " ";

		if ($this->concat_flag) {
			$this->string .= "-map '[v]'" . " ";
		}

		return $this->string;
	}

	// *************************************************************************************************** //
	// ** ffmpeg_video_filter_complex :: public functions												** //
	// *************************************************************************************************** //

	public function concat_update_filter() {
	
		$concat = new ffmpeg_filter();
		$concat->id = $this->new_id();
		$concat->name = "concat";

		$concat->values = array( "n=" . count($this->input_files) , "v=1" , "a=0");

		$concat->input_streams = array();
		for ($i=0; $i < count($this->input_files) ; $i++) { 
			array_push($concat->input_streams, $i . ":0");			
		}
		$concat->output_streams = array("v");

		// reset the filters array
		$this->filters = array($concat);

	}

	public function concat_add($file) {
		$this->concat_flag = true;
		array_push($this->input_files, $file);
		$this->concat_update_filter();
	}

}

?>
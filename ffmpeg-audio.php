<?php

require_once("ffmpeg-parameter.php");

class ffmpeg_audio {

	public $ffmpeg_wrapper;
	public $attribs;

	public function __construct($wrapper) {
		$this->ffmpeg_wrapper = $wrapper;
		$this->attribs = array();
	}

	public function add_attrib($param, $value = "") {
		array_push($this->attribs, new ffmpeg_parameter($param,$value));
	}

	public function get_string() {

		$str = "";
		foreach ($this->attribs as $param) {
			$str .= $param->get_string() . " ";
		}
		return $str;
	}

	public function encoding($fmt) {
		$this->add_attrib("c:a",$fmt);
	}

	public function qscale($int = 0) {
		$this->add_attrib("q:a",$int);
	}

	public function disable() {
		$this->add_attrib("an");
	}

	public function copy() {
		$this->encoding("copy");
	}

	public function sample_rate($rate) {
		$this->add_attrib("ar",$rate);
	}

	public function channels($chan) {
		$this->add_attrib("ac",$chan);
	}

	public function quality($quality) {
		$this->add_attrib("aq",$quality);
	}

	public function bitrate($bitrate) {
		$this->add_attrib("b:a",$bitrate);
	}

	public function start_time($time) {
		$this->add_attrib("ss",$time);
	}

	public function length($length) {
		$this->add_attrib("t",$length);
	}

}

?>
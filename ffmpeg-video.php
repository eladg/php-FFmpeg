<?php

require_once("ffmpeg-parameter.php");

class ffmpeg_video {

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

	public function rate($rate) {
		$this->add_attrib("r",$rate);
	}

	public function encoding($fmt) {
		$this->add_attrib("c:v",$fmt);
	}

	public function group_of_picture($int) {
		$this->add_attrib("g",$int);
	}

	public function qscale($int = 0) {
		$this->add_attrib("q:v",$int);
	}

	public function disable() {
		$this->add_attrib("vn");
	}

	public function copy() {
		$this->encoding("copy");
	}

	public function bitrate($bitrate) {
		$this->add_attrib("b:v",$bitrate);
	}

	public function start_time($time) {
		$this->add_attrib("ss",$time);
	}

	public function length($length) {
		$this->add_attrib("t",$length);
	}

	public function format($format) {
		$this->add_attrib("f",$format);
	}

	public function clear_metadata() {
		$this->add_attrib("map_metadata","-1");
	}

}

?>
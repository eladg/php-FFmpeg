<?php

require_once("ffmpeg-wrapper.php");

class ffmpeg_presets {

	static public $ffmpeg_path = "/usr/local/bin/ffmpeg";

	static function empty_movie($size, $rate, $length) {
		
		$ffmpeg = new ffmpeg_wrapper(ffmpeg_presets::$ffmpeg_path);

		$ffmpeg->set_input_format("rawvideo");
		$ffmpeg->set_pixcel_format("rgb24");
		$ffmpeg->add_global_param("s",$size);
		$ffmpeg->add_input("/dev/zero");
		$ffmpeg->video->rate($rate);
		$ffmpeg->video->length($length);

		return $ffmpeg;

	}

	static function movie_cutter($file, $start, $length) {

		$ffmpeg = new ffmpeg_wrapper(ffmpeg_presets::$ffmpeg_path);

		$ffmpeg->add_input($file);
		$ffmpeg->add_global_param("ss",$start);

		$ffmpeg->video->encoding("copy");
		$ffmpeg->audio->encoding("copy");

		$ffmpeg->video->length($length);

		return $ffmpeg;

	}

}

?>
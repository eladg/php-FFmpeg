<?php

require_once("ffmpeg-wrapper.php");

class ffmpeg_presets {

	static function empty_movie($size, $length, $rate = 30) {
		
		$ffmpeg = new ffmpeg_wrapper();

		$ffmpeg->set_input_format("rawvideo");
		$ffmpeg->set_pixcel_format("rgb24");
		$ffmpeg->add_global_param("s",$size);
		$ffmpeg->add_input("/dev/zero");
		$ffmpeg->video->rate($rate);
		$ffmpeg->video->length($length);

		return $ffmpeg;

	}

	static function movie_cutter($file, $start, $length) {

		$ffmpeg = new ffmpeg_wrapper();

		$ffmpeg->add_global_param("ss",$start);

		$ffmpeg->video->encoding("copy");
		$ffmpeg->audio->encoding("copy");

		$ffmpeg->video->length($length);

		return $ffmpeg;

	}

	static function video_consolidator($file, $new_width, $new_height) {

		$ffmpeg = new ffmpeg_wrapper();

		$ffmpeg->add_input($file);
		$ffmpeg->video->qscale(0);

		// initialize flags
		$info = ffmpeg_wrapper::video_stream_info($file);
		$letterbox_flag = false;
		$transpose_flag = false;
		$scale_flag     = false;
		$pad_flag       = false;

		$input_width  = intval($info["width"]);
		$input_heigth = intval($info["height"]);
		$aspect_ratio = floatval($input_width / $input_heigth);

		/**
		 *  fix letterbox videos
		 */
		$letterbox_array = ffmpeg_wrapper::analyze_letterbox_video($file);
		if ($ffmpeg->video_filter->fix_letterbox($letterbox_array,$new_width,$new_height)) {
			if (FFMPEG_WRAPPER_DEBUG_PRINTS) {
				echo ">> letterbox needed" . PHP_EOL;
			}
		} else {
			if (FFMPEG_WRAPPER_DEBUG_PRINTS) {
				echo ">> no letterbox needed" . PHP_EOL;
			}
		}

		/**
		 *  convert vertical videos
		 */
		$transpose_degree = ffmpeg_wrapper::analyze_rotation($file);
		if ($ffmpeg->video_filter->transpose($transpose_degree)) {
			if (FFMPEG_WRAPPER_DEBUG_PRINTS) {
				echo ">> transpose needed" . PHP_EOL;
			}
		} else {
			if (FFMPEG_WRAPPER_DEBUG_PRINTS) {
				echo ">> no transpose needed" . PHP_EOL;
			}
		}

		/**
		 *  check if padding is needed
		 */

		/**
		 *  scale to new given sizes
		 */
		
		if ( $input_width != $new_width || $input_heigth != $new_height	) {
			$scale_flag = true;
			if (FFMPEG_WRAPPER_DEBUG_PRINTS) {
				echo ">> scale needed" . PHP_EOL;
			}
			$ffmpeg->video_filter->scale($new_width,$new_height);
		} else {
			if (FFMPEG_WRAPPER_DEBUG_PRINTS) {
				echo ">> no scale needed" . PHP_EOL;
				echo ">>> "; 
				echo "input: " . $input_width . "x" . $input_heigth . PHP_EOL;
				echo ">>> "; 
				echo "new_scale: " . $new_width . "x" . $new_height . PHP_EOL;
			}
		}

		if (FFMPEG_WRAPPER_DEBUG_PRINTS) {
			echo PHP_EOL;
		}

		return $ffmpeg;
	}

}

?>
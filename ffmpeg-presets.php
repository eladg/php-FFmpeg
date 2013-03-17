<?php

require_once("ffmpeg-wrapper.php");
require_once("ffmpeg-utils.php");

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

		$ffmpeg->add_input($file);
		$ffmpeg->add_global_param("ss",$start);

		// encoding
		$ffmpeg->video->encoding("copy");
		$ffmpeg->audio->encoding("copy");

		// qscale
		$ffmpeg->audio->qscale("0");
		$ffmpeg->video->qscale("0");

		$ffmpeg->video->length($length);

		return $ffmpeg;

	}

	static function split_screen_video($files) {

		$total = count($files);

		// @TODO: dynamic size needed
		$input_size = new ffmpeg_size(1280,720);

		// @TODO: 	find/get the length of the longest movie
		// 			alternatively, use something like -shortest to 'movie=' filter
		$ffmpeg = ffmpeg_presets::empty_movie($input_size->to_string(), 300);

		// find the size of the cube
		if (in_array($total, range(1, 4))) {
			$cube = 2;
		} else if (in_array($total, range(5, 9))) {
			$cube = 3;
		} else if (in_array($total, range(10, 16))) {
			$cube = 4;
		} else {
			$cube = 1;
		}

		$output_w = $input_size->width / $cube;
		$output_h = $input_size->heigth / $cube;

		// from top to bottom, left to right
		$points = array();
		for ($i=0; $i < $total ; $i++) { 
			$row = floor($i / $cube);
			$colm = ($i % $cube);

			$px = round($colm*$output_w);
			$py = round($row*$output_h);
			
			// echo $row . ":" . $colm . "\t" . $px . ":" . $py . PHP_EOL;
			$points[$i] = new ffmpeg_point($colm*$output_w,$row*$output_h);

			$ffmpeg->video_filter->overlay_with_filters(
				$files[$i],
				$points[$i]->x,
				$points[$i]->y,
				ffmpeg_filter::scale($input_size->width/$cube,$input_size->heigth/$cube)
			);

		}

		return $ffmpeg;

	}

	static function video_consolidator($file, $new_width, $new_height) {

		$ffmpeg = new ffmpeg_wrapper();

		$ffmpeg->add_input($file);
		$ffmpeg->video->qscale(0);

		// initialize flags
		$info = ffmpeg_wrapper::video_stream_info($file);
		$input_width      = intval($info["width"]);
		$input_heigth     = intval($info["height"]);
		$aspect_ratio     = round(floatval($input_width / $input_heigth),3);
		$new_aspect_ratio = round(floatval($new_width / $new_height),3);

		/**
		 *  fix letterbox videos
		 */
		$ffmpeg->fix_letterbox($file,$new_width,$new_height);

		/**
		 *  convert vertical videos
		 */
		$ffmpeg->fix_vertical($file);

		/**
		 *  check if padding is needed
		 */
		$ffmpeg->fix_aspect_ratio_normal($aspect_ratio, $new_aspect_ratio);

		/**
		 *  scale to new given sizes
		 */		
		if ( $input_width != $new_width || $input_heigth != $new_height	) {
			if (FFMPEG_WRAPPER_DEBUG_PRINTS) {
				echo ">> scale needed" . PHP_EOL;
			}
			$ffmpeg->video_filter->scale($new_width,$new_height);
		} else {
			if (FFMPEG_WRAPPER_DEBUG_PRINTS) {
				echo ">> no scale needed" . PHP_EOL;
			}
		}

		return $ffmpeg;
	}

}

?>
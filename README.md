ffmpeg-wrapper
==============

php-ffmpeg-wrapper is a class implementation of the popular ffmpeg audio/video command-line editing tool.

## Examples:

### Create a 10 seconds of 1280x720 video.

```php
<?php
$ffmpeg = ffmpeg_presets::empty_movie("1280x720",10);
$ffmpeg->set_output("empty.mp4");
$ffmpeg->run();

echo $ffmpeg->response() . PHP_EOL;
?>
```

### Trim, scale and encode to a new format.

```php
<?php
$ffmpeg = new ffmpeg_wrapper("/usr/local/bin/ffmpeg");

$ffmpeg->add_input("video_file.mp4");                 # input
$ffmpeg->set_output("output.mp4");                    # output

$ffmpeg->trim_range(1.2,3.3);                         # trim
$ffmpeg->video_filter->scale(640,480);                # scale

$ffmpeg->audio->encoding("libfaac");                  # audio/video configuration
$ffmpeg->audio->bitrate("192k");
$ffmpeg->video->encoding("libx264");
$ffmpeg->video->group_of_picture(30);
$ffmpeg->video->qscale(0);

echo $ffmpeg->run() . PHP_EOL;                        #execute
?>
```

### Concatanate inputs

```php
<?php

$ffmpeg = new ffmpeg_wrapper("/usr/local/bin/ffmpeg");

$ffmpeg->add_concatenate_input("input_1.mp4");        # input files...
$ffmpeg->add_concatenate_input("input_2.mp4");
...
$ffmpeg->add_concatenate_input("input_n.mp4");
$ffmpeg->set_output("concat.output.mp4");             # output

$ffmpeg->video->encoding("mpeg2video");               # audio/video configuration
$ffmpeg->video->group_of_picture(1);
$ffmpeg->video->qscale(0);
$ffmpeg->audio->disable();                            # disable audio

echo $ffmpeg->run() . PHP_EOL;                        #execute
?>
```

### Overlay inputs with and without filters

```php
<?php

$length = 6;
$rate   = 30;

$ffmpeg = ffmpeg_presets::empty_movie("1280x720",$length,$rate);

# create the video filters
$scale_640x360 = ffmpeg_filter::scale(640,360);       
$crop_640x360  = ffmpeg_filter::filter_with_params("crop",array(640,360,0,0), array(), array() );
$fade_end      = ffmpeg_filter::filter_with_params("fade",array("out",$length*$rate-10,"10"), array(), array());

//                                           input video  offset input filter overlay filter
//                                                |       |   |       |            |
//                                                v       v   v       v            v
$ffmpeg->video_filter->overlay_with_filters("input_1.png",0  ,0,$scale_640x360, $fade_end);
$ffmpeg->video_filter->overlay_with_filters("input_2.png",640,0,$scale_640x360, null);
$ffmpeg->video_filter->overlay_with_filters("input_3.png",0,360,$crop_640x360);
$ffmpeg->video_filter->overlay("input_4.png",640,360);

$ffmpeg->run();
?>
```

### Split screen CUBED video

```php
<?php

$files = array (
	"files/movie0.mp4",
	"files/movie1.mp4",
	"files/movie2.mp4",
	"files/movie3.mp4",
);

$ffmpeg = ffmpeg_presets::split_screen_video($files);
$ffmpeg->set_output("video.mp4");

$ffmpeg->run();

?>
```
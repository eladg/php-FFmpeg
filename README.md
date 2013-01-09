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

$ffmpeg->add_input("files/video_file.mp4");           # input
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
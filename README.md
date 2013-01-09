ffmpeg-wrapper
==============

PHP class wrapping of the popular ffmpeg video command line tool

Examples:

Create a 10 seconds of 1280x720 video.

  <?php
  $ffmpeg = ffmpeg_presets::empty_movie("1280x720",30,10);
  $ffmpeg->set_output("empty.mp4");
  $ffmpeg->run();
  
  echo $ffmpeg->response() . PHP_EOL;
  ?>

Trim and Scale video.

  <?php
  $ffmpeg = new ffmpeg_wrapper("/usr/local/bin/ffmpeg");
  $ffmpeg->simulate = true;
  
  #input/output files
  $ffmpeg->add_input("files/video_file.mp4");
  $ffmpeg->set_output("output.mp4");  
  
  #trim
  $ffmpeg->set_start_from("11.2");
  $ffmpeg->video->length("8.3");
  
  #scale
  $ffmpeg->video_filter->scale(640,480);
  
  #audio/video configuration
  $ffmpeg->audio->encoding("libfaac");
  $ffmpeg->audio->bitrate("192k");
  $ffmpeg->video->encoding("mpeg2video");
  $ffmpeg->video->group_of_picture(1);
  $ffmpeg->video->qscale(0);
  
  #execute
  echo $ffmpeg->run() . PHP_EOL;
  ?>

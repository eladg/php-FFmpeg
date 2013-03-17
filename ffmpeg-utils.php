<?php

class ffmpeg_size {
	public $width;
	public $heigth;

	public function __construct($w,$h) {
		$this->width  = intval($w);
		$this->heigth = intval($h);
	}

	public function to_string($fmt = "x") {
		if ($fmt == ":") {
			return $this->width . ":" . $this->heigth;
		} else {
			return $this->width . "x" . $this->heigth;
		}
	}
}

class ffmpeg_point {
	public $x;
	public $y;

	public function __construct($px,$py) {
		$this->x = $px;
		$this->y = $py;
	}
	public function to_string() {
		return $this->x . ":" . $this->y;
	}
}


?>
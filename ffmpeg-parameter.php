<?php

class ffmpeg_parameter { 

	public $key;
	public $value;

	public function __construct($key, $value = "") {
		$this->key = strval($key);
		$this->value = strval($value);
	}

	public function get_string() {

		if ($this->value != "" ) {
			return "-" . $this->key . " " . $this->value;	
		} else {
			return "-" . $this->key;
		}
	}

	public function get_string_for_path() {
		return "-" . $this->key . " \"" . $this->value . "\"";	
	}
}

?>
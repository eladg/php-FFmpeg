<?php

class ffmpeg_filter { 

	public $id;
	public $name;
	public $values;
	public $input_streams;
	public $output_streams;
	public $filter;

	public function __construct() {
		$filter = null;		
	}

	static public function filter_with_params($name,$values,$input_streams,$output_streams,$next_filter = null) {
		$filter = new ffmpeg_filter();
		$filter->name = $name;
		$filter->values = $values;
		$filter->input_streams = $input_streams;
		$filter->output_streams = $output_streams;
		$filter->filter = $next_filter;

		return $filter;
	}

	static public function scale($width,$heigth) {
		$filter = new ffmpeg_filter();

		$filter->name = "scale";
		$filter->values = array($width,$heigth);
		$filter->input_streams = array();
		$filter->output_streams = array();

		return $filter;
	}

	public function get_string() {
		$str = $this->name;

		if (!empty($this->values)) {
			$str .=  "=";
		} else {
			return $str;
		}

		$numItems = count($this->values);
		$i = 0;

		foreach ($this->values as $param) {
			$str .= $param;
			if (!(++$i === $numItems)) {
				$str .= ":";
			}
  		}

  		if (!is_null($this->filter)) {
  			$str .= "," . $this->filter->get_string();
  		}

  		return $str;
	}

	public function add_seq_filter($filter) {
		$this->filter = $filter;
	}
}

?>
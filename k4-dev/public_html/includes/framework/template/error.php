<?php

class FAErrorStack {
	var $errors = array();

	function reset() {
		$this->errors = array();
	}

	function push($error) {
		return array_push($this->errors, $error);
	}

	function &pop() {
		return array_pop($this->errors);
	}
}

class Error {
	function &getStack() {
		static $instance = NULL;

		if ($instance == NULL)
			$instance = new FAErrorStack();

		return $instance;
	}

	function reset() {
		$stack = &Error::getStack();
		$stack->reset();
	}

	function grab($type = 'FAError') {
		$stack = &Error::getStack();

		for ($i = sizeof($stack->errors) - 1; $i >= 0; $i--) {
			if (is_a($stack->errors[$i], $type))
				return TRUE;
		}
	}

	function pitch(&$error) {
		assert(is_a($error, 'FAError') || die("Thrown errors must inherit from FAError"));

		$stack = &Error::getStack();
		$stack->push($error);

		return FALSE;
	}
}

?>

<?php
function isint(&$val) {
	if(ctype_digit($val) || is_int($val)) {
		$val = (int) $val;
		return true;
	}
	return false;
}
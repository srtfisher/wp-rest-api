<?php namespace WpRest\Common;

class Helper {
	/**
	 * Detect if a String is JSON
	 *
	 * @link http://stackoverflow.com/a/6041773/290197
	 * @param  string
	 * @return boolean
	 */
	public static function isJson($string)
	{
		json_decode($string);
		return (json_last_error() == JSON_ERROR_NONE);
	}

	/**
	 * Automatically Attempt to Decode any JSON in the string
	 *
	 * @param  string
	 * @return  mixed
	 */
	public static function jsonDecode($string, $assoc = false, $depth = 512, $options = 0)
	{
		return (self::isJson($string)) ? json_decode($string, $assoc, $depth, $options) : $string;
	}
}
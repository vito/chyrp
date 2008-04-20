<?php

	/**
	 * File: Input
	 *
	 * Chyrp - A Lightweight Blogging Engine
	 *
	 * Version:
	 *     v1.1.3
	 *
	 * License:
	 *     GPL-3
	 *
	 * Chyrp Copyright:
	 *     Copyright (c) 2008 Alex Suraci, <http://i.am.toogeneric.com/>
	 */

	/**
	 * Function: sanitize_input
	 * Makes sure no inherently broken ideas such as magic_quotes break our application
	 *
	 * Parameters:
	 *     $data - The array to be sanitized, usually one of ($_GET, $_POST, $_COOKIE, $_REQUEST)
	 */
	function sanitize_input(& $data)
	{
		$isMagic = get_magic_quotes_gpc();

		foreach ($data as & $value)
		{
			if (is_array($value))
			{
				sanitize_input($value);
			}
			else
			{
				$value = $isMagic ? stripslashes($value) : $value;
			}
		}
	}

	sanitize_input($_GET);
	sanitize_input($_POST);
	sanitize_input($_COOKIE);
	sanitize_input($_REQUEST);


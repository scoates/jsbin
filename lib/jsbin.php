<?php

function jsbin_template($template, array $params=null)
{
	$_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..'
		. DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR
		. $template;
	if (!is_readable($_path)) {
		throw new Exception("Could not find template file.");
	}
	unset($template);
	ob_start();
	include $_path;
	return ob_get_clean();
}


function getCodeIdParams($request) {
	$revision = array_pop($request);
	$code_id = array_pop($request);
	
	if ($code_id == null) {
		$code_id = $revision;
		$revision = 1;
	}
	
	return array($code_id, $revision);
}


function defaultCode($not_found = false) {
  $library = '';
  
  if (isset($_GET['html']) && $_GET['html']) {
    $html = $_GET['html'];
  } else {
		$html = jsbin_template('default_html.php');
  } 

  $javascript = '';

  if (!isset($_GET['js']) || !$_GET['js']) {
    if ($not_found) {
		$javascript = jsbin_template('no_code_js.php');
    } else {
		$javascript = jsbin_template('default_js.php');
    }
  } else {
    $javascript = $_GET['js'];
  }

  if (get_magic_quotes_gpc()) {
    $html = stripslashes($html);
    $javascript = stripslashes($javascript);
  }

  return array($html, $javascript);
}

// I'd consider using a tinyurl type generator, but I've yet to find one.
// this method also produces *pronousable* urls
function generateCodeId($tries = 0)
{
	// generates 5 char word
	static $vowels = 'aeiou';
	static $const = 'bcdfghjklmnpqrstvwxyz';

	for ($tries=0; $tries < 10; $tries++) {
		$code_id = '';
		for ($i = 0; $i < 5; $i++) {
			if ($i % 2) { // even = vowels
				$code_id .= $const[rand(0, 20)];
			} else {
				$code_id .= $vowels[rand(0, 4)]; 
			} 
		}
		
		if ($tries > 2) {
			$code_id .= $tries;
		}
		
		// check if it's free
		$sql = sprintf('select id from sandbox where url="%s"', mysql_real_escape_string($code_id));
		$result = mysql_query($sql);

		if (!mysql_num_rows($result)) {
			return $code_id;
		}
	}

	// if we get this far, we have exhausted 10 tries
	echo 'Too many tries to find a new code_id - please contact using <a href="/about">about</a>';
	exit;

}


<?php
$baseDir  = dirname(__file__);
$docDir   = $baseDir . '/pages/';
$docIndex = 'index';

require_once $baseDir . '/MarkdownWiki.php';
require_once $baseDir . '/Markdown.php';

//echo "Index.php: {$docDir}\n";
//echo "BaseDir: {$baseDir}\n";

// Parsing the request
$request = getRequest($docIndex);
$request->filename = "{$docDir}{$request->page}.markdown";

if (file_exists($request->filename)) {
	//echo "Found: {$request->filename}\n";
	$request->content = file_get_contents($request->filename);
}

// If content doesn't exist go into editing mode.
if (is_null($request->content)) {
	$request->action = 'edit';
}

switch($request->action) {
	case 'display':
		doDisplay($request);
		break;
	case 'edit':
		doEdit($request);
		break;
	case 'history':
	case 'admin':
	case 'browse':
	default:
		echo "<h3>Action: {$request->action}</h3>";
		break;
}

echo '<pre>'; print_r($request); echo '</pre>';
//phpinfo();

function doDisplay($request) {
	$content = htmlspecialchars($request->content);
	echo <<<HTML
<pre>{$content}</pre>
HTML;
}

function doEdit($request) {
	echo <<<HTML
<textarea cols="78" rows="20">{$request->content}</textarea>
HTML;
}


function getRequest($docIndex) {
	$request = (object) NULL;
	
	if (!empty($_REQUEST['id'])) {
		$request->page   = $_REQUEST['id'];
		$request->action = $_REQUEST['action'];
	} elseif (!empty($_SERVER['PATH_INFO'])) {
		$request->page   = substr($_SERVER['PATH_INFO'], 1);
		$request->action = 'display';
	} else {
		$request->page   = '';
		$request->action = 'display';
	}
	
	if ($request->page=='') {
		$request->page = $docIndex;
	}
	
	if ($request->page[strlen($request->page)-1]=='/') {
		$request->page .= $docIndex;
	}

	$request->filename = NULL;
	$request->content  = NULL;

	return $request;
}

?>
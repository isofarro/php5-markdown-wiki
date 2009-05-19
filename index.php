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
$filename = "{$docDir}{$request->page}.markdown";

if (file_exists($filename)) {
	echo "Found: {$filename}\n";
	$request->content = file_get_contents($filename);
}

// If content doesn't exist go into editing mode
if (is_null($request->content)) {
	$request->action = 'edit';
}


echo '<pre>'; print_r($request); echo '</pre>';
//phpinfo();


function getRequest($docIndex) {
	$request = (object) NULL;
	
	if (!empty($_REQUEST['id'])) {
		$request->action = $_REQUEST['action'];
		$request->page   = $_REQUEST['id'];
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

	$request->content = NULL;

	return $request;
}

?>
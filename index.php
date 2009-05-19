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
echo '<pre>'; print_r($request); echo '</pre>';
//phpinfo();

$filename = "{$docDir}{$request->page}.markdown";
if (file_exists($filename)) {
	echo "Found: {$filename}\n";
	$content = file_get_contents($filename);
	
	echo <<<HTML
<pre>
{$content}
</pre>
HTML;
}





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
	return $request;
}

?>
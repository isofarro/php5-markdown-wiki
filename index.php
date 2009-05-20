<?php
$baseDir  = dirname(__file__);
$docDir   = $baseDir . '/pages/';
$docIndex = 'index';

require_once $baseDir . '/Markdown.php';

//echo "Index.php: {$docDir}\n";
//echo "BaseDir: {$baseDir}\n";

// Parsing the request
$request = getRequest($docIndex);
$request->filename = "{$docDir}{$request->page}.text";

if (file_exists($request->filename)) {
	//echo "Found: {$request->filename}\n";
	$request->content = file_get_contents($request->filename);
	$request->updated = filectime($request->filename);
}

// If content doesn't exist go into editing mode.
if (is_null($request->content)) {
	$request->action = 'edit';
}

switch($request->action) {
	case 'display':
		$response = doDisplay($request);
		break;
	case 'edit':
		$response = doEdit($request);
		break;
	case 'save':
		$response = doSave($request);
		break;
	case 'history':
	case 'admin':
	case 'browse':
	default:
		echo "<h3>Action: {$request->action}</h3>";
		break;
}

renderPage($response);


echo '<pre>'; print_r($request); echo '</pre>';
//phpinfo();

function doDisplay($request) {
	$response = array('request'=>$request);

	$response['title']   = "Displaying: {$request->page}";
	$content = htmlspecialchars($request->content);
	$response['content'] = <<<HTML
<pre>{$content}</pre>
HTML;

	$response['footer'] = <<<HTML
<ul>
	<li><a href="{$request->path}?action=edit&amp;id={$request->page}">Edit</a></li>
</ul>
HTML;

	return $response;
}

function doEdit($request) {
	$response = array('request'=>$request);

	$response['title']   = "Editing: {$request->page}";
	$response['content'] = <<<HTML
<form action="{$request->path}/{$request->page}" method="post">
	<fieldset>
		<legend>Editing</legend>
		<label for="title">Title:</label><br>	
		<input type="text" name="title" id="title" size="78"><br>
		
		<label for="text">Content:</label><br>	
		<textarea cols="78" rows="20" name="text" id="text">{$request->content}</textarea>
		<br>

		<input type="submit" name="preview" value="Preview">
		<input type="submit" name="save" value="Save">
		<input type="hidden" name="updated" value="{$request->updated}">
	</fieldset>
</form>
HTML;
	$response['footer'] = '';

	return $response;
}

function doSave($request) {
	return doDisplay($request);
}


function renderPage($response) {
	echo <<<PAGE
<html lang="en-GB">
<head>
	<title>{$response['title']}</title>
</head>
<body>
	<div id="page">
		<div id="head"></div>
		<div id="content">{$response['content']}</div>	
		<div id="related"></div>	
		<div id="foot">{$response['footer']}</div>	
	</div>
</body>
</html>
PAGE;
}




function getRequest($docIndex) {
	$request = (object) NULL;
	$request->action = 'display';

	if (!empty($_SERVER['PATH_INFO'])) {
		$request->page   = substr($_SERVER['PATH_INFO'], 1);
	}

	if ($_SERVER['REQUEST_METHOD']=='POST') {
		$request->post = (object) NULL;
		$request->action = 'preview';

		print_r($_POST);
		if (!empty($_POST['save'])) {
			$request->action = 'save';
		}
		
		$request->post->title   = $_POST['title'];
		$request->post->text    = $_POST['text'];
		$request->post->updated = $_POST['updated'];
	} elseif ($_SERVER['REQUEST_METHOD']=='GET') {
	
		if (!empty($_GET['id'])) {
			$request->page   = $_GET['id'];
			$request->action = $_GET['action'];
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
	}
	
	if ($request->page[strlen($request->page)-1]=='/') {
		$request->page .= $docIndex;
	}

	$request->filename = NULL;
	$request->content  = NULL;
	$request->path     = '/markdown.php';

	return $request;
}

?>
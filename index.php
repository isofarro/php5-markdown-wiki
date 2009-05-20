<?php
$baseDir  = dirname(__file__);
$docDir   = $baseDir . '/pages/';
$docIndex = 'index';

require_once $baseDir . '/markdown.php';

//echo "Index.php: {$docDir}\n";
//echo "BaseDir: {$baseDir}\n";

// Parsing the request
$request = getRequest($docIndex);
$request->filename = "{$docDir}{$request->page}.text";

if (file_exists($request->filename)) {
	$request->content = file_get_contents($request->filename);
	$request->updated = filectime($request->filename);

	if (!empty($request->post)) {
		if ($request->updated > $request->post->updated) {
			$request->messages[] =
				"Editing conflict: The page you are editing has been updated by someone else.";
			$request->action = 'preview';
		}
	}
} else {
	$request->content = '';
	$request->updated = 0;
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
	case 'preview':
		$response = doPreview($request);
		break;
	case 'history':
	case 'admin':
	case 'browse':
	default:
		echo "<h3>Action: {$request->action}</h3>";
		break;
}

renderPage($response);


//echo '<pre>'; print_r($request); echo '</pre>';
//phpinfo();

function doDisplay($request) {
	$response = array('request'=>$request);

	$response['title']   = "Displaying: {$request->page}";
	$response['content'] = Markdown($request->content);

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


function doPreview($request) {
	$response = array('request'=>$request);

	$msg = '';
	if ($request->messages) {
		foreach($request->messages as $message) {
			$msg .= <<<HTML
<li>{$message}</li>
HTML;
		}
		$msg = "<ul>\n{$msg}</ul>";
	}
	$response['messages'] = $msg;
	$content = Markdown($request->post->text);
	$response['title']   = "Preview: {$request->page}";
	$response['content'] = <<<HTML
<h2>Preview: {$request->page}</h2>
{$response['messages']}
{$content}
<form action="{$request->path}/{$request->page}" method="post">
	<fieldset>
		<legend>Editing</legend>
		<label for="text">Content:</label><br>	
		<textarea cols="78" rows="20" name="text" id="text">{$request->post->text}</textarea>
		<br>

		<input type="submit" name="preview" value="Preview">
		<input type="submit" name="save" value="Save">
		<input type="hidden" name="updated" value="{$request->post->updated}">
	</fieldset>
</form>
HTML;
	$response['footer'] = '';

	return $response;
}


function doSave($request) {
	if (file_exists($request->filename) && 
		$request->updated > $request->post->updated) {
		$request->message[] = "Editing conflict";
		return doPreview($request);
	}
	// TODO: check the directory exists
	$directory = dirname($request->filename);
	if (!file_exists($directory)) {
		mkdir($directory, 0777, true);		
	} elseif (!is_dir($directory)) {
		$request->message[] =
			"Cannot create {$request->page}";
	}
	file_put_contents(
		$request->filename,
		$request->post->text
	);
	$request->content = $request->post->text;
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
		<div id="content">
{$response['content']}
		</div>	
		<div id="related"></div>	
		<div id="foot">
{$response['footer']}
		</div>	
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

		//print_r($_POST);
		if (!empty($_POST['save'])) {
			$request->action = 'save';
		}
		
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
	$request->messages = array();

	return $request;
}

function slugify($text) {
	$text = strtolower(trim($text));
	$text = preg_replace(
		array(
			'/([\'\"]+)/',
			'/([^a-z0-9\/]+)/',
			'/(--+)/'
		),
		array(
			'',
			'-',
			'-'
		), $text
	);
	return $text;
}

?>
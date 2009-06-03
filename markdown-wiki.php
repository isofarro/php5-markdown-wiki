<?php

class MarkdownWiki {
	// Wiki default configuration. All overridableindex
	protected $config = array(
		'docDir'      => '/tmp/',
		'defaultPage' => 'index',
		'newPageText' => 'Start editing your new page'
	);
	
	// An instance of the Markdown parser
	protected $parser;
	protected $baseUrl;

	public function __construct($config=false) {
		$this->initWiki();
		if ($config) {
			$this->setConfig($config);
		}
	}
	
	protected function initWiki() {
		$baseDir = dirname(__FILE__) . '/';

		// Including the markdown parser
		//echo "BaseDir: {$baseDir}\n";
		require_once $baseDir . 'markdown.php';
	}
	
	public function wikiLink($link) {
		global $docIndex;

		$isNew = false;
		$wikiUrl = $link;
		
		if (preg_match('/^\/?([a-z0-9-]+(\/[a-z0-9-]+)*)$/', $link, $matches)) {
			$wikiUrl = "{$this->baseUrl}{$matches[1]}";
			$isNew = !$this->isMarkdownFile($link);
		} elseif ($link=='/') {
			$wikiUrl = "{$this->baseUrl}{$this->config['defaultPage']}";
			$isNew = !$this->isMarkdownFile($this->config['defaultPage']);
		}
	
		return array($isNew, $wikiUrl);
	}

	public function isMarkdownFile($link) {
		//echo "{$docDir}{$link}.text<br>";
		return file_exists("{$this->config['docDir']}{$link}.text");
	}

	public function setConfig($config) {
		$this->config = array_merge($this->config, $config);
	}

	public function handleRequest($request=false, $server=false) {
		$action           = $this->parseRequest($request, $server);
		$action->model    = $this->getModelData($action);
		
		// If this is a new file, switch to edit mode
		if ($action->model->updated==0 && $action->action=='display') {
			$action->action = 'edit';
		}		

		$action->response = $this->doAction($action);
		$output           = $this->renderResponse($action->response);

		//echo '<pre>'; print_r($action); echo '</pre>';
	}
	
	public function doAction($action) {
		
		switch($action->action) {
			case 'UNKNOWN': # Default to display
			case 'display':
				$response = $this->doDisplay($action);
				break;
			case 'edit':
				$response = $this->doEdit($action);
				break;
			case 'preview':
				$response = $this->doPreview($action);
				break;
			case 'save':
				$response = $this->doSave($action);
				break;
			case 'history':
			case 'admin':
			case 'browse':
			default:
				$response = array( 
					'messages' => array(
						"Action {$action->action} not implemented."
					)
				);
				print_r($action);
				break;
		}

		return $response;
	}
	
	protected function doDisplay($action) {
		$response = array(
			'title'    => "Displaying: {$action->page}",
			'content'  => $this->renderDocument($action),
			'editForm' => '',
			'options'  => array(
				'Edit' => "{$action->base}{$action->page}?action=edit&amp;id={$action->page}"
			),
			'related'  => ''
		);
		
		return $response;
	}
	
	protected function doEdit($action) {
		$response = array(
			'title'    => "Editing: {$action->page}",
			'content'  => '',
			'editForm' => $this->renderEditForm($action),
			'options'  => array(
				'Cancel' => "{$action->base}{$action->page}"
			),
			'related'  => ''
		);
		
		return $response;
	}
	
	protected function doPreview($action) {
		$response = array(
			'title'    => "Editing: {$action->page}",
			'content'  => $this->renderPreviewDocument($action),
			'editForm' => $this->renderEditForm($action),
			'options'  => array(
				'Cancel' => "{$action->base}{$action->page}"
			),
			'related'  => ''
		);
		
		return $response;
	}

	protected function doSave($action) {
		// TODO: Implement some sort of versioning
		if (empty($action->model)) {
			// This is a new file
			echo "INFO: Saving a new file\n";
		} elseif ($action->model->updated==$action->post->updated) {
			// Check there isn't an editing conflict
			$action->model->content = $action->post->text;
			$this->setModelData($action->model);
		} else {
			echo "WARN: Editing conflict!\n";
		}
		
		return $this->doDisplay($action);
	}

	protected function getModelData($action) {
		$data = (object) NULL;
		
		$data->file    = $this->getFilename($action->page);
		$data->content = $this->getContent($data->file);
		$data->updated = $this->getLastUpdated($data->file);
		
		return $data;
	}
	
	protected function setModelData($model) {
		$directory = dirname($model->file);
		if (!file_exists($directory)) {
			mkdir($directory, 0777, true);		
		} elseif (!is_dir($directory)) {
			echo "ERROR: Cannot create {$model->file}\n";
		}

		file_put_contents($model->file, $model->content);
	}
	
	public function parseRequest($request=false, $server=false) {
		$action = (object) NULL;

		if (!$request) { $request = $_REQUEST; }
		if (!$server)  { $server  = $_SERVER;  }
		
		//echo "Request: "; print_r($request);
		//echo "Server : "; print_r($server);
		
		$action->method = $this->getMethod($request, $server);
		$action->page   = $this->getPage($request, $server);
		$action->action = $this->getAction($request, $server);
		$action->base   = $this->getBaseUrl($request, $server);

		if ($action->method=='POST') {
			$action->post = $this->getPostDetails($request, $server);
		}
		
		// Take a copy of the action base for the wikiLink function
		$this->baseUrl = $action->base;

		return $action;
	}
	
	protected function getFilename($page) {
		return "{$this->config['docDir']}{$page}.text";
	}
	
	protected function getContent($filename) {
		if (file_exists($filename)) {
			return file_get_contents($filename);
		}
		return $this->config['newPageText'];
	}
	
	protected function getLastUpdated($filename) {
		if (file_exists($filename)) {
			return filectime($filename);
		}
		return 0;
	}
	
	protected function getMethod($request, $server) {
		if (!empty($server['REQUEST_METHOD'])) {
			return $server['REQUEST_METHOD'];
		}
		return 'UNKNOWN';
	}
	
	protected function getPage($request, $server) {
		$page = '';
		
		// Determine the page name
		if (!empty($server['PATH_INFO'])) {
			//echo "Path info detected\n";
			// If we are using PATH_INFO then that's the page name
			$page = substr($server['PATH_INFO'], 1);
			
		} elseif (!empty($request['id'])) {
			$page = $request['id'];
			
		} else {
			// TODO: Keep checking
			//echo "WARN: Could not find a pagename\n";
		}

		// Check whether a default Page is being requested
		if ($page=='' || preg_match('/\/$/', $page)) {
			$page .= $this->config['defaultPage'];
		}
		
		return $page;
	}
	
	protected function getAction($request, $server) {
		if ($server['REQUEST_METHOD']=='POST') {
			if (!empty($request['preview'])) {
				return 'preview';
			} elseif (!empty($request['save'])) {
				return 'save';
			}
		} elseif (!empty($request['action'])) {
			return $request['action'];
		} elseif (!empty($server['PATH_INFO'])) {
			return 'display';
		}
		
		// TODO: handle version history etc.
		
		return 'UNKNOWN';
	}
	
	protected function getBaseUrl($request, $server) {
		if (!empty($this->config['baseUrl'])) {
			return $this->config['baseUrl'];
		}
		/**
			PATH_INFO $_SERVER keys
    [SERVER_NAME] => localhost
    [DOCUMENT_ROOT] => /home/user/sites/default/htdocs
    [SCRIPT_FILENAME] => /home/user/sites/default/htdocs/index-sample.php
    [REQUEST_METHOD] => GET
    [QUERY_STRING] => 
    [REQUEST_URI] => /index-sample.php
    [SCRIPT_NAME] => /index-sample.php
    [PHP_SELF] => /index-sample.php
		**/

		$scriptName = $server['SCRIPT_NAME'];
		$requestUrl = $server['REQUEST_URI'];
		$phpSelf    = $server['PHP_SELF'];
		
		if ($requestUrl==$scriptName) {
			// PATH_INFO based
		} elseif(strpos($requestUrl, $scriptName)===0) {
			// Query string based
		} else {
			// Maybe mod_rewrite based?
			// Perhaps we need a config entry here
		}
	
		return '/index-sample.php/'; // PATH-INFO base
	}
	
	protected function getPostDetails($request, $server) {
		$post = (object) NULL;
		$post->text    = $request['text'];
		$post->updated = $request['updated'];
		return $post;
	}

	/*********
	
		RESPONSE RENDERERS	
	
	*********/

	public function renderResponse($response) {
		if (!empty($this->config['layout'])) {
			// TODO: Use a custom template
		} else {
			$footer = array();
			
			if (!empty($response['options'])) {
				$footer[] = '<ul>';
				foreach($response['options'] as $label=>$link) {
					$footer[] = <<<HTML
<li><a href="{$link}">{$label}</a></li>
HTML;
				}
				$footer[] = '</ul>';
			}
			$response['footer'] = implode("\n", $footer);

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
{$response['editForm']}
		</div>	
		<div id="related">
{$response['related']}		
		</div>	
		<div id="foot">
{$response['footer']}
		</div>	
	</div>
</body>
</html>
PAGE;

		}
	}

	protected function renderDocument($action) {
		return Markdown(
			$action->model->content, 
			array($this, 'wikiLink')
		);
	}

	protected function renderPreviewDocument($action) {
		return Markdown(
			$action->post->text, 
			array($this, 'wikiLink')
		);
	}

	protected function renderEditForm($action) {
		if (!empty($action->post)) {
			$form = array(
				'raw'     => $action->post->text,
				'updated' => $action->post->updated
			);		
		} else {
			$form = array(
				'raw'     => $action->model->content,
				'updated' => $action->model->updated
			);
		}

		return <<<HTML
<form action="{$action->base}{$action->page}" method="post">
	<fieldset>
		<legend>Editing</legend>
		<label for="text">Content:</label><br>	
		<textarea cols="78" rows="20" name="text" id="text">{$form['raw']}</textarea>
		<br>

		<input type="submit" name="preview" value="Preview">
		<input type="submit" name="save" value="Save">
		<input type="hidden" name="updated" value="{$form['updated']}">
	</fieldset>
</form>
HTML;

	}


}


if (!empty($_SERVER['REQUEST_URI'])) {
	# Dealing with a web request
	$wiki = new MarkdownWiki($config);
	$wiki->handleRequest();
	//print_r($wiki);
}

?>
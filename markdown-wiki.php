<?php

class MarkdownWiki {
	// Wiki default configuration. All overridable
	protected $config = array(
		'docDir'      => '/tmp/',
		'defaultPage' => 'index',
		'newPageText' => 'Start editing your new page'
	);
	
	// An instance of the Markdown parser
	protected $parser;

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
	
	public function setConfig($config) {
		$this->config = array_merge($this->config, $config);
	}

	public function handleRequest($request=false, $server=false) {
		$action           = $this->parseRequest($request, $server);
		$action->model    = $this->getModelData($action);
		
		// If this is a new file, switch to edit mode
		if ($action->model->updated==0) {
			$action->action = 'edit';
		}		
		
		$action->response = $this->doAction($action);
		$output           = $this->renderResponse($action);
	}

	public function renderResponse($action) {
		$output = '';
		return $output;
	}
	
	public function doAction($action) {
		$response           = (object) NULL;
		$response->messages = array();
		
		switch($action->action) {
			case 'display':
			
				break;
			default:
				$response->messages[] = 
					"Action {$action->action} not implemented.";
				break;
		}

		return $response;
	}
	
	
	public function getModelData($action) {
		$data = (object) NULL;
		
		$data->file    = $this->getFilename($action->page);
		$data->content = $this->getContent($data->file);
		$data->updated = $this->getLastUpdated($data->file);
		
		return $data;
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

		if ($action->method=='POST') {
			$action->post = $this->getPostDetails($request, $server);
		}		

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
			echo "WARN: Could not find a pagename\n";
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
	
	protected function getPostDetails($request, $server) {
		$post = (object) NULL;
		$post->text    = $request['text'];
		$post->updated = $request['updated'];
		return $post;
	}

}



if ($_REQUEST) {
	# Dealing with a web request
	$wiki = new MarkdownWiki($config);
	$wiki->handleRequest();
	//print_r($wiki);
}

?>
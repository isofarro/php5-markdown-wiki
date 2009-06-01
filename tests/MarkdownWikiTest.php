<?php

require_once dirname(dirname(__FILE__)) . '/markdown-wiki.php';

class MarkdownWikiTest extends PHPUnit_Framework_TestCase {
	var $wiki;
	var $config = array(
		'docDir'      => '/tmp/',
		'defaultPage' => 'TestDefaultPage'
	);
	
	public function setUp() {
		$this->wiki = new MarkdownWiki($this->config);
	}
	
	

	public function testRequestReturnsAction() {
		$request = array();
		$server  = array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO'      => '/TestPageName'
		);
		
		$action = $this->wiki->parseRequest($request, $server);
		$this->assertNotNull($action);
		$this->assertType('stdClass', $action);
	}

	public function testPathInfoGetRequestAction() {
		$request = array();
		$server  = array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO'      => '/TestPageName'
		);

		$action = $this->wiki->parseRequest($request, $server);
		$this->assertEquals('TestPageName', $action->page);
		$this->assertEquals('GET', $action->method);

	}
	
	public function testDefaultPathInfoPageRequestAction() {
		$request = array();
		$server  = array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO'      => '/'
		);
		
		$action = $this->wiki->parseRequest($request, $server);
		$this->assertEquals('TestDefaultPage', $action->page);
		$this->assertEquals('GET', $action->method);
		$this->assertEquals('display', $action->action);

	}


	public function testDefaultPathInfoDirPageRequestAction() {
		$request = array();
		$server  = array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO'      => '/Directory/'
		);
		
		$action = $this->wiki->parseRequest($request, $server);
		$this->assertEquals('Directory/TestDefaultPage', $action->page);
		$this->assertEquals('GET', $action->method);
		$this->assertEquals('display', $action->action);

	}
	
	public function testSavePostRequestAction() {
		$request = array(
			'save'    => 'Save this page',
			'text'    => 'This is a one line message',
			'updated' => time()
		);
		$server  = array(
			'REQUEST_METHOD' => 'POST',
			'PATH_INFO'      => '/index'
		);

		$action = $this->wiki->parseRequest($request, $server);
		$this->assertEquals('POST', $action->method);
		$this->assertEquals('save', $action->action);
		$this->assertEquals('This is a one line message', $action->post->text);
		//print_r($action);
	}

	public function testPreviewPostRequestAction() {
		$request = array(
			'preview' => 'Preview my changes',
			'text'    => 'This is a one altered line message',
			'updated' => time()
		);
		$server  = array(
			'REQUEST_METHOD' => 'POST',
			'PATH_INFO'      => '/index'
		);

		$action = $this->wiki->parseRequest($request, $server);
		$this->assertEquals('POST', $action->method);
		$this->assertEquals('preview', $action->action);
		$this->assertEquals('This is a one altered line message', $action->post->text);
		//print_r($action);
	}

}


?>
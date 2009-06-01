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
	
	
/****

	public function testRequestReturnsAction() {
		$request = array(
		
		);
		$server  = array(
			'REQUEST_METHOD' => 'GET'
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
****/

	
	public function testDefaultPageRequestAction() {
		$request = array(
		
		);
		$server  = array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO'      => '/'
		);
		
		$action = $this->wiki->parseRequest($request, $server);
		$this->assertEquals('TestDefaultPage', $action->page);
		$this->assertEquals('GET', $action->method);
		$this->assertEquals('display', $action->action);

	}


	public function testDefaultDirPageRequestAction() {
		$request = array(
		
		);
		$server  = array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO'      => '/Directory/'
		);
		
		$action = $this->wiki->parseRequest($request, $server);
		$this->assertEquals('Directory/TestDefaultPage', $action->page);
		$this->assertEquals('GET', $action->method);
		$this->assertEquals('display', $action->action);

	}

}


?>
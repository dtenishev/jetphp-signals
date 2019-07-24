<?php

namespace jetphp\rabbitmq\tests\unit;

use jetphp\signals\Handler;
use PHPUnit\Framework\TestCase;

class HandlerTest extends TestCase {

	protected function setUp() {
		if ( !extension_loaded( 'pcntl' ) ) {
			$this->markTestSkipped( 'pcntl extension not loaded' );
		}
		if ( !extension_loaded( 'posix' ) ) {
			$this->markTestSkipped( 'posix extension not loaded' );
		}
	}

	public function testHandle() {
		$caughtSigno = 0;
		$catchSigno = \SIGALRM;
		$signalHandler = $this->getSignalHandler();
		$signalHandler->handle( $catchSigno, function( $signo ) use ( &$caughtSigno ) {
			$caughtSigno = $signo;
		} );
		\pcntl_alarm( 1 );
		usleep( 1100000 );
		pcntl_signal_dispatch();
		$this->assertEquals( $catchSigno, $caughtSigno, 'Caught wrong signal' );
	}

	public function testHandlerArray() {
		$signalHandler = $this->getSignalHandler();
		$handler = $signalHandler->handle( \SIGINT, array( $this, __METHOD__ ) );
		$this->assertNotTrue( is_array( $handler ), "Handler should be lambda function" );
	}

	public function testUnhandleOne() {
		$called = false;
		$signalHandler = $this->getSignalHandler();
		$handler = $signalHandler->handle( \SIGALRM, function( $signo ) use ( &$called ) {
			$called = true;
		} );
		$signalHandler->unhandle( \SIGALRM, $handler );
		$signalHandler->onSignal( \SIGALRM );
		$this->assertNotTrue( $called, 'Expected handler won\'t be called' );
	}

	public function testUnhandleAll() {
		$called = false;
		$signalHandler = $this->getSignalHandler();
		$signalHandler->handle( \SIGALRM, function( $signo ) use ( &$called ) {
			$called = true;
		} );
		$signalHandler->unhandle( \SIGALRM );
		$signalHandler->onSignal( \SIGALRM );
		$this->assertNotTrue( $called, 'Expected no handlers will be called' );
	}

	public function testRestore() {
		$called = false;
		$signalHandler = $this->getSignalHandler();
		$signalHandler->handle( \SIGALRM, function( $signo ) use ( &$called ) {
			$called = true;
		} );
		$signalHandler->restore();
		$signalHandler->onSignal( \SIGALRM );
		$this->assertNotTrue( $called, 'Expected no handlers will be called' );
	}

	private function getSignalHandler() {
		return new Handler();
	}

}
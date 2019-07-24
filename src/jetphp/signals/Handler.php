<?php

namespace jetphp\signals;

class Handler {

	protected $signals;
	protected $ignoredSignals;

	public function __construct() {
		if ( !extension_loaded( 'pcntl' ) ) {
			throw new \RuntimeException( 'Pcntl extension is missing' );
		}
		$this->signals = [];
		$this->ignoredSignals = [];
	}

	public function __destruct() {
		$this->restore();
	}

	/**
	 * @param int|array $signoOrArray
	 */
	public function ignore( $signoOrArray ) {
		if ( is_scalar( $signoOrArray ) ) {
			$signoOrArray = array( $signoOrArray );
		}
		foreach ( $signoOrArray as $signo ) {
			\pcntl_signal( $signo, \SIG_IGN );
			$this->ignoredSignals[$signo] = $signo;
		}
	}

	public function handle( $signo, $handler ) {
		if ( !is_callable( $handler ) ) {
			throw new \InvalidArgumentException( 'Unexpected handler type' );
		}
		if ( !isset( $this->signals[$signo] ) ) {
			\pcntl_signal( $signo, [ $this, 'onSignal' ], false );
			$this->signals[$signo] = [];
		}
		if ( is_array( $handler ) ) {
			$handler = function() use ($handler) {
				call_user_func( $handler );
			};
		}
		$handlerKey = spl_object_hash( $handler );
		$this->signals[$signo][$handlerKey] = $handler;
		return $handler;
	}

	public function unhandle( $signo, $handler = null ) {
		if ( !isset( $this->signals[$signo] ) ) {
			return;
		}
		if ( !is_null( $handler ) ) {
			$handlerKey = spl_object_hash( $handler );
			if ( isset( $this->signals[$signo][$handlerKey] ) ) {
				unset( $this->signals[$signo][$handlerKey] );
			}
		} else {
			$this->signals[$signo] = [];
		}
		if ( empty( $this->signals[$signo] ) ) {
			\pcntl_signal( $signo, \SIG_DFL );
		}
	}

	public function restore() {
		foreach ( array_keys( $this->signals ) as $signo ) {
			\pcntl_signal( $signo, \SIG_DFL );
		}
		$this->signals = [];
		$this->ignoredSignals = [];
	}

	public function onSignal( $signo/*, $siginfo*/ ) {
		if ( !isset( $this->signals[$signo] ) || empty( $this->signals[$signo] ) ) {
			return;
		}
		foreach ( $this->signals[$signo] as $handler ) {
			call_user_func( $handler, $signo );
		}
	}

}

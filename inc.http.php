<?php

class HTTP {

	public $curl;
	public $url = '';
	public $method = 'GET';
	public $data = array();
	public $headers = array();

	public function request() {
		$this->curl = $ch = curl_init();

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $this->url);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($this->method));
		curl_setopt($ch, CURLOPT_FAILONERROR, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLINFO_HEADER_OUT, true);

		if ( $this->headers ) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
		}

		if ( $this->data ) {
			$data = $this->data;
			is_string($data) or $data = http_build_query($this->data);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}

		$response = new HTTPResponse(curl_exec($ch));
		$this->info = curl_getinfo($ch);
		curl_close($ch);

		return $response;
	}

	static public function create($url, $options = array()) {
		if ( is_array($url) ) {
			$options = $url;
			$url = @$options['url'];
		}

		isset($url) && $options['url'] = $url;

		$http = new self;

		foreach ( $options AS $name => $value ) {
			$http->$name = $value;
		}

		return $http;
	}

}

class HTTPResponse {

	public $raw = '';

	public $head = '';
	public $code = 0;
	public $status = '';
	public $headers = null;

	public $body = '';
	public $response = null;

	public function __construct( $raw ) {
		$this->raw = $raw;

		$x = explode("\r\n\r\n", $raw, 2);
		$this->head = $x[0];
		$this->body = isset($x[1]) ? $x[1] : '';

		$this->parseHeaders();
		$this->parseBody();
	}

	public function parseHeaders() {
		if ( null === $this->headers ) {
			$this->headers = array();

			$lines = explode("\n", $this->head);

			foreach ( $lines AS $i => $line ) {
				$line = trim($line);

				if ( $i ) {
					// Header
					$x = explode(':', $line, 2);
					$this->headers[strtolower($x[0])][] = trim($x[1]);
				}
				else {
					// Status
					if ( preg_match('/\s(\d+)\s+(.+)/', $line, $match) ) {
						$this->code = (int)$match[1];
						$this->status = $match[2];
					}
				}
			}
		}

		return $this->headers;
	}

	public function parseBody() {
		if ( null === $this->response ) {
			$this->response = (string)$this->body;

			if ( isset($this->headers['content-type'][0]) ) {
				$x = explode(';', $this->headers['content-type'][0]);

				switch ( strtolower($x[0]) ) {
					case 'text/json':
					case 'application/json':
						$this->response = json_decode(trim($this->body));
					break;
				}
			}
		}

		return $this->response;
	}

	public function __tostring() {
		return $this->raw;
	}

}



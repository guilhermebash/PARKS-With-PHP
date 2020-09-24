<?php

	include __DIR__ . '/Telnet.php';

	class Parks {

		private $telnet_instance;

		function __construct($ip, $login, $password){
			$telnet = new PHPTelnet();
			$telnet->connect($ip, $login, $password);
			$this->telnet_instance = $telnet;
		}

		public function getOnuInfo($serial_or_alias){

			$command = "show gpon onu {$serial_or_alias} summary";
			$this->telnet_instance->DoCommand($command, $response);

			$lines = preg_split("/((\r?\n)|(\r\n?))/", $response);

			unset($lines[array_key_first($lines)]);
			unset($lines[array_key_last($lines)]);

			$onu_data = new \StdClass();

			foreach ($lines as $line){

				$line_info = explode(':', $line);
				
				$name = $line_info[array_key_first($line_info)];
				$value = $line_info[array_key_last($line_info)];

				$name = trim($name);
				$name = str_replace(' ', '_', $name);

				$value = trim($value);

				$onu_data->$name = $value;

			}

			return $onu_data;
		}
	}

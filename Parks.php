<?php

	include __DIR__ . '/Telnet.php';

	class Parks {

		private $telnet_instance;

		function __construct($ip, $login, $password){
			$telnet = new PHPTelnet();
			$telnet->connect($ip, $login, $password);
			$this->telnet_instance = $telnet;
		}

		private function getLinesResponse($string){
			$lines = preg_split("/((\r?\n)|(\r\n?))/", $string);
			unset($lines[array_key_first($lines)]);
			unset($lines[array_key_last($lines)]);
			return $lines;
		}

		public function getOnuInfo($serial_or_alias){

			$command = "show gpon onu {$serial_or_alias} summary";
			$this->telnet_instance->DoCommand($command, $response);

			$lines = $this->getLinesResponse($response);

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

		public function changeAliasOnuBySerial($serial_onu, $alias){

			$onu_info = $this->getOnuInfo($serial_onu);

			if ($onu_info == $alias)
				return ['status' => false, 'message' => 'The ONU already has this alias'];

			$interface = $onu_info->Interface;
			$onu_serial = $onu_info->Serial;
			
			$command = 'configure terminal';
			$this->telnet_instance->DoCommand($command, $response);

			$lines = $this->getLinesResponse($response);

			if (count($lines)>0)
				return ['status' => false, 'message' => $lines[1]];

			$command = "interface {$interface}";
			$this->telnet_instance->DoCommand($command, $response);

			$lines = $this->getLinesResponse($response);

			if (count($lines)>0)
				return ['status' => false, 'message' => $lines[1]];

			$command = "onu {$onu_serial} alias {$alias}";
			$this->telnet_instance->DoCommand($command, $response);

			$lines = $this->getLinesResponse($response);

			if (count($lines) > 0){
				$line = explode(':', $lines[1]);
				$name = trim($line[array_key_first($line)]);
				$value = trim($line[array_key_last($line)]);
				if ($name == '%ERROR')
					return ['status' => false, 'message' => $value];
				else {
					return ['status' => false, 'message' => $lines[1]];
				}
			}

			$command = "exit";
			$this->telnet_instance->DoCommand($command, $response);

			$command = "exit";
			$this->telnet_instance->DoCommand($command, $response);

			$onu_info = $this->getOnuInfo($serial_onu);

			if ($onu_info->Alias == $alias)
				return ['status' => true, 'message' => 'Alias ​​changed successfully.'];

			return ['status' => false, 'message' => 'A mysterious error has occurred.'];
		}
	}

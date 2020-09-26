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

			if ($lines[1] == '% Unknown command.')
				return ['status' => false, 'message' => $lines[1]];

			

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

			return ['status' => true, 'data' => $onu_data];
		}

		public function removeOnu($serial_or_alias){
			$onu_info = $this->getOnuInfo($serial_or_alias);

			if ($onu_info['status'] === false)
				return ['status' => false, 'message' => $onu_info['message']];
			
			$interface = $onu_info['data']->Interface;
			$onu_serial = $onu_info['data']->Serial;

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

			$command = "no onu {$onu_serial}";
			$this->telnet_instance->DoCommand($command, $response);
		}

		public function changeAliasOnuBySerial($serial_onu, $alias){

			$onu_info = $this->getOnuInfo($serial_onu);



			if ($onu_info['status'] === false)
				return ['status' => false, 'message' => $onu_info['message']];

			if ($onu_info == $alias)
				return ['status' => false, 'message' => 'The ONU already has this alias'];

			$interface = $onu_info['data']->Interface;
			$onu_serial = $onu_info['data']->Serial;

	
			
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

			$command = "end";
			$this->telnet_instance->DoCommand($command, $response);

			$onu_info = $this->getOnuInfo($serial_onu);

			if ($onu_info['data']->Alias == $alias)
				return ['status' => true, 'message' => 'Alias ​​changed successfully.'];

			return ['status' => false, 'message' => 'A mysterious error has occurred.'];
		}

		public function getFlowPerfilCommands($onu_or_alias){

			$command = "end";
			$this->telnet_instance->DoCommand($command, $response);
			
			$onu_info = $this->getOnuInfo($onu_or_alias);

			if ($onu_info['status'] !== true)
				return ['status' => false, 'message' => 'Unidentified ONU'];

			$interface = $onu_info['data']->Interface;
			$pon = str_replace('gpon', '', $interface);
			$pon = str_replace('/', '', $pon);

			$onu_serial = $onu_info['data']->Serial;

			$commands = [
				'flow-profile' => "onu {$onu_serial} flow-profile onu_bridge_vlan_{$pon}_pon{$pon}",
				'vlan-translation-profile' => "onu {$onu_serial} vlan-translation-profile _{$pon} uni-port 1" 
			];

			return $commands;
		}

		public function getPowerLevelByOnu($onu_or_alias){
			$onu_info = $this->getOnuInfo($onu_or_alias);	
			$power_level = $onu_info['data']->Power_Level;
			$power_level = explode(' ', $power_level);
			$power_level = $power_level[array_key_first($power_level)];
			$power_level = str_replace('dBm', '', $power_level);
			return $power_level;
		}

		public function activeOnu($onu_or_alias, $identification){
			$onu_info = $this->getOnuInfo($onu_or_alias);
			$interface = $onu_info['data']->Interface;

			$serial_number = $onu_info['data']->Serial;

			$result = $this->changeAliasOnuBySerial($serial_number, $identification);

			$flow_commands = $this->getFlowPerfilCommands($serial_number);

			$command = "end";
			$this->telnet_instance->DoCommand($command, $response);

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
		
			$command = $flow_commands['flow-profile'];
			$this->telnet_instance->DoCommand($command, $response);

			$command = $flow_commands['vlan-translation-profile'];
			$this->telnet_instance->DoCommand($command, $response);

			$command = "end";
			$this->telnet_instance->DoCommand($command, $response);

			$command = "copy r s";
			$this->telnet_instance->DoCommand($command, $response);

			sleep(10);

			$sinal = $this->getPowerLevelByOnu($serial_number);

			return ['status'=>true, 'message' => 'ONU ativa com o sinal: ' . $sinal];

			
		}	
	}

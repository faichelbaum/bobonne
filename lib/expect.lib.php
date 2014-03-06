<?php

class Expect
{
	public $pid = 0;
	public $ppid = 1;
	public $status = 0;
	
	private $descriptorspec = array(
									0 => array("pipe", "r"),
									1 => array("pipe", "w"),
									2 => array("pipe", "w")
								);
	private $pipes = array();
	private $env = array(
							'TERM' => 'xterm'
						);
	private $command = '';
	private $home = '';
	private $back = '';
	private $proc = null;
	private $start = -1;

	private $url = '';
	
	
	public function __construct($url, $basename, $home) {
		$url = preg_replace('/[^:]\/\//', '\/', $url); #this->stream);
		if ($url[0] == '#') return FALSE;
		if (trim($url) == '') return FALSE;
		$this->url = trim($url);
		$log = str_replace(array('://', '/'), array('_', '_'), $basename);
		$this->command = "/usr/bin/cvlc -vvv -R --stats --mms-caching 1000 --http-caching 1000 --rtsp-caching 1000 --rtp-caching 1000 --mms-caching 1000 '".$this->url."' 2>&1 | tee /tmp/".$log.".log";
		$this->home = trim($home);
		$this->back = CM_HOME;
		chdir($this->home);
	}
	
	public function stop() {
		foreach ($this->pipes as $id => $pipe)
			fclose($this->pipes[$id]);
		unset($this->pipes);
		$status = proc_get_status($this->proc);
		exec("kill -9 `ps aux | grep vlc | grep -v grep | grep '".$this->url."' | awk '{ print $2 }'` &> /dev/null");
		exec('kill -9 '.$status['pid'].' &> /dev/null');
		proc_close($this->proc);
		/*
		$this->status = proc_get_status($this->proc);
		return proc_close($this->proc);
		*/
		@chdir($this->back);
		return null; //$this->proc;
	}
	
	public function __destruct() {
		//$this->stop();
		return null;
	}
	
	public function execute($filters) {
		$this->start = microtime(true);
		if (($this->proc = proc_open($this->command, $this->descriptorspec, $this->pipes, $this->home, $this->env)) === FALSE) {
			return FALSE;
		}
		stream_set_blocking($this->pipes[1], 0);
		stream_set_blocking($this->pipes[2], 0);
		
		$output = '';
		$fake = false;
		$condition = false;
		$duration = 0;
		if (DEBUG) 
		{
			echo "### CMD ###\n";
			echo "expect in progress (".$this->command.")\n";
		}
		while (($buffer = fgets($this->pipes[1], BUF_SIZ)) != NULL
            || ($errbuf = fgets($this->pipes[2], BUF_SIZ)) != NULL || true) {
			if (strlen($buffer))
				$output .= $buffer;
			if (strlen($errbuf))
				$output .= $errbuf;
			foreach ($filters as $code => $filter)
			{
				if (!$filter['pattern'] || !isset($filter['pattern'])) continue;
				$filter['pattern'] = str_replace('$url', $this->url, $filter['pattern']);
				$match = array();
				if (preg_match('^'.$filter['pattern'].'^', $output, $match))
				{
					if (DEBUG) echo "### PATTERN + MATCH\n";
					if (DEBUG) var_dump($filter['pattern'], $match);
					if (isset($filter['type']) && ($filter['type'] == 'fake'))
					{
						if (!isset($filter['for']))
							$fake = $filter['code']; //true;
						else
						{
							$fake = $filter['for'];
							$real = $filter['code'];
						}
					}
					if (isset($filter['type']) && ($filter['type'] == 'condition'))
					{
						$condition = $filter['code']; //true;
					}
					elseif (isset($filter['type']) && ($filter['type'] == 'partial') && ($fake || $condition) && !isset($filter['value']))
					{
						if (DEBUG) echo "to return: $code (F || C)\n";
						return $code;
					}
					elseif (isset($filter['type']) && ($filter['type'] == 'partial') && !$fake && $condition && !isset($filter['value']))
						continue;
					elseif (isset($filter['field']) && isset($filter['value']) && ($match[$filter['field']] <= $filter['value']))
					{
						if (DEBUG) echo "to return: $code (".$match[$filter['field']]." ".$filter['value'].") (F & V)\n";
						return $code;
					}
					elseif (isset($filter['type']) && ($filter['type'] == 'success'))
					{
						if ($fake && $condition && ($fake == $condition))
						{
							if (DEBUG) echo "to return: $real (S || P)\n";
							return $real;
						}
						else
						{
							if (DEBUG) echo "to return: $code (S || P)\n";
							return $code;
						}
					}
					elseif ($filter['type'] == 'problem')
					{
						if (DEBUG) echo "to return: $code (S || P)\n";
						return $code;
					}
				}
				$matches = null ; unset($matches);
			}
			$duration = microtime(true) - $this->start;
			//if (DEBUG) echo "T: $duration / ".TIMEOUT."\n";
			if ($duration > TIMEOUT)
				return FILTER_TIMEOUT;
				
			//if (DEBUG) var_dump($output);
		}
		
		return FALSE;
	}
}

?>

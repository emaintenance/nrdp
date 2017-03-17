<?php
// Matthieu PERRIN - 2017
// Submit check result from file in check_result_path to command_file

require_once(dirname(__FILE__).'/../../config.inc.php');
require_once(dirname(__FILE__).'/../../includes/utils.inc.php');

function submit_checkresults2nagios()
{	
    global $cfg;
//$command_file = "/usr/local/nagios/var/rw/nagios.cmd";
//$check_result_path = "/usr/local/nagios/var/spool/checkresults";
//$tmp_path = "/tmp/checkresults";

$files = scandir($cfg["check_results_dir"]);

// For each file in /usr/local/nagios/var/spool/checkresults
foreach($files as $file) 
{	

	if (( strcmp($file, "..") != 0 ) && ( strcmp($file, ".") != 0 ))
	{
	// Open file
	$handle = fopen($cfg["check_results_dir"]."/".$file, "r");	
	if ($handle) {
		// For each line in file
		while (($line = fgets($handle)) !== false) {
			
			// Get host_name, service_description, return_code, output, start_time	
			if ( preg_match('/host_name/' , $line) == 1 )
			{
				$host_name=str_replace("host_name=","",$line);
			}
			elseif( preg_match('/service_description/' , $line) == 1 ) 
			{
				$service_description=str_replace("service_description=","",$line);
			}
			elseif( preg_match('/return_code/' , $line) == 1 ) 
			{
				$return_code=str_replace("return_code=","",$line);
			}
			elseif( preg_match('/output/' , $line) == 1 ) 
			{
				$output=str_replace("output=","",$line);
			}
			elseif( preg_match('/start_time/' , $line) == 1 ) 
			{
				$starttime=str_replace("start_time=","",$line);
				$start_time = substr($starttime, 0, 10);
			}		
		}
		
		// If host_name is define...
		if ( isset($host_name) )
		if ( strlen($host_name) > 1 )
		{
			// If service_description is define : create command
			//if ( strlen($service_description) > 1 )
			if ( isset($service_description)  )
			{
				$cmd = "[".$start_time."] PROCESS_SERVICE_CHECK_RESULT;".$host_name .";". $service_description.";".$return_code.";".$output;	
			}
			else
			{
				$cmd = "[".$start_time."] PROCESS_HOST_CHECK_RESULT;".$host_name .";".$return_code.";".$output;	
			}
			$cmd = str_replace(array("\r\n", "\n", "\r"), '', $cmd)."\n";	
			
			// Send command to Nagios
			//file_put_contents($command_file, $cmd);
			
			$fc = @fopen($cfg["command_file"],"w+");
			fwrite($fc, $cmd);
			fclose($fc);
		}
		
		$host_name="";
		$service_description="";
		
		fclose($handle);
		
		// Remove file
		unlink($cfg["check_results_dir"]."/".$file);
	} else {
		// error opening the file.
	} 	
	
	}
}

}
?>
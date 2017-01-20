<?php 

$bamfile = $argv[1];
$bedfile_name = $argv[2];
$ngs_path = $argv[3];

$patient_id = str_replace(".bam", "", basename($bamfile));

$pathinfo = pathinfo("$bamfile");
$workdir = $pathinfo['dirname']."/..";

$qca_dir_tech = $workdir."/QCAs_tech";

$lines = file($bedfile_name);
$count = count($lines);

$fpn = fopen("$qca_dir_tech/".$patient_id.".QCA_tech.csv", 'w');

fwrite($fpn, "patient_id;amplicon;chr;pos;dp;status\n");

$array_x = array();
$array_y = array();

for($l = 0; $l < $count; $l++){
	$results = null;
	$line = $lines[$l];
	$line = trim($line);
	$elements = explode ("\t", $line);

	$amplicon = $elements[0];
	$chr = $elements[1];
	$start = $elements[2];
	$stop = $elements[3];
	
	exec("/biopathdata/pipelineuser/NGS/Programs/samtools-0.1.19_no_cap_no_cap/samtools depth -r $chr:$start-$stop $bamfile", $results);
	
	$pos = $start;
	for($m = 0; $m < count($results); $m++){
		$dp = explode("\t", $results[$m])[2];
		

		if (preg_match("/egatif/", $patient_id)) {
			if($dp > 50){
				$status = "FAIL";
				fwrite($fpn, "$patient_id;$amplicon;$chr;$pos;$dp;$status\n");
			}
		}
		else{
			if($dp < 200){
				$status = "FAIL";
				fwrite($fpn, "$patient_id;$amplicon;$chr;$pos;$dp;$status\n");
			}
		}
		
		$pos++;
	}
	
}

fclose($fpn);

?>

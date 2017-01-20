<?php 

$bamfile = $argv[1];
$bedfile_name = $argv[2];
$ngs_path = $argv[3];

include("$ngs_path/Programs/pChart/class/pData.class.php");
include("$ngs_path/Programs/pChart/class/pDraw.class.php");
include("$ngs_path/Programs/pChart/class/pImage.class.php");
include("$ngs_path/Programs/pChart/class/pPie.class.php");
include("$ngs_path/Programs/pChart/class/pIndicator.class.php");

$patient_id = str_replace(".bam", "", basename($bamfile));

$pathinfo = pathinfo("$bamfile");
$workdir = $pathinfo['dirname']."/..";

$qca_dir_dp_normalize = $workdir."/QCA_DP_Normalize";

$lines = file($bedfile_name);
$count = count($lines);

$fpn = fopen("$qca_dir_dp_normalize/".$patient_id.".QCA_DP_Normalize.csv", 'w');

fwrite($fpn, "patient_id;amplicon;chr;start;stop;dp_mean;status\n");

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
	
	exec("/biopathdata/pipelineuser/NGS/Programs/samtools-0.1.19_no_cap/samtools depth -r $chr:$start-$stop $bamfile", $results);
	
	$array_dp = array();
	$summ = 0;
	for($m = 0; $m < count($results); $m++){
		$summ += explode("\t", $results[$m])[2];
		array_push($array_dp, explode("\t", $results[$m])[2]);
	}
	
	$results_size = (count($results) == 0) ? 1 : count($results);

	$dp_mean = round($summ/$results_size);
	
	if($dp_mean >= 200){
		$status = "PASS";
	}
	else{
		$status = "FAIL";
	}
	
	fwrite($fpn, "$patient_id;$amplicon;$chr;$start;$stop;$dp_mean;$status\n");
}

fclose($fpn);


?>

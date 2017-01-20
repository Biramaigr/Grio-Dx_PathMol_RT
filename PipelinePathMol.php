<?php

$reads_dir = $argv[1];
$mapping_algo = $argv[2];
$is_pp = $argv[3];
$is_indel_realign = $argv[4];
$bedfile = $argv[5];
$bedfile_name = $argv[6];
$tech = $argv[7];
$ngs_path = $argv[8];
$mcl_noisy_check = $argv[9];
$mcl_bgidl = $argv[10];
$mcl_af_min = $argv[11];
$mcl_dp_min = $argv[12];
$mode = $argv[13];
$sequencer = $argv[14];
$input_type = $argv[15];
$serie = $argv[16];
$server = $argv[17];

$bed_used = pathinfo("$bedfile")['basename'];

$pathinfo = pathinfo("$reads_dir");
$workdir = $pathinfo['dirname']."/Analyses";

$synchro = $workdir."/Synchro";
$synchro_reccurence = $workdir."/Synchro_reccurence";
$log_dir = $workdir."/log";

system("mkdir -p $synchro $synchro_reccurence $log_dir");



if(strtoupper($mode) == "PRODUCTION"){
	$mode = "Production";
}
else{
	$mode = "Development";
}

mkdir($reads_dir."_group1");
mkdir($reads_dir."_group2");
mkdir($reads_dir."_group3");
mkdir($reads_dir."_group4");

$size = GroupPartition("$reads_dir", "life");

$files = getFiles("$reads_dir");
sort($files);
$size = count($files);

system("php $ngs_path/Scripts/PathMol_RT/$mode/PipelineLife.php ".$reads_dir."_group1 $bedfile $bedfile_name $is_indel_realign $tech $mapping_algo $ngs_path $mcl_noisy_check $mcl_bgidl $mcl_af_min $mcl_dp_min $input_type $mode $server 1> $log_dir/output.txt 2> $log_dir/error.txt&");
system("php $ngs_path/Scripts/PathMol_RT/$mode/PipelineLife.php ".$reads_dir."_group2 $bedfile $bedfile_name $is_indel_realign $tech $mapping_algo $ngs_path $mcl_noisy_check $mcl_bgidl $mcl_af_min $mcl_dp_min $input_type $mode $server 1> $log_dir/output.txt 2> $log_dir/error.txt&");
system("php $ngs_path/Scripts/PathMol_RT/$mode/PipelineLife.php ".$reads_dir."_group3 $bedfile $bedfile_name $is_indel_realign $tech $mapping_algo $ngs_path $mcl_noisy_check $mcl_bgidl $mcl_af_min $mcl_dp_min $input_type $mode $server 1> $log_dir/output.txt 2> $log_dir/error.txt&");
system("php $ngs_path/Scripts/PathMol_RT/$mode/PipelineLife.php ".$reads_dir."_group4 $bedfile $bedfile_name $is_indel_realign $tech $mapping_algo $ngs_path $mcl_noisy_check $mcl_bgidl $mcl_af_min $mcl_dp_min $input_type $mode $server 1> $log_dir/output.txt 2> $log_dir/error.txt&");

while(1 > 0){
	if(alreadydone("$synchro") == $size){
		break;
	}
}


system("php $ngs_path/Scripts/PathMol_RT/$mode/All_QCA.php $workdir/Bams $ngs_path $bedfile_name $mode");

system("php $ngs_path/Scripts/PathMol_RT/$mode/All_QC_Library.php $workdir $ngs_path $mode $sequencer $input_type > $workdir/QC_Libraries.csv");

system("php $ngs_path/Scripts/PathMol_RT/$mode/DB_Storage.php $workdir/variants $workdir/QCAs $workdir/QC_Libraries.csv $serie $ngs_path $sequencer $bed_used");

system("php $ngs_path/Scripts/PathMol_RT/$mode/All_UpdateReccurence.php $serie");

system("touch $workdir/done");

mkdir("/biopathnas/NGS_Pathologie_Moléculaire/Analyses/$serie/");
system("cp -r $workdir/* /biopathnas/NGS_Pathologie_Moléculaire/Analyses/$serie/");

function getFiles($dir){
	$listfiles = scandir($dir);
	$tab = array();
	for($f = 0; $f < count($listfiles); $f++){
		$entry = $listfiles[$f];
		
		if (!preg_match("/^\./", $entry)) {
			array_push($tab, $entry);	
		}
	}
	
	sort($tab);
	return $tab;
}


function alreadydone($dir1) {
	$elts = array();
	if ($handle = opendir($dir1)) {
    	while (false !== ($entry = readdir($handle))) {
    		if ($entry != "." && $entry != ".."){
        		array_push($elts, $entry);
        	}
    	}
    	closedir($handle);
	}
	
	$size = count($elts);
	
	return $size;
}

function GroupPartition($folder, $sequencer){
	
	$elts = getFiles($folder);
	$increment = 0;
	$nbtot = count($elts);

	for($f = 0; $f < count($elts); $f++){
		$entry = $elts[$f];
	
		$increment++;
		
		if($nbtot < 8){
			if($sequencer == "illumina"){
				if($nbtot == 2){
					system("cp $folder/$entry ".$folder."_group1/");
				}
				else if($nbtot == 4){
					if($increment <= 2){
						system("cp $folder/$entry ".$folder."_group1/");
					}
					else{
						system("cp $folder/$entry ".$folder."_group2/");
					}
				}
				else if($nbtot == 6){
					if($increment <= 2){
						system("cp $folder/$entry ".$folder."_group1/");
					}
					else if($increment <= 4){
						system("cp $folder/$entry ".$folder."_group2/");
					}
					else{
						system("cp $folder/$entry ".$folder."_group3/");
					}
				}
			}
			else{
				if($nbtot == 1){
					system("cp $folder/$entry ".$folder."_group1/");
				}
				else if($nbtot == 2){
					if($increment == 1){
						system("cp $folder/$entry ".$folder."_group1/");
					}
					else{
						system("cp $folder/$entry ".$folder."_group2/");
					}
				}
				else if($nbtot == 3){
					if($increment == 1){
						system("cp $folder/$entry ".$folder."_group1/");
					}
					else if($increment == 2){
						system("cp $folder/$entry ".$folder."_group2/");
					}
					else{
						system("cp $folder/$entry ".$folder."_group3/");
					}
				}
				else if($nbtot == 4){
					if($increment == 1){
						system("cp $folder/$entry ".$folder."_group1/");
					}
					else if($increment == 2){
						system("cp $folder/$entry ".$folder."_group2/");
					}
					else if($increment == 3){
						system("cp $folder/$entry ".$folder."_group3/");
					}
					else{
						system("cp $folder/$entry ".$folder."_group4/");
					}
				}
				else if($nbtot == 5){
					if($increment == 1){
						system("cp $folder/$entry ".$folder."_group1/");
					}
					else if($increment == 2){
						system("cp $folder/$entry ".$folder."_group2/");
					}
					else if($increment == 3){
						system("cp $folder/$entry ".$folder."_group3/");
					}
					else if($increment == 4){
						system("cp $folder/$entry ".$folder."_group4/");
					}
					else{
						system("cp $folder/$entry ".$folder."_group1/");
					}
				}
				else if($nbtot == 6){
					if($increment == 1){
						system("cp $folder/$entry ".$folder."_group1/");
					}
					else if($increment == 2){
						system("cp $folder/$entry ".$folder."_group2/");
					}
					else if($increment == 3){
						system("cp $folder/$entry ".$folder."_group3/");
					}
					else if($increment == 4){
						system("cp $folder/$entry ".$folder."_group4/");
					}
					else if($increment == 5){
						system("cp $folder/$entry ".$folder."_group1/");
					}
					else{
						system("cp $folder/$entry ".$folder."_group2/");
					}
				}
				else if($nbtot == 7){
					if($increment == 1){
						system("cp $folder/$entry ".$folder."_group1/");
					}
					else if($increment == 2){
						system("cp $folder/$entry ".$folder."_group2/");
					}
					else if($increment == 3){
						system("cp $folder/$entry ".$folder."_group3/");
					}
					else if($increment == 4){
						system("cp $folder/$entry ".$folder."_group4/");
					}
					else if($increment == 5){
						system("cp $folder/$entry ".$folder."_group1/");
					}
					else if($increment == 6){
						system("cp $folder/$entry ".$folder."_group2/");
					}
					else{
						system("cp $folder/$entry ".$folder."_group3/");
					}
				}
			}
		}
		else{
			
			if($increment <= (intval($nbtot/4) + (intval($nbtot/4))%2)){
				system("cp $folder/$entry ".$folder."_group1/");
			}
			else if($increment <= intval($nbtot/4) + (intval($nbtot/4))%2 + intval($nbtot/4) + (intval($nbtot/4))%2){
				system("cp $folder/$entry ".$folder."_group2/");
			}
			else if($increment <= intval($nbtot/4) + (intval($nbtot/4))%2 + intval($nbtot/4) + (intval($nbtot/4))%2 + intval($nbtot/4) + (intval($nbtot/4))%2){
				system("cp $folder/$entry ".$folder."_group3/");
			}
			else{
				system("cp $folder/$entry ".$folder."_group4/");
			}
		}
	}
	
	return $nbtot;

}

?>

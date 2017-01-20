<?php

$bamdir = $argv[1];
$ngs_path = $argv[2];
$bedfile_name = $argv[3];
$mode = $argv[4];

$pathinfo = pathinfo("$bamdir");
$workdir = $pathinfo['dirname'];

$qca_dir = $workdir."/QCAs";
$qca_dir_tech = $workdir."/QCAs_tech";
$qca_dir_dp_normalize = $workdir."/QCA_DP_Normalize";

mkdir($qca_dir);
mkdir($qca_dir_tech);
mkdir($qca_dir_dp_normalize);

if ($handle = opendir($bamdir)) {
    while (false !== ($entry = readdir($handle))) {
        if (preg_match("/.bam$/", $entry) && !preg_match("/^V_/", $entry)) {
			system("php $ngs_path/Scripts/PathMol_RT/$mode/QCA.php $bamdir/$entry $bedfile_name $ngs_path");
			
			system("php $ngs_path/Scripts/PathMol_RT/$mode/QCA_DP_Normalize.php $bamdir/$entry $bedfile_name $ngs_path");

			system("php $ngs_path/Scripts/PathMol_RT/$mode/QCA_tech.php $bamdir/$entry $bedfile_name $ngs_path");
        }
    }
    closedir($handle);
}

?>

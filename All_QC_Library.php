<?php

$workdir = $argv[1];
$ngs_path = $argv[2];
$mode = $argv[3];
$sequencer = $argv[4];
$input_type = $argv[5];

$qca_dir_tech = $workdir."/variants";

echo "patient_id;nb_reads_fw;nb_reads_q30_fw;%read_q30_fw;nb_reads_rv;nb_reads_q30_rv;%read_q30_rv;read_length_mean_fw;read_quality_mean_fw;read_length_mean_rv;read_quality_mean_rv;nb_mapped_reads_fw;%mapped_reads_fw;nb_mapped_reads_rv;%mapped_reads_rv\n";

if ($handle = opendir("$qca_dir_tech")) {
    while (false !== ($entry = readdir($handle))) {
	if (preg_match("/.variants.csv$/", $entry)) {
      		$patient_id = str_replace(".variants.csv", "", $entry);

		if($sequencer == "illumina"){
			$patient_id1 = substr($patient_id, 0, strlen($patient_id)-4);

			system("php $ngs_path/Scripts/PathMol_RT/$mode/QC_Library.php $workdir/../fastQ/".$patient_id1."_R1_001.fastq $workdir/../fastQ/".$patient_id1."_R2_001.fastq $workdir/Bams/".$patient_id.".bam $sequencer");
		}
		else{
			$patient_id1 = $patient_id;

			if($input_type == "fastq"){
				system("php $ngs_path/Scripts/PathMol_RT/$mode/QC_Library.php $workdir/../fastQ/".$patient_id1.".fastq $workdir/../fastQ/".$patient_id1.".fastq $workdir/Bams/".$patient_id.".bam $sequencer");
			}
			else{
				system("php $ngs_path/Scripts/PathMol_RT/$mode/QC_Library.php $workdir/../BamToFastq/".$patient_id1.".fastq $workdir/../BamToFastq/".$patient_id1.".fastq $workdir/Bams/".$patient_id.".bam $sequencer");
			}
		}
	} 
    }
    closedir($handle);
}

?>

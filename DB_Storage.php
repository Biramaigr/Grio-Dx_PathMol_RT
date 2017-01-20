<?php

date_default_timezone_set('Europe/Paris');
$date = date("j-m-Y");

$variants_dir = $argv[1];
$qcas_dir = $argv[2];
$qclibfile = $argv[3];
$serie = $argv[4];
$ngs_path = $argv[5];
$sequencer = $argv[6];
$bedfile = $argv[7];

$db = new PDO("mysql:host=localhost;dbname=LRT", "diagnostic", "genpatho");

$serie = str_replace("-", "_", $serie);

$table_run = "RUN_$serie";
$table_variants = "variants_$serie";
$table_amplicons_depth = "amplicons_depth_$serie";
$table_amplicons_DP_Normalize = "amplicons_DP_Normalize_$serie";
$table_amplicons_depth_tech = "amplicons_depth_tech_$serie";
$table_library = "library_$serie";

$run_serie = $bedfile;

$sql = "create table if not exists $table_run(run_name varchar(100), run_serie varchar(100), analysis_date varchar(100), validation_status_tech varchar(3), tech_by text, validation_status_bio varchar(3), bio_by text, check_status_tech varchar(3), chek_tech_by text, check_status_bio varchar(3), chek_bio_by text, observations text, track text)";
$db->exec($sql);

$sql = "insert into $table_run values ('$serie', '$run_serie', '$date', 'No', '', 'No', '', 'No', '', 'No', '', '', 'BWA 0.7.5a;Samtools v-0.1.19;Picard-tools v-1.127;GATK HaplotypeCaller;IonTorrentServer;Pileup2vcf;MutaCaller-1.6;snpEff v-4.0')";
$db->exec($sql);

$sql = "create table if not exists $table_variants(patient_id varchar(100), variant_id varchar(100), gene varchar(100), nm varchar(100), exon varchar(100), distance_from_exon int, nt_change text, aa_change text, chr varchar(10), position int, ref text, alt text, dp int, dp_alt int, af double, mutation_type varchar(20), mutation_length int, strand varchar(5), maf_classification varchar(100), qual int, af_esp double, af_1000g double, dbsnp_id varchar(50), cosmic_id varchar(50), sift_pred varchar(50), polyphen_pred varchar(50), soft varchar(50), success int, recurrence int, pathmol_db_value varchar(50), pathmol_db_artefact varchar(50), doge_db_value varchar(50), doge_db_artefact varchar(50), is_ionserver_variant varchar(10), conclusion varchar(50), technical_validation varchar(10), validation_result varchar(50), to_export varchar(5), user_status varchar(50))";
$db->exec($sql);


if ($handle = opendir($variants_dir)) {
    while (false !== ($variants = readdir($handle))) {
        if (!preg_match("/^\./", $variants) && $variants != "AllPatients.csv") {
			if (file_exists("$variants_dir/$variants")) {
				$lines = file("$variants_dir/$variants");
				$count = count($lines);

				$patient_id = str_replace(".variants.csv", "", basename($variants));

				if($count <= 1){
					$sql = "insert into $table_variants(patient_id, chr) values ('$patient_id')";
					$db->exec($sql);
				}
				else{
					for($l = 1; $l < $count; $l++){
						$line = $lines[$l];
						$line = trim($line);
						$elements = explode (";", $line);
						
						$gene = $elements[0];
						$nm = $elements[1];
						$exon = ($elements[2] != "") ? $elements[2] : 0;
						$distance_from_exon = ($elements[3] != "") ? $elements[3] : 0;
						$nt_change = $elements[4];
						$aa_change = $elements[5];
						$chr = $elements[6];
						$position = $elements[7];
						$ref = $elements[8];
						$alt = $elements[9];
						$dp = $elements[10];
						$dp_alt = $elements[11];
						$af = $elements[12];
						$mutation_type = $elements[13];
						$strand = $elements[14];
						$maf_classification = $elements[15];
						$qual = ($elements[16] != "" && $elements[16] != ".") ? $elements[16] : 0;
						$af_esp = ($elements[17] != "") ? $elements[17] : 0;
						$af_1000g = ($elements[18] != "") ? $elements[18] : 0;
						$dbsnp_id = $elements[19];
						$cosmic_id = $elements[20];
						$sift_pred = $elements[21];
						$polyphen_pred = $elements[22];
						$soft = $elements[23];
						$success = $elements[24];
						
						if(strlen($ref) > strlen($alt)){
							$mutation_length = strlen($ref) - 1;
						}
						else if(strlen($alt) > strlen($ref)){
							$mutation_length = strlen($alt) - 1;
						}
						else{
							$mutation_length = 0;
						}

						$string_db = getPathmolDB_value($chr, $position, $ref, $alt);
						$Pathmol_DB_value = explode(":", $string_db)[0];

						$string_artefact = getPathmolArtefactDB_value($chr, $position, $ref, $alt);
						$Pathmol_artefact_DB_value = explode(":", $string_artefact)[0];

						$conclusion = ($Pathmol_artefact_DB_value != "") ? $Pathmol_artefact_DB_value : "";

						$conclusion = ($Pathmol_DB_value != "") ? $Pathmol_DB_value : "";

						$string_doge = getDoGeDB_value($chr, $position, $ref, $alt);
						$DoGe_DB_value = explode(":", $string_doge)[0];
						$DoGe_DB_artefact = explode(":", $string_doge)[1];

						$is_ionserver_variant = isIonServerVariant($patient_id, $chr, $position, $ref, $alt, $serie);

						$variant_id = "$chr$position$ref$alt";

						$sql = "insert into $table_variants values ('$patient_id', '$variant_id', '$gene', '$nm', '$exon', '$distance_from_exon', '$nt_change', '$aa_change', '$chr', '$position', '$ref', '$alt', '$dp', '$dp_alt', '$af', '$mutation_type', '$mutation_length', '$strand', '$maf_classification', '$qual', '$af_esp', '$af_1000g', '$dbsnp_id', '$cosmic_id', '$sift_pred', '$polyphen_pred', '$soft', '$success', '1', '$Pathmol_DB_value', '$Pathmol_artefact_DB_value', '$DoGe_DB_value', '$DoGe_DB_artefact', '$is_ionserver_variant', '$conclusion', 'No', '', 'No', '')";

						$db->exec($sql);
					}
				}
			}

        }
    }
    closedir($handle);
}


$sql = "create table if not exists $table_amplicons_depth(patient_id varchar(100), amplicon varchar(100), chr varchar(10), start int, stop int, dp_mean int, dp_min int, dp_max int, status varchar(10), sample_number int)";
$db->exec($sql);

if ($handle = opendir($qcas_dir)) {
	while (false !== ($qcas = readdir($handle))) {
		if (!preg_match("/^\./", $qcas) && !preg_match("/\.png/", $qcas) && !preg_match("/Thumbs.db/", $qcas)) {
			if (file_exists("$qcas_dir/$qcas")) {
				$lines = file("$qcas_dir/$qcas");
				$count = count($lines);
			
				for($l = 1; $l < $count; $l++){
					$line = $lines[$l];
					$line = trim($line);
				
					if($line != ""){
						$elements = explode (";", $line);
						$patient_id = $elements[0];
						$amplicon = $elements[1];
						$chr = $elements[2];
						$start = $elements[3];
						$stop = $elements[4];
						$dp_mean = ($elements[5] != "") ? $elements[5] : 0;
						$status = $elements[6];
						$dp_min = ($elements[7] != "") ? $elements[7] : 0;
						$dp_max = ($elements[8] != "") ? $elements[8] : 0;
						
						if($sequencer == "illumina"){
							$frags = explode("_", $patient_id);
							$sample_number = substr($frags[1], 1);
						}
						else{
							$sample_number = 0;
						}

						$sql = "insert into $table_amplicons_depth(patient_id, amplicon, chr, start, stop, dp_mean, dp_min, dp_max, status, sample_number) values ('$patient_id', '$amplicon', '$chr', '$start', '$stop', '$dp_mean', '$dp_min', '$dp_max', '$status', '$sample_number')";

						$db->exec($sql);
					}
				}
			}
		}
	}
	closedir($handle);
}


$sql = "create table if not exists $table_amplicons_DP_Normalize(patient_id varchar(100), amplicon varchar(100), chr varchar(10), start int, stop int, dp_mean int, status varchar(10))";
$db->exec($sql);

if ($handle = opendir($qcas_dir)) {
	while (false !== ($qcas = readdir($handle))) {
		if (!preg_match("/^\./", $qcas) && !preg_match("/\.png/", $qcas) && !preg_match("/Thumbs.db/", $qcas)) {
			if (file_exists("$qcas_dir/$qcas")) {
				$lines = file("$qcas_dir/$qcas");
				$count = count($lines);
			
				for($l = 1; $l < $count; $l++){
					$line = $lines[$l];
					$line = trim($line);
				
					if($line != ""){
						$elements = explode (";", $line);
						$patient_id = $elements[0];
						$amplicon = $elements[1];
						$chr = $elements[2];
						$start = $elements[3];
						$stop = $elements[4];
						$dp_mean = ($elements[5] != "") ? $elements[5] : 0;
						$status = $elements[6];
						$dp_min = ($elements[7] != "") ? $elements[7] : 0;
						$dp_max = ($elements[8] != "") ? $elements[8] : 0;
						
						if($sequencer == "illumina"){
							$frags = explode("_", $patient_id);
							$sample_number = substr($frags[1], 1);
						}
						else{
							$sample_number = 0;
						}

						$sql = "insert into $table_amplicons_DP_Normalize(patient_id, amplicon, chr, start, stop, dp_mean, status) values ('$patient_id', '$amplicon', '$chr', '$start', '$stop', '$dp_mean', '$status')";

						$db->exec($sql);
					}
				}
			}
		}
	}
	closedir($handle);
}


$sql = "create table if not exists $table_amplicons_depth_tech(patient_id varchar(100), amplicon varchar(100), group_id text, chr varchar(10), pos int, dp int, status varchar(10), sample_number int, is_cumul varchar(5), start int, stop int, is_reseq varchar(5), reseq_done varchar(5), user_status varchar(50))";
$db->exec($sql);

$qcas_dir_tech = str_replace("QCAs", "QCAs_tech", $qcas_dir);

if ($handle = opendir($qcas_dir_tech)) {
	while (false !== ($qcas_tech = readdir($handle))) {
        	if (!preg_match("/^\./", $qcas_tech) && !preg_match("/\.png/", $qcas_tech) && !preg_match("/Thumbs.db/", $qcas_tech)) {
			if (file_exists("$qcas_dir_tech/$qcas_tech")) {
				$lines = file("$qcas_dir_tech/$qcas_tech");
				$count = count($lines);
	
				$group_id = "";
				for($l = 1; $l < $count; $l++){
					$line = $lines[$l];
					$line = trim($line);
					
					if($line != ""){
						$elements = explode (";", $line);
						$patient_id = $elements[0];
						$amplicon = $elements[1];
						$chr = $elements[2];
						$pos = $elements[3];
						$dp = ($elements[4] != "") ? $elements[4] : 0;
						$status = $elements[5];

						if($sequencer == "illumina"){
							$frags = explode("_", $patient_id);
							$sample_number = substr($frags[1], 1);
						}
						else{
							$sample_number = 0;
						}

						if($group_id == "" || ($pos > $pos_last + 2) || $amplicon != $amplicon_last){
							$group_id = "$patient_id$amplicon$chr$pos";
						}

						$sql = "insert into $table_amplicons_depth_tech(patient_id, amplicon, group_id, chr, pos, dp, status, sample_number, is_cumul, start, stop, is_reseq, reseq_done, user_status) values ('$patient_id', '$amplicon', '$group_id', '$chr', '$pos', '$dp', '$status', '$sample_number', 'No', 0, 0, 'NONE', 'No', '')";
						$db->exec($sql);

						$pos_last = $pos;
						$amplicon_last = $amplicon;
					}
				}
			}
		}
	}
	closedir($handle);
}

$sql = "create table if not exists $table_library (patient_id varchar(100), nb_reads_fw int, nb_reads_q30_fw int, read_q30_percent_fw int, nb_reads_rv int, nb_reads_q30_rv int, read_q30_percent_rv int, read_length_mean_fw varchar(50), read_quality_mean_fw int, read_length_mean_rv varchar(50), read_quality_mean_rv int, nb_mapped_reads_fw int, mapped_reads_percent_fw int, nb_mapped_reads_rv int, mapped_reads_percent_rv int, sample_number int, is_already_view varchar(5), sequencer varchar(20), validation_status_tech varchar(3), tech_by varchar(100), validation_status_bio varchar(3), bio_by varchar(100))";
$db->exec($sql);

if (file_exists("$qclibfile")) {
	$lines = file("$qclibfile");
	$count = count($lines);

	for($l = 1; $l < $count; $l++){
		$line = $lines[$l];
		$line = trim($line);
	
		if($line != ""){
			$elements = explode (";", $line);
			$patient_id = $elements[0];
			$nb_reads_fw = $elements[1];
			$nb_reads_q30_fw = $elements[2];
			$read_q30_percent_fw = $elements[3];
			$nb_reads_rv = $elements[4];
			$nb_reads_q30_rv = $elements[5];
			$read_q30_percent_rv = $elements[6];
			$read_length_mean_fw = $elements[7];
			$read_quality_mean_fw = $elements[8];
			$read_length_mean_rv = $elements[9];
			$read_quality_mean_rv = $elements[10];
			$nb_mapped_reads_fw = $elements[11];
			$mapped_reads_percent_fw = $elements[12];
			$nb_mapped_reads_rv = $elements[13];
			$mapped_reads_percent_rv = $elements[14];

			if($sequencer == "illumina"){
				$frags = explode("_", $patient_id);
				$sample_number = substr($frags[1], 1);
			}
			else{
				$sample_number = 0;
			}

			$sql = "insert into $table_library(patient_id, nb_reads_fw, nb_reads_q30_fw, read_q30_percent_fw, nb_reads_rv, nb_reads_q30_rv, read_q30_percent_rv, read_length_mean_fw, read_quality_mean_fw, read_length_mean_rv, read_quality_mean_rv, nb_mapped_reads_fw, mapped_reads_percent_fw, nb_mapped_reads_rv, mapped_reads_percent_rv, sample_number, is_already_view, sequencer, validation_status_tech, tech_by, validation_status_bio, bio_by) values ('$patient_id', '$nb_reads_fw', '$nb_reads_q30_fw', '$read_q30_percent_fw', '$nb_reads_rv', '$nb_reads_q30_rv', '$read_q30_percent_rv', '$read_length_mean_fw', '$read_quality_mean_fw', '$read_length_mean_rv', '$read_quality_mean_rv', '$nb_mapped_reads_fw', '$mapped_reads_percent_fw', '$nb_mapped_reads_rv', '$mapped_reads_percent_rv', '$sample_number', 'No', '$sequencer', 'No', '', 'No', '')";

			$db->exec($sql);
		}
	}
}

function getPathmolDB_value($chr, $position, $ref, $alt){
	$annotation = $comments = "";
	$pdo = new PDO('mysql:host=localhost;dbname=PathMol_tumeur_solide_v_2', 'diagnostic', 'genpatho');
	$query = $pdo->prepare("SELECT annotation, comments from Pathmol_DB where chr='$chr' and position='$position' and ref='$ref' and alt='$alt'");
	
	$query->execute();

	while($row=$query->fetch()){
		$annotation = $row["annotation"];
		$comments = $row["comments"];
	}
	

	return "$annotation:$comments";
}


function getPathmolArtefactDB_value($chr, $position, $ref, $alt){
	$annotation = $comments = "";
	$pdo = new PDO('mysql:host=localhost;dbname=PathMol_tumeur_solide_v_2', 'diagnostic', 'genpatho');
	$query = $pdo->prepare("SELECT annotation, comments from Pathmol_Artefacts_DB where chr='$chr' and position='$position' and ref='$ref' and alt='$alt'");
	
	$query->execute();

	while($row=$query->fetch()){
		$annotation = $row["annotation"];
		$comments = $row["comments"];
	}
	

	return "$annotation:$comments";
}

function getDoGeDB_value($chr, $position, $ref, $alt){
	$pathogenicity = $isArtefact = "";
	$pdo = new PDO('mysql:host=31.10.141.11;dbname=VariantsDB', 'diagnostic', 'genpatho');
	$query = $pdo->prepare("SELECT pathogenicity, isArtefact from uptodateVariants where chr='$chr' and pos='$position' and reference='$ref' and alternate='$alt'");
	
	$query->execute();

	while($row=$query->fetch()){
		$pathogenicity = $row["pathogenicity"];
		$isArtefact = $row["isArtefact"];
	}
	

	return "$pathogenicity:$isArtefact";
}

function isIonServerVariant($patient, $chr, $position, $ref, $variant, $serie){
	$varcount = 0;
	$pdo = new PDO('mysql:host=localhost;dbname=LRT', 'diagnostic', 'genpatho');
	$query = $pdo->prepare("SELECT count(*) as varcount from variants_rt_$serie where chr='$chr' and position='$position' and ref='$ref' and variant='$variant' and patient_id='$patient'");

	$query->execute();

	while($row=$query->fetch()){
		$varcount = $row["varcount"];
	}
	

	return "$varcount";
}

?>

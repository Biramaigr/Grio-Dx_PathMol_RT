<?php

$variants_pileup2vcf = $argv[1];
$variants_mcl = $argv[2];

$pathinfo = pathinfo("$variants_pileup2vcf");
$basename = $pathinfo['basename'];

$tmpdir = sys_get_temp_dir();

$sample_id = str_replace(".vcf", "", $basename);

$sample_id_rc = str_replace("-", "_", $sample_id);
$sample_id_rc = str_replace(".", "_", $sample_id_rc);


$dbh = new PDO("sqlite:$tmpdir/$sample_id_rc.db");
$dbh->exec("drop table if exists variants_$sample_id_rc");
$dbh->exec("CREATE TABLE IF NOT EXISTS variants_$sample_id_rc (variant_id text, sample_id varchar(100), gene varchar(50), nm varchar(100), exon varchar(50), distance_from_exon varchar(10), nt_change text, aa_change text, chr varchar(2), pos int, ref text, alt text, dp int, dp_alt int, af double, type varchar(10), strand varchar(5), maf_classification varchar(100), qual int, af_esp double, af_1000g double, dbsnp_id varchar(100), cosmic_id varchar(100), sift_pred text, polyphen_pred text, soft varchar(50));");


if (file_exists($variants_pileup2vcf)) {
	$lines = file($variants_pileup2vcf);
	$count = count($lines);
		
	for($l = 0; $l < $count; $l++){
		$line = $lines[$l];
		if($line && ! preg_match("/^#/", $line)){
			$dbsnp = $cosmic_id = $strand = $maf_classification = $aa_change = $nt_change = $exon = $nm = $gene = $af = $dp_tot = $dp_alt = $dp_ref = "";
			$info = $more = $qual = $type = $alt = $ref = $id = $pos = $chr = $distance_from_exon = "";
			$af_esp = $af_1000g = $sift_pred = $polyphen_pred = "";
			$line = trim($line);
			$elements = explode ("\t", $line);
			
			$chr = $elements[0];
			$pos = $elements[1];
			$id = $elements[2];
			$ref = $elements[3];
			$alt = $elements[4];
			$type = (strlen($ref) == strlen($alt)) ? "SNV" : "INDEL";
			$qual = $elements[5];
			$more = $elements[7];
			$info = $elements[9];
			
			if($id != "."){
				$frag_id = explode (";", $id);
				
				$dbsnp = $frag_id[0];
				$cosmic_id = (count($frag_id) == 2) ? $frag_id[1] : "";
			}

			$frags_info = explode (":", $info);
			$dp_alt = explode (",", $frags_info[2])[1];
			$dp_tot = $frags_info[3];
			$af = explode (",", $frags_info[1])[1];
			$af = round($af/100, 5);

			$more_to_process = explode(";EFF=", $more)[1];

			$frag_more = explode ("|", $more_to_process);
			$gene = $frag_more[5];
			$nm = $frag_more[8];
			$exon = $frag_more[9];
			$frags = explode("/", $frag_more[3]);
			$nt_change = (count($frags) == 2) ? $frags[1] : $frags[0];
			$aa_change = (count($frags) == 2) ? $frags[0] : "";
			
			$maf_classification = substr(explode(";EFF=", $more)[1], 0, strpos(explode(";EFF=", $more)[1], "("));

			if(preg_match('/;STRAND=/', $more)){
				$strand = substr(explode(";STRAND=", $more)[1], 0, 1);
			}

			if(preg_match('/;EA_AC=/', $more)){
				$af_esp = explode(",", explode(";EA_AC=", $more)[1])[0]/(explode(",", explode(";EA_AC=", $more)[1])[0]+explode(",", explode(";EA_AC=", $more)[1])[1]);
			}
			
			if(preg_match('/;EUR_AF=/', $more)){
				$af_1000g = substr(explode(";EUR_AF=", $more)[1], 0, strpos(explode(";EUR_AF=", $more)[1], ";"));
			}

			if(preg_match('/;dbNSFP_SIFT_pred=/', $more)){
				$sift_pred = substr(explode(";dbNSFP_SIFT_pred=", $more)[1], 0, strpos(explode(";dbNSFP_SIFT_pred=", $more)[1], ";"));
			}

			if(preg_match('/;dbNSFP_Polyphen2_HDIV_pred=/', $more)){
				$polyphen_pred = substr(explode(";dbNSFP_Polyphen2_HDIV_pred=", $more)[1], 0, strpos(explode(";dbNSFP_Polyphen2_HDIV_pred=", $more)[1], ";"));
			}

			$distance_from_exon = "";
			$pattern = '/[-|\+](\d+)/';
			preg_match_all ($pattern , $nt_change , $matches);

			if(preg_match($pattern, $nt_change)){
				$distance_from_exon = $matches[1][0];
			}
			
			$soft = "pileup2vcf";
		
			$variant_id = $sample_id."".$chr."".$pos."".$ref."".$alt;
			
			$dbh->exec("insert into variants_$sample_id_rc values ('$variant_id', '$sample_id', '$gene', '$nm', '$exon', '$distance_from_exon', '$nt_change', '$aa_change', '$chr', '$pos', '$ref', '$alt', '$dp_tot', '$dp_alt', '$af', '$type', '$strand', '$maf_classification', '$qual', '$af_esp', '$af_1000g', '$dbsnp', '$cosmic_id', '$sift_pred', '$polyphen_pred', '$soft');");	
		}
	}
}


if (file_exists($variants_mcl)) {
	$lines = file($variants_mcl);
	$count = count($lines);
		
	for($l = 0; $l < $count; $l++){
		$line = $lines[$l];
		if($line && ! preg_match("/^#/", $line)){
			$dbsnp = $cosmic_id = $strand = $maf_classification = $aa_change = $nt_change = $exon = $nm = $gene = $af = $dp_tot = $dp_alt = $dp_ref = "";
			$info = $more = $qual = $type = $alt = $ref = $id = $pos = $chr = $distance_from_exon = "";
			$af_esp = $af_1000g = $sift_pred = $polyphen_pred = "";
			$line = trim($line);
			$elements = explode ("\t", $line);
			
			$chr = $elements[0];
			$pos = $elements[1];
			$id = $elements[2];
			$ref = $elements[3];
			$alt = $elements[4];
			$type = (strlen($ref) == strlen($alt)) ? "SNV" : "INDEL";
			$qual = $elements[5];
			$more = $elements[7];
			$info = $elements[9];
			
			if($id != "."){
				$frag_id = explode (";", $id);
				
				$dbsnp = $frag_id[0];
				$cosmic_id = (count($frag_id) == 2) ? $frag_id[1] : "";
			}

			$frags_info = explode (":", $info);
			$refalt = explode (",", $frags_info[1]);
			$dp_ref = $refalt[0];
			$dp_alt = $refalt[1];
			$dp_tot = $dp_ref + $dp_alt;
			$af = round($dp_alt/$dp_tot, 2);

			$more_to_process = explode(";EFF=", $more)[1];

			$frag_more = explode ("|", $more_to_process);
			$gene = $frag_more[5];
			$nm = $frag_more[8];
			$exon = $frag_more[9];
			$frags = explode("/", $frag_more[3]);
			$nt_change = (count($frags) == 2) ? $frags[1] : $frags[0];
			$aa_change = (count($frags) == 2) ? $frags[0] : "";
			
			$maf_classification = substr(explode(";EFF=", $more)[1], 0, strpos(explode(";EFF=", $more)[1], "("));

			if(preg_match('/;STRAND=/', $more)){
				$strand = substr(explode(";STRAND=", $more)[1], 0, 1);
			}

			if(preg_match('/;EA_AC=/', $more)){
				$af_esp = explode(",", explode(";EA_AC=", $more)[1])[0]/(explode(",", explode(";EA_AC=", $more)[1])[0]+explode(",", explode(";EA_AC=", $more)[1])[1]);
			}
			
			if(preg_match('/;EUR_AF=/', $more)){
				$af_1000g = substr(explode(";EUR_AF=", $more)[1], 0, strpos(explode(";EUR_AF=", $more)[1], ";"));
			}

			if(preg_match('/;dbNSFP_SIFT_pred=/', $more)){
				$sift_pred = substr(explode(";dbNSFP_SIFT_pred=", $more)[1], 0, strpos(explode(";dbNSFP_SIFT_pred=", $more)[1], ";"));
			}

			if(preg_match('/;dbNSFP_Polyphen2_HDIV_pred=/', $more)){
				$polyphen_pred = substr(explode(";dbNSFP_Polyphen2_HDIV_pred=", $more)[1], 0, strpos(explode(";dbNSFP_Polyphen2_HDIV_pred=", $more)[1], ";"));
			}

			$distance_from_exon = "";
			$pattern = '/[-|\+](\d+)/';
			preg_match_all ($pattern , $nt_change , $matches);

			if(preg_match($pattern, $nt_change)){
				$distance_from_exon = $matches[1][0];
			}
			
			$soft = "mutacaller";
		
			$variant_id = $sample_id."".$chr."".$pos."".$ref."".$alt;
			
			$dbh->exec("insert into variants_$sample_id_rc values ('$variant_id', '$sample_id', '$gene', '$nm', '$exon', '$distance_from_exon', '$nt_change', '$aa_change', '$chr', '$pos', '$ref', '$alt', '$dp_tot', '$dp_alt', '$af', '$type', '$strand', '$maf_classification', '$qual', '$af_esp', '$af_1000g', '$dbsnp', '$cosmic_id', '$sift_pred', '$polyphen_pred', '$soft');");	
		}
	}
}


echo "gene;nm;exon;distance_from_exon;nt_change;aa_change;chr;pos;ref;alt;dp;dp_alt;af;type;strand;maf_classification;qual;af_esp;af_1000g;dbsnp_id;cosmic_id;sift_pred;polyphen_pred;soft;success\n";

$stmt = $dbh->query("select distinct chr, pos, ref, alt from variants_$sample_id_rc order by chr asc, pos asc");
$stmt->setFetchMode(PDO::FETCH_ASSOC);

while($row = $stmt->fetch()) {
	$chr = $row['chr'];
	$pos = $row['pos'];
	$ref = $row['ref'];
	$alt = $row['alt'];

	$stmt1 = $dbh->query("select * from variants_$sample_id_rc where chr = '$chr' and pos = '$pos' and ref = '$ref' and alt = '$alt' order by chr asc, pos asc");
	$stmt1->setFetchMode(PDO::FETCH_ASSOC);

	$success = 0;
	$soft = "";
	$af_list = array();
	$dp_list = array();
	$dp_alt_list = array();

	while($row1 = $stmt1->fetch()) {
		$gene = $row1['gene'];
		$nm = $row1['nm'];
		$exon = $row1['exon'];
		$distance_from_exon = $row1['distance_from_exon'];
		$nt_change = $row1['nt_change'];
		$aa_change = $row1['aa_change'];
		$dp = $row1['dp'];
		$dp_alt = $row1['dp_alt'];
		$af = $row1['af'];
		$type = $row1['type'];
		$strand = $row1['strand'];
		$maf_classification = $row1['maf_classification'];
		$qual = $row1['qual'];
		$af_esp = $row1['af_esp'];
		$af_1000g = $row1['af_1000g'];
		$dbsnp_id = $row1['dbsnp_id'];
		$cosmic_id = $row1['cosmic_id'];
		$sift_pred = $row1['sift_pred'];
		$polyphen_pred = $row1['polyphen_pred'];
		$soft .= $row1['soft']."|";

		array_push($af_list, $af);
		array_push($dp_list, $dp);
		array_push($dp_alt_list, $dp_alt);

		$success++;
	}

	$soft = ($soft != "") ? substr($soft, 0, strlen($soft) - 1) : $soft;

	echo "$gene;$nm;$exon;$distance_from_exon;$nt_change;$aa_change;$chr;$pos;$ref;$alt;".max($dp_list).";".max($dp_alt_list).";".max($af_list).";$type;$strand;$maf_classification;$qual;$af_esp;$af_1000g;$dbsnp_id;$cosmic_id;$sift_pred;$polyphen_pred;$soft;$success\n";	
}


$dbh = null;

?>

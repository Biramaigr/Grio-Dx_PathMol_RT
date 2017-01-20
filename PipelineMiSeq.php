<?php

$reads_dir = $argv[1];
$bwa_algo = $argv[2];
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

$pathinfo = pathinfo("$reads_dir");
$workdir = $pathinfo['dirname']."/Analyses";

$reads_dir_filter = $reads_dir."_filter";
$log_dir = $workdir."/log";
$bam_raw_dir = $workdir."/Bams_RAW";
$bam_dir = $workdir."/Bams";
$vcf_dir_mcl = $workdir."/Resultats_mutacaller";
$vcf_dir_gatk = $workdir."/Resultats_gatk";
$vcf_dir_tvc = $workdir."/Resultats_tvc";
$output_tmp = $workdir."/tmp_tvc";
$variants_dir = $workdir."/variants";
$synchro = $workdir."/Synchro";


if($is_pp == "Yes"){
	system("mkdir -p $reads_dir_filter $bam_raw_dir $bam_dir $vcf_dir_mcl $vcf_dir_gatk $vcf_dir_tvc $output_tmp $variants_dir");
}
else{
	system("mkdir -p $bam_raw_dir $bam_dir $vcf_dir_mcl $vcf_dir_gatk $vcf_dir_tvc $output_tmp $variants_dir");
}


$files = getFiles("$reads_dir");

sort($files);

for($f = 0; $f < count($files); $f++){
	$read1 = $files[$f];
	$read2 = $files[$f+1];
	#echo $reads_dir."/".$files[$f]."\n";
	$read1_filtered = preg_replace("/.fastq.gz|.fastq|.fq|.bam/", ".filtered.fastq", $read1);
	$read2_filtered = preg_replace("/.fastq.gz|.fastq|.fq|.bam/", ".filtered.fastq", $read2);
	
	$out = preg_replace("/.fastq.gz|.fastq|.fq|.bam/", "", $read1);
	$out = preg_replace("/_R1|-R1|R1/", "", $out);
	
	if(preg_match("/.gz$/", $read1)){
		system("gzip -d $reads_dir/$read1");
		#echo "gzip -d $reads_dir/$read1\n";
	}
	
	if(preg_match("/.gz$/", $read2)){
		system("gzip -d $reads_dir/$read2");
	}
	
	$read1_gunzip = preg_replace("/.gz$/", "", $read1);
	$read2_gunzip = preg_replace("/.gz$/", "", $read2);
	
	
	if($is_pp == "Yes"){
		system("perl $ngs_path/Scripts/PathMol_Tumeur_Solide_Dev/$mode/preProcess.pl $reads_dir/$read1_gunzip $reads_dir_filter/$read1_filtered 30 50 160 70 >> $log_dir/$out.log 2>&1");
		system("perl $ngs_path/Scripts/PathMol_Tumeur_Solide_Dev/$mode/preProcess.pl $reads_dir/$read2_gunzip $reads_dir_filter/$read2_filtered 30 50 160 50 >> $log_dir/$out.log 2>&1");
		
		if($bwa_algo == "mem"){
			system("/biopathdata/pipelineuser/NGS/Programs/bwa-0.7.5a/bwa mem -t 2 $ngs_path/Databases/hg19_chr/hg19.fa $reads_dir_filter/$read1_filtered $reads_dir_filter/$read2_filtered | /biopathdata/pipelineuser/NGS/Programs/samtools-0.1.19/samtools view -bT $ngs_path/Databases/hg19_chr/hg19.fa -> $bam_raw_dir/$out.q0.bam");	
		}
		else{
			system("/biopathdata/pipelineuser/NGS/Programs/bwa-0.7.5a/bwa bwasw -t 2 -q 1 -r 1 $ngs_path/Databases/hg19_chr/hg19.fa $reads_dir_filter/$read1_filtered $reads_dir_filter/$read2_filtered | /biopathdata/pipelineuser/NGS/Programs/samtools-0.1.19/samtools view -bT $ngs_path/Databases/hg19_chr/hg19.fa -> $bam_raw_dir/$out.q0.bam");			
		}
	}
	else{
		if($bwa_algo == "mem"){
			system("/biopathdata/pipelineuser/NGS/Programs/bwa-0.7.5a/bwa mem -t 2 $ngs_path/Databases/hg19_chr/hg19.fa $reads_dir/$read1_gunzip $reads_dir/$read2_gunzip | /biopathdata/pipelineuser/NGS/Programs/samtools-0.1.19/samtools view -bT $ngs_path/Databases/hg19_chr/hg19.fa -> $bam_raw_dir/$out.q0.bam");	
		}
		else{
			system("/biopathdata/pipelineuser/NGS/Programs/bwa-0.7.5a/bwa bwasw -t 2 -q 1 -r 1 $ngs_path/Databases/hg19_chr/hg19.fa $reads_dir/$read1_gunzip $reads_dir/$read2_gunzip | /biopathdata/pipelineuser/NGS/Programs/samtools-0.1.19/samtools view -bT $ngs_path/Databases/hg19_chr/hg19.fa -> $bam_raw_dir/$out.q0.bam");	
		}
	}

	#system("/biopathdata/pipelineuser/NGS/Programs/samtools-0.1.19/samtools view -bq 10 $bam_raw_dir/$out.q0.bam > $bam_raw_dir/$out.nrg.bam");
	system("/biopathdata/pipelineuser/NGS/Programs/samtools-0.1.19/samtools view -bq 1 $bam_raw_dir/$out.q0.bam > $bam_raw_dir/$out.nrg.bam");
	system("java -Xmx8g -Djava.io.tmpdir=$ngs_path/tmp -jar $ngs_path/Programs/picard-tools/picard.jar AddOrReplaceReadGroups RGLB=read_id RGPL=illumina RGPU=run RGSM=rgsm I=$bam_raw_dir/$out.nrg.bam O=$bam_dir/$out.raw.bam SORT_ORDER=coordinate CREATE_INDEX=TRUE VALIDATION_STRINGENCY=LENIENT");
	
	if($tech != "amplicon"){
		system("/biopathdata/pipelineuser/NGS/Programs/samtools-0.1.19/samtools rmdup $bam_dir/$out.raw.bam $bam_dir/$out.rdup.bam");
		system("/biopathdata/pipelineuser/NGS/Programs/samtools-0.1.19/samtools index $bam_dir/$out.rdup.bam");
	}
	else{
		rename("$bam_dir/$out.raw.bam", "$bam_dir/$out.rdup.bam");
		rename("$bam_dir/$out.raw.bai", "$bam_dir/$out.rdup.bai");
	}
	
	if($is_indel_realign == "No"){
		rename("$bam_dir/$out.rdup.bam", "$bam_dir/$out.bam");
		rename("$bam_dir/$out.rdup.bai", "$bam_dir/$out.bai");
	}
	else{
		system("java -Xmx8g -Djava.io.tmpdir=$ngs_path/tmp -jar $ngs_path/Programs/GATK/GenomeAnalysisTK.jar -T RealignerTargetCreator -nt 6 -I $bam_dir/$out.rdup.bam --downsampling_type NONE --disable_auto_index_creation_and_locking_when_reading_rods -R $ngs_path/Databases/hg19_chr/hg19.fa -L $bedfile -o $bam_dir/$out.intervals");
		
		system("java -Xmx8g -Djava.io.tmpdir=$ngs_path/tmp -jar $ngs_path/Programs/GATK/GenomeAnalysisTK.jar -I $bam_dir/$out.rdup.bam -R $ngs_path/Databases/hg19_chr/hg19.fa -T IndelRealigner --downsampling_type NONE --disable_auto_index_creation_and_locking_when_reading_rods -targetIntervals $bam_dir/$out.intervals -L $bedfile -o $bam_dir/$out.realigned.bam");
		system("java -Xmx8g -Djava.io.tmpdir=$ngs_path/tmp -jar $ngs_path/Programs/GATK/GenomeAnalysisTK.jar -T BaseRecalibrator -nct 12 -I $bam_dir/$out.realigned.bam --disable_auto_index_creation_and_locking_when_reading_rods --downsampling_type NONE -R $ngs_path/Databases/hg19_chr/hg19.fa -knownSites $ngs_path/Databases/snpEff/refDbsnp/dbsnp_135.hg19.vcf -L $bedfile -o $bam_dir/$out.recal.grp");
		system("java -Xmx8g -Djava.io.tmpdir=$ngs_path/tmp -jar $ngs_path/Programs/GATK/GenomeAnalysisTK.jar -T PrintReads -nct 12 -R $ngs_path/Databases/hg19_chr/hg19.fa -I $bam_dir/$out.realigned.bam --disable_auto_index_creation_and_locking_when_reading_rods --downsampling_type NONE -BQSR $bam_dir/$out.recal.grp -L $bedfile -o $bam_dir/$out.bam");
		
		unlink("$bam_dir/$out.rdup.bam");
		unlink("$bam_dir/$out.rdup.bai");
		unlink("$bam_dir/$out.intervals");
		unlink("$bam_dir/$out.realigned.bam");
		unlink("$bam_dir/$out.realigned.bai");
		unlink("$bam_dir/$out.recal.grp");
		
	}
	
	system("java -Djava.io.tmpdir=$ngs_path/tmp -Xmx8g -jar $ngs_path/Programs/MutaCaller-1.6/MutaCaller-1.6.jar -LIBS_PATH=$ngs_path/Programs/MutaCaller-1.6 -MATE_TYPE=PAIRED -DPMIN=$mcl_dp_min -AFMIN=$mcl_af_min -PQUALMIN=10 -CLUSTER_WINDOW=20 -CLUSTER_NUMBER=4 -REFERENCE=$ngs_path/Databases/hg19_tmap_chr/hg19.fa -BEDFILE=$bedfile -BIG_INDELS=$mcl_bgidl -BIG_INDELS_OPTIONS=100,10,10,$mcl_af_min,YES -INPUT=$bam_dir/$out.bam -OUTPUT=$vcf_dir_mcl/$out.mcl.ns.vcf");
	system("cat $vcf_dir_mcl/$out.mcl.ns.vcf | /biopathdata/pipelineuser/NGS/Programs/vcftools_0.1.12b/bin/vcf-sort > $vcf_dir_mcl/$out.mcl.vcf");
	unlink("$vcf_dir_mcl/$out.mcl.ns.vcf");
	system("php $ngs_path/Scripts/PathMol_Tumeur_Solide_Dev/$mode/Variants_Annotate.php $vcf_dir_mcl/$out.mcl.vcf $ngs_path");

	system("php $ngs_path/Scripts/PathMol_Tumeur_Solide_Dev/$mode/Variants2DB.php $vcf_dir_gatk/$out.haplotype.snpEff.vcf $vcf_dir_tvc/$out.tvc.snpEff.vcf $vcf_dir_mcl/$out.mcl.snpEff.vcf  > $variants_dir/$out.variants.csv");
	
	mkdir("$synchro/$out");
	
	$f++;
}

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


?>

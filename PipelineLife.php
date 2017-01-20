<?php

$reads_dir = $argv[1];
$bedfile = $argv[2];
$bedfile_name = $argv[3];
$is_indel_realign = $argv[4];
$tech = $argv[5];
$map_type = $argv[6];
$ngs_path = $argv[7];
$mcl_noisy_check = $argv[8];
$mcl_bgidl = $argv[9];
$mcl_af_min = $argv[10];
$p2v_af_min = $mcl_af_min*100;
$mcl_dp_min = $argv[11];
$input_type = $argv[12];
$mode = $argv[13];
$server = $argv[14];

$reference = ($server == "S5") ? "/biopathdata/pipelineuser/NGS/Databases/hg19_S5.fasta" : "/biopathdata/pipelineuser/NGS/Databases/hg19_GAO.fasta";

$pathinfo = pathinfo("$reads_dir");
$workdir = $pathinfo['dirname']."/Analyses";

$log_dir = $workdir."/log";
$bam_raw_dir = $workdir."/Bams_RAW";
$bam_dir = $workdir."/Bams";
$pileup_dir = $workdir."/Pileup";
$vcf_dir_pileup2vcf = $workdir."/Resultats_pileup2vcf";
$vcf_dir_pileup2vcf_raw = $workdir."/Resultats_pileup2vcf_raw";
$vcf_dir_mcl = $workdir."/Resultats_mutacaller";
$variants_dir = $workdir."/variants";
$synchro = $workdir."/Synchro";

if($input_type == "bam"){
	$bamtofastq = $pathinfo['dirname']."/BamToFastq";
	system("mkdir -p $bamtofastq");
	system("php $ngs_path/Scripts/PathMol_RT/$mode/Bam2Fastq.php $reads_dir $bamtofastq $ngs_path");
}

system("mkdir -p $workdir $bam_raw_dir $bam_dir $pileup_dir $vcf_dir_pileup2vcf $vcf_dir_pileup2vcf_raw $vcf_dir_mcl $variants_dir");

$files = getFiles("$reads_dir");

sort($files);

for($f = 0; $f < count($files); $f++){
	$bamfile = $files[$f];
	
	$out = preg_replace("/.bam/", "", $bamfile);
	
	system("cp $reads_dir/$bamfile $bam_dir/");	

	system("/biopathdata/pipelineuser/NGS/Programs/samtools-0.1.19/samtools index $bam_dir/$out.bam");	
	
	system("/biopathdata/pipelineuser/NGS/Programs/samtools-0.1.18/samtools mpileup -A -s -O -B -d 20000 -f $reference $bam_dir/$out.bam > $pileup_dir/$out.pileup");		
	system("python /biopathdata/pipelineuser/NGS/Programs/pileup2vcf/p2v -i $pileup_dir/$out.pileup --minFreq $p2v_af_min --minDepth $mcl_dp_min --minReadsAlt 25 --minDeltaDepthToCallIndels 100 --outputPrefix $vcf_dir_pileup2vcf_raw/$out --regions $bedfile --refGenome $reference --clinics --loglevel 4");
	system("cp $vcf_dir_pileup2vcf_raw/$out.GoodVariants.vcf $vcf_dir_pileup2vcf/$out.pileup2vcf.vcf");
	system("php $ngs_path/Scripts/PathMol_RT/$mode/Variants_Annotate.php $vcf_dir_pileup2vcf/$out.pileup2vcf.vcf $ngs_path");
	
	system("java -Djava.io.tmpdir=$ngs_path/tmp -Xmx8g -jar $ngs_path/Programs/MutaCaller-1.6/MutaCaller-1.6-rt.jar -LIBS_PATH=$ngs_path/Programs/MutaCaller-1.6 -MATE_TYPE=PAIRED -DPMIN=$mcl_dp_min -AFMIN=$mcl_af_min -PQUALMIN=10 -CLUSTER_WINDOW=20 -CLUSTER_NUMBER=4 -REFERENCE=$reference -BEDFILE=$bedfile -BIG_INDELS=NO -BIG_INDELS_OPTIONS=100,10,10,$mcl_af_min,YES -INPUT=$bam_dir/$out.bam -OUTPUT=$vcf_dir_mcl/$out.mcl.ns.vcf");
	system("cat $vcf_dir_mcl/$out.mcl.ns.vcf | /biopathdata/pipelineuser/NGS/Programs/vcftools_0.1.12b/bin/vcf-sort > $vcf_dir_mcl/$out.mcl.vcf");
	unlink("$vcf_dir_mcl/$out.mcl.ns.vcf");
	system("php $ngs_path/Scripts/PathMol_RT/$mode/Variants_Annotate.php $vcf_dir_mcl/$out.mcl.vcf $ngs_path");

	system("php $ngs_path/Scripts/PathMol_RT/$mode/Variants2DB.php $vcf_dir_pileup2vcf/$out.pileup2vcf.snpEff.vcf $vcf_dir_mcl/$out.mcl.snpEff.vcf  > $variants_dir/$out.variants.csv");

	mkdir("$synchro/$out");
	
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

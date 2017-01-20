<?php

$variants_file = $argv[1];
$ngs_path = $argv[2];

$variants_file_snpeff = str_replace(".vcf", ".snpEff.vcf", $variants_file);
$variants_file_temp1 = str_replace(".vcf", ".temp1.vcf", $variants_file);
$variants_file_temp2 = str_replace(".vcf", ".temp2.vcf", $variants_file);
$variants_file_temp3 = str_replace(".vcf", ".temp3.vcf", $variants_file);
$variants_file_temp4 = str_replace(".vcf", ".temp4.vcf", $variants_file);
$variants_file_temp5 = str_replace(".vcf", ".temp5.vcf", $variants_file);

system("java -Djava.io.tmpdir=/biopathdata/pipelineuser/NGS/tmp -Xmx8g -jar $ngs_path/Programs/snpEff/SnpSift.jar annotate -v $ngs_path/Databases/snpEff/dbsnp_b147.vcf.gz $variants_file > $variants_file_temp1");

system("java -Djava.io.tmpdir=/biopathdata/pipelineuser/NGS/tmp -Xmx8g -jar $ngs_path/Programs/snpEff/SnpSift.jar annotate -v $ngs_path/Databases/snpEff/refCosmic/Cosmic_v69_DeVa_Edition.vcf $variants_file_temp1 > $variants_file_temp2");

system("java -Djava.io.tmpdir=/biopathdata/pipelineuser/NGS/tmp -Xmx8g -jar $ngs_path/Programs/snpEff/snpEff.jar eff -c $ngs_path/Programs/snpEff/snpEff.config -v -hgvs -noLog -noStats -noMotif -noNextProt hg19 $variants_file_temp2 > $variants_file_temp3");

system("java -Djava.io.tmpdir=/biopathdata/pipelineuser/NGS/tmp -Xmx8g -jar $ngs_path/Programs/snpEff/SnpSift.jar dbnsfp -f SIFT_pred,Polyphen2_HDIV_pred -v -db $ngs_path/Databases/snpEff/dbNSFP/dbNSFP/dbNSFP2.5.txt.gz $variants_file_temp3 > $variants_file_temp4");

system("java -Djava.io.tmpdir=/biopathdata/pipelineuser/NGS/tmp -Xmx8g -jar $ngs_path/Programs/snpEff/SnpSift.jar annotate -v $ngs_path/Databases/snpEff/ESP6500SI-V2-SSA137.GRCh38-liftover.vcf $variants_file_temp4 > $variants_file_temp5");
 
system("java -Djava.io.tmpdir=/biopathdata/pipelineuser/NGS/tmp -Xmx8g -jar $ngs_path/Programs/snpEff/SnpSift.jar annotate -v $ngs_path/Databases/snpEff/ALL.wgs.phase3_shapeit2_mvncall_integrated_v5b.20130502.sites.vcf.gz $variants_file_temp5 > $variants_file_snpeff");

unlink($variants_file_temp1);
unlink($variants_file_temp2);
unlink($variants_file_temp3);
unlink($variants_file_temp4);
unlink($variants_file_temp5);

?>

<?php

$bamdir = $argv[1];
$fastqdir = $argv[2];
$ngs_path = $argv[3];

mkdir($fastqdir);

if ($handle = opendir($bamdir)) {
    while (false !== ($entry = readdir($handle))) {
        if (preg_match("/.bam$/", $entry)) {
		$out = str_replace(".bam", "", $entry);
		system("$ngs_path/Programs/bedtools2/bin/bamToFastq -i $bamdir/$entry -fq $fastqdir/$out.fastq");
        }
    }
    closedir($handle);
}


?>

<?php

$serie = $argv[1];
$chr = $argv[2];

$pdo = new PDO('mysql:host=localhost;dbname=LRT', 'diagnostic', 'genpatho');


$table = "variants_$serie";


$query1 = $pdo->prepare("select distinct variant_id from $table where chr = '$chr'");

$query1->execute();

while($row1=$query1->fetch()){
	$variant_id = $row1['variant_id'];
	
	$query = $pdo->prepare("select count(distinct patient_id) as reccurence from $table where variant_id = '$variant_id'");
	

	$query->execute();
	
	while($row=$query->fetch()){
		$reccurence = $row['reccurence'];
		
		if($reccurence > 1){
			$sql = "update $table set recurrence = '$reccurence' where variant_id = '$variant_id'";
			$pdo->exec($sql);
		}
	}
}

system("mkdir /biopathdata/pipelineuser/NGS/PathMol/RUNS/$serie/Analyses/Synchro_reccurence/$chr");
?>


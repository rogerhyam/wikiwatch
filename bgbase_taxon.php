<?php
	
	
// wrapper for calling tests on the BgbaseTaxon class
require_once('config.php');
require_once('classes/BgbaseTaxon.php');
require_once('classes/SpecifyTaxon.php');

$t = new BgbaseTaxon(get_bgbase_connection(), 28561);

echo "\n";
echo $t->get_name_num();
echo "\n";
echo $t->get_full_name();
echo "\n";
echo $t->get_rank();
echo "\n";
echo $t->get_name();
echo "\n";
echo $t->get_author();
echo "\n";
$parent = $t->get_parent_taxon();
echo $parent->get_name_num() . ' ' . $parent->get_full_name();
echo "\n";
echo $parent->get_signature();
echo "\n";

echo "\n-----------------------------\n";

$s = new SpecifyTaxon(get_specify_connection(), $t);
$s->save();

	
?>
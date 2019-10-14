<?php

class BgbaseAbstractTaxon{
	
	private $name_num = null;
	private $parent_taxon = null;

	function __construct($name_num){
		$this->name_num = $name_num;
	}
	
	function get_name_num(){
		return $this->name_num;
	}
	
	/**
	* This is overridden by stub to give 
	* a string we can used to track implied taxa
	*/
	function get_signature(){
		return (string)$this->name_num;
	}
	
	function get_cultivar(){
		return null;
	}

	function is_accepted(){
		return true;
	}
	
	function is_hybrid(){
		return false;
	}
	
	function get_accepted_taxon(){
		return null;
	}
}

// a class that represents a taxon loaded from BGBase

class BgbaseTaxon extends BgbaseAbstractTaxon{
	
	private $name_row = null;
	public $infra_names = array();
	private $mysqli = null;
	private $accepted_taxon = null;
	
	/**
	* Load self from bgbase dump based on mysqli connection
	*/
	function __construct($mysqli, $name_num){
		
		parent::__construct($name_num);
		
		$this->mysqli = $mysqli;
		
		// get the main name row for the name
		$result = $mysqli->query("SELECT * FROM names WHERE name_num = $name_num");
		if($result->num_rows == 0) return false;
		$this->name_row = $result->fetch_assoc();
		
		// get any rows in the MV table that are subspecific names
		$sql = "SELECT * FROM names_mv WHERE name_num = $name_num and infra_rank is not null";
		$result = $mysqli->query($sql);
		while($row = $result->fetch_assoc()){
			$this->infra_names[] = $row;
		}

	}
	
	function get_rank(){
		
		// if it has a cultivar name then it is of rank cultivar according to me
		if($this->name_row['CULTIVAR']) return 'Cultivar';
		
		// if it doesn't have infraspecific names then it might be a species or above
		if(count($this->infra_names) == 0) return 'Species';
		
		// got to here so it has infraspecific names (and isn't a cultivar)
		// play a guessing game going from lowest rank up
		// remember it could be a subvar of a var of a subspecies!
		if ($this->has_infra_rank('G')) return 'Grex';
		if ($this->has_infra_rank('SF')) return 'Subform';
		if ($this->has_infra_rank('SV')) return 'Subvariety';
		if ($this->has_infra_rank('NV')) return 'Nothovar';
		if ($this->has_infra_rank('F')) return 'Forma';
		if ($this->has_infra_rank('V')) return 'Variety';
		if ($this->has_infra_rank('S')) return 'Subspecies';

		// give up - could be a genus or family
		return 'Unknown';
	
	}
	
	function has_infra_rank($code){
		foreach($this->infra_names as $row){
			if($row['INFRA_RANK'] == $code) return true;
		}
		return false;
	}
	
	function get_infra_name($code){
		foreach($this->infra_names as $row){
			if($row['INFRA_RANK'] == $code) return trim($row['INFRA_EPI']);
		}
		return false;
	}
	
	function get_infra_authority($code){
		foreach($this->infra_names as $row){
			if($row['INFRA_RANK'] == $code) return trim($row['INFRA_AUTH']);
		}
		return false;
	}
	
	// returns the actual significant portion of the name e.g. the specific or subspecific epithet
	function get_name(){
		
		// depends on what the rank is.
		switch($this->get_rank()){
			case 'Cultivar': return $this->name_row['CULTIVAR'];
			case 'Species': return $this->name_row['SPECIES'];
			case 'Unknown': return $this->name_row['GENUS'];
			case 'Subspecies': return $this->get_infra_name('S');
			case 'Variety': return $this->get_infra_name('V');
			case 'Forma': return $this->get_infra_name('F');
			case 'Nothovar': return $this->get_infra_name('NV');
			case 'Subvariety': return $this->get_infra_name('SV');
			case 'Subform': return $this->get_infra_name('SF');
			case 'Grex': return $this->get_infra_name('G');
		}
		
		return false;
	}
	
	function get_cultivar(){
		return $this->name_row['CULTIVAR'];
	}
	
	function is_accepted(){
		if($this->name_row['ACCEPT'] == 'A') return true;
		return false;
	}
	
	function get_accepted_taxon(){
		
		// short cut it if we've been asked before
		if($this->accepted_taxon != null) return $this->accepted_taxon;
		
		// there should be an alt_name in the names_mv field 
		// which points to an Accepted name
		$sql = "select n.name_num
			from names as n join names_mv as mv on mv.ALT_NAME = n.name_num
			where n.ACCEPT = 'A'
			and mv.name_num = 133701";
		$result = $this->mysqli->query($sql);
		if($result->num_rows == 0){
			return null;
		}else{
			$row = $result->fetch_assoc();
			$this->accepted_taxon = new BgbaseTaxon($this->mysqli, $row['name_num']);
			return $this->accepted_taxon;
		}
		
	}
	
	function is_hybrid(){
		if($this->name_row['SPEC_HYBR']) return true;
		return false;
	}
	
	function get_author(){
		
		// depends on what the rank is.
		switch($this->get_rank()){
			case 'Cultivar': return $this->name_row['SPEC_AUTH'];
			case 'Species': return $this->name_row['SPEC_AUTH'];
			case 'Unknown': return $this->name_row['SPEC_AUTH'];
			case 'Subspecies': return $this->get_infra_authority('S');
			case 'Variety': return $this->get_infra_authority('V');
			case 'Forma': return $this->get_infra_authority('F');
			case 'Nothovar': return $this->get_infra_authority('NV');
			case 'Subvariety': return $this->get_infra_authority('SV');
			case 'Subform': return $this->get_infra_authority('SF');
			case 'Grex': return $this->get_infra_authority('G');
		}
		
		return false;
	}
	
	// just the string as in the names table
	function get_full_name(){
		return $this->name_row['NAME_FULL'];
	}
	
	function get_parent_taxon(){
		
		echo "\nGET Parent\n";
		
		// only create it once
		if($this->parent_taxon != null) return $this->parent_taxon;
		
		// again everything is rank dependent
		$rank = $this->get_rank();
		
		echo "\n$rank\n";
		
		// if we are at the level of 'Unknown' then we have topped out
		if($rank == 'Unknown') return false;
		
		// if it is a genus then we join it to a made up family based on its FAMILY field
		if($rank == 'Genus'){
			$this->generate_family_parent_taxon();
			return $this->parent_taxon;
		}
		
		
		// if it is a species then there is an unknown genus out there for it
		if($rank == 'Species'){
			/*
			$genus_name = $this->name_row['GENUS'];
			$name_num = $this->name_row['NAME_NUM']; // prevent circular references
			$sql = "SELECT name_num from names where GENUS = '$genus_name' AND SPECIES is NULL AND ACCEPT = 'HT' AND NAME_NUM != $name_num;";
			echo "\n$sql\n";
			$result = $this->mysqli->query($sql);
			if($result->num_rows == 1){
				$row = $result->fetch_assoc();
				$this->parent_taxon = new BgbaseTaxon($this->mysqli, $row['name_num']);
			}else{
				$this->generate_genus_parent_taxon();
			};
			*/
			$this->generate_genus_parent_taxon();
			return $this->parent_taxon;
		}
		
		// everything below species is joined to a species because that is nomenclaturally correct.
		// only issues are with subvars and subforms and subsubspecies? but there are fewer than 100 of these (51?)
		$genus_name = $this->name_row['GENUS'];
		$species_name = $this->name_row['SPECIES'];
		$auth = $this->name_row['SPEC_AUTH'];
		
		$sql = "SELECT name_num
			from names 
			where genus = '$genus_name'
			and species = '$species_name'
			and cultivar is null
			and spec_auth = '$auth'
			and name_num not in  (
				select distinct(name_num) from names_mv where INFRA_RANK is not null
			)";
			
		$result = $this->mysqli->query($sql);
		if($result->num_rows == 0){
			// we haven't found a parent species taxon so we must create one
			$this->generate_species_parent_taxon();
		}elseif($result->num_rows > 1){
			echo "\nToo many parents of infraspecific taxon $this->name_num \n";
			echo $sql;
			exit;
		}else{
			$row = $result->fetch_assoc();
			$this->parent_taxon = new BgbaseTaxon($this->mysqli, $row['name_num']);
		}
		
		return $this->parent_taxon;
		
	}
	
	function generate_species_parent_taxon(){
		
		$this->parent_taxon = new StubTaxon(
			'Species',
			ucfirst(strtolower($this->name_row['FAMILY'])),
			ucfirst(strtolower($this->name_row['GENUS'])),
			$this->name_row['SPECIES'],
			$this->name_row['SPEC_AUTH']);
			
	}
	
	function generate_genus_parent_taxon(){
		
		$this->parent_taxon = new StubTaxon(
			'Genus',
			ucfirst(strtolower($this->name_row['FAMILY'])),
			ucfirst(strtolower($this->name_row['GENUS'])),
			null,
			null);
			
	}
	
	function generate_family_parent_taxon(){
		
		$this->parent_taxon = new StubTaxon(
			'Family',
			ucfirst(strtolower($this->name_row['FAMILY'])),
			null,
			null,
			null);
	}
	
} // end class

// This is used to create a taxon that doesn't exist in BGBase but that is implied.
// i.e. missing species for

class StubTaxon extends BgbaseAbstractTaxon{
	
	private $rank =  null;
	private $family = null;
	private $genus = null;
	private $species = null;
	private $author = null;
	
	function __construct($rank, $family, $genus, $species, $author){
		parent::__construct(-1);
		
		$this->rank =  $rank;
		$this->family = $family;
		$this->genus = $genus;
		$this->species = $species;
		$this->author = $author;
	}
	
	function get_parent_taxon(){
		switch ($this->rank) {
			case 'Family': return null;
			case 'Genus': return new StubTaxon('Family', $this->family, null, null, $this->author);
			case 'Species': return new StubTaxon('Family', $this->family, $this->genus, null, $this->author);
			default: return null;
		}
	}
	
	
	function get_name(){
		
		switch ($this->rank) {
			case 'Species': return $this->species;
			case 'Genus': return $this->genus;
			case 'Family': return $this->family;
			default: return null;
		}
		
	}
	
	function get_full_name(){
		if($this->rank != 'Family'){
			return "$this->genus $this->species $this->author";
		}else{
			return $this->family;
		}
	}
	
	function get_signature(){
		return "$this->rank:$this->family/$this->genus/$this->species";
	}
	
	function get_rank(){
		return $this->rank;
	}
}	
	
?>
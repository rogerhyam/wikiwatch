<?php
	
// wraps around a bgbase taxon (either real or just a stub) and writes it to 
	
class SpecifyTaxon{
	
	private $mysqli = null;
	private $bg_taxon = null;
	private $specify_row = null;
	private $field_map = array();
	private $rank_definitions = null;
	
	
	
	private $specify_fields = array(
	"TaxonID",
	"TimestampCreated",
	"TimestampModified",
	"Version",
	"Author",
	"CitesStatus",
	"COLStatus",
	"CommonName",
	"CultivarName",
	"EnvironmentalProtectionStatus",
	"EsaStatus",
	"FullName",
	"GroupNumber",
	"GUID",
	"HighestChildNodeNumber",
	"Integer1",
	"Integer2",
	"Integer3",
	"Integer4",
	"Integer5",
	"IsAccepted",
	"IsHybrid",
	"IsisNumber",
	"LabelFormat",
	"Name",
	"NcbiTaxonNumber",
	"NodeNumber",
	"Number1",
	"Number2",
	"Number3",
	"Number4",
	"Number5",
	"RankID",
	"Remarks",
	"Source",
	"TaxonomicSerialNumber",
	"Text1",
	"Text10",
	"Text11",
	"Text12",
	"Text13",
	"Text14",
	"Text15",
	"Text16",
	"Text17",
	"Text18",
	"Text19",
	"Text2",
	"Text20",
	"Text3",
	"Text4",
	"Text5",
	"Text6",
	"Text7",
	"Text8",
	"Text9",
	"UnitInd1",
	"UnitInd2",
	"UnitInd3",
	"UnitInd4",
	"UnitName1",
	"UnitName2",
	"UnitName3",
	"UnitName4",
	"UsfwsCode",
	"Visibility",
	"YesNo1",
	"YesNo10",
	"YesNo11",
	"YesNo12",
	"YesNo13",
	"YesNo14",
	"YesNo15",
	"YesNo16",
	"YesNo17",
	"YesNo18",
	"YesNo19",
	"YesNo2",
	"YesNo3",
	"YesNo4",
	"YesNo5",
	"YesNo6",
	"YesNo7",
	"YesNo8",
	"YesNo9",
	"ModifiedByAgentID",
	"AcceptedID",
	"ParentID",
	"VisibilitySetByID",
	"HybridParent2ID",
	"TaxonTreeDefItemID",
	"HybridParent1ID",
	"CreatedByAgentID",
	"TaxonTreeDefID"
);

	
	function __construct($mysqli, $bg_taxon){
		
		$this->mysqli = $mysqli;
		$this->bg_taxon = $bg_taxon;
		$this->load_rank_definitions();
		
	}
	
	/**
	* Build a little lookup table for the rank stuff.
	*/
	function load_rank_definitions(){
		$this->rank_definitions = array();
		$sql = "SELECT TaxonTreeDefItemID, Name, RankID, ParentItemID, TaxonTreeDefID FROM taxontreedefitem where TaxonTreeDefID = 1;";
		$result = $this->mysqli->query($sql);
		
		while($row = $result->fetch_assoc()){
			$this->rank_definitions[ucfirst($row['Name'])] = $row;
		}
		
	}
	
	// find out if there is an equivalent in specify already
	function load(){
		
		// shortcut if we have already loaded it
		if($this->specify_row != null) return true;

		// see if we have one with 
		$sig = $this->bg_taxon->get_signature();
		$sql = "SELECT * FROM taxon WHERE Text20 = '$sig'";
		
		$result = $this->mysqli->query($sql);
		if($result->num_rows == 0){
			$this->specify_row = null;
			return false;
		}else{
			$this->specify_row = $result->fetch_assoc();
			return true;
		}
		
	}
	
	function save(){
		
		$this->generate_field_map();
		
		if($this->load()){
			$this->update();
		}else{
			$this->create();
		}

	}
	
	
	function update(){
		echo "\nUPDATE TAXON\n";
		print_r($this->mysqli->error);
	}
	
	function create(){
		echo "\nCREATE TAXON\n";
		
		$sql = "INSERT INTO taxon (`" . implode('`,`', $this->specify_fields) . "`) VALUES (";
		
		$done_first = false;
		foreach($this->specify_fields as $field_name){
			
			if($done_first) $sql .= ', ';
		
			// special case for created and modified
			if($field_name == 'TimestampCreated' || $field_name == 'TimestampModified'){
				$sql .= 'NOW()';
				$done_first = true;
				continue;
			}
		
			// if it hasn't been mapped to a value continue
			if(!isset($this->field_map[$field_name])){
				$sql .= 'NULL';
				$done_first = true;
				continue;
			}
			
			
			$val = $this->field_map[$field_name];
			if(is_int($val)){
				$sql .= $val;
			}elseif(is_bool($val)){
				$sql .= $val ? 1 : 0;
			}elseif(is_string($val)){
				$sql .= "'" . $this->mysqli->real_escape_string($val) . "'";
			}
			$done_first = true;
			
		}
		
		
		$sql .= ");";
		
		$result = $this->mysqli->query($sql);
		
		if($this->mysqli->error){
			print_r($this->mysqli->error);
			echo "\n";
			echo $sql;
		}
		
		
		// if we just created it we don't know what its ID is or any other auto gen stuff so reload it
		$this->specify_row = null;
		$this->load();
		
		// FIXME - NEED TO ADD PARENTAGE NEXT!! HOW DO WE BUILD 
		
		// echo $sql;
		
		
	}
	
	function generate_field_map(){
		
		$this->field_map['Text20'] = $this->bg_taxon->get_signature();
		$this->field_map['CultivarName'] = $this->bg_taxon->get_cultivar();
		$this->field_map['FullName'] = $this->bg_taxon->get_full_name();
		// FIXME HighestChildNodeNumber
		$this->field_map['IsHybrid'] = $this->bg_taxon->is_hybrid();
		$this->field_map['Name'] = $this->bg_taxon->get_name();
		
		// I believe the node numbers can be set to 0 and will be calculated by the System > Trees > Update Taxon Tree command.
		$this->field_map['NodeNumber'] = 0;
		$this->field_map['HighestChildNodeNumber'] = 0;
					
		// Rank stuff 
		$rank_def = $this->rank_definitions[$this->bg_taxon->get_rank()];

		$this->field_map['RankID'] = $rank_def['RankID'];
		$this->field_map['TaxonTreeDefItemID'] = $rank_def['TaxonTreeDefItemID'];
		$this->field_map['TaxonTreeDefID'] = $rank_def['TaxonTreeDefID'];
		
		$this->field_map['Source'] = "BG-BASE seed data";
		$this->field_map['ModifiedByAgentID'] = 1;
		$this->field_map['CreatedByAgentID'] = 1;
		
		// AcceptedID
		if($this->bg_taxon->is_accepted()){
			$this->field_map['IsAccepted'] = true;
			$this->field_map['AcceptedID'] = null;
		}else{
			$this->field_map['IsAccepted'] = false;
			$bg_accepted = $this->bg_taxon->get_accepted_taxon();
			$this->accepted_taxon = new SpecifyTaxon($this->mysqli, $bg_accepted);
			$this->accepted_taxon->save(); // makes sure it exists and/or is up to date
			$this->field_map['AcceptedID'] = $this->accepted_taxon->specify_row['TaxonID'];
		}
		
		// ParentID - we always set a parent id! it has to be in the tree.
		$parent = $this->bg_taxon->get_parent_taxon();
		
		if(!$parent){
			// we need to join it to the root of the tree Plantae - see what I did there
			$this->field_map['ParentID'] = $this->get_taxon_root_id();
		}else{
			$this->parent_taxon = new SpecifyTaxon($this->mysqli, $parent);
			$this->parent_taxon->save(); // makes sure it exists and/or is up to date
			$this->field_map['ParentID'] = $this->parent_taxon->specify_row['TaxonID'];
		}
		
		// MUST HAVE A VERSION NUMBER OR HYBERNATE CRASHES!
		if($this->specify_row && $this->specify_row['Version']){
			$this->field_map['Version'] = $this->specify_row['Version']++;
		}else{
			$this->field_map['Version'] = 1;
		}
	
		// N.B. We don't both checking that t
			
		// FIXME HybridParent1ID HybridParent2ID
		
		// FIXME Data source stuff - we should link to sources when they are provided.
		
	}
	
	function get_taxon_root_id(){
		
		$sql = "SELECT TaxonID FROM taxon WHERE name = 'Plantae'";
		$result = $this->mysqli->query($sql);
		if($result->num_rows != 1){
			echo "Everything stops as we can't find the root taxon Plantae TaxonID.";
			exit;
		}else{
			$row = $result->fetch_assoc();
			return $row['TaxonID'];
		}

	}
	
	
}

?>
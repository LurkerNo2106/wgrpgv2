<?php

require_once "Database.php";
require_once "RPGNPCStats.php";
require_once "RPGSkill.php";
include_once "constants.php";
include_once "common.php";

class RPGNPC{

	private $_intNPCID;
	private $_strNPCName;
	private $_intCurrentHP;
	private $_objStats;
	private $_intWeight;
	private $_intHeight;
	private $_intExperienceGiven;
	private $_intGoldDropMin;
	private $_intGoldDropMax;
	private $_strStartText;
	private $_strForceStartText;
	private $_strEndText;
	private $_strFleeText;
	private $_strFailFleeText;
	private $_strDefeatText;
	private $_blnHasStartEvent;
	private $_blnHasEndEvent;
	private $_strAIName;
	private $_arrSkillList;
	private $_arrActiveSkillList;
	private $_objEquippedWeapon;
	private $_objEquippedArmour;
	private $_objEquippedTop;
	private $_objEquippedBottom;
	private $_objEquippedSecondary;
	private $_dtmCreatedOn;
	private $_strCreatedBy;
	private $_dtmModifiedOn;
	private $_strModifiedBy;
	
	public function RPGNPC($intNPCID = null){
		if($intNPCID){
			$this->loadNPCInfo($intNPCID);
		}
	}
	
	private function populateVarFromRow($arrNPCInfo){
		$this->setNPCID($arrNPCInfo['intNPCID']);
		$this->setNPCName($arrNPCInfo['strNPCName']);
		$this->setWeight($arrNPCInfo['intWeight']);
		$this->setHeight($arrNPCInfo['intHeight']);
		$this->setExperienceGiven($arrNPCInfo['intExperienceGiven']);
		$this->setGoldDropMin($arrNPCInfo['intGoldDropMin']);
		$this->setGoldDropMax($arrNPCInfo['intGoldDropMax']);
		$this->setStartText($arrNPCInfo['strStartText']);
		$this->setForceStartText($arrNPCInfo['strForceStartText']);
		$this->setEndText($arrNPCInfo['strEndText']);
		$this->setFleeText($arrNPCInfo['strFleeText']);
		$this->setFailFleeText($arrNPCInfo['strFailFleeText']);
		$this->setDefeatText($arrNPCInfo['strDefeatText']);
		$this->setHasStartEvent($arrNPCInfo['blnHasStartEvent']);
		$this->setHasEndEvent($arrNPCInfo['blnHasEndEvent']);
		$this->setAIName($arrNPCInfo['strAIName']);
		$this->setCreatedOn($arrNPCInfo['dtmCreatedOn']);
		$this->setCreatedBy($arrNPCInfo['strCreatedBy']);
		$this->setModifiedOn($arrNPCInfo['dtmModifiedOn']);
		$this->setModifiedBy($arrNPCInfo['strModifiedBy']);
	}
	
	private function loadNPCInfo($intNPCID){
		$objDB = new Database();
		$arrNPCInfo = array();
			$strSQL = "SELECT *
						FROM tblnpc
							LEFT JOIN tblnpcbattletext
								USING (intNPCID)
							WHERE intNPCID = " . $objDB->quote($intNPCID);
			$rsResult = $objDB->query($strSQL);
			while ($arrRow = $rsResult->fetch(PDO::FETCH_ASSOC)){
				$arrNPCInfo['intNPCID'] = $arrRow['intNPCID'];
				$arrNPCInfo['strNPCName'] = $arrRow['strNPCName'];
				$arrNPCInfo['intWeight'] = $arrRow['intWeight'];
				$arrNPCInfo['intHeight'] = $arrRow['intHeight'];
				$arrNPCInfo['intExperienceGiven'] = $arrRow['intExperienceGiven'];
				$arrNPCInfo['intGoldDropMin'] = $arrRow['intGoldDropMin'];
				$arrNPCInfo['intGoldDropMax'] = $arrRow['intGoldDropMax'];
				$arrNPCInfo['strStartText'] = $arrRow['strStartText'];
				$arrNPCInfo['strForceStartText'] = $arrRow['strForceStartText'];
				$arrNPCInfo['strEndText'] = $arrRow['strEndText'];
				$arrNPCInfo['strFleeText'] = $arrRow['strFleeText'];
				$arrNPCInfo['strFailFleeText'] = $arrRow['strFailFleeText'];
				$arrNPCInfo['strDefeatText'] = $arrRow['strDefeatText'];
				$arrNPCInfo['blnHasStartEvent'] = $arrRow['blnHasStartEvent'];
				$arrNPCInfo['blnHasEndEvent'] = $arrRow['blnHasEndEvent'];
				$arrNPCInfo['strAIName'] = $arrRow['strAIName'];
				$arrNPCInfo['dtmCreatedOn'] = $arrRow['dtmCreatedOn'];
				$arrNPCInfo['strCreatedBy'] = $arrRow['strCreatedBy'];
				$arrNPCInfo['dtmModifiedOn'] = $arrRow['dtmModifiedOn'];
				$arrNPCInfo['strModifiedBy'] = $arrRow['strModifiedBy'];
			}
		$this->populateVarFromRow($arrNPCInfo);
		$this->loadSkills();
		$this->_objEquippedArmour = $this->loadEquippedArmour();
		$this->_objEquippedTop = $this->loadEquippedTop();
		$this->_objEquippedBottom = $this->loadEquippedBottom();
		$this->_objEquippedWeapon = $this->loadEquippedWeapon();
		$this->_objEquippedSecondary = $this->loadEquippedSecondary();
		$this->_objStats = new RPGNPCStats($intNPCID);
		$this->_objStats->loadBaseStats();
		$this->setCurrentHP($this->getModifiedMaxHP());
	}
	
	public function loadSkills(){
		$objDB = new Database();
		$this->_arrSkillList = array();
		$strSQL = "SELECT intSkillID, strSkillType
					FROM tblnpcskillxr
						INNER JOIN tblskill
							USING (intSkillID)
						WHERE intNPCID = " . $objDB->quote($this->getNPCID());
		$rsResult = $objDB->query($strSQL);
		while($arrRow = $rsResult->fetch(PDO::FETCH_ASSOC)){
			$this->_arrSkillList[$arrRow['strSkillType']][] = new RPGSkill($arrRow['intSkillID']);
		}
	}
	
	public function loadActiveSkillList(){
		$this->_arrActiveSkillList = $this->_arrSkillList;
		foreach($this->_arrActiveSkillList as $strSkillType => $arrSkillList){
			foreach($arrSkillList as $key => $objSkill){
				$this->_arrActiveSkillList[$strSkillType][$key]->setCurrentCooldown(0);
			}
		}
	}
	
	public function takeDamage($intDamage){
		$intDamage = max(0, $intDamage);
		$this->setCurrentHP($this->getCurrentHP() - $intDamage);
		return $intDamage;
	}
	
	public function isDead(){
		return intval($this->getCurrentHP()) <= 0 ? 1 : 0;
	}
	
	public function getCurrentHP(){
		return $this->_intCurrentHP;
	}
	
	public function setCurrentHP($intCurrentHP){
		$this->_intCurrentHP = $intCurrentHP;
	}
	
	public function loadEquippedArmour(){
		$objDB = new Database();
		$strSQL = "SELECT intItemID
					FROM tblitem
						INNER JOIN tblnpcitemxr
							USING (intItemID)
					WHERE strItemType LIKE 'Armour:%'
						AND intNPCID = " . $objDB->quote($this->getNPCID()) . "
						AND blnEquipped = 1";
		$rsResult = $objDB->query($strSQL);
		if($rsResult->rowCount() > 0){
			$arrRow = $rsResult->fetch(PDO::FETCH_ASSOC);
			$objArmour = new RPGItem($arrRow['intItemID']);
			return $objArmour;
		}
		else{
			$objArmour = new RPGItem();
			$objArmour->setWaitTime(0);
			return $objArmour;
		}
	}
	
	public function loadEquippedTop(){
		$objDB = new Database();
		$strSQL = "SELECT intItemID
					FROM tblitem
						INNER JOIN tblnpcitemxr
							USING (intItemID)
					WHERE strItemType LIKE 'Armour:Top'
						AND intNPCID = " . $objDB->quote($this->getNPCID()) . "
						AND blnEquipped = 1";
		$rsResult = $objDB->query($strSQL);
		if($rsResult->rowCount() > 0){
			$arrRow = $rsResult->fetch(PDO::FETCH_ASSOC);
			$objArmour = new RPGItem($arrRow['intItemID']);
			return $objArmour;
		}
		else{
			$objArmour = new RPGItem();
			$objArmour->setWaitTime(0);
			return $objArmour;
		}
	}
	
	public function loadEquippedBottom(){
		$objDB = new Database();
		$strSQL = "SELECT intItemID
					FROM tblitem
						INNER JOIN tblnpcitemxr
							USING (intItemID)
					WHERE strItemType LIKE 'Armour:Bottom'
						AND intNPCID = " . $objDB->quote($this->getNPCID()) . "
						AND blnEquipped = 1";
		$rsResult = $objDB->query($strSQL);
		if($rsResult->rowCount() > 0){
			$arrRow = $rsResult->fetch(PDO::FETCH_ASSOC);
			$objArmour = new RPGItem($arrRow['intItemID']);
			return $objArmour;
		}
		else{
			$objArmour = new RPGItem();
			$objArmour->setWaitTime(0);
			return $objArmour;
		}
	}
	
	public function loadEquippedWeapon(){
		$objDB = new Database();
		$strSQL = "SELECT intItemID
					FROM tblitem
						INNER JOIN tblnpcitemxr
							USING (intItemID)
					WHERE strItemType LIKE 'Weapon:%'
						AND (strHandType = 'Primary' OR strHandType = 'Both')
						AND intNPCID = " . $objDB->quote($this->getNPCID()) . "
						AND blnEquipped = 1";
		$rsResult = $objDB->query($strSQL);
		if($rsResult->rowCount() > 0){
			$arrRow = $rsResult->fetch(PDO::FETCH_ASSOC);
			$objWeapon = new RPGItem($arrRow['intItemID']);
			return $objWeapon;
		}
		else{
			$objWeapon = new RPGItem();
			$objWeapon->setWaitTime(0);
			return $objWeapon;
		}
	}
	
	public function loadEquippedSecondary(){
		$objDB = new Database();
		$strSQL = "SELECT intItemID
					FROM tblitem
						INNER JOIN tblnpcitemxr
							USING (intItemID)
					WHERE strItemType LIKE 'Weapon:%'
						AND strHandType = 'Secondary'
						AND intNPCID = " . $objDB->quote($this->getNPCID()) . "
						AND blnEquipped = 1";
		$rsResult = $objDB->query($strSQL);
		if($rsResult->rowCount() > 0){
			$arrRow = $rsResult->fetch(PDO::FETCH_ASSOC);
			$objSecondary = new RPGItem($arrRow['intItemID']);
			return $objSecondary;
		}
		else{
			$objSecondary = new RPGItem();
			$objSecondary->setWaitTime(0);
			return $objSecondary;
		}
	}
	
	public function getRandomDrops(){
		$objDB = new Database();
		$arrDrops = array();
		$strSQL = "SELECT intItemID, strItemName, strItemType, intDropRating
					FROM tblnpcitemxr
						INNER JOIN tblitem
						USING(intItemID)
					WHERE blnDropped = 1
						AND intNPCID = " . $objDB->quote($this->getNPCID());
		$rsResult = $objDB->query($strSQL);
		while($arrRow = $rsResult->fetch(PDO::FETCH_ASSOC)){
			$intRand = mt_rand(0, 10000);
			if($intRand <= $arrRow['intDropRating']){
				$arrDrops[$arrRow['intItemID']] = $arrRow['strItemName'];
			}
		}
		return $arrDrops;
	}
	
	public function getEnemyBMI(){
		return ($this->getWeight() / dblLBS_PER_KG) / pow($this->getHeight() / 100, 2);
	}
	
	public function EnemyGainWeight($intAmount){
		$this->setWeight($this->getWeight() + $intAmount);
	}
	
	public function getModifiedMaxHP(){
		return round($this->_objStats->getCombinedStats('intMaxHP') + ($this->_objStats->getCombinedStats('intVitality') / 2));
	}
	
	public function getModifiedDamage(){
		return round(($this->_objStats->getCombinedStats('intStrength') / 2) + $this->getEquippedWeapon()->getDamage());
	}
	
	public function getModifiedMagicDamage(){
		return round(($this->_objStats->getCombinedStats('intIntelligence') / 2) + $this->getEquippedWeapon()->getMagicDamage());
	}
	
	public function getModifiedDefence(){
		return round(($this->_objStats->getCombinedStats('intVitality') / 4) + $this->getEquippedArmour()->getDefence() + $this->getEquippedSecondary()->getDefence());
	}
	
	public function getModifiedMagicDefence(){
		return round(($this->_objStats->getCombinedStats('intIntelligence') / 4) + $this->getEquippedArmour()->getMagicDefence());
	}
	
	public function getModifiedBlockRate(){
		return round($this->_objStats->getCombinedStatsSecondary('intBlockRate'));
	}
	
	public function getModifiedBlock(){
		return min((0.5 + ($this->_objStats->getCombinedStatsSecondary('intBlockReduction') / 100)), 1.0);
	}
	
	public function getStatusEffectResistance(){
		return round($this->_objStats->getCombinedStats('intWillpower') * 2);
	}
	
	public function getStatusEffectSuccessRate(){
		return round($this->_objStats->getCombinedStats('intWillpower') * 1);
	}
	
	public function getModifiedCritRate(){
		return round($this->_objStats->getCombinedStats('intDexterity') * 2);
	}
	
	public function getAdditionalDamage(){
		return round($this->_objStats->getCombinedStats('intWillpower') / 4);
	}
	
	public function getModifiedCritDamage(){
		return 1.5;
	}
	
	public function getModifiedCritResistance(){
		return round($this->_objStats->getCombinedStats('intDexterity') * 1);
	}
	
	public function getModifiedFleeRate(){
		return round($this->_objStats->getCombinedStats('intAgility') / 4);
	}
	
	public function getModifiedFleeResistance(){
		return round($this->_objStats->getCombinedStatsSecondary('intFleeResistance'));
	}
	
	public function getModifiedEvasion(){
		return round(($this->_objStats->getCombinedStats('intAgility') * 2) + $this->_objStats->getCombinedStatsSecondary('intEvasion'));
	}
	
	public function getModifiedPierceRate(){
		return round($this->_objStats->getCombinedStatsSecondary('intPierce'));
	}
	
	public function getModifiedAccuracy(){
		return round(($this->_objStats->getCombinedStats('intDexterity') * 2) + $this->_objStats->getCombinedStatsSecondary('intAccuracy'));
	}
	
	public function getWaitTime($udfWaitType){
		$intGearWait = $this->_objEquippedWeapon->getWaitTime() + $this->_objEquippedSecondary->getWaitTime() + $this->_objEquippedArmour->getWaitTime() + $this->_objEquippedTop->getWaitTime() + $this->_objEquippedBottom->getWaitTime();
		if($udfWaitType == 'Standard'){
			// standard attack
			return round(250 + $intGearWait - ($this->_objStats->getCombinedStats('intAgility') / 2) + (250 * $this->getImmobilityFactor()));
		}
		else{
			// skills will add on or decrease wait time by some amount defined by udfWaitType variable
			return round(250 + $udfWaitType + $intGearWait - ($this->_objStats->getCombinedStats('intAgility') / 2) + (250 * $this->getImmobilityFactor()));
		}
	}
	
	public function getStats(){
		return $this->_objStats;
	}
	
	public function getNPCID(){
		return $this->_intNPCID;
	}
	
	public function setNPCID($intNPCID){
		$this->_intNPCID = $intNPCID;
	}
	
	public function getNPCName(){
		return $this->_strNPCName;
	}
	
	public function setNPCName($strNPCName){
		$this->_strNPCName = $strNPCName;
	}
	
	public function getWeight(){
		return $this->_intWeight;
	}
	
	public function setWeight($intWeight){
		$this->_intWeight = $intWeight;
	}
	
	public function getEquippedArmour(){
		return $this->_objEquippedArmour;
	}
	
	public function setEquippedArmour($objArmour){
		$this->_objEquippedArmour = $objArmour;
	}
	
	public function getEquippedWeapon(){
		return $this->_objEquippedWeapon;
	}
	
	public function setEquippedWeapon($objWeapon){
		$this->_objEquippedWeapon = $objWeapon;
	}
	
	public function getEquippedSecondary(){
		return $this->_objEquippedSecondary;
	}
	
	public function setEquippedSecondary($objSecondary){
		$this->_objEquippedSecondary = $objSecondary;
	}
	
	public function getHeight(){
		return $this->_intHeight;
	}
	
	public function setHeight($intHeight){
		$this->_intHeight = $intHeight;
	}
	
	public function getExperienceGiven(){
		return $this->_intExperienceGiven;
	}
	
	public function setExperienceGiven($intExperienceGiven){
		$this->_intExperienceGiven = $intExperienceGiven;
	}
	
	public function getGoldDropMin(){
		return $this->_intGoldDropMin;
	}
	
	public function setGoldDropMin($intGoldDropMin){
		$this->_intGoldDropMin = $intGoldDropMin;
	}
	
	public function getGoldDropMax(){
		return $this->_intGoldDropMax;
	}
	
	public function setGoldDropMax($intGoldDropMax){
		$this->_intGoldDropMax = $intGoldDropMax;
	}
	
	public function getStartText(){
		return $this->_strStartText;
	}
	
	public function setStartText($strStartText){
		$this->_strStartText = $strStartText;
	}
	
	public function getForceStartText(){
		return $this->_strForceStartText;
	}
	
	public function setForceStartText($strForceStartText){
		$this->_strForceStartText = $strForceStartText;
	}
	
	public function getEndText(){
		return $this->_strEndText;
	}
	
	public function setEndText($strEndText){
		$this->_strEndText = $strEndText;
	}
	
	public function getFleeText(){
		return $this->_strFleeText;
	}
	
	public function setFleeText($strFleeText){
		$this->_strFleeText = $strFleeText;
	}
	
	public function getFailFleeText(){
		return $this->_strFailFleeText;
	}
	
	public function setFailFleeText($strFailFleeText){
		$this->_strFailFleeText = $strFailFleeText;
	}
	
	public function getDefeatText(){
		return $this->_strDefeatText;
	}
	
	public function setDefeatText($strDefeatText){
		$this->_strDefeatText = $strDefeatText;
	}
	
	public function getHasStartEvent(){
		return $this->_blnHasStartEvent;
	}
	
	public function setHasStartEvent($blnHasStartEvent){
		$this->_blnHasStartEvent = $blnHasStartEvent;
	}
	
	public function getHasEndEvent(){
		return $this->_blnHasEndEvent;
	}
	
	public function setHasEndEvent($blnHasEndEvent){
		$this->_blnHasEndEvent = $blnHasEndEvent;
	}
	
	public function getAIName(){
		return $this->_strAIName;
	}
	
	public function setAIName($strAIName){
		$this->_strAIName = $strAIName;
	}
	
	public function getCreatedOn(){
		return $this->_dtmCreatedOn;
	}
	
	public function setCreatedOn($dtmCreatedOn){
		$this->_dtmCreatedOn = $dtmCreatedOn;
	}
	
	public function getCreatedBy(){
		return $this->_strCreatedBy;
	}
	
	public function setCreatedBy($strCreatedBy){
		$this->_strCreatedBy = $strCreatedBy;
	}
	
	public function getModifiedOn(){
		return $this->_dtmModifiedOn;
	}
	
	public function setModifiedOn($dtmModifiedOn){
		$this->_dtmModifiedOn = $dtmModifiedOn;
	}
	
	public function getModifiedBy(){
		return $this->_strModifiedBy;
	}
	
	public function setModifiedBy($strModifiedBy){
		$this->_strModifiedBy = $strModifiedBy;
	}
	
	public function getImmobilityFactor(){
		return max(0, ((($this->getBMI() / 40) / 10) - (($this->_objStats->getCombinedStats('intStrength') / 4) / 100)));
	}
	
	public function getBMI(){
		return ($this->getWeight() / dblLBS_PER_KG) / pow($this->getHeight() / 100, 2);
	}
	
	public function getHeightInFeet(){
		$dblFeet = $this->getHeight() / dblCM_PER_FOOT;
		$whole = floor($dblFeet);
		$fraction = $dblFeet - $whole;
		$intInches = round($fraction * intFEET_PER_INCH);
		if($intInches == 12){
			$whole++;
			$intInches = 0;
		}
		return strval($whole) . "'" . strval($intInches) . "\"";
	}
	
	public function getSkillList($strSkillType){
		return $this->_arrSkillList[$strSkillType];
	}
	
	public function getActiveSkillList($strSkillType){
		return $this->_arrActiveSkillList[$strSkillType];
	}
}

?>
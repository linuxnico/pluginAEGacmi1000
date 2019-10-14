<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class AEG_acmi1000 extends eqLogic {
    /*     * *************************Attributs****************************** */

    //tableau des oids snmp des alimentaions aeg acmi1000
    const oid = array('oidAegAlarme'=> '1.3.6.1.4.1.15416.381.8.1.1.3.',
           'oidAegAlarmeNom'=> '1.3.6.1.4.1.15416.381.8.1.1.2.',
           'oidAegDefaultAC'=> '1.3.6.1.4.1.15416.381.8.1.1.3.5',
           'oidAegEnAlarme'=> '1.3.6.1.4.1.15416.381.7.7.0',
           'oidAegPuissance'=> '1.3.6.1.4.1.15416.381.3.3.0',
           'oidAegCourantCharge'=> '1.3.6.1.4.1.15416.381.3.2.0',
           'oidAegTensionCharge'=> '1.3.6.1.4.1.15416.381.3.1.0',
           'oidAegCourantBatterie'=> '1.3.6.1.4.1.15416.381.1.3.0',
           'oidAegTensionBatterie'=> '1.3.6.1.4.1.15416.381.1.2.0',
           'oidAegCourantSource'=> '1.3.6.1.4.1.15416.381.2.2.0',
           'oidAegTensionSource'=> '1.3.6.1.4.1.15416.381.2.1.0'
         );


    /*     * ***********************Methode static*************************** */
    //gestion des dependances
    public static function dependancy_info() {
       $return = array();
       $return['progress_file'] = '/tmp/AEG_acmi1000_dep';
       $return['log'] = 'AEG_acmi1000_dep';
       $test = exec("sudo dpkg-query -l 'php*-snmp*' | grep php", $ping, $retour);
       if(count($ping)>0)
       {
         $return['state'] = 'ok';
       } else {
         $return['state'] = 'nok';
       }
       return $return;
     }
    //install des dependances
    public function dependancy_install() {
      log::add('AEG_acmi1000','info','Installation des dÃ©pÃ©ndances php-snmp');
      passthru('sudo apt install php-snmp -y >> ' . log::getPathToLog('AEG_acmi1000_dep') . ' 2>&1 &');
    }
    // creation de staches cron suivant config de l'equipement
    public static function cron() {
  		$dateRun = new DateTime();
      // log::add('AEG_acmi1000', 'debug', "on passe par le cron");
  		foreach (eqLogic::byType('AEG_acmi1000') as $eqLogic) {
  			$autorefresh = $eqLogic->getConfiguration('autorefresh');
  			if ($eqLogic->getIsEnable() == 1 && $autorefresh != '') {
  				try {
  					$c = new Cron\CronExpression($autorefresh, new Cron\FieldFactory);
  					if ($c->isDue($dateRun)) {
              $cmd = $eqLogic->getCmd(null, 'refresh');//retourne la commande "refresh si elle existe
    				  if (!is_object($cmd)) {//Si la commande n'existe pas
                // log::add('AEG_acmi1000', 'debug', "pas de commande refresh ". $eqLogic->getHumanName());
    				  	continue; //continue la boucle
    				  }
              // log::add('AEG_acmi1000', 'debug', "on passe par le cron ET on refresh ". $eqLogic->getHumanName());
    				  $cmd->execCmd(); // la commande existe on la lance
  					}
  				} catch (Exception $exc) {
  					log::add('AEG_acmi1000', 'error', __('Expression cron non valide pour ', __FILE__) . $eqLogic->getHumanName() . ' : ' . $autorefresh);
  				}
  			}
  		}
  	}


    /*     * *********************Méthodes d'instance************************* */
    //fonction de recuperation de l'etat d'une des 76 alarmes
    public function alarme($i) {
      // log::add('AEG_acmi1000','debug', "on recup l'alarme ".$i);
      $ip = $this->getConfiguration("ip");
      $val = snmpget($ip, "public", self::oid['oidAegAlarme'].$i);
      $val = substr($val, strpos($val, ':')+1);
      // log::add('AEG_acmi1000','debug', "resultat: ".$val);
      if ($val==0) //on inverse
      {return 1;}
      return 0;
    }
    //fonction de recuperation de la puissance consommee
    public function puissance() {
      $ip = $this->getConfiguration("ip");
      $val = snmpget($ip, "public", self::oid['oidAegPuissance']);
      $val = substr($val, strpos($val, ':')+2);
      if ($val=="") { $val = "0";}
      return $val;
    }
    //fonction de recuperation de la tension source
    public function tensionSource() {
      $ip = $this->getConfiguration("ip");
      $val = snmpget($ip, "public", self::oid['oidAegTensionSource']);
      $val = substr($val, strpos($val, ':')+1);
      if ($val=="") { $val = "0";}
      return $val/100;
    }
    //fonction de recuperation de la tension de la charge
    public function tensionCharge() {
      $ip = $this->getConfiguration("ip");
      $val = snmpget($ip, "public", self::oid['oidAegTensionCharge']);
      $val = substr($val, strpos($val, ':')+1);
      if ($val=="") { $val = "0";}
      return $val/100;
    }
    //fonction de recuperation de la tension Batterie
    public function tensionBatterie() {
      $ip = $this->getConfiguration("ip");
      $val = snmpget($ip, "public", self::oid['oidAegTensionBatterie']);
      $val = substr($val, strpos($val, ':')+1);
      if ($val=="") { $val = "0";}
      return $val/100;
    }
    //fonction de recuperation du courant source 48v
    public function courantSource() {
      $ip = $this->getConfiguration("ip");
      $val = snmpget($ip, "public", self::oid['oidAegCourantSource']);
      $val = substr($val, strpos($val, ':')+1);
      if ($val=="") { $val = "0";}
      return $val/100;
    }
    //fonction de recuperation du courant de la charge
    public function courantCharge() {
      $ip = $this->getConfiguration("ip");
      $val = snmpget($ip, "public", self::oid['oidAegCourantCharge']);
      $val = substr($val, strpos($val, ':')+1);
      if ($val=="") { $val = "0";}
      return $val/100;
    }
    //fonction de recuperation du courant batterie
    public function courantBatterie() {
      $ip = $this->getConfiguration("ip");
      $val = snmpget($ip, "public", self::oid['oidAegCourantBatterie']);
      $val = substr($val, strpos($val, ':')+1);
      if ($val=="") { $val = "0";}
      return $val/100;
    }
    //fonction de recuperation de la presence de la tension source(220v)
    public function tensionSourcePrimaire() {
      $ip = $this->getConfiguration("ip");
      $val = snmpget($ip, "public", self::oid['oidAegDefaultAC']);
      $val = substr($val, strpos($val, ':')+1);
      if ($val=="") { $val = "0";}
      if ($val==1) //on inverse
      {return 0;}
      return 1;
    }
    //fonction de vrification de la presence de l'equipement sur le reseau
    public function ping() {
      $ip = $this->getConfiguration("ip");
      $ping = exec("ping -c 1 ".$ip, $ping, $return);
      if($return=='1')
      {
         return 0;
      }
      else
      {
         return 1;
      }
    }

    public function preInsert() {

    }

    public function postInsert() {

    }
    // renseigne l'autorefresh si vide
    public function preSave() {
      if ($this->getConfiguration('autorefresh') == '') {
			     $this->setConfiguration('autorefresh', '*/30 * * * *');
		  }
    }

    public function postSave() {
  // creation commande refresh
      $refresh = $this->getCmd(null, 'refresh');
  		if (!is_object($refresh)) {
  			$refresh = new AEG_acmi1000Cmd();
  			$refresh->setName(__('Rafraichir', __FILE__));
  		}
  		$refresh->setEqLogic_id($this->getId());
  		$refresh->setLogicalId('refresh');
  		$refresh->setType('action');
  		$refresh->setSubType('other');
      $refresh->setOrder(1);
      $refresh->setIsHistorized(1);
  		$refresh->save();

// creation commande puissance consommee
      $puissance = $this->getCmd(null, 'power');
  		if (!is_object($puissance)) {
  			$puissance = new AEG_acmi1000Cmd();
  			$puissance->setName(__('Puissance Sortie', __FILE__));
  		}
  		$puissance->setLogicalId('power');
  		$puissance->setEqLogic_id($this->getId());
  		$puissance->setType('info');
  		$puissance->setSubType('numeric');
      $puissance->setUnite('W');
      $puissance->setOrder(2);
      $puissance->setIsHistorized(1);
      $puissance->setConfiguration("minValue", 1);
      $puissance->setConfiguration("maxValue", 5000);
  		$puissance->save();

// creation commande tension Source
      $tensionSource = $this->getCmd(null, 'tensionSource');
  		if (!is_object($tensionSource)) {
  			$tensionSource = new AEG_acmi1000Cmd();
  			$tensionSource->setName(__('Tension Source 48V', __FILE__));
  		}
  		$tensionSource->setLogicalId('tensionSource');
  		$tensionSource->setEqLogic_id($this->getId());
  		$tensionSource->setType('info');
  		$tensionSource->setSubType('numeric');
      $tensionSource->setUnite('V');
      $tensionSource->setOrder(3);
      $tensionSource->setIsHistorized(1);
      $tensionSource->setConfiguration("minValue", 0);
      $tensionSource->setConfiguration("maxValue", 108);
  		$tensionSource->save();

// creation commande courant Source
      $courantSource = $this->getCmd(null, 'courantSource');
  		if (!is_object($courantSource)) {
  			$courantSource = new AEG_acmi1000Cmd();
  			$courantSource->setName(__('Courant Source 48V', __FILE__));
  		}
  		$courantSource->setLogicalId('courantSource');
  		$courantSource->setEqLogic_id($this->getId());
  		$courantSource->setType('info');
  		$courantSource->setSubType('numeric');
      $courantSource->setUnite('A');
      $courantSource->setOrder(4);
      $courantSource->setIsHistorized(1);
      $courantSource->setConfiguration("minValue", 0);
      $courantSource->setConfiguration("maxValue", 60);
  		$courantSource->save();

// creation commande tension equipement
      $tensionCharge = $this->getCmd(null, 'tensionCharge');
  		if (!is_object($tensionCharge)) {
  			$tensionCharge = new AEG_acmi1000Cmd();
  			$tensionCharge->setName(__('Tension Charge 48V', __FILE__));
  		}
  		$tensionCharge->setLogicalId('tensionCharge');
  		$tensionCharge->setEqLogic_id($this->getId());
  		$tensionCharge->setType('info');
  		$tensionCharge->setSubType('numeric');
      $tensionCharge->setUnite('V');
      $tensionCharge->setOrder(5);
      $tensionCharge->setIsHistorized(1);
      $tensionCharge->setConfiguration("minValue", 0);
      $tensionCharge->setConfiguration("maxValue", 108);
  		$tensionCharge->save();

// creation commande courant equipement
      $courantCharge = $this->getCmd(null, 'courantCharge');
  		if (!is_object($courantCharge)) {
  			$courantCharge = new AEG_acmi1000Cmd();
  			$courantCharge->setName(__('Courant Charge 48V', __FILE__));
  		}
  		$courantCharge->setLogicalId('courantCharge');
  		$courantCharge->setEqLogic_id($this->getId());
  		$courantCharge->setType('info');
  		$courantCharge->setSubType('numeric');
      $courantCharge->setUnite('A');
      $courantCharge->setOrder(6);
      $courantCharge->setIsHistorized(1);
      $courantCharge->setConfiguration("minValue", 0);
      $courantCharge->setConfiguration("maxValue", 60);
  		$courantCharge->save();

// creation commande tension batterie
      $tensionBatterie = $this->getCmd(null, 'tensionBatterie');
  		if (!is_object($tensionBatterie)) {
  			$tensionBatterie = new AEG_acmi1000Cmd();
  			$tensionBatterie->setName(__('Tension Batterie 48V', __FILE__));
  		}
  		$tensionBatterie->setLogicalId('tensionBatterie');
  		$tensionBatterie->setEqLogic_id($this->getId());
  		$tensionBatterie->setType('info');
  		$tensionBatterie->setSubType('numeric');
      $tensionBatterie->setUnite('V');
      $tensionBatterie->setOrder(7);
      $tensionBatterie->setIsHistorized(1);
      $tensionBatterie->setConfiguration("minValue", 0);
      $tensionBatterie->setConfiguration("maxValue", 108);
  		$tensionBatterie->save();
// creation commande courant batterie
      $courantBatterie = $this->getCmd(null, 'courantBatterie');
  		if (!is_object($courantBatterie)) {
  			$courantBatterie = new AEG_acmi1000Cmd();
  			$courantBatterie->setName(__('Courant Batterie 48V', __FILE__));
  		}
  		$courantBatterie->setLogicalId('courantBatterie');
  		$courantBatterie->setEqLogic_id($this->getId());
  		$courantBatterie->setType('info');
  		$courantBatterie->setSubType('numeric');
      $courantBatterie->setUnite('A');
      $courantBatterie->setOrder(8);
      $courantBatterie->setIsHistorized(1);
      $courantBatterie->setConfiguration("minValue", 0);
      $courantBatterie->setConfiguration("maxValue", 60);
      $courantBatterie->setAlert('dangerif', '#value#>1');
  		$courantBatterie->save();

// creation presence tension source primaire
      $tensionSourcePrimaire = $this->getCmd(null, 'tensionSourcePrimaire');
  		if (!is_object($tensionSourcePrimaire)) {
  			$tensionSourcePrimaire = new AEG_acmi1000Cmd();
  			$tensionSourcePrimaire->setName(__('Presence Primaire', __FILE__));
  		}
  		$tensionSourcePrimaire->setLogicalId('tensionSourcePrimaire');
  		$tensionSourcePrimaire->setEqLogic_id($this->getId());
  		$tensionSourcePrimaire->setType('info');
  		$tensionSourcePrimaire->setSubType('binary');
      $tensionSourcePrimaire->setOrder(9);
      $tensionSourcePrimaire->setIsHistorized(1);
      $tensionSourcePrimaire->setAlert('dangerif', '#value#=0');
  		$tensionSourcePrimaire->save();

// creation commande presence reseau
      $presence = $this->getCmd(null, 'presence');
  		if (!is_object($presence)) {
  			$presence = new AEG_acmi1000Cmd();
  			$presence->setName(__('Presence IP', __FILE__));
  		}
  		$presence->setLogicalId('presence');
  		$presence->setEqLogic_id($this->getId());
  		$presence->setType('info');
  		$presence->setSubType('binary');
      $presence->setOrder(10);
      $presence->setAlert('dangerif', '#value#=0');
      $presence->setIsHistorized(1);
  		$presence->save();

// on creer les alarmes si besoin
      $this->creationAlarmes();

    }

    // fonction de creation de toutes les commandes alarmes, de 1 a 76
    public function creationAlarmes() {
      $alarmesValidees = $this->getConfiguration("alarmes");
      // on ne veut plus l'etat des alarmes donc on verifie si elle existe deja
      // et le cas echeants, on les supprimes
      if ($alarmesValidees==0) {
        for ($i=1; $i<=76; $i++) {
            $alarme = $this->getCmd(null, 'alarme'.$i);
            if (is_object($alarme)) {
              $alarme->remove();
            }
        }
        return;
      }
      // sinon on cree toutes les alarmes possibles.
      // tableau des intitules des 76 alarmes
      $nomAlarme = array( '',
                          'CFG_ERROR',
                          'CALIB_ERROR',
                          'INTERP_ERR',
                          'BACKEEP_ERR',
                          'DEFAUT_AC',
                          'DF AC LONG',
                          'UDC HAUT',
                          'UDC BAS',
                          'RED DEF<=N',
                          'RED DEF>N',
                          'DEF. CAN',
                          'DC SPR1',
                          'BAT PROT 1',
                          'BAT PROT 2',
                          'BAT PROT 3',
                          'BAT PROT 4',
                          'BATT CFD',
                          'AUX. CFD',
                          'VBATT BAS',
                          'CAPT VBATT',
                          'CAPT IBATT',
                          'CAPT. T.',
                          'CAPT. T2',
                          'B. REGUL',
                          'BOOST',
                          'TEST DECH.',
                          'TEST CONN.',
                          'AhNplein',
                          'AhBas',
                          'DECH. BATT',
                          'B.SURTEMP.',
                          'B.TEMP BAS',
                          'C.VIE BATT',
                          'B. SPR1',
                          'DELESTAGE',
                          'DELEST. 2',
                          'AUX.C.DEL1',
                          'AUX.C.DEL2',
                          'PROT. UTIL',
                          'PROT. NP_1',
                          'PROT. NP_2',
                          'SURCHARGE',
                          'CAPT VUTIL',
                          'CAPT IUTIL',
                          'Ld. SPR1',
                          'MODEM FAIL',
                          'DJS (-ID1)',
                          'DJSS',
                          'DEFAUT AS',
                          'DEF 1 CVR',
                          'DEF >1 CVR',
                          'ALIM AUX.',
                          'Umin (RBV)',
                          'RESERVE 1',
                          'RESERVE 2',
                          'DJD BATT.',
                          'MAINTEN.',
                          'FUS. OND',
                          'SURCH. CS',
                          'DEPART AC',
                          'DEF. CPI',
                          'OND - URG',
                          'OND - NURG',
                          'DEF 1 OND',
                          'DEF >1 OND',
                          'ONDUL. 1',
                          'ONDUL. 2',
                          'ONDUL. 3',
                          'ONDUL. 4',
                          'ONDUL. 5',
                          'ONDUL. 6',
                          'ONDUL. 7',
                          'ONDUL. 8',
                          'B.N PLEINE',
                          'CAPA.BASSE',
                          'TBD'
                          );
      $alarme = array();
      $ip = $this->getConfiguration("ip");
      for ($i=1; $i<=76; $i++) {
          $nom = 'Alarme - '.$nomAlarme[$i];  // on creer le nom de chaque alarme avec son intitule
          $alarme[$i] = $this->getCmd(null, 'alarme'.$i);
          if (!is_object($alarme[$i])) {
            $alarme[$i] = new AEG_acmi1000Cmd();
            $alarme[$i]->setName($nom, __FILE__);
          }
          $alarme[$i]->setLogicalId('alarme'.$i);
          $alarme[$i]->setEqLogic_id($this->getId());
          $alarme[$i]->setType('info');
          $alarme[$i]->setSubType('binary');
          $alarme[$i]->setOrder(100+$i);  // on les mets a la fin
          $alarme[$i]->setIsVisible(0); // on les mets par defaut invisible
          $alarme[$i]->setIsHistorized(0); // on ne les historises pas
          $alarme[$i]->save();
      }




    }

    public function preUpdate() {
      // on verifie au'il y a bien une ip de definie
      if ($this->getConfiguration('ip') == '') {
      			throw new Exception(__('L\'adresse IP ne peut etre vide', __FILE__));
      		}
    }

    public function postUpdate() {
    // trop long on ne le fait pas
     // on fait un refresh a la creation et a la mise a jour
      //$cmd = $this->getCmd(null, 'refresh'); // On recherche la commande refresh de l’équipement
  	  //if (is_object($cmd) and $this->getIsEnable() == 1 ) { //elle existe et l'equipement est active, on lance la commande
	//		     $cmd->execCmd();
	//	  }
    }

    public function preRemove() {

    }

    public function postRemove() {

    }

    /*     * **********************Getteur Setteur*************************** */
}

class AEG_acmi1000Cmd extends cmd {
    /*     * *************************Attributs****************************** */

    /*     * ***********************Methode static*************************** */

    /*     * *********************Methode d'instance************************* */

    public function execute($_options = array()) {
        $eqlogic = $this->getEqLogic(); //récupère l'éqlogic de la commande $this
		    switch ($this->getLogicalId()) {	//vérifie le logicalid de la commande
			       case 'refresh': // LogicalId de la commande rafraîchir que l’on a créé dans la méthode Postsave de la classe  .
                 log::add('AEG_acmi1000', 'debug', "on passe par le refresh de : ".$eqlogic->getHumanName());
				         $eqlogic->checkAndUpdateCmd('power', $eqlogic->puissance()); // on met à jour la commande
                 $eqlogic->checkAndUpdateCmd('tensionSource', $eqlogic->tensionSource()); // on met à jour la commande
                 $eqlogic->checkAndUpdateCmd('tensionCharge', $eqlogic->tensionCharge()); // on met à jour la commande
                 $eqlogic->checkAndUpdateCmd('tensionBatterie', $eqlogic->tensionBatterie()); // on met à jour la commande
                 $eqlogic->checkAndUpdateCmd('courantSource', $eqlogic->courantSource()); // on met à jour la commande
                 $eqlogic->checkAndUpdateCmd('courantCharge', $eqlogic->courantCharge()); // on met à jour la commande
                 $eqlogic->checkAndUpdateCmd('courantBatterie', $eqlogic->courantBatterie()); // on met à jour la commande
                 $eqlogic->checkAndUpdateCmd('tensionSourcePrimaire', $eqlogic->tensionSourcePrimaire()); // on met à jour la commande
                 $eqlogic->checkAndUpdateCmd('presence', $eqlogic->ping()); // on met à jour la commande
                 if ($this->getConfiguration('alarmes')==1){ // si on a valide la recuperation des etats des alarmes
                   for ($i=1; $i<=76; $i++) {
                      $eqlogic->checkAndUpdateCmd('alarme'.$i, $this->alarme($i)); // on met à jour la commande
                    }
                 }
				         break;
		         }

    }

    /*     * **********************Getteur Setteur*************************** */
}

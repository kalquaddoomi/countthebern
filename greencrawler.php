<?php
/**
 * Created by PhpStorm.
 * User: khaled
 * Date: 3/4/16
 * Time: 10:28 AM
 */

chdir(dirname(__FILE__));
include "states.php";
require "./vendor/autoload.php";
error_reporting(E_ERROR);

$db = new MysqliDb('localhost', "a", "a", 'countthebern');


$outCandidates = array();
foreach($us_states as $stateAb => $stateName) {
    $db->where('state_abbr', $state);
    $checkState = $db->getOne('states');

    $greenCrawl = "http://www.thegreenpapers.com/P20/$stateAb-D";
    $greenDom = \Sunra\PhpSimple\HtmlDomParser::file_get_html($greenCrawl);
    echo "Reading State: " . $stateName . "\n";
    $when = $greenDom->getElementById("evtmaj");
    $dateWhen = explode(':', $when->innertext);
    $dateWhen = explode('&nbsp;', $dateWhen[1]);
    $date = $dateWhen[3]." ".$dateWhen[2]." ".substr($dateWhen[4], 0, 4);
    $electDate = date('Y-m-d', strtotime($date));

    $tables = $greenDom->find("table");
    $footer = $greenDom->find("td[id=foot]", 0);
    if ($footer) {
        $fDom = \Sunra\PhpSimple\HtmlDomParser::str_get_html($footer);
        $keyA = $fDom->find("a", 1);
    }
    $counter = 0;
    foreach ($tables as $table) {
        $counter++;

        if ($counter == 2) {
            $tDom = \Sunra\PhpSimple\HtmlDomParser::str_get_html($table);
            $info = $tDom->find("td[id=tdhl]", 1);
            $dcells = $tDom->find("td[id=tddn]");
            $ccells = $tDom->find("td[id=tdnn]");
            $tcells = $tDom->find("td[id=tddb]");

            $infoProp = trim(preg_replace('/\s+/', ' ', $info->plaintext));
            foreach ($ccells as $CandidateCell) {
                $recordRow = $CandidateCell->parent();
                $dataCells = $recordRow->children();
                array_shift($dataCells);
                foreach ($dataCells as $aData) {
		    //die(var_dump($aData->plaintext));
                    //$dataReport = html_entity_decode(htmlspecialchars_decode($aData->plaintext));
                    //$dataBits = preg_split("@[\s+　]@u", $dataReport);
		    $dataBits = explode("&nbsp;", $aData->plaintext);
		    //die(var_dump($dataBits));
                    $outCandidates[$stateAb][$CandidateCell->plaintext][] = str_replace(',', '', trim($dataBits[0]));
                }
            }

            for ($i = 0; $i < 5; $i++) {
                //$totalReport = html_entity_decode(htmlspecialchars_decode($tcells[$i]->plaintext));
                //$totalBits = preg_split("@[\s+　]@u", $totalReport);
		$totalBits = explode("&nbsp;", $tcells[$i]->plaintext);
                $stateTotals[$stateAb][$i] = str_replace(',', '', trim($totalBits[0]));
            }
        }
    }
    $voteType[$stateAb] = $infoProp;
    $electType[$stateAb] = $keyA->plaintext;
    $electDates[$stateAb] = $electDate;
}

//die(var_dump($outCandidates));

foreach($outCandidates as $state=>$theCandidates) {
    echo "Processing State: ".$state."\n";
    $db->where('state_abbr', $state);
    $currState = $db->getOne('states');


    $stateElectInfo = explode(" ", $electType[$state]);
    $sitem = $stateTotals[$state];
    $stateMake = array(
        'state_abbr' => $state,
        'total_cast_votes'=>(is_numeric($sitem[0]) ? $sitem[0] : 0),
        'total_pledge'=>(is_numeric($sitem[1]) ? $sitem[1] : 0),
        'total_unpledge'=>(is_numeric($sitem[2]) ? $sitem[2] : 0),
        'total_delegates'=>(is_numeric($sitem[3]) ? $sitem[3] : 0),
        'election_type'=>$stateElectInfo[1],
        'election_eligible'=>$stateElectInfo[0],
        'primary_date'=>($state == "DA" ? '2016-03-21' : $electDates[$state]),
        'statename'=>$us_states[$state],
        'vote_count_Type'=>$voteType[$state]

    );
    if($currState['id']) {
        echo "Updating existing state: ".$currState['id']."\n";
        $db->where('id', $currState['id']);
        $db->update('states', $stateMake);
    } else {
        echo "Inserting new State\n";
        $currState = $db->insert('states', $stateMake);
    }
    foreach($theCandidates as $candidateName=>$item) {
        $candidateData = array(
            "name" => $candidateName,
            "running_status" => "running"
        );
        $db->where('name', $candidateName);
        $candidate = $db->getOne('candidates');

        if($db->count <= 0){
            $candidate = $db->insert('candidates', $candidateData);
            $db->where('name', $candidateName);
            $candidate = $db->getOne('candidates');
        }

        $cId = $candidate['id'];
	/*
	$itemIn = array();
        foreach($item as $aItem) {
 	    $dataBits = explode("  ", $aItem);
	    $itemIn[] = $dataBits[0];
	}	
	*/
        $candidateStateData = array(
            'state' => $state,
            'candidate_id' => $cId,
            'votes' => (is_numeric($item[0]) ? $item[0] : 0),
            'pledged' => (is_numeric($item[1]) ? $item[1] : 0),
            'unpledged' => (is_numeric($itemIn[2]) ? $itemIn[2] : 0)
        );
        $db->where('state', $state);
        $db->where('candidate_id', $cId);
        $votes = $db->getOne('candidate_state_results');
        if($votes['id']) {
            $db->where('id', $votes['id']);
            $db->update('candidate_state_results', $candidateStateData);
        } else {
            $db->insert('candidate_state_results', $candidateStateData);
        }
    }
}


echo "Done!";
exit();
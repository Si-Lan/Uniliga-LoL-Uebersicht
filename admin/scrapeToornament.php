<?php
require_once('../lib/simple_html_dom.php');

$dbservername = $dbdatabase = $dbusername = $dbpassword = $dbport = NULL;
include('../DB-info.php');


function scrape_toornament_teams($toorID){
	$returnArr = array("return"=>1, "echo"=>"", "writes"=>0, "updates"=>0);
	global $dbservername, $dbusername, $dbpassword, $dbdatabase, $dbport;
	$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);

	if ($dbcn -> connect_error){
		$returnArr["echo"] .= "<span style='color: red'>Database Connection failed : " . $dbcn->connect_error . "<br></span>";
		return $returnArr;
	}
	$tourn_check = $dbcn->query("SELECT TournamentID FROM tournaments WHERE TournamentID = {$toorID}")->fetch_all();
	if ($tourn_check == NULL) {
		$returnArr["echo"] .= "<span style='color: orangered'>angefragtes Turnier nicht in Datenbank <br></span>";
		return $returnArr;
	}

    $toorURL1 = "https://play.toornament.com/en_GB/tournaments/";
    $toorURL2 = "/participants/?page=";
    $maxPages = 1;
	$returnArr["echo"] .= "<span style='color: blue'>writing Teams:<br></span>";

    for($pageCounter = 1; $pageCounter <= $maxPages; $pageCounter++) {
        $response = get_headers($toorURL1 . $toorID . $toorURL2 . $pageCounter);

        if (str_contains($response[0],"200")) {

            $html = file_get_html($toorURL1 . $toorID . $toorURL2 . $pageCounter);

            if ($pageCounter == 1) {
                $pages = $html->find('ul.pagination-nav',0);
                if ($pages != NULL) {
                    $lastPage = $pages->find('li.page', -1)->find('a', 0)->plaintext;
                    $maxPages = $lastPage;
                }
            }

            $teams = $html->find('div.size-1-of-4');

			$returnArr["echo"] .= "<span style='color: deepskyblue'>Page $pageCounter<br></span>";

            for ($i = 0; $i < count($teams); $i++) {
                $teamhtml = $teams[$i];
                $id = explode('/', explode('participants/', $teamhtml->find('a')[0]->href)[1])[0];

                $teamname = $teamhtml->find('div.name', 0)->plaintext;

                $imgid = $teamhtml->find('img', 0);
                if ($imgid == NULL) {
                    $imgid = 'NULL';
                    $imgidN = NULL;
                } else {
                    $imgid = explode('/', explode('file/', $imgid->src)[1])[0];
                    $imgidN = $imgid;
                }

				$returnArr["echo"] .= "<span style='color: lightblue'>- $teamname<br></span>";
				$returnArr["echo"] .= "--- ID: $id<br>";
				$returnArr["echo"] .= "--- ImgID: $imgid<br>";
                $returnArr[] = $id;


                $teamIDsDB = $dbcn->query("SELECT TeamID, TournamentID, TeamName, imgID FROM teams WHERE TournamentID = {$toorID} AND TeamID = {$id}")->fetch_all();
                if ($teamIDsDB == NULL) {
					$returnArr["echo"] .= "<span style='color: lawngreen'>- writing in Database<br></span>";
                    $teamquery = "INSERT INTO teams (TeamID, TournamentID, TeamName, imgID) VALUES ($id,$toorID,'$teamname',$imgid)";
                    $dbcn->query($teamquery);
					$returnArr["writes"]++;
                } else {
					$returnArr["echo"] .= "<span style='color: orange'>team ist schon in DB<br></span>";
                    if (array_slice($teamIDsDB[0],0,4) == [$id,$toorID,$teamname,$imgidN]) {
						$returnArr["echo"] .= "<span style='color: yellow'>Daten sind unverändert<br></span>";
                    }else {
						$returnArr["echo"] .= "<span style='font-size: 30px; color: orange'>neue Daten, Update:<br></span>";
						$returnArr["echo"] .= "<pre>". print_r($teamIDsDB[0],true)."<br>". print_r([$id,$toorID,$teamname,$imgidN],true). "</pre>";
                        $dbcn->query("UPDATE `teams` SET TeamName = '{$teamname}', imgID = {$imgid} WHERE TournamentID = {$toorID} AND TeamID = {$id}");
						$returnArr["updates"]++;
                    }
                }

            }
        } else {
			$returnArr["echo"] .= "<span style='color: red'>Fehler beim Aufrufen von Toornament". "<br></span>";
            break;
        }
    }
	$returnArr["echo"] .= "<br>";
    return $returnArr;
}


function scrape_toornament_tourn_inf($toorID){
    global $dbservername, $dbusername, $dbpassword, $dbdatabase, $dbport;
    $dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);

    if ($dbcn -> connect_error){
        echo "<span style='color: red'>Database Connection failed : " . $dbcn->connect_error . "<br></span>";
        return;
    }

    $toorURL1 = "https://play.toornament.com/en_GB/tournaments/";

    $response = get_headers($toorURL1 . $toorID . "/");
    if (str_contains($response[0],"200")){
        $html = file_get_html($toorURL1 . $toorID . "/");
        $infdiv = $html->find('div.information',0);

        $name = $infdiv->find('div.name',0)->find('h1',0)->plaintext;

        if (str_contains($name,"Uniliga")){
            if (str_contains($name,"Sommer")){
                $split = "Sommer";
            } elseif (str_contains($name,"Winter")){
                $split = "Winter";
            } else {
                echo "<span style='color: orangered'>Keine Sommer/Winterseason gefunden <br></span>";
                $split = NULL;
            }

            if (str_contains($name,"20")) {
                $pos = strpos($name,"20");
                if ($pos+3 <= strlen($name)) {
                    $season = substr($name,$pos+2,2);
                } else {
                    $season = NULL;
                    echo "<span style='color: orangered'>Kein Season-Jahr gefunden <br></span>";
                }
            } else {
                $season = NULL;
                echo "<span style='color: orangered'>Kein Season-Jahr gefunden". "<br></span>";
            }
        } else {
            echo "<span style='color: orangered'>kein Uniliga-Turnier". "<br></span>";
            return;
        }

        $datediv = $infdiv->find('div.dates',0);
        $datestart = $datediv->find('date-view',0)->value;
        $dateend = $datediv->find('date-view',1)->value;

        $imgdiv = $html->find('div.image');
        if ($imgdiv == NULL){
            $imgID = 'NULL';
        } else {
            $imgdiv = $imgdiv[0]->find('img', 0);
            $imgID = explode('/', $imgdiv->src)[3];
        }

        echo "<span style='color: blue'>writing Tournament:<br></span>";
        echo "<span style='color: lightblue'>- $name<br></span>";
        echo "--- Split: $split<br>";
        echo "--- Season: $season<br>";
        echo "--- Date: $datestart - $dateend<br>";
        echo "--- Image-ID: $imgID<br>";

		$tournIDsDB = $dbcn->query("SELECT * FROM tournaments WHERE TournamentID = {$toorID}")->fetch_all();
        if ($tournIDsDB == NULL) {
            echo "<span style='color: lawngreen'>- schreibe in DB<br></span>";
            $tournquery = "INSERT INTO tournaments (TournamentID, `Name`, Split, Season, DateStart, DateEnd, imgID) VALUES ($toorID,'$name','$split',$season,'$datestart','$dateend',$imgID)";
            $dbcn->query($tournquery);
        } else {
            echo "<span style='color: orange'>tournament ist schon in DB<br></span>";
            $newdata = [$toorID,$name,$split,$season,$datestart,$dateend,$imgID];
            if ($tournIDsDB[0] == $newdata) {
                echo "<span style='color: yellow'>Daten sind unverändert<br></span>";
            }else {
                echo "<span style='font-size: 30px; color: orange'>neue Daten, Update:<br></span>";
                echo "<pre>"; print_r($tournIDsDB[0]); echo "<br>"; print_r($newdata); echo "</pre>";
                $dbcn->query("UPDATE `tournaments` SET `Name` = '{$name}', Split = '{$split}', Season = {$season}, DateStart = '{$datestart}', DateEnd = '{$dateend}', imgID = {$imgID} WHERE TournamentID = {$toorID}");
            }
        }

    } else {
        echo "<span style='color: red'>Fehler beim Aufrufen von Toornament <br></span>";
    }
    echo "<br>";
}


function scrape_toornament_divs($toorID) {
	global $dbservername, $dbusername, $dbpassword, $dbdatabase, $dbport;
	$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);

	if ($dbcn -> connect_error){
		echo "<span style='color: red'>Database Connection failed : " . $dbcn->connect_error . "<br></span>";
		return [];
	}

	$tourn_check = $dbcn->query("SELECT TournamentID FROM tournaments WHERE TournamentID = {$toorID}")->fetch_all();
	if ($tourn_check == NULL) {
		echo "<span style='color: orangered'>angefragtes Turnier nicht in Datenbank<br></span>";
		return [];
	}

    $returnArr = [];

    echo "<span style='color: blue'>writing Divisions:<br></span>";

    $toorURL1 = "https://play.toornament.com/en_GB/tournaments/";

    $response = get_headers($toorURL1 . $toorID . "/");

    if (str_contains($response[0],"200")){
        $html = file_get_html($toorURL1 . $toorID . "/");
        $divdivs = $html->find('div.structure-stage');

        for ($i = 0; $i < count($divdivs); $i++){
            $divNum = $divdivs[$i]->find('div.title',0)->plaintext;
            $divFormat = $divdivs[$i]->find('div.item',0)->plaintext;
            if (str_contains($divNum, "Gruppenphase")) {
                if (str_contains($divFormat,"Groups") or str_contains($divFormat,"Swiss")) {
                    if(str_contains($divFormat,"Groups")) {
                        $format = "Groups";
                    } else {
                        $format = "Swiss";
                    }
                    $divID = explode('/', $divdivs[$i]->parentNode()->href)[5];

                    $pos = strpos($divNum, "Liga");
                    if ($pos - 3 >= 0) {
                        $divNum = substr($divNum, $pos - 3, 1);

                    } else {
                        echo "<span style='color: orangered'>Liganame anders als erwartet, kann Nummer nicht lesen <br></span>";
                        continue;
                    }
                } else {
                    echo "<span style='color: orangered'>Division " . ($i+1) . " hat weder Groups noch Swiss als Format <br></span>";
                    continue;
                }
            } else {
                echo "<span style='color: yellow'>Division " . ($i+1) . " ist keine Gruppenphase einer Liga <br></span>";
                continue;
            }

            $returnArr[] = $divID;

            echo "<span style='color: lightblue'>- Liga $divNum<br></span>";
            echo "--- ID: $divID<br>";
            echo "--- Format: $format<br>";

			$divIDsDB = $dbcn->query("SELECT * FROM divisions WHERE DivID = {$divID} AND TournamentID = {$toorID}")->fetch_all();
			if ($divIDsDB == NULL) {
				echo "<span style='color: lawngreen'>- schreibe in DB<br></span>";
				$divquery = "INSERT INTO divisions (DivID, TournamentID, Number, format) VALUES ({$divID},{$toorID},{$divNum},'{$format}')";
				$dbcn->query($divquery);
			} else {
				echo "<span style='color: orange'>Liga ist schon in DB - weiter denken<br></span>";
				$newdata = [$divID,$toorID,$divNum,$format];
				if ($divIDsDB[0] == $newdata) {
					echo "<span style='color: yellow'>Daten sind unverändert<br></span>";
				}else {
					echo "<span style='font-size: 30px; color: orange'>neue Daten, Update:<br></span>";
					echo "<pre>"; print_r($divIDsDB[0]); echo "<br>"; print_r($newdata); echo "</pre>";
					$dbcn->query("UPDATE divisions SET `Number` = {$divNum}, format = '{$format}' WHERE DivID = {$divID} AND TournamentID = {$toorID}");
				}
			}

            echo "<br>";
        }
    } else {
        echo "<span style='color: red'>Fehler beim Aufrufen von Toornament <br></span>";
    }
    return $returnArr;
}


function scrape_toornament_groups($toorID,$divID, bool $delete_missing = FALSE){
	global $dbservername, $dbusername, $dbpassword, $dbdatabase, $dbport;
	$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);

	if ($dbcn -> connect_error){
		echo "<span style='color: red'>Database Connection failed : " . $dbcn->connect_error . "<br></span>";
		return [];
	}

	$tourn_check = $dbcn->query("SELECT TournamentID FROM tournaments WHERE TournamentID = {$toorID}")->fetch_all();
	if ($tourn_check == NULL) {
		echo "<span style='color: orangered'>angefragtes Turnier nicht in Datenbank<br></span>";
		return [];
	}
	$div_check = $dbcn->query("SELECT DivID, `format` FROM divisions WHERE DivID = {$divID}")->fetch_all();
	if ($div_check == NULL) {
		echo "<span style='color: orangered'>angefragte Liga nicht in Datenbank<br></span>";
		return [];
	} else if ($div_check[0][1] != "Groups") {
		echo "<span style='color: orangered'>Liga ist nicht im Gruppen-Format<br></span>";
		if ($div_check[0][1] == "Swiss") {
			echo "<span style='color: lightblue'>Liga ist im Swiss-Format<br></span>";
			if (str_contains(strval(get_headers("https://play.toornament.com/en_GB/tournaments/" . $toorID . "/stages/" . $divID . "/")[0]),"302")) {
				$swiss_group_url = explode("/",strval(get_headers("https://play.toornament.com/en_GB/tournaments/" . $toorID . "/stages/" . $divID . "/")[6]));
				$location = count($swiss_group_url)-2;
				$swiss_group = $swiss_group_url[$location];
				$groupIDisDB = $dbcn->query("SELECT * FROM `groups` WHERE GroupID = {$swiss_group} AND DivID = {$divID}")->fetch_all();
				if ($groupIDisDB == NULL) {
					echo "<span style='color: lawngreen'>- schreibe in DB<br></span>";
					$groupquery = "INSERT INTO `groups` (GroupID, DivID, Number) VALUES ({$swiss_group},{$divID},0)";
					$dbcn->query($groupquery);
				} else {
					echo "<span style='color: orange'>Gruppe ist schon in DB<br></span>";
				}
			} else {
				echo "Fehler beim lesen der Swiss-Gruppe";
			}
		}
		echo "<br>";
		return [];
	}

    $returnArr = [];

    echo "<span style='color: blue'>writing Groups:<br></span>";

    $toorURL1 = "https://play.toornament.com/en_GB/tournaments/";

    $response = get_headers($toorURL1 . $toorID . "/stages/" . $divID . "/");

    if (str_contains($response[0],"200")) {
        $html = file_get_html($toorURL1 . $toorID . "/stages/" . $divID . "/");
        $groupdivs = $html->find('div.structure-group');

        for ($i=0; $i < count($groupdivs); $i++) {
            $groupNum = $groupdivs[$i]->find('div.title',0)->plaintext;
            $groupNum = substr($groupNum,strlen($groupNum)-1);
            $groupID = explode('/', $groupdivs[$i]->parentNode()->href)[7];

            echo "<span style='color: lightblue'>- Gruppe $groupNum<br></span>";
            echo "--- ID: $groupID<br>";

            $returnArr[] = $groupID;

			$groupIDisDB = $dbcn->query("SELECT * FROM `groups` WHERE GroupID = {$groupID} AND DivID = {$divID}")->fetch_all();
			if ($groupIDisDB == NULL) {
				echo "<span style='color: lawngreen'>- schreibe in DB<br></span>";
				$groupquery = "INSERT INTO `groups` (GroupID, DivID, Number) VALUES ({$groupID},{$divID},{$groupNum})";
				$dbcn->query($groupquery);
			} else {
				echo "<span style='color: orange'>Gruppe ist schon in DB<br></span>";
				$newdata = [$groupID,$divID,$groupNum];
				if ($groupIDisDB[0] == $newdata) {
					echo "<span style='color: yellow'>Daten sind unverändert<br></span>";
				}else {
					echo "<span style='font-size: 30px; color: orange'>neue Daten, Update:<br></span>";
					echo "<pre>"; print_r($groupIDisDB[0]); echo "<br>"; print_r($newdata); echo "</pre>";
					$dbcn->query("UPDATE `groups` SET `Number` = {$groupNum} WHERE DivID = {$divID} AND GroupID = {$groupID}");
				}
			}
            echo "<br>";
        }
		if ($delete_missing) {
			$groups_in_DB = $dbcn->query("SELECT GroupID FROM `groups` WHERE DivID=$divID")->fetch_all(MYSQLI_ASSOC);
			foreach ($groups_in_DB as $group_in_DB) {
				if (!in_array($group_in_DB["GroupID"],$returnArr)) {
					echo "<span style='color: orangered;'>". $group_in_DB["GroupID"] . " doesn't exist anymore, delete<br></span>";
					$dbcn->query("DELETE FROM `groups` WHERE DivID=$divID AND GroupID=".$group_in_DB["GroupID"]);
				}
			}
		}
    } else {
        echo "<span style='color: red'>Fehler beim Aufrufen von Toornament". "<br></span>";
    }
    return $returnArr;
}


function scrape_toornaments_teams_in_groups($tournID,$divID,$groupID, $test=FALSE, bool $delete_missing=FALSE) {
    $returnArr = array("echo"=>"","writes"=>0,"updates"=>0);
	if ($test) {
		return $returnArr;
	}
    global $dbservername, $dbusername, $dbpassword, $dbdatabase, $dbport;
    $dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);

    if ($dbcn -> connect_error){
        $returnArr["echo"] .= "<span style='color: red'>Database Connection failed : " . $dbcn->connect_error . "<br></span>";
        return $returnArr;
    }

    $tourn_check = $dbcn->query("SELECT TournamentID FROM tournaments WHERE TournamentID = {$tournID}")->fetch_all();
    if ($tourn_check == NULL) {
        $returnArr["echo"] .= "<span style='color: orangered'>angefragtes Turnier nicht in Datenbank<br></span>";
        return $returnArr;
    }
    $div_check = $dbcn->query("SELECT DivID, `format`, `Number` FROM divisions WHERE DivID = {$divID}")->fetch_all();
    if ($div_check == NULL) {
        $returnArr["echo"] .= "<span style='color: orangered'>angefragte Liga nicht in Datenbank<br></span>";
        return $returnArr;
    } else if ($div_check[0][1] != "Groups") {
        $returnArr["echo"] .= "<span style='color: orangered'>Liga ist nicht im Gruppen-Format<br></span>";
        return $returnArr;
    }
    $group_check = $dbcn->query("SELECT GroupID, `Number` FROM `groups` WHERE GroupID = {$groupID}")->fetch_all();
    if ($group_check == NULL) {
        $returnArr["echo"] .= "<span style='color: orangered'>angefragte Gruppe nicht in Datenbank<br></span>";
        return $returnArr;
    }
	$teams_gotten = array();

    $returnArr["echo"] .= "<span style='color: blue'>writing Teams in Div ". $div_check[0][2] ." Group ". $group_check[0][1] .":<br></span>";

    $toorURL1 = "https://play.toornament.com/en_GB/tournaments/";

    $response = get_headers($toorURL1 . $tournID . "/stages/" . $divID . "/groups/" . $groupID . "/#structure");

    if (str_contains($response[0],"200")) {
        $html = file_get_html($toorURL1 . $tournID . "/stages/" . $divID . "/groups/" . $groupID . "/#structure");
        $group_teams = $html->find('div.ranking-container');
        for ($i = 0; $i < count($group_teams); $i++) {
            $rank = intval($group_teams[$i]->find('div.rank',0)->plaintext);
            $name = $group_teams[$i]->find('div.name',0)->plaintext;
            $metrics = $group_teams[$i]->find('div.metric');
            $played = $metrics[0]->plaintext;
            $wins = $metrics[1]->plaintext;
            $draws = $metrics[2]->plaintext;
            $losses = $metrics[3]->plaintext;
            $points = intval($metrics[8]->plaintext);

            $teamID = $dbcn->query("SELECT TeamID FROM teams WHERE TournamentID  = '$tournID' AND TeamName = '$name'")->fetch_all();
            if ($teamID == NULL) {
                $returnArr["echo"] .= "<span style='color: orangered;'>- Team $name ist noch nicht in Teams-Tabelle<br></span>";
                continue;
            }
            $teamID = $teamID[0][0];
			$teams_gotten[] = $teamID;

            $returnArr["echo"] .= "<span style='color: lightblue'>- Team $name<br></span>";
            $returnArr["echo"] .= "--- $rank.  G: $played  W: $wins  D: $draws  L: $losses  P: $points <br>";


			$team_in_group_is_in_DB = $dbcn->query("SELECT * FROM teamsingroup WHERE GroupID = {$groupID} AND TeamID = {$teamID}")->fetch_all();
			if ($team_in_group_is_in_DB == NULL) {
				$returnArr["echo"] .= "<span style='color: lawngreen'>- schreibe in DB<br></span>";
				$returnArr["writes"]++;
				$groupteamsquery = "INSERT INTO teamsingroup (GroupID, TeamID, `Rank`, played, Wins, Draws, Losses, Points) VALUES ($groupID, $teamID, $rank, $played, $wins, $draws, $losses, $points)";
				$dbcn->query($groupteamsquery);
			} else {
				$returnArr["echo"] .= "<span style='color: orange'>Gruppendaten des Teams ist schon in DB<br></span>";
				$newdata = [$groupID,$teamID,$rank,$played,$wins,$draws,$losses,$points];
				if ($team_in_group_is_in_DB[0] == $newdata) {
					$returnArr["echo"] .= "<span style='color: yellow'>Daten sind unverändert<br></span>";
				}else {
					$returnArr["echo"] .= "<span style='font-size: 30px; color: orange'>neue Daten, Update:<br></span>";
					$returnArr["updates"]++;
					$returnArr["echo"] .= "<pre>" . print_r($team_in_group_is_in_DB[0],true) ."<br>" . print_r($newdata,true) . "</pre>";
					$dbcn->query("UPDATE teamsingroup SET `Rank` = {$rank}, played = {$played}, Wins = {$wins}, Draws = {$draws}, Losses = {$losses}, Points = {$points} WHERE GroupID = {$groupID} AND TeamID = {$teamID}");
				}
			}
            $returnArr["echo"] .= "<br>";
        }
		if ($delete_missing) {
			$teams_in_group_DB = $dbcn->query("SELECT TeamID FROM teamsingroup WHERE GroupID=$groupID")->fetch_all(MYSQLI_ASSOC);
			foreach ($teams_in_group_DB as $team_in_group) {
				if (!in_array($team_in_group["TeamID"], $teams_gotten)) {
					$returnArr["echo"] .= "<span style='color: orangered;'>" . $team_in_group["TeamID"] . " not in Group anymore, delete<br></span>";
					$dbcn->query("DELETE FROM teamsingroup WHERE GroupID=$groupID AND TeamID=" . $team_in_group["TeamID"]);
				}
			}
		}
    } else {
        $returnArr["echo"] .= "<span style='color: red'>Fehler beim Aufrufen von Toornament". "<br></span>";
    }
    return $returnArr;
}

function scrape_toornaments_teams_in_groups_swiss($tournID,$divID,$groupID, $test=FALSE) {
    $returnArr = array("echo"=>"","writes"=>0,"updates"=>0);
	if ($test) {
		return $returnArr;
	}
    global $dbservername, $dbusername, $dbpassword, $dbdatabase, $dbport;
    $dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);

    if ($dbcn -> connect_error){
        $returnArr["echo"] .= "<span style='color: red'>Database Connection failed : " . $dbcn->connect_error . "<br></span>";
        return $returnArr;
    }

    $tourn_check = $dbcn->query("SELECT TournamentID FROM tournaments WHERE TournamentID = {$tournID}")->fetch_all();
    if ($tourn_check == NULL) {
        $returnArr["echo"] .= "<span style='color: orangered'>angefragtes Turnier nicht in Datenbank<br></span>";
        return $returnArr;
    }
    $div_check = $dbcn->query("SELECT DivID, `format`, `Number` FROM divisions WHERE DivID = {$divID}")->fetch_all();
    if ($div_check == NULL) {
        $returnArr["echo"] .= "<span style='color: orangered'>angefragte Liga nicht in Datenbank<br></span>";
        return $returnArr;
    } else if ($div_check[0][1] != "Swiss") {
        $returnArr["echo"] .= "<span style='color: orangered'>Liga ist nicht im Swiss-Format<br></span>";
        return $returnArr;
    }
    $group_check = $dbcn->query("SELECT GroupID FROM `groups` WHERE GroupID = {$groupID}")->fetch_all();
    if ($group_check == NULL) {
        $returnArr["echo"] .= "<span style='color: orangered'>angefragte Gruppe nicht in Datenbank<br></span>";
        return $returnArr;
    }

    $returnArr["echo"] .= "<span style='color: blue'>writing Teams in Div ". $div_check[0][2] .":<br></span>";

    $toorURL1 = "https://play.toornament.com/en_GB/tournaments/";
    $maxPages = 1;

    for($pageCounter = 1; $pageCounter <= $maxPages; $pageCounter++) {
        $response = get_headers($toorURL1 . $tournID . "/stages/" . $divID . "/groups/" . $groupID . "/?page=" . $pageCounter);

        if (str_contains($response[0], "200")) {
            $html = file_get_html($toorURL1 . $tournID . "/stages/" . $divID . "/groups/" . $groupID . "/?page=" . $pageCounter);

            if ($pageCounter == 1) {
                $pages = $html->find('ul.pagination-nav',0);
                if ($pages != NULL) {
                    $lastPage = $pages->find('li.page', -1)->find('a', 0)->plaintext;
                    $maxPages = $lastPage;
                }
            }

            $group_teams = $html->find('div.ranking-container');
            $returnArr["echo"] .= "<span style='color: deepskyblue'>Page $pageCounter<br></span>";

            for ($i = 0; $i < count($group_teams); $i++) {
                $rank = intval($group_teams[$i]->find('div.rank', 0)->plaintext);
                $name = $group_teams[$i]->find('div.name', 0)->plaintext;
                $metric = $group_teams[$i]->find('div.metric', 0)->plaintext;

				$games = $group_teams[$i]->find('div.history', 0)->find('div.result');

				$played = count($games);
				$wins = 0;
				$draws = 0;
				$losses = 0;

				foreach ($games as $game) {
					$res = $game->plaintext;
					if ($res == "W") {
						$wins++;
					} elseif ($res == "D") {
						$draws++;
					} elseif ($res == "L" || $res == "F") {
						$losses++;
					}
				}

                $teamID = $dbcn->query("SELECT TeamID FROM teams WHERE TournamentID  = '$tournID' AND TeamName = '$name'")->fetch_all();
                if ($teamID == NULL) {
                    $returnArr["echo"] .= "<span style='color: orangered;'>- Team $name ist noch nicht in Teams-Tabelle<br></span>";
                    continue;
                }
                $teamID = $teamID[0][0];

                $returnArr["echo"] .= "<span style='color: lightblue'>- Team $name<br></span>";
                $returnArr["echo"] .= "--- $rank.  Pt: $metric, Pl: $played, W: $wins, D: $draws, L: $losses <br>";

				$team_in_group_is_in_DB = $dbcn->query("SELECT * FROM teamsingroup WHERE GroupID = {$groupID} AND TeamID = {$teamID}")->fetch_all();
				if ($team_in_group_is_in_DB == NULL) {
					$returnArr["echo"] .= "<span style='color: lawngreen'>- schreibe in DB<br></span>";
					$returnArr["writes"]++;
					$groupteamsquery = "INSERT INTO teamsingroup (GroupID, TeamID, `Rank`, played, Wins, Draws, Losses, Points) VALUES ($groupID, $teamID, $rank, $played, $wins, $draws, $losses, {$metric})";
					$dbcn->query($groupteamsquery);
				} else {
					$returnArr["echo"] .= "<span style='color: orange'>Gruppendaten des Teams ist schon in DB<br></span>";
					$newdata = [$groupID, $teamID, $rank, $played, $wins, $draws, $losses, $metric];
					if ($team_in_group_is_in_DB[0] == $newdata) {
						$returnArr["echo"] .= "<span style='color: yellow'>Daten sind unverändert<br></span>";
					} else {
						$returnArr["echo"] .= "<span style='font-size: 30px; color: orange'>neue Daten, Update:<br></span>";
						$returnArr["updates"]++;
						$returnArr["echo"] .= "<pre>" . print_r($team_in_group_is_in_DB[0],true) . "<br>" . print_r($newdata,true) . "</pre>";
						$dbcn->query("UPDATE teamsingroup SET `Rank` = {$rank}, Points = {$metric}, played = {$played}, Wins = {$wins}, Draws = {$draws}, Losses = $losses WHERE GroupID = {$groupID} AND TeamID = {$teamID}");
					}
				}
                $returnArr["echo"] .= "<br>";
            }
        } else {
            $returnArr["echo"] .= "<span style='color: red'>Fehler beim Aufrufen von Toornament" . "<br></span>";
            break;
        }
    }
    return $returnArr;
}


function scrape_toornaments_players($tournID,$teamID,$test=FALSE) {
    $returnArr = array("return"=>1, "echo"=>"", "writes"=>0, "NameUpdate"=>0, "SNameUpdate"=>0, "notInToor"=>0);
	if ($test) {
		return $returnArr;
	}
    $returnArr["echo"] .= "<span style='color: blue'>writing Players for Team $teamID :<br></span>";
    global $dbservername, $dbusername, $dbpassword, $dbdatabase, $dbport;
    $dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);

    if ($dbcn -> connect_error){
        $returnArr["return"] = 1;
        $returnArr["echo"] .= "<span style='color: red'>Database Connection failed : " . $dbcn->connect_error . "<br><br></span>";
        return $returnArr;
    }
    $tourn_check = $dbcn->query("SELECT TournamentID FROM tournaments WHERE TournamentID = {$tournID}")->fetch_all();
    if ($tourn_check == NULL) {
        $returnArr["return"] = 1;
        $returnArr["echo"] .= "<span style='color: orangered'>angefragtes Turnier nicht in Datenbank<br><br></span>";
        return $returnArr;
    }
    $team_check = $dbcn->query("SELECT TeamID FROM teams WHERE TeamID = {$teamID}")->fetch_all();
    if ($team_check == NULL) {
        $returnArr["return"] = 1;
        $returnArr["echo"] .= "<span style='color: orangered'>angefragtes Team nicht in Datenbank<br><br></span>";
        return $returnArr;
    }
    $teamplayersDB = $dbcn->query("SELECT PlayerID, PlayerName, SummonerName, notInToor FROM players WHERE TournamentID = {$tournID} AND TeamID = {$teamID}")->fetch_all(MYSQLI_ASSOC);

    $toorURL1 = "https://play.toornament.com/en_GB/tournaments/";
    $response = get_headers($toorURL1 . $tournID . "/participants/" . $teamID . "/info");

    if (str_contains($response[0],"200")) {
        $results = [];
        $html = file_get_html($toorURL1 . $tournID . "/participants/" . $teamID . "/info");
        $playersdiv = $html->find('div.grid-flex.vertical.spacing-medium')[0];
        $playerdivs = $playersdiv->find('div.grid-flex.vertical.spacing-tiny');
        for ($i = 0; $i < count($playerdivs); $i++){
            $playernames = $playerdivs[$i]->find('div.size-content');
            $name = $playernames[0]->plaintext;
            $summonerName = $playernames[1]->plaintext;
            if (str_contains($summonerName,"Summoner Name: ")){
                $summonerName = explode("Summoner Name: ",$summonerName)[1];
            }
            $name = trim($name);
            $summonerName = trim($summonerName);
            $results[$i]["PlayerName"] = $name;
            $results[$i]["SummonerName"] = $summonerName;
        }

        $DB_in_Toor = [];
		$teamplayers_for_loop = $teamplayersDB;
		foreach ($results as $iT=>$playerFromToor) {
			$returnArr["echo"] .= "<span style='color: lightblue'>- Spielername: {$playerFromToor["PlayerName"]}<br>- Summoner Name: {$playerFromToor["SummonerName"]}<br></span>";
			$found_in_DB = FALSE;
            foreach ($teamplayers_for_loop as $iDB=>$playerInDB) {
                if ($playerInDB["SummonerName"] == $playerFromToor["SummonerName"] && $playerInDB["PlayerName"] == $playerFromToor["PlayerName"]) {
                    $returnArr["echo"] .= "<span style='color: orange'>Spieler ist schon in DB<br></span>";
                    $found_in_DB = TRUE;
                    $DB_in_Toor[] = $iDB;
                    break;
                }
                if ($playerInDB["SummonerName"] == $playerFromToor["SummonerName"]) {
                    $returnArr["echo"] .= "<span style='color: orange'>Spieler hat neuen Namen : {$playerInDB["PlayerName"]} -> {$playerFromToor["PlayerName"]}<br></span>";
                    $returnArr["NameUpdate"]++;
                    $dbcn->query("UPDATE players SET PlayerName = '{$playerFromToor["PlayerName"]}' WHERE PlayerID = {$playerInDB["PlayerID"]}");
                    $found_in_DB = TRUE;
                    $DB_in_Toor[] = $iDB;
                    break;
                }
                if ($playerInDB["PlayerName"] == $playerFromToor["PlayerName"]) {
                    $returnArr["echo"] .= "<span style='color: orange'>Spieler hat neuen Summoner-Namen : {$playerInDB["SummonerName"]} -> {$playerFromToor["SummonerName"]}<br></span>";
                    $returnArr["SNameUpdate"]++;
                    $dbcn->query("UPDATE players SET SummonerName = '{$playerFromToor["SummonerName"]}' WHERE PlayerID = {$playerInDB["PlayerID"]}");
                    $found_in_DB = TRUE;
                    $DB_in_Toor[] = $iDB;
                    break;
                }
            }
			if ($found_in_DB) {
				unset($teamplayers_for_loop[end($DB_in_Toor)]);
			}
            if (!($found_in_DB)) {
                $returnArr["echo"] .= "<span style='color: lawngreen'>Spieler nicht in DB - schreibe<br></span>";
                $returnArr["writes"]++;
                $dbcn->query("INSERT INTO players (TeamID, TournamentID, PlayerName, SummonerName) VALUES ({$teamID},{$tournID},'{$playerFromToor["PlayerName"]}','{$playerFromToor["SummonerName"]}')");
            }
        }

        foreach ($teamplayersDB as $iDB=>$playerInDB) {
            if (!in_array($iDB,$DB_in_Toor)) {
                $returnArr["echo"] .= "<span style='color: orangered'>Spieler nicht mehr in Toornament: P: {$playerInDB["PlayerName"]} S: {$playerInDB["SummonerName"]}<br></span>";
                $returnArr["notInToor"]++;
                $dbcn->query("UPDATE players SET notInToor = TRUE WHERE PlayerID = {$playerInDB["PlayerID"]}");
            }
        }

    } else {
        $returnArr["echo"] .= "<span style='color: red'>Fehler beim Aufrufen von Toornament". "<br></span>";
    }
    $returnArr["echo"] .= "<br>";
    //echo $returnArr["echo"];
    return $returnArr;
}


function scrape_toornament_matches_from_group($tournID, $divID, $groupID) {
    $returnArr = array("return"=>0, "echo"=>"<span style='color: blue'>writing Matches for Group $groupID :<br></span>", "writes"=>0, "changes"=>[0, []]);
    global $dbservername, $dbusername, $dbpassword, $dbdatabase, $dbport;
    $dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);

    if ($dbcn -> connect_error){
        $returnArr["return"] = 1;
        $returnArr["echo"] .= "<span style='color: red'>Database Connection failed : " . $dbcn->connect_error . "<br><br></span>";
        return $returnArr;
    }
    $tourn_check = $dbcn->query("SELECT TournamentID FROM tournaments WHERE TournamentID = {$tournID}")->fetch_all();
    if ($tourn_check == NULL) {
        $returnArr["return"] = 1;
        $returnArr["echo"] .= "<span style='color: orangered'>angefragtes Turnier nicht in Datenbank<br><br></span>";
        return $returnArr;
    }
    $div_check = $dbcn->query("SELECT DivID, `format` FROM divisions WHERE DivID = {$divID}")->fetch_all();
    if ($div_check == NULL) {
        $returnArr["return"] = 1;
        $returnArr["echo"] .= "<span style='color: orangered'>angefragte Liga nicht in Datenbank<br><br></span>";
        return $returnArr;
    } else if ($div_check[0][1] != "Groups") {
        $returnArr["return"] = 1;
        $returnArr["echo"] .= "<span style='color: orangered'>Liga ist nicht im Gruppen-Format<br><br></span>";
        return $returnArr;
    }
    $group_check = $dbcn->query("SELECT GroupID FROM `groups` WHERE GroupID = {$groupID}")->fetch_all();
    if ($group_check == NULL) {
        $returnArr["return"] = 1;
        $returnArr["echo"] .= "<span style='color: orangered'>angefragte Gruppe nicht in Datenbank<br><br></span>";
        return $returnArr;
    }

    $toorURL1 = "https://play.toornament.com/en_GB/tournaments/";
    $response = get_headers($toorURL1 . $tournID . "/stages/" . $divID . "/groups/" . $groupID . "/#structure");

    if (str_contains($response[0],"200")) {
        $results = [];
        $html = file_get_html($toorURL1 . $tournID . "/stages/" . $divID . "/groups/" . $groupID . "/#structure");
        $rounds = $html->find("div.grid-flex.vertical.spacing-large",-1)->find("div.grid-flex.vertical");
        foreach ($rounds as $i_r => $round) {
            $matches = $round->find("div.size-content",1)->find("a");
            foreach ($matches as $i_m => $match) {
                $results[$i_r*4 + $i_m] = [];
                $results[$i_r*4 + $i_m]['matchID'] = explode("/", $match->href)[5];
                $results[$i_r*4 + $i_m]['groupID'] = $groupID;
                $results[$i_r*4 + $i_m]['played'] = 0;
                $results[$i_r*4 + $i_m]['round'] = explode(" ", $round->find("div.size-content",0)->plaintext)[1];
                $team1Name = trim($match->find("div.opponent-1 div.name", 0)->plaintext);
                $team2Name = trim($match->find("div.opponent-2 div.name", 0)->plaintext);
                $team1ID = $dbcn->query("SELECT TeamID FROM teams WHERE TournamentID = {$tournID} AND TeamName= '{$team1Name}'")->fetch_all()[0][0];
                $team2ID = $dbcn->query("SELECT TeamID FROM teams WHERE TournamentID = {$tournID} AND TeamName= '{$team2Name}'")->fetch_all()[0][0];
                $results[$i_r*4 + $i_m]['team1ID'] = $team1ID;
                $results[$i_r*4 + $i_m]['team2ID'] = $team2ID;
            }
        }
        foreach ($results as $result) {
            $matchinDB = $dbcn->query("SELECT GroupID, Team1ID, Team2ID, round FROM matches WHERE MatchID = {$result['matchID']}")->fetch_all();
            if ($matchinDB == NULL) {
                // match not in Database
                $returnArr["writes"]++;
                $returnArr["echo"] .= "<span style='color: lawngreen'>- schreibe in DB<br></span>";
                $dbcn->query("INSERT INTO matches (matchid, groupid, team1id, team2id, round, played) VALUES ({$result['matchID']}, {$result['groupID']}, {$result['team1ID']}, {$result['team2ID']}, {$result['round']}, {$result['played']})");
            } else {
                // match already in Database
                $returnArr["echo"] .= "<span style='color: orange'>Match ist schon in DB<br></span>";
                $newdata = [$result['groupID'],$result['team1ID'],$result['team2ID'],$result['round']];
                if ($matchinDB[0] == $newdata) {
                    $returnArr["echo"] .= "<span style='color: yellow'>Daten sind unverändert<br></span>";
                } else {
                    $returnArr["echo"] .= "<span style='font-size: 30px; color: orange'>neue Daten, Update:<br></span>" . json_encode($matchinDB[0]) . "<br>" . json_encode($newdata) . "<br>";
                    $returnArr["changes"][0]++;
                    $returnArr["changes"][1][] = [$matchinDB[0],$newdata];
                    $dbcn->query("UPDATE matches SET Team1ID = {$result['team1ID']}, Team2ID = {$result['team2ID']}, round = {$result['round']} WHERE MatchID = {$result['matchID']} AND GroupID = {$result['groupID']}");
                }
            }
        }
    } else {
        $returnArr["echo"] .= "<span style='color: red'>Fehler beim Aufrufen von Toornament". "<br></span>";
    }
    $returnArr["echo"] .= "<br>";
    return $returnArr;
}

function scrape_toornament_matches_from_swiss($tournID, $divID, $groupID) {
	$returnArr = array("return"=>0, "echo"=>"<span style='color: blue'>writing Matches for Group $groupID :<br></span>", "writes"=>0, "changes"=>[0, []]);
	global $dbservername, $dbusername, $dbpassword, $dbdatabase, $dbport;
	$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);

	if ($dbcn -> connect_error){
		$returnArr["return"] = 1;
		$returnArr["echo"] .= "<span style='color: red'>Database Connection failed : " . $dbcn->connect_error . "<br><br></span>";
		return $returnArr;
	}
	$tourn_check = $dbcn->query("SELECT TournamentID FROM tournaments WHERE TournamentID = {$tournID}")->fetch_all();
	if ($tourn_check == NULL) {
		$returnArr["return"] = 1;
		$returnArr["echo"] .= "<span style='color: orangered'>angefragtes Turnier nicht in Datenbank<br><br></span>";
		return $returnArr;
	}
	$div_check = $dbcn->query("SELECT DivID, `format` FROM divisions WHERE DivID = {$divID}")->fetch_all();
	if ($div_check == NULL) {
		$returnArr["return"] = 1;
		$returnArr["echo"] .= "<span style='color: orangered'>angefragte Liga nicht in Datenbank<br><br></span>";
		return $returnArr;
	} else if ($div_check[0][1] != "Swiss") {
		$returnArr["return"] = 1;
		$returnArr["echo"] .= "<span style='color: orangered'>Liga ist nicht im Swiss-Format<br><br></span>";
		return $returnArr;
	}
	$group_check = $dbcn->query("SELECT GroupID FROM `groups` WHERE GroupID = {$groupID}")->fetch_all();
	if ($group_check == NULL) {
		$returnArr["return"] = 1;
		$returnArr["echo"] .= "<span style='color: orangered'>angefragte Gruppe nicht in Datenbank<br><br></span>";
		return $returnArr;
	}

	$toorURL1 = "https://play.toornament.com/en_GB/tournaments/";
	$maxPages = 1;

	for ($pageCounter = 1; $pageCounter <= $maxPages; $pageCounter++) {
		$response = get_headers($toorURL1 . $tournID . "/stages/" . $divID . "/groups/" . $groupID . "/");
		if (str_contains($response[0],"200")) {
			$results = [];
			$html = file_get_html($toorURL1 . $tournID . "/stages/" . $divID . "/groups/" . $groupID . "/");

			if ($pageCounter == 1) {
				$pages = $html->find('ul.pagination-nav',0);
				if ($pages != NULL) {
					$lastPage = $pages->find('li.page', -1)->find('a', 0)->plaintext;
					$maxPages = $lastPage;
				}
			}

			$teams = $html->find('div.ranking-container');

			for ($i = 0; $i < count($teams); $i++) {
				$matches = $teams[$i]->find('div.ranking-extra',0)->find('a');
				foreach ($matches as $i_m => $match) {
					$matchID = explode("/", $match->href)[5];
					if (!array_key_exists($matchID,$results)) {
						$results[$matchID] = array('matchID'=>$matchID);
						$results[$matchID]['groupID'] = $groupID;
						$results[$matchID]['played'] = 0;
						$results[$matchID]['round'] = $i_m;
						$team1Name = trim($match->find("div.opponent-1 div.name", 0)->plaintext);
						$team2Name = trim($match->find("div.opponent-2 div.name", 0)->plaintext);
						$team1ID = $dbcn->query("SELECT TeamID FROM teams WHERE TournamentID = {$tournID} AND TeamName= '{$team1Name}'")->fetch_all()[0][0];
						$team2ID = $dbcn->query("SELECT TeamID FROM teams WHERE TournamentID = {$tournID} AND TeamName= '{$team2Name}'")->fetch_all()[0][0];
						$results[$matchID]['team1ID'] = $team1ID;
						$results[$matchID]['team2ID'] = $team2ID;
					}
				}
			}

			foreach ($results as $result) {
				$matchinDB = $dbcn->query("SELECT GroupID, Team1ID, Team2ID, round FROM matches WHERE MatchID = {$result['matchID']}")->fetch_all();
				if ($matchinDB == NULL) {
					//match not in DB
					$returnArr["writes"]++;
					$returnArr["echo"] .= "<span style='color: lawngreen'>- schreibe in DB<br></span>";
					$dbcn->query("INSERT INTO matches (matchid, groupid, team1id, team2id, round, played) VALUES ({$result['matchID']}, {$result['groupID']}, {$result['team1ID']}, {$result['team2ID']}, {$result['round']}, {$result['played']})");
				} else {
					//match already in DB
					$returnArr["echo"] .= "<span style='color: orange'>Match ist schon in DB<br></span>";
					$newdata = [$result['groupID'],$result['team1ID'],$result['team2ID'],$result['round']];
					if ($matchinDB[0] == $newdata) {
						$returnArr["echo"] .= "<span style='color: yellow'>Daten sind unverändert<br></span>";
					} else {
						$returnArr["echo"] .= "<span style='font-size: 30px; color: orange'>neue Daten, Update:<br></span>" . json_encode($matchinDB[0]) . "<br>" . json_encode($newdata) . "<br>";
						$returnArr["changes"][0]++;
						$returnArr["changes"][1][] = [$matchinDB[0],$newdata];
						$dbcn->query("UPDATE matches SET Team1ID = {$result['team1ID']}, Team2ID = {$result['team2ID']}, round = {$result['round']} WHERE MatchID = {$result['matchID']} AND GroupID = {$result['groupID']}");
					}
				}
			}
		} else {
			$returnArr["echo"] .= "<span style='color: red'>Fehler beim Aufrufen von Toornament". "<br></span>";
		}
	}
	$returnArr["echo"] .= "<br>";
	return $returnArr;
}

function scrape_toornament_matches($tournID,$matchID,$test=FALSE) {
    $returnArr = array("return"=>0, "echo"=>"<span style='color: blue'>writing Matches:<br></span>", "changes"=>[0, []]);
	if ($test) {
		return $returnArr;
	}
    global $dbservername, $dbusername, $dbpassword, $dbdatabase, $dbport;
    $dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);

    if ($dbcn -> connect_error){
        $returnArr["return"] = 1;
        $returnArr["echo"] .= "<span style='color: red'>Database Connection failed : " . $dbcn->connect_error . "<br><br></span>";
        return $returnArr;
    }
    $tourn_check = $dbcn->query("SELECT TournamentID FROM tournaments WHERE TournamentID = {$tournID}")->fetch_all();
    if ($tourn_check == NULL) {
        $returnArr["return"] = 1;
        $returnArr["echo"] .= "<span style='color: orangered'>angefragtes Turnier nicht in Datenbank<br><br></span>";
        return $returnArr;
    }

    $toorURL1 = "https://play.toornament.com/en_GB/tournaments/";
    $response = get_headers($toorURL1 . $tournID . "/matches/" . $matchID . "/");

    if (str_contains($response[0],"200")) {
        $results = [];
        $html = file_get_html($toorURL1 . $tournID . "/matches/" . $matchID . "/");
        $results["date"] = date("Y-m-d H:i:s",strtotime($html->find("div.match.format-info datetime-view",0)->value));
        $results["played"] = ($html->find("div.match.format-info div.value",1)->plaintext == "completed") ? 1 : 0;
        $bestOf = count($html->find("div.match.format-info div.title"));
        $results["bestOf"] = $bestOf;
        $Score = $html->find("div.primary div.state div.result");
        if (count($Score)>0) {
			// matchresult: 0 draw or notplayed - 1 team1 win - 2 team2 win
			$matchresult = 0;
			$s1class = explode(' ', $Score[0]->find(".result-1",0)->class);
			$s2class = explode(' ', $Score[0]->find(".result-2",0)->class);
			if (in_array('win',$s1class)) {
				$matchresult = 1;
			} elseif (in_array('win', $s2class)) {
				$matchresult = 2;
			}
			$S1 = trim($Score[0]->find(".result-1",0)->plaintext);
			$S2 = trim($Score[0]->find(".result-2",0)->plaintext);
			if ($bestOf == 1) {
                $S1 = ($S1 == "-") ? NULL : (($S1 == "W") ? 1 : intval($S1));
                $S2 = ($S2 == "-") ? NULL : (($S2 == "W") ? 1 : intval($S2));
            }
            if ($bestOf == 2) {
                $S1 = ($S1 == "-") ? NULL : (($S1 == "W") ? 2 : (($S1 == "D") ? 1 : intval($S1)));
                $S2 = ($S2 == "-") ? NULL : (($S2 == "W") ? 2 : (($S2 == "D") ? 1 : intval($S2)));
            }
            if ($bestOf == 3) {
                $S1 = ($S1 == "-") ? NULL : (($S1 == "W") ? 2 : (($S1 == "L") ? -1 : intval($S1)));
                $S2 = ($S2 == "-") ? NUll : (($S2 == "W") ? 2 : (($S2 == "L") ? -1 : intval($S2)));
            }
            if ($bestOf == 5) {
                $S1 = ($S1 == "-") ? NULL : (($S1 == "W") ? 3 : (($S1 == "L") ? -1 : intval($S1)));
                $S2 = ($S2 == "-") ? NULL : (($S2 == "W") ? 3 : (($S2 == "L") ? -1 : intval($S2)));
            }
        } else {
            $S1 = NULL;
            $S2 = NULL;
			$matchresult = 0;
        }
        $results["T1Score"] = $S1;
        $results["T2Score"] = $S2;
		$results["Winner"] = $matchresult;

        $matchinDB = $dbcn->query("SELECT Team1Score, Team2Score, plannedDate, played, bestOf, Winner FROM matches WHERE MatchID = {$matchID}")->fetch_all();
        $newdata = [strval($results["T1Score"]), strval($results["T2Score"]), strval($results["date"]), strval($results["played"]), strval($results["bestOf"]), strval($results["Winner"])];
        if ($matchinDB[0] == $newdata) {
            $returnArr["echo"] .= "<span style='color: yellow'>Daten sind unverändert<br></span>";
        } else {
            $returnArr["echo"] .= "<span style='font-size: 30px; color: orange'>neue Daten, Update:<br></span>" . json_encode($matchinDB[0]) . "<br>" . json_encode($newdata) . "<br>";
            $returnArr["changes"][0]++;
            $returnArr["changes"][1][] = [$matchinDB[0], $newdata];
            $results["T1Score"] = (strval($results["T1Score"]) == NULL) ? "NULL" : $results["T1Score"];
            $results["T2Score"] = (strval($results["T2Score"]) == NULL) ? "NULL" : $results["T2Score"];
            $dbcn->query("UPDATE matches SET Team1Score = {$results["T1Score"]}, Team2Score = {$results["T2Score"]}, plannedDate = '{$results["date"]}', played = {$results["played"]}, bestOf = {$results["bestOf"]}, Winner = {$results["Winner"]} WHERE MatchID = {$matchID}");
        }
    } else {
        $returnArr["echo"] .= "<span style='color: red'>Fehler beim Aufrufen von Toornament". "<br></span>";
    }
    $returnArr["echo"] .= "<br>";
    return $returnArr;
}
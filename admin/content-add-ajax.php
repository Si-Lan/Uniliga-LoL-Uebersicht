<?php
$type = $_REQUEST["type"];

if ($type == "create_tournament_buttons") {
	$dbservername = $dbdatabase = $dbusername = $dbpassword = $dbport = NULL;
    include('../DB-info.php');

    $toor_tourn_url = "https://play.toornament.com/de/tournaments/";

    try {
        $dbcn = new mysqli($dbservername, $dbusername, $dbpassword, $dbdatabase, $dbport);

        if ($dbcn->connect_error) {
            echo "Database Connection failed : " . $dbcn->connect_error;
        } else {
            $toornamentsRes = $dbcn->query("SELECT name, split, season, imgID, TournamentID FROM tournaments ORDER BY TournamentID DESC");
            $toornaments = $toornamentsRes->fetch_all();

            if ($toornaments != NULL) {
                echo "<div class='refresh-button refresh-tournaments' onclick='create_tournament_buttons()'>
                        Refresh
                    </div>";
                for ($i = 0; $i < count($toornaments); $i++) {
                    $currTournName = $toornaments[$i][0];
                    $currTournID = $toornaments[$i][4];


                    $teamNum = $dbcn->query("SELECT COUNT(TeamID) FROM teams WHERE TournamentID = $currTournID")->fetch_all()[0][0];
                    $PlayerNum = $dbcn->query("SELECT COUNT(PlayerID) FROM players WHERE TournamentID = $currTournID")->fetch_all()[0][0];
                    $DivNum = $dbcn->query("SELECT COUNT(DivID) FROM divisions WHERE TournamentID = $currTournID")->fetch_all()[0][0];
                    $GroupNum = $dbcn->query("SELECT COUNT(`groups`.GroupID) FROM `groups`,divisions WHERE divisions.TournamentID = {$currTournID} AND `groups`.DivID = divisions.DivID")->fetch_all()[0][0];
                    $teamsInGroupNum = $dbcn->query("SELECT COUNT(teamsingroup.TeamID) FROM teamsingroup,`groups`,divisions WHERE divisions.TournamentID = $currTournID AND `groups`.DivID = divisions.DivID AND teamsingroup.GroupID = `groups`.GroupID")->fetch_all()[0][0];
                    $matchesNum = $dbcn->query("SELECT COUNT(matches.MatchID) FROM matches,`groups`,divisions WHERE divisions.TournamentID = $currTournID AND `groups`.DivID = divisions.DivID AND matches.GroupID = `groups`.GroupID")->fetch_all()[0][0];
					$playoffsNum = $dbcn->execute_query("SELECT COUNT(playoffs.PlayoffID) FROM playoffs WHERE playoffs.TournamentID = ?",[$currTournID])->fetch_row()[0];
					$playoffmatchNum = $dbcn->execute_query("SELECT COUNT(playoffmatches.PlayoffID) FROM playoffmatches,playoffs WHERE playoffs.TournamentID = ? AND playoffmatches.PlayoffID = playoffs.PlayoffID",[$currTournID])->fetch_row()[0];

                    echo "<div class='turnier-button-wrap'>";
                    echo "<div class=\"turnier-button $currTournID\">
                        <span><a href='https://play.toornament.com/de/tournaments/$currTournID/'>$currTournName</a></span>
                    </div>";

                    echo "<div class='tbutton-act turnier-button-open do-open $currTournID tbutton-last' onclick='toggle_tournament_actions(\"$currTournID\")'>
                            Open Information and Actions &nbsp<img src='../icons/material/expand_more.svg' alt='ausklappen'>
                        </div>";
                    echo "<div class='tbutton-act-wrap $currTournID'>";
                    echo "<a class='tbutton-act get turnier-button-add-data dgreen $currTournID' href='../cron-jobs/download_team_img.php?t=$currTournID'>
                            download Team Logos
                        </a>";
                    echo "<div class='tbutton-act get turnier-button-add-teams green $currTournID followed' onclick=\"get_teams('$currTournID')\">
                            Get all Teams &nbsp<i>($teamNum)</i>
                        </div>";
                    echo "<div class='tbutton-act get turnier-button-add-players green $currTournID followed' onclick=\"get_players('$currTournID')\">
                            Get Players for all Teams &nbsp<i>($PlayerNum)</i>
                        </div>";
                    echo "<div class='tbutton-act get turnier-button-add-divisions green $currTournID followed' onclick=\"get_divisions('$currTournID')\">
                            Get Divisions &nbsp<i>($DivNum)</i>
                        </div>";
                    echo "<div class='tbutton-act get turnier-button-add-groups green $currTournID followed' onclick=\"get_groups('$currTournID')\">
                            Get Groups &nbsp<i>($GroupNum)</i>
                        </div>";
					echo "<div class='tbutton-act get turnier-button-add-groups-deletem orange $currTournID followed' onclick=\"get_groups('$currTournID',true)\">
                            Get Groups (delete missing) &nbsp<i>($GroupNum)</i>
                        </div>";
                    echo "<div class='tbutton-act get turnier-button-add-teams-groups green $currTournID followed' onclick=\"get_teams_in_groups('$currTournID')\">
                            Get Standings / match Teams to Groups &nbsp<i>($teamsInGroupNum)</i>
                        </div>";
					echo "<div class='tbutton-act get turnier-button-add-teams-groups-deletem orange $currTournID followed' onclick=\"get_teams_in_groups('$currTournID',true)\">
                            Get Standings / match Teams to Groups (delete missing) &nbsp<i>($teamsInGroupNum)</i>
                        </div>";
                    echo "<div class='tbutton-act get turnier-button-add-matches-groups green $currTournID followed' onclick=\"get_matches_from_groups('$currTournID')\">
                            Get Matches &nbsp<i>($matchesNum)</i>
                        </div>";
                    echo "<div class='tbutton-act get turnier-button-add-matches green $currTournID followed' onclick=\"get_matches('$currTournID')\">
                            Get Match-Results for all Matches
                        </div>";
					echo "<div class='tbutton-act get turnier-button-add-matches-unplayed green $currTournID followed' onclick=\"get_matches('$currTournID', false)\">
                            Get Match-Results for unplayed Matches
                        </div>";
					echo "<div class='tbutton-act get turnier-button-add-playoffs green $currTournID followed' onclick=\"get_playoffs('$currTournID')\">
                            Get Playoffs &nbsp<i>($playoffsNum)</i>
                        </div>";
					echo "<div class='tbutton-act get turnier-button-add-playoffs-matches green $currTournID followed' onclick=\"get_playoffs_matches('$currTournID')\">
                            Get Playoff-Matches &nbsp<i>($playoffmatchNum)</i>
                        </div>";
					echo "<div class='tbutton-act get turnier-button-add-playoffs-matches-details green $currTournID followed' onclick=\"get_matches('$currTournID', true, true)\">
                            Get Playoff-Match-Details for all
                        </div>";
					echo "<div class='tbutton-act get turnier-button-add-playoffs-matches-details-unplayed green $currTournID tbutton-last' onclick=\"get_matches('$currTournID', false, true)\">
                            Get Playoff-Match-Details for unplayed
                        </div>";
                    echo "</div>";

                    echo "<div class='all-get-result no-res get-result $currTournID'></div>";
                    echo "</div>";
                }
            }
        }
    } catch (Exception $e) {
        echo "Database Connection failed";
    }
}
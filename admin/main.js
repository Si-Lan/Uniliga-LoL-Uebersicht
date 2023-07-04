// noinspection JSPotentiallyInvalidUsageOfThis

// AJAX-Functions
function get_toor_tournaments() {
    let tournID = document.getElementById("input-tournament-id").value;
    let xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function() {
        if (this.readyState === 4 && this.status === 200) {
            console.log(this.responseText);
            if(this.responseText === "") {
                if (!(document.getElementsByClassName("turnier-get-result")[0].classList.contains('no-res'))) {
                    document.getElementsByClassName("turnier-get-result")[0].classList.add('no-res');
                }
            } else {
                if (document.getElementsByClassName("turnier-get-result")[0].classList.contains('no-res')) {
                    document.getElementsByClassName("turnier-get-result")[0].classList.remove('no-res');
                }
            }
            document.getElementsByClassName("turnier-get-result")[0].innerHTML = this.responseText;
        }
    };
    xmlhttp.open("GET", "scrapeToor-ajax.php?type=tournaments&id=" + tournID, true);
    xmlhttp.send();
}
function clear_tourn_res_info(){
    if (!(document.getElementsByClassName("turnier-get-result")[0].classList.contains('no-res'))) {
        document.getElementsByClassName("turnier-get-result")[0].classList.add('no-res');
        document.getElementsByClassName("turnier-get-result")[0].innerHTML = "";
    }
}

function clear_all_res_info(tournID) {
    if (!(document.getElementsByClassName("all-get-result " + tournID)[0].classList.contains('no-res'))) {
        document.getElementsByClassName("all-get-result " + tournID)[0].classList.add('no-res');
        document.getElementsByClassName("all-get-result " + tournID)[0].innerHTML = "";
    }
}

function get_teams(tournID) {
    if(!(document.getElementsByClassName("turnier-button-add-teams " + tournID)[0].classList.contains('loading-data'))) {
        document.getElementsByClassName("turnier-button-add-teams " + tournID)[0].classList.add('loading-data');
        document.getElementsByClassName("turnier-button-add-teams " + tournID)[0].innerHTML = "loading Data ...  Please Wait&nbsp<div class=\"lds-dual-ring\"></div>";
        document.getElementsByClassName("turnier-button-add-teams " + tournID)[0].setAttribute("onclick", "");
    }
    let xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        if (this.readyState === 4 && this.status === 200) {
            if (document.getElementsByClassName("all-get-result " + tournID)[0].classList.contains('no-res')) {
                document.getElementsByClassName("all-get-result " + tournID)[0].classList.remove('no-res');
            }
            document.getElementsByClassName("all-get-result " + tournID)[0].innerHTML = this.responseText;
            let new_num = new XMLHttpRequest();
            new_num.onreadystatechange = function () {
                if (this.readyState === 4 && this.status === 200) {
                    let num = this.responseText;
                    document.getElementsByClassName("turnier-button-add-teams " + tournID)[0].classList.remove('loading-data');
                    document.getElementsByClassName("turnier-button-add-teams " + tournID)[0].innerHTML = "Get all Teams &nbsp<i>("+num+" in DB)</i>";
                    document.getElementsByClassName("turnier-button-add-teams " + tournID)[0].setAttribute("onclick", "get_teams('" + tournID + "')");
                }
            };
            new_num.open("GET","../ajax-functions/get-DB-AJAX.php?type=number-teams&tournament="+tournID);
            new_num.send();
        }
    };
    xmlhttp.open("GET", "scrapeToor-ajax.php?type=teams&id=" + tournID, true);
    xmlhttp.send();
}

function get_players(tournID) {
    console.log("----- Start Players -----");
    let currButton = $("div.turnier-button-add-players."+tournID);
    if (!(currButton.hasClass('loading-data'))) {
        $(".tbutton-act.get."+tournID).addClass('loading-data');
        currButton.html("loading Data ...  Please Wait&nbsp<div class=\"lds-dual-ring\"></div>");
        set_all_actions_onclick(tournID,0);
    }
    $(".all-get-result."+tournID).html("<div class='all-get-result-content'><div class='clear-button' onclick=\"clear_all_res_info('"+ tournID +"')\">clear</div></div>");

    let xmlhttpT = new XMLHttpRequest();
    xmlhttpT.onreadystatechange = async function () {
        if (this.readyState === 4 && this.status === 200) {
            let teams = JSON.parse(this.responseText);
            console.log("Teams got:");
            console.log(teams);
            let loops_done = 0;
            let max_loops = teams.length;

            for (let i = 0; i < teams.length; i++) {
                console.log("starting with Team " + (i+1));
                let xmlhttp = new XMLHttpRequest();
                xmlhttp.onreadystatechange = function () {
                    if (this.readyState === 4 && this.status === 200) {
                        loops_done++;
                        console.log("Team " + (i+1) + " ready");
                        $(".all-get-result." + tournID).removeClass('no-res');
                        let container = $(".all-get-result." + tournID + " .all-get-result-content");
                        let result = tryParseJSONObject(this.responseText);
                        if (result) {
                            console.log(result);
                            container.append(result[0]);
                        } else {
                            console.log(this.responseText);
                            container.append("error in output, check console<br>");
                        }
                        container.scrollTop(container.prop("scrollHeight"));
                        if (loops_done >= max_loops) {
                            let new_num = new XMLHttpRequest();
                            new_num.onreadystatechange = function () {
                                if (this.readyState === 4 && this.status === 200) {
                                    let num = this.responseText;
                                    $(".tbutton-act.get." + tournID).removeClass('loading-data');
                                    $("div.turnier-button-add-players."+tournID).html("Get Players for all Teams &nbsp<i>("+num+" in DB)</i>");
                                    set_all_actions_onclick(tournID, 1);
                                }
                            };
                            new_num.open("GET","../ajax-functions/get-DB-AJAX.php?type=number-players&tournament="+tournID);
                            new_num.send();
                            console.log("----- Players Done -----");
                        }
                    }
                };
                if ((i + 1) % 2 === 0) {
                    console.log("---- Call #" + (i + 1));
                    console.log("-- sleep --")
                    await new Promise(r => setTimeout(r, 1000));
                    console.log("-- slept --");
                }
                xmlhttp.open("GET", "scrapeToor-ajax.php?type=players&id="+tournID+"&teamid="+teams[i]["TeamID"]);
                xmlhttp.send();
            }
        }
    };
    xmlhttpT.open("GET", "../ajax-functions/get-DB-ajax.php?type=teams&Tid="+tournID, true);
    xmlhttpT.send();
}

function get_divisions(tournID) {
    if(!(document.getElementsByClassName("turnier-button-add-divisions " + tournID)[0].classList.contains('loading-data'))) {
        document.getElementsByClassName("turnier-button-add-divisions " + tournID)[0].classList.add('loading-data');
        document.getElementsByClassName("turnier-button-add-divisions " + tournID)[0].innerHTML = "loading Data ...  Please Wait&nbsp<div class=\"lds-dual-ring\"></div>";
        document.getElementsByClassName("turnier-button-add-divisions " + tournID)[0].setAttribute("onclick", "");
    }
    let xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        if (this.readyState === 4 && this.status === 200) {
            if (document.getElementsByClassName("all-get-result " + tournID)[0].classList.contains('no-res')) {
                document.getElementsByClassName("all-get-result " + tournID)[0].classList.remove('no-res');
            }
            document.getElementsByClassName("all-get-result " + tournID)[0].innerHTML = this.responseText;
            let new_num = new XMLHttpRequest();
            new_num.onreadystatechange = function () {
                if (this.readyState === 4 && this.status === 200) {
                    let num = this.responseText;
                    document.getElementsByClassName("turnier-button-add-divisions " + tournID)[0].classList.remove('loading-data');
                    document.getElementsByClassName("turnier-button-add-divisions " + tournID)[0].innerHTML = "Get Divisions &nbsp<i>("+num+" in DB)</i>";
                    document.getElementsByClassName("turnier-button-add-divisions " + tournID)[0].setAttribute("onclick", "get_divisions('" + tournID + "')");
                }
            };
            new_num.open("GET","../ajax-functions/get-DB-AJAX.php?type=number-divs&tournament="+tournID);
            new_num.send();
        }
    };
    xmlhttp.open("GET", "scrapeToor-ajax.php?type=divisions&id=" + tournID, true);
    xmlhttp.send();
}

function get_groups(tournID, del=false) {
    if(!(document.getElementsByClassName("turnier-button-add-groups " + tournID)[0].classList.contains('loading-data'))) {
        document.getElementsByClassName("turnier-button-add-groups " + tournID)[0].classList.add('loading-data');
        document.getElementsByClassName("turnier-button-add-groups " + tournID)[0].innerHTML = "loading Data ...  Please Wait&nbsp<div class=\"lds-dual-ring\"></div>";
        document.getElementsByClassName("turnier-button-add-groups " + tournID)[0].setAttribute("onclick", "");
    }
    let xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        if (this.readyState === 4 && this.status === 200) {
            if (document.getElementsByClassName("all-get-result " + tournID)[0].classList.contains('no-res')) {
                document.getElementsByClassName("all-get-result " + tournID)[0].classList.remove('no-res');
            }
            document.getElementsByClassName("all-get-result " + tournID)[0].innerHTML = this.responseText;
            let new_num = new XMLHttpRequest();
            new_num.onreadystatechange = function () {
                if (this.readyState === 4 && this.status === 200) {
                    let num = this.responseText;
                    document.getElementsByClassName("turnier-button-add-groups " + tournID)[0].classList.remove('loading-data');
                    document.getElementsByClassName("turnier-button-add-groups " + tournID)[0].innerHTML = "Get Groups &nbsp<i>("+num+" in DB)";
                    document.getElementsByClassName("turnier-button-add-groups " + tournID)[0].setAttribute("onclick", "get_groups('" + tournID + "')");
                }
            };
            new_num.open("GET","../ajax-functions/get-DB-AJAX.php?type=number-groups&tournament="+tournID);
            new_num.send();
        }
    };
    if (!del) {
        xmlhttp.open("GET", "scrapeToor-ajax.php?type=groups&id=" + tournID, true);
    } else {
        xmlhttp.open("GET", "scrapeToor-ajax.php?type=groups&id=" + tournID + "&delete", true);
    }
    xmlhttp.send();
}

function get_teams_in_groups(tournID, del=false) {
    if(!(document.getElementsByClassName("turnier-button-add-teams-groups " + tournID)[0].classList.contains('loading-data'))) {
        document.getElementsByClassName("turnier-button-add-teams-groups " + tournID)[0].classList.add('loading-data');
        document.getElementsByClassName("turnier-button-add-teams-groups " + tournID)[0].innerHTML = "loading Data ...  Please Wait&nbsp<div class=\"lds-dual-ring\"></div>";
        document.getElementsByClassName("turnier-button-add-teams-groups " + tournID)[0].setAttribute("onclick", "");
    }
    let xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        if (this.readyState === 4 && this.status === 200) {
            if (document.getElementsByClassName("all-get-result " + tournID)[0].classList.contains('no-res')) {
                document.getElementsByClassName("all-get-result " + tournID)[0].classList.remove('no-res');
            }
            document.getElementsByClassName("all-get-result " + tournID)[0].innerHTML = this.responseText;
            let new_num = new XMLHttpRequest();
            new_num.onreadystatechange = function () {
                if (this.readyState === 4 && this.status === 200) {
                    let num = this.responseText;
                    document.getElementsByClassName("turnier-button-add-teams-groups " + tournID)[0].classList.remove('loading-data');
                    document.getElementsByClassName("turnier-button-add-teams-groups " + tournID)[0].innerHTML = "Get Standings / match Teams to Groups  &nbsp<i>("+num+" in DB)";
                    document.getElementsByClassName("turnier-button-add-teams-groups " + tournID)[0].setAttribute("onclick", "get_teams_in_groups('" + tournID + "')");
                }
            };
            new_num.open("GET","../ajax-functions/get-DB-AJAX.php?type=number-teamsingroup&tournament="+tournID);
            new_num.send();
        }
    };
    if (!del) {
        xmlhttp.open("GET", "scrapeToor-ajax.php?type=teams-in-groups&id=" + tournID, true);
    } else {
        xmlhttp.open("GET", "scrapeToor-ajax.php?type=teams-in-groups&id=" + tournID + "&delete", true);
    }
    xmlhttp.send();
}

function get_matches_from_groups(tournID) {
    console.log("----- Start Matches from Groups -----");
    let currButton = $("div.turnier-button-add-matches-groups."+tournID);
    if(!(currButton.hasClass('loading-data'))) {
        $(".tbutton-act.get."+tournID).addClass('loading-data');
        currButton.html("loading Data ...  Please Wait&nbsp<div class=\"lds-dual-ring\"></div>");
        set_all_actions_onclick(tournID,0);
    }
    document.getElementsByClassName("all-get-result " + tournID)[0].innerHTML = "<div class='all-get-result-content'><div class='clear-button' onclick=\"clear_all_res_info('"+ tournID +"')\">clear</div></div>";

    let xmlhttpDivs = new XMLHttpRequest();
    xmlhttpDivs.onreadystatechange = async function () {
        if (this.readyState === 4 && this.status === 200) {
            let divs = JSON.parse(this.responseText);
            console.log("divisions got:");
            console.log(divs);
            let loops_done = 0;
            let max_loops = 0;

            for (let i_d = 0; i_d < divs.length; i_d++) {
                console.log("starting in division " + (i_d+1));

                let xmlhttpGroup = new XMLHttpRequest();
                xmlhttpGroup.onreadystatechange = async function () {
                    // noinspection JSPotentiallyInvalidUsageOfThis
                    if (this.readyState === 4 && this.status === 200) {
                        // noinspection JSPotentiallyInvalidUsageOfThis
                        let groups = JSON.parse(this.responseText);
                        console.log("groups got:");
                        console.log(groups);
                        max_loops += groups.length;

                        for (let i_g = 0; i_g < groups.length; i_g++) {
                            console.log("starting in div " + (i_d+1) + " group " + (i_g+1));

                            let xmlhttpMatch = new XMLHttpRequest();
                            xmlhttpMatch.onreadystatechange = function () {
                                if (this.readyState === 4 && this.status === 200) {
                                    loops_done++;
                                    console.log("div " + (i_d+1) + " group " + i_g + " ready");
                                    if (document.getElementsByClassName("all-get-result " + tournID)[0].classList.contains('no-res')) {
                                        document.getElementsByClassName("all-get-result " + tournID)[0].classList.remove('no-res');
                                    }
                                    let result = JSON.parse(this.responseText);
                                    console.log(result);
                                    $(".all-get-result." + tournID + " .all-get-result-content").append(result[0]);
                                    if (loops_done >= max_loops) {
                                        let new_num = new XMLHttpRequest();
                                        new_num.onreadystatechange = function () {
                                            if (this.readyState === 4 && this.status === 200) {
                                                let num = this.responseText;
                                                $(".tbutton-act.get."+tournID).removeClass('loading-data');
                                                $("div.turnier-button-add-matches-groups."+tournID).html("Get Matches &nbsp<i>("+num+" in DB)");
                                                set_all_actions_onclick(tournID,1);
                                            }
                                        };
                                        new_num.open("GET","../ajax-functions/get-DB-AJAX.php?type=number-matches&tournament="+tournID);
                                        new_num.send();
                                        console.log("----- Matches from Group Done -----");
                                    }

                                }
                            };
                            xmlhttpMatch.open("GET", "scrapeToor-ajax.php?type=matches-from-group&Tid=" + tournID + "&Did=" + divs[i_d]["DivID"] + "&Gid=" + groups[i_g]["GroupID"], true);
                            xmlhttpMatch.send();
                        }

                    }
                };
                xmlhttpGroup.open("GET", "../ajax-functions/get-DB-ajax.php?type=groups&Did=" + divs[i_d]["DivID"], true);
                xmlhttpGroup.send();
            }

        }
    };
    xmlhttpDivs.open("GET", "../ajax-functions/get-DB-ajax.php?type=divisions&Tid=" + tournID, true);
    xmlhttpDivs.send();
}

function get_matches(tournID, all = true) {
    console.log("----- Start Matches -----")
    let currButton;
    if (all) {
        currButton = $("div.turnier-button-add-matches."+tournID);
    } else {
        currButton = $("div.turnier-button-add-matches-unplayed."+tournID);
    }
    if (!(currButton.hasClass('loading-data'))) {
        $(".tbutton-act.get."+tournID).addClass('loading-data');
        currButton.html("loading Data ...  Please Wait&nbsp<div class=\"lds-dual-ring\"></div>");
        set_all_actions_onclick(tournID,0);
    }
    $(".all-get-result."+tournID).html("<div class='all-get-result-content'><div class='clear-button' onclick=\"clear_all_res_info('"+ tournID +"')\">clear</div></div>");

    let xmlhttpM = new XMLHttpRequest();
    xmlhttpM.onreadystatechange = async function () {
        if (this.readyState === 4 && this.status === 200) {
            let matches = JSON.parse(this.responseText);
            console.log("matches got:")
            console.log(matches);
            let loops_done = 0;
            let max_loops = matches.length;
            let container = $(".all-get-result." + tournID + " .all-get-result-content");

            for (let i = 0; i < matches.length; i++) {
                console.log("starting with Match " + (i + 1));
                let xmlhttp = new XMLHttpRequest();
                xmlhttp.onreadystatechange = function () {
                    if (this.readyState === 4 && this.status === 200) {
                        loops_done++;
                        console.log("Match " + (i + 1) + " ready");
                        $(".all-get-result." + tournID).removeClass('no-res');
                        let result = JSON.parse(this.responseText);
                        console.log(result);
                        container.append(result[0]);
                        container.scrollTop(container.prop("scrollHeight"));
                        if (loops_done >= max_loops) {
                            container.append("<br>----- Matches Done -----<br>");
                            container.scrollTop(container.prop("scrollHeight"));
                            $(".tbutton-act.get." + tournID).removeClass('loading-data');
                            if (all) {
                                currButton.html("Get Match-Results for all Matches");
                            } else {
                                currButton.html("Get Match-Results for unplayed Matches");
                            }
                            set_all_actions_onclick(tournID, 1);
                            console.log("----- Matches Done -----");
                        }
                    }
                };
                if ((i + 1) % 2 === 0) {
                    console.log("---- Call #" + (i + 1));
                    console.log("-- sleep --")
                    await new Promise(r => setTimeout(r, 1000));
                    console.log("-- slept --");
                }
                xmlhttp.open("GET", "scrapeToor-ajax.php?type=matches&Tid=" + tournID + "&Mid=" + matches[i]["MatchID"]);
                xmlhttp.send();
            }
        }
    };
    if (all) {
        xmlhttpM.open("GET", "../ajax-functions/get-DB-ajax.php?type=matches&Tid="+tournID,true);
    } else {
        xmlhttpM.open("GET", "../ajax-functions/get-DB-ajax.php?type=matches-unplayed&Tid="+tournID,true);
    }
    xmlhttpM.send();
}

function get_playoffs(tournID) {
    let currButton = $(".turnier-button-add-playoffs." + tournID);
    if(!(currButton.hasClass('loading-data'))) {
        $(".tbutton-act.get."+tournID).addClass('loading-data');
        currButton.html("loading Data ...  Please Wait&nbsp<div class=\"lds-dual-ring\"></div>");
        set_all_actions_onclick(tournID,0);
    }
    let xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        if (this.readyState === 4 && this.status === 200) {
            let result_container = $(".all-get-result."+tournID);
            if (result_container.hasClass('no-res')) {
                result_container.removeClass('no-res');
            }
            result_container.html("<div class='all-get-result-content'><div class='clear-button' onclick=\"clear_all_res_info('"+ tournID +"')\">clear</div></div>");
            let container = $(".all-get-result." + tournID + " .all-get-result-content");
            container.append(this.responseText);
            let new_num = new XMLHttpRequest();
            new_num.onreadystatechange = function () {
                if (this.readyState === 4 && this.status === 200) {
                    let num = this.responseText;
                    currButton.removeClass('loading-data');
                    currButton.html("Get Playoffs &nbsp<i>("+num+" in DB)</i>");
                    set_all_actions_onclick(tournID,1);
                }
            };
            new_num.open("GET","../ajax-functions/get-DB-AJAX.php?type=number-playoffs&tournament="+tournID);
            new_num.send();
        }
    };
    xmlhttp.open("GET", "scrapeToor-ajax.php?type=playoffs&Tid=" + tournID, true);
    xmlhttp.send();
}

function get_playoffs_matches(tournID) {
    let currButton = $(".turnier-button-add-playoffs-matches." + tournID);
    if(!(currButton.hasClass('loading-data'))) {
        $(".tbutton-act.get."+tournID).addClass('loading-data');
        currButton.html("loading Data ...  Please Wait&nbsp<div class=\"lds-dual-ring\"></div>");
        set_all_actions_onclick(tournID,0);
    }
    let result_container = $(".all-get-result."+tournID);
    result_container.html("<div class='all-get-result-content'><div class='clear-button' onclick=\"clear_all_res_info('"+ tournID +"')\">clear</div></div>");

    let xmlhttpPlayoffs = new XMLHttpRequest();
    xmlhttpPlayoffs.onreadystatechange = async function () {
        if (this.readyState === 4 && this.status === 200) {

            let playoffs = JSON.parse(this.responseText);
            let loops_done = 0;

            let max_loops = playoffs.length;
            for (let i_p = 0; i_p < playoffs.length; i_p++) {
                let xmlhttp = new XMLHttpRequest();
                xmlhttp.onreadystatechange = async function () {
                    if (this.readyState === 4 && this.status === 200) {
                        loops_done++;
                        if (result_container.hasClass('no-res')) {
                            result_container.removeClass('no-res');
                        }
                        let container = $(".all-get-result." + tournID + " .all-get-result-content");
                        let result = JSON.parse(this.responseText);
                        container.append(result[0]);
                        if (loops_done >= max_loops) {
                            let new_num = new XMLHttpRequest();
                            new_num.onreadystatechange = function () {
                                if (this.readyState === 4 && this.status === 200) {
                                    let num = this.responseText;
                                    $(".tbutton-act.get."+tournID).removeClass('loading-data');
                                    currButton.html("Get Playoff-Matches &nbsp<i>(" + num + " in DB)</i>");
                                    set_all_actions_onclick(tournID, 1);
                                }
                            };
                            new_num.open("GET", "../ajax-functions/get-DB-AJAX.php?type=number-playoff-matches&tournament=" + tournID);
                            new_num.send();
                        }
                    }
                };
                xmlhttp.open("GET", "scrapeToor-ajax.php?type=playoff-matchups&Tid="+tournID+"&Pid="+playoffs[i_p]["PlayoffID"], true);
                xmlhttp.send();
            }
        }
    };
    xmlhttpPlayoffs.open("GET", "../ajax-functions/get-DB-ajax.php?type=playoffs&Tid="+tournID, true);
    xmlhttpPlayoffs.send();
}

function get_playoffs_matches_details(tournID) {

}

function set_all_actions_onclick(tournID,set) {
    if (set === 1) {
        $(".turnier-button-add-teams."+tournID).attr("onClick","get_teams('"+tournID+"')");
        $(".turnier-button-add-players."+tournID).attr("onClick","get_players('"+tournID+"')");
        $(".turnier-button-add-divisions."+tournID).attr("onClick","get_divisions('"+tournID+"')");
        $(".turnier-button-add-groups."+tournID).attr("onClick","get_groups('"+tournID+"')");
        $(".turnier-button-add-groups-deletem."+tournID).attr("onClick","get_groups('"+tournID+"', true)");
        $(".turnier-button-add-teams-groups."+tournID).attr("onClick","get_teams_in_groups('"+tournID+"')");
        $(".turnier-button-add-teams-groups-deletem."+tournID).attr("onClick","get_teams_in_groups('"+tournID+"', true)");
        $(".turnier-button-add-matches-groups."+tournID).attr("onClick","get_matches_from_groups('"+tournID+"')");
        $(".turnier-button-add-matches."+tournID).attr("onClick","get_matches('"+tournID+"')");
        $(".turnier-button-add-matches-unplayed."+tournID).attr("onClick","get_matches('"+tournID+"', false)");
        $(".turnier-button-add-playoffs."+tournID).attr("onClick","get_playoffs('"+tournID+"')");
        $(".turnier-button-add-playoffs-matches."+tournID).attr("onClick","get_playoffs_matches('"+tournID+"')");
        $(".turnier-button-add-playoffs-matches-details."+tournID).attr("onClick","get_playoffs_matches_details('"+tournID+"')");
    } else if (set === 0) {
        $(".tbutton-act.get."+tournID).attr("onClick","");
    }
}

// run on page start functions
$(document).ready(create_tournament_buttons());
function create_tournament_buttons() {
    //console.log('create')
    let ref_button = document.getElementsByClassName('refresh-tournaments')[0] ?? null;
    if (ref_button != null) {
        ref_button.innerHTML = "Refreshing...";

    }
    let xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function() {
        if (this.readyState === 4 && this.status === 200) {
            document.getElementsByClassName("turnier-select")[0].innerHTML = this.responseText;
        }
    };
    xmlhttp.open("GET","content-add-ajax.php?type=create_tournament_buttons",true);
    xmlhttp.send();
    return 0;
}


// Page-JS
function toggle_tournament_actions(tournament_id) {
 let open_button = document.getElementsByClassName('turnier-button-open ' + tournament_id)[0];
 let button_wrap = document.getElementsByClassName('tbutton-act-wrap ' + tournament_id)[0];
 if (open_button.classList.contains('do-open')) {
     open_button.innerHTML = "Close Information and Actions &nbsp<img src='../icons/material/expand_less.svg' alt='einklappen'>";
     open_button.classList.remove('do-open');
     open_button.classList.add('do-close');
     if (!(button_wrap.classList.contains('open'))) {
         button_wrap.classList.add('open');
     }
     if (open_button.classList.contains('tbutton-last')) {
         open_button.classList.remove('tbutton-last');
     }
 } else if (open_button.classList.contains('do-close')) {
     open_button.innerHTML = "Add Information and Actions &nbsp<img src='../icons/material/expand_more.svg' alt='ausklappen'>";
     open_button.classList.remove('do-close');
     open_button.classList.add('do-open');
     if (button_wrap.classList.contains('open')) {
         button_wrap.classList.remove('open');
     }
     if (!(open_button.classList.contains('tbutton-last'))) {
         open_button.classList.add('tbutton-last');
     }
 }
}

function tryParseJSONObject (jsonString){
    try {
        let o = JSON.parse(jsonString);
        if (o && typeof o === "object") {
            return o;
        }
    }
    catch (e) { }

    return false;
}
<?php
/**
 * Created by PhpStorm.
 * User: khaled
 * Date: 3/6/16
 * Time: 12:36 PM
 */
include "../states.php";
require $_SERVER['DOCUMENT_ROOT'] . "/../vendor/autoload.php";

class VoteThinker {

}

$db = new MysqliDb('localhost', "a", "a", 'countthebern');
$db->get('states');
$totalElections = $db->count;
$db->where('total_cast_votes', 0, ">");
$statesVoted = $db->get('states');
$votedStates = $db->count;
$vType = array("Open", "Closed", "Modified");
$electType = array("Primary", "Caucus");
$regions = array("NORTHEAST", "MIDWEST", "SOUTH", "WEST", "TERRITORIES");

function getData($cId, $elig=null, $elect=null, $region = null) {
    $db = new MysqliDb('localhost', "a", "a", 'countthebern');
    $db->join("states s", "s.state_abbr=v.state", "LEFT");
    $db->where('v.candidate_id', $cId);
    if($elect) {
        $db->where('s.election_type', $elect);
    }
    if($elig) {
        $db->where('s.election_eligible', $elig);
    }
    if($region) {
        $db->where('s.region', $region);
    }
    $db->groupBy('v.candidate_id');
    return(current($db->get ("candidate_state_results v", null, "sum(v.pledged), sum(v.votes)")));
}

function getRegionalWins($region) {
    $wins[1] = 0;
    $wins[2] = 0;
    $db = new MysqliDb('localhost', "a", "a", 'countthebern');
    $db->where('region', $region);
    $states = $db->get ('states');
    foreach($states as $aState) {
        $db->where('state', $aState['state_abbr']);
        $db->orderBy('votes');
        $cmp = $db->getOne ('candidate_state_results');
        if($cmp['votes'] > 0) {
            $id = $cmp['candidate_id'];
            $wins[$id]++;
        }
    }
    return $wins;
}

$total_delegates = $db->get ("states", null, "sum(total_pledge)");
$total_delegates = $total_delegates[0]['sum(total_pledge)'];
$db->where('candidate_id', 6, '!=');
$selected_delegates = $db->get('candidate_state_results', null, 'sum(pledged)');
$pledged_to_date = $selected_delegates[0]['sum(pledged)'];

$hrcTotals = getData(1);
$bsTotals = getData(2);

foreach($regions as $r) {
    $aWins = array();
    $tWins = 0;
    $hrcr[] = getData(1, null, null, $r);
    $bsr[] = getData(2, null, null, $r);
    $avr[] = getData(6, null, null, $r);
    $rwins[] = getRegionalWins($r);
    $aWins = getRegionalWins($r);
    $tWins = $aWins[1]+$aWins[2];
    $vic[$r] = $tWins;
}

foreach($electType as $eT) {
    foreach($vType as $vT) {
        $hrc[] = getData(1, $vT, $eT);
        $bs[] = getData(2, $vT, $eT);
        $av[] = getData(6, $vT, $eT);
    }
}


$rCounter = 0;
$endTotal = array("Bernie"=>0, "Hill"=>0);
foreach($regions as $r) {
    $hill = $hrcr[$rCounter]['sum(v.pledged)'];
    $bernie = $bsr[$rCounter]['sum(v.pledged)'];
    $total = $hill+$bernie;
    $hillShare = $hill / $total;
    $bernieShare = $bernie / $total;
    $remain = $avr[$rCounter]['sum(v.pledged)'];
    $predict[$r]['BernShare'] = $bernieShare;
    $predict[$r]['HillShare'] = $hillShare;
    $predict[$r]['available'] = $remain;
    $predict[$r]['Bernie'] = $remain * $bernieShare;
    $predict[$r]['Hill'] = $remain * $hillShare;
    $endTotal['Bernie'] += $remain * $bernieShare;
    $endTotal['Hill'] += $remain * $hillShare;
    $rCounter++;
}

foreach($electType as $eT) {
    $hrcf[] = getData(1, null, $eT);
    $bsf[] = getData(2, null, $eT);
    $avf[] = getData(6, null, $eT);
}

$rfCounter = 0;
$fendTotal = array("Bernie"=>0, "Hill"=>0);
foreach($electType as $electFormat) {
    $hillf = $hrcf[$rfCounter]['sum(v.pledged)'];
    $bernief = $bsf[$rfCounter]['sum(v.pledged)'];
    $totalf = $hillf+$bernief;
    $hillSharef = $hillf / $totalf;
    $bernieSharef = $bernief / $totalf;
    $remainf = $avf[$rfCounter]['sum(v.pledged)'];
    $predictf[$electFormat]['BernShare'] = $bernieSharef;
    $predictf[$electFormat]['HillShare'] = $hillSharef;
    $predictf[$electFormat]['available'] = $remainf;
    $predictf[$electFormat]['Bernie'] = $remainf * $bernieSharef;
    $predictf[$electFormat]['Hill'] = $remainf * $hillSharef;
    $fendTotal['Bernie'] += $remainf * $bernieSharef;
    $fendTotal['Hill'] += $remainf * $hillSharef;
    $rfCounter++;
}

$rfendTotal = array("Bernie"=>0, "Hill"=>0);
foreach($regions as $r) {
    foreach($electType as $et) {
        $hrcrf[$r][$et] = getData(1, null, $et, $r);
        $bsrf[$r][$et] = getData(2, null, $et, $r);
        $avrf[$r][$et] = getData(6, null, $et, $r);
        $hillrf = $hrcrf[$r][$et]['sum(v.pledged)'];
        $bernierf = $bsrf[$r][$et]['sum(v.pledged)'];
        $totalrf = $hillrf+$bernierf;
        if($totalrf == 0) $totalrf = 1;
        $hillSharerf = $hillrf / $totalrf;
        $bernieSharerf = $bernierf / $totalrf;
        $remainrf = $avrf[$r][$et]['sum(v.pledged)'];
        $predictrf[$r][$et]['BernShare'] = $bernieSharerf;
        $predictrf[$r][$et]['HillShare'] = $hillSharerf;
        $predictrf[$r][$et]['available'] = $remainrf;
        $predictrf[$r][$et]['Bernie'] = $remainrf * $bernieSharerf;
        $predictrf[$r][$et]['Hill'] = $remainrf * $hillSharerf;
        $rfendTotal['Bernie'] += $remainrf * $bernieSharerf;
        $rfendTotal['Hill'] += $remainrf * $hillSharerf;
    }
}






$db->where('candidate_id', 1);
$db->orWhere('candidate_id', 2);
$db->orWhere('candidate_id', 6);
$db->orderBy('state', 'asc');
$db->orderBy('candidate_id', 'asc');
$byStateResults = $db->get('candidate_state_results');
?>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Count The Bern</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="apple-touch-icon" href="apple-touch-icon.png">
    <!-- Place favicon.ico in the root directory -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
    <script type="text/javascript" src="./dist/fusioncharts/js/fusioncharts.js"></script>
    <script type="text/javascript" src="./dist/fusioncharts/js/themes/fusioncharts.theme.fint.js"></script>
    <script type="text/javascript">
        var contestsGauge = {
            "chart": {
            "caption": "Presidential Preference Elections",
                "subcaption": "<?php echo $votedStates." of ".$totalElections." voted"?>",
                "lowerLimit": "0",
                "upperLimit": "<?php echo $totalElections?>",
                "theme": "fint"
            },
            "colorRange": {
            "color": [
                {
                    "minValue": "0",
                    "maxValue": "30",
                    "code": "#88f001"
                },
                {
                    "minValue": "30",
                    "maxValue": "45",
                    "code": "#f2e719"
                },
                {
                    "minValue": "45",
                    "maxValue": "57",
                    "code": "#ff1a31"
                }
            ]
            },
            "dials": {
            "dial": [
                    {
                        "value": "<?php echo $votedStates ?>"
                    }
                ]
            }
        };
        var delegatesGauge = {
            "chart": {
                "caption": "Pledged Delegates",
                "subcaption": "<?php echo $pledged_to_date." of ".$total_delegates." voted"?>",
                "lowerLimit": "0",
                "upperLimit": "<?php echo $total_delegates?>",
                "theme": "fint"
            },
            "colorRange": {
                "color": [
                    {
                        "minValue": "0",
                        "maxValue": "2026",
                        "code": "#88f001"
                    },
                    {
                        "minValue": "2026",
                        "maxValue": "3039",
                        "code": "#f2e719"
                    },
                    {
                        "minValue": "3039",
                        "maxValue": "4051",
                        "code": "#ff1a31"
                    }
                ]
            },
            "dials": {
                "dial": [
                    {
                        "value": "<?php echo $pledged_to_date ?>"
                    }
                ]
            }
        };
        var race = {
            "chart": {
                "caption": "Race for The Democratic Nomination",
                "subCaption": "2,388 Needed for Nomination",
                "yAxisName": "Pledged Delegates",
                "yAxisMaxValue": "4051",
                "paletteColors": "#0075c2",
                "bgColor": "#ffffff",
                "showBorder": "0",
                "showCanvasBorder": "0",
                "usePlotGradientColor": "0",
                "plotBorderAlpha": "10",
                "placeValuesInside": "1",
                "valueFontColor": "#ffffff",
                "showAxisLines": "1",
                "axisLineAlpha": "25",
                "divLineAlpha": "10",
                "alignCaptionWithCanvas": "0",
                "showAlternateVGridColor": "0",
                "captionFontSize": "14",
                "subcaptionFontSize": "14",
                "subcaptionFontBold": "0",
                "toolTipColor": "#ffffff",
                "toolTipBorderThickness": "0",
                "toolTipBgColor": "#000000",
                "toolTipBgAlpha": "80",
                "toolTipBorderRadius": "2",
                "toolTipPadding": "5",
                "labelDisplay" : "auto"

            },
            "data": [
                {
                    "label": "Sanders, Bernard \"Bernie\"",
                    "value": "<?php echo $bsTotals['sum(v.pledged)'] ?>"
                },
                {
                    "label": "Clinton, Hillary Rodham",
                    "value": "<?php echo $hrcTotals['sum(v.pledged)'] ?>"
                }

            ],
        };
            FusionCharts.ready(function () {
                var consGauge = new FusionCharts({
                    "type": "angulargauge",
                    "renderAt": "chart-elections-container",
                    "width": "400",
                    "height": "250",
                    "dataFormat": "json",
                    "dataSource":contestsGauge
                });
                consGauge.render();
                var delsGauge = new FusionCharts({
                    "type": "angulargauge",
                    "renderAt": "chart-delegates-container",
                    "width": "400",
                    "height": "250",
                    "dataFormat": "json",
                    "dataSource":delegatesGauge
                });
                delsGauge.render();
                var raceChart = new FusionCharts({
                    "type": "bar2d",
                    "renderAt": "chart-race-container",
                    "width": "400",
                    "height": "250",
                    "dataFormat": "json",
                    "dataSource":race
                });
                raceChart.render();
            });
    </script>

</head>
<body>
    <div class="head col-lg-12 center">
    <h2>Can Bernie Sanders Still Win the Democratic Nomination?</h2>
    <h1>YES!</h1>
    <h4>The numbers behind Bernie Sanders nomination</h4>
    <h6>Source: http://www.thegreenpapers.com</h6>
        <h5><a href="path.php">See State Delegate Breakdown</a></h5>
    </div>

    <div class="col-lg-12 center" style="padding-bottom:10px; border-bottom:1px solid black; font-size:16px; font-weight:bold;">
        <h5>Predictions For End Results, Based on Regional Performance to Date (2383 Required to Win): </h5>
        <span class="col-lg-6"><p>Bernie Sanders :</p> (Predicted)
            <?php echo round($endTotal['Bernie'], 3) ?>
            + (Current)  <?php echo $bsTotals['sum(v.pledged)']; ?>
            = <?php echo (round($endTotal['Bernie'], 3)+$bsTotals['sum(v.pledged)']); ?></span>
        <span class="col-lg-6"><p>Hillary Clinton :</p> (Predicted)
            <?php echo round($endTotal['Hill'], 3) ?>
            + (Current) <?php echo $hrcTotals['sum(v.pledged)']; ?>
            = <?php echo (round($endTotal['Hill'], 3)+$hrcTotals['sum(v.pledged)']); ?> </span>
    </div>
    <div class="main col-lg-12">

    <div class="col-lg-4">
        <div id="chart-delegates-container">An angular gauge will load here!</div>
    <p>A total of <?php echo $pledged_to_date ?> delegates have been selected from <?php echo $total_delegates ?>, leaving <?php echo $total_delegates - $pledged_to_date ?> to go.</p>
    </div>
    <div class="col-lg-4">
    <div id="chart-elections-container">An angular gauge will load here!</div>
<p>A total of <?php echo $votedStates ?> election have been held of <?php echo $totalElections ?>, leaving <?php echo $totalElections - $votedStates ?> to go.</p>
    </div>
    <div class="col-lg-4">
        <div id="chart-race-container">A column2d chart will load here!</div>
        <p class="big-notice">
        The Nomination Process is just getting warmed up!
    </p>
    </div>


    <div class="col-lg-12">
        <table class="table table-striped table-bordered">
            <thead>
            <tr>
                <th></th>
                <th></th>
                <th colspan="3" >Primary</th>
                <th colspan="3">Caucus</th>
                <th></th>
            </tr>
            <tr>
                <th>Candidate</th>
                <th>Totals</th>
                <th>Open</th>
                <th>Modified</th>
                <th>Closed</th>
                <th>Open</th>
                <th>Modified</th>
                <th>Closed</th>
                <th>Total</th>
                <th>Short</th>
            </tr>
            </thead>
            <tbody>
            <?php
                echo "<tr>\n";
                echo "<td>Sanders, Bernard \"Bernie\"</td>";
                echo "<td>Pledged (Voted) Delegates</td>";
                $total = 0;
                for($i=0; $i<6; $i++) {
                    $total += $bs[$i]['sum(v.pledged)'];
                    echo "<td>".$bs[$i]['sum(v.pledged)']."</td>";
                }
                echo "<td>".$total."</td>";
                echo "<td>".(2383 - $total)."</td>";
                echo "</tr>\n";
                echo "<tr>\n";
                echo "<td>Clinton, Hillary Rodham</td>";
                echo "<td>Pledged (Voted) Delegates</td>";
                $total = 0;
                for($i=0; $i<6; $i++) {
                    $total += $hrc[$i]['sum(v.pledged)'];
                    echo "<td>".$hrc[$i]['sum(v.pledged)']."</td>";
                }
                echo "<td>".$total."</td>";
                echo "<td>".(2383 - $total)."</td>";
                echo "</tr>\n";

                echo "<tr>\n";
                echo "<td>Remaining</td>";
                echo "<td>Pledged (Voted) Delegates</td>";
                $total = 0;
                for($i=0; $i<6; $i++) {
                    $total += $av[$i]['sum(v.pledged)'];
                            echo "<td>".$av[$i]['sum(v.pledged)']."</td>";
                        }
                echo "<td>".$total."</td>";
                echo "<td></td>";
                echo "</tr>\n";
            ?>
            </tbody>
        </table>
    </div>


    <div class="col-lg-12">

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Candidate</th>
                    <th>Totals</th>
                    <th>NORTHEAST</th>
                    <th>MIDWEST</th>
                    <th>SOUTH</th>
                    <th>WEST</th>
                    <th>TERRITORIES</th>
                    <th>TOTAL</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Sanders, Bernard "Bernie"</td>
                    <td>States Won</td>
                    <?php
                    $total = 0;
                    for($i=0; $i<5; $i++) {
                        echo "<td>".$rwins[$i][2]."</td>";
                        $total += $rwins[$i][2];
                    }
                    echo "<td>".$total."</td>";
                    ?>
                </tr>
                <tr>
                    <td></td>
                    <td>Pledged Delegates</td>
                    <?php
                    $total = 0;
                    for($i=0; $i<5; $i++) {
                        $total += $bsr[$i]['sum(v.pledged)'];
                        echo "<td>".$bsr[$i]['sum(v.pledged)']."</td>";
                    }
                    echo "<td>".$total."</td>";
                    ?>
                </tr>
                <tr>
                    <td>Clinton, Hillary Rodham</td>
                    <td>States Won</td>
                    <?php
                        $total = 0;
                        for($i=0; $i<5; $i++) {
                            echo "<td>".$rwins[$i][1]."</td>";
                            $total += $rwins[$i][1];
                        }
                        echo "<td>".$total."</td>";
                    ?>
                </tr>
                <tr>
                    <td></td>
                    <td>Pledged Delegates</td>
                    <?php
                    $total = 0;
                    for($i=0; $i<5; $i++) {
                        $total += $hrcr[$i]['sum(v.pledged)'];
                        echo "<td>".$hrcr[$i]['sum(v.pledged)']."</td>";
                    }
                    echo "<td>".$total."</td>";
                    ?>
                </tr>

                <tr>
                    <td>Remaining</td>
                    <td>States</td>
                    <?php
                    $total = 0;
                    foreach($regions as $region) {
                        $db->where('region', $region);
                        $db->get ('states');
                        $rem = ($db->count - $vic[$region]);
                        echo "<td>".$rem."</td>";
                        $total += $rem;
                    }
                    echo "<td>".$total."</td>";
                    ?>
                </tr>
                <tr>
                    <td></td>
                    <td>Pledged Delegates</td>
                    <?php
                    $total = 0;
                    for($i=0; $i<5; $i++) {
                        $total += $avr[$i]['sum(v.pledged)'];
                        echo "<td>".$avr[$i]['sum(v.pledged)']."</td>";
                    }
                    echo "<td>".$total."</td>";
                    ?>
                </tr>

            </tbody>
        </table>

    </div>
        <div class="col-lg-11 col-lg-offset-1">
            <h3>Predictions By Various Factors (Based on current Delegate Wins)</h3>
        </div>
        <div class="col-lg-12">
            <h4>Predictions By Region</h4>
            <table class="table table-striped table-bordered">
                <thead>
                <tr>
                    <th></th>
                    <?php foreach($regions as $reg) {
                        echo "<th colspan=3>$reg</th>";
                    } ?>
                </tr>
                <tr>
                    <th>Candidate</th>
                    <?php foreach($regions as $reg) {
                            echo "<th>Left</th>";
                            echo "<th>Average Win %</th>";
                            echo "<th>Predict To Win</th>";
                        }
                    echo "<th>Final Count</th>";
                    ?>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>Sanders, Bernard "Bernie"</td>
                    <?php foreach($regions as $reg) {
                        echo "<td>".$predict[$reg]['available']."</td>";
                        echo "<td>".round($predict[$reg]['BernShare'], 4)."</td>";
                        echo "<td>".round($predict[$reg]['Bernie'], 3)."</td>";
                    }?>
                    <td><?php echo $endTotal['Bernie'] ?></td>
                </tr>
                <tr>
                    <td>Clinton, Hillary Rodham</td>
                    <?php foreach($regions as $reg) {
                        echo "<td>".$predict[$reg]['available']."</td>";
                        echo "<td>".round($predict[$reg]['HillShare'], 4)."</td>";
                        echo "<td>".round($predict[$reg]['Hill'], 3)."</td>";
                    }?>
                    <td><?php echo $endTotal['Hill'] ?></td>
                </tr>
                </tbody>
            </table>

        </div>

        <div class="col-lg-12">
            <h4>Predictions By Election Format</h4>
            <table class="table table-striped table-bordered">
                <thead>
                <tr>
                    <th></th>
                    <?php foreach($electType as $et) {
                        echo "<th colspan=3>$et</th>";
                    } ?>
                </tr>
                <tr>
                    <th>Candidate</th>
                    <?php foreach($electType as $et) {
                        echo "<th>Left</th>";
                        echo "<th>Average Win %</th>";
                        echo "<th>Predict To Win</th>";
                    }
                    echo "<th>Final</th>";
                    ?>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>Sanders, Bernard "Bernie"</td>
                    <?php foreach($electType as $et) {
                            echo "<td>".$predictf[$et]['available']."</td>";
                            echo "<td>".round($predictf[$et]['BernShare'], 4)."</td>";
                            echo "<td>".round($predictf[$et]['Bernie'], 3)."</td>";
                        }
                    ?>
                    <td><?php echo $fendTotal['Bernie'] ?></td>
                </tr>
                <tr>
                    <td>Clinton, Hillary Rodham</td>
                    <?php foreach($electType as $et) {
                            echo "<td>".$predictf[$et]['available']."</td>";
                            echo "<td>".round($predictf[$et]['HillShare'], 4)."</td>";
                            echo "<td>".round($predictf[$et]['Hill'], 3)."</td>";
                        }
                    ?>
                    <td><?php echo $fendTotal['Hill'] ?></td>
                </tr>
                </tbody>
            </table>

        </div>

        <div class="col-lg-12">
            <h4>Predictions By Region, By Election Format</h4>
            <table class="table table-striped table-bordered">
                <thead>
                <tr>
                    <th></th>
                    <?php
                        foreach($regions as $r) {
                            echo "<th colspan=6>$r</th>";
                        }
                    ?>
                </tr>
                <tr>
                    <th></th>
                    <?php
                        foreach($regions as $r) {
                            foreach ($electType as $et) {
                                echo "<th colspan=3>$et</th>";
                            }
                        }
                    ?>
                </tr>
                <tr>
                    <th>Candidate</th>
                    <?php
                    foreach($regions as $r) {
                        foreach ($electType as $et) {
                            echo "<th>Left</th>";
                            echo "<th>Average Win %</th>";
                            echo "<th>Predict To Win</th>";
                        }
                    }
                    echo "<th>Final</th>";
                    ?>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>Sanders, Bernard "Bernie"</td>
                    <?php
                    foreach($regions as $r) {
                        foreach ($electType as $et) {
                            echo "<td>" . $predictrf[$r][$et]['available'] . "</td>";
                            echo "<td>" . round($predictrf[$r][$et]['BernShare'], 4) . "</td>";
                            echo "<td>" . round($predictrf[$r][$et]['Bernie'], 3) . "</td>";
                        }
                    }
                    ?>
                    <td><?php echo $rfendTotal['Bernie'] ?></td>
                </tr>
                <tr>
                    <td>Clinton, Hillary Rodham</td>
                    <?php
                    foreach($regions as $r) {
                        foreach ($electType as $et) {
                            echo "<td>" . $predictrf[$r][$et]['available'] . "</td>";
                            echo "<td>" . round($predictrf[$r][$et]['HillShare'], 4) . "</td>";
                            echo "<td>" . round($predictrf[$r][$et]['Hill'], 3) . "</td>";
                        }
                    }
                    ?>
                    <td><?php echo $rfendTotal['Hill'] ?></td>
                </tr>
                </tbody>
            </table>

        </div>


</div>
    <script>
        (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
                (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
            m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
        })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

        ga('create', 'UA-76059946-1', 'auto');
        ga('send', 'pageview');

    </script>

</body>
</html>

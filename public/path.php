<?php
/**
 * Created by PhpStorm.
 * User: khaled
 * Date: 3/21/16
 * Time: 1:04 PM
 */
include "../states.php";
error_reporting(E_ERROR);
require $_SERVER['DOCUMENT_ROOT'] . "/../vendor/autoload.php";

$db = new MysqliDb('localhost', "a", "a", 'countthebern');
$db->where('candidate_id', 1);
$db->orderBy('state', 'asc');
$byStateResultsDetails = $db->get('candidate_state_results');
foreach($byStateResultsDetails as $details) {
    $name = $details['state'];
    $byStateResults['hrc'][$name] = array($details['votes'], $details['pledged']);
}
$db->where('candidate_id', 2);
$db->orderBy('state', 'asc');
$byStateResultsDetails = $db->get('candidate_state_results');
foreach($byStateResultsDetails as $details) {
    $name = $details['state'];
    $byStateResults['bs'][$name] = array($details['votes'], $details['pledged']);
}
$db->where('candidate_id', 6);
$db->orderBy('state', 'asc');
$byStateResultsDetails = $db->get('candidate_state_results');
foreach($byStateResultsDetails as $details) {
    $name = $details['state'];
    $byStateResults['avail'][$name] = array($details['votes'], $details['pledged']);
}
$db->where(1);
$db->orderBy('primary_date', 'asc');
$byStateResults['all'] = $db->get('states');
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
</head>
<body>
    <h4><a href="index.php">Back to Dashboard</a></h4>
    <div class="col-lg-12">
        <a name="chartview"></a>
        <h5>Results (change <a href="#predictions">PREDICTIONS</a> to alter)</h5>
        <div id="chart-delegates-hrc">LED gauges will load here!</div>
        <div id="chart-delegates-bs">LED gauges will load here!</div>
    </div>
    <div class="col-lg-12">
        <button type="button" id="bernieup">Bernie Up!</button>
    </div>

    <div class="col-lg-12">
        <h4>How we got to where we are</h4>
        <table class="table table-bordered">
            <thead>
            <tr>
                <th></th>
                <th></th>
                <th></th>
                <th colspan="2">Percentage</th>
                <th colspan="2">Popular Vote</th>
                <th colspan="2">Delegates</th>

            </tr>
            <tr>
                <th>State</th>
                <th>Election</th>
                <th>Election Format</th>
                <th>Delegates Left</th>
                <th>Clinton</th>
                <th>Sanders</th>
                <th>Clinton</th>
                <th>Sanders</th>
                <th>Bernie Bump</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($byStateResults['all'] as $stateCheck) {
                $stateId = $stateCheck['state_abbr'];
                if(!isset($byStateResults['avail'][$stateId][1]) || $byStateResults['avail'][$stateId][1] <= 0 || $byStateResults['hrc'][$stateId][1] > 0 || $byStateResults['hrc'][$stateId][1] > 0) {
                    echo "<tr>\n";
                    echo "<td>" . $stateId . "</td>";
                    echo "<td>" . $stateCheck['primary_date'] . "</td>";
                    echo "<td>" . $stateCheck['election_eligible'] . " " . $stateCheck['election_type']  . "</td>";
                    echo "<td>" . ($byStateResults['avail'][$stateId][1] ?: 0) . "</td>";
                    echo "<td>" . (round(($byStateResults['hrc'][$stateId][0] / $stateCheck['total_cast_votes']) * 100, 1))
                        . "</td>";
                    echo "<td>" . (round(($byStateResults['bs'][$stateId][0] / $stateCheck['total_cast_votes']) * 100, 1))
                        . "</td>";
                    echo "<td>" . $byStateResults['hrc'][$stateId][1] . "</td>";
                    $lastDiff = $totalBS-$totalHRC;
                    $totalHRC += $byStateResults['hrc'][$stateId][1];
                    echo "<td>" . $byStateResults['bs'][$stateId][1] . "</td>";
                    $totalBS += $byStateResults['bs'][$stateId][1];
                    $newDiff = $totalBS-$totalHRC;
                    $gainLoss= $newDiff-$lastDiff;
                    if($gainLoss > 0) {
                        $glColor = 'black';
                        $gainLoss = '+'.$gainLoss;
                    } else {
                        $glColor = 'red';
                    }
                    echo "<td style='font-weight:bold; color:".$glColor."'>$newDiff <span >($gainLoss)</span></td>";
                    echo "</tr>\n";
                }
            }
            ?>
            </tbody>
        </table>
    </div>

    <div class="col-lg-12">
        <a name="predictions"></a>
        <h3>And Predicting the Future <a style="margin-left:50px;font-size:14px;" href="#chartview">Back to Graph</a></h3>
        <table class="table table-bordered">
            <thead>
            <tr>
                <th></th>
                <th></th>
                <th></th>
                <th colspan="2">Percentage</th>
                <th colspan="2">Delegates</th>
            </tr>
            <tr>
                <th>State</th>
                <th>Election</th>
                <th>Election Format</th>
                <th>Delegates Left</th>
                <th>Clinton</th>
                <th>Sanders</th>
                <th>Clinton</th>
                <th>Sanders</th>
            </tr>
            <tr>
                <th></th>
                <th></th>
                <th></th>
                <th><input type='textbox' id='hrc-per-global' class='global' value='0' /></th>
                <th><input type='textbox' id='bs-per-global' class='global' value='0' /></th>
                <th></th>
                <th></th>

            </tr>
            </thead>
            <tbody>
            <?php foreach($byStateResults['all'] as $stateCheck) {
                $stateId = $stateCheck['state_abbr'];
                if($byStateResults['avail'][$stateId][1] > 0) {
                    echo "<tr>\n";
                    echo "<td>" . $stateId . "</td>";
                    echo "<td>" . $stateCheck['primary_date'] . "</td>";
                    echo "<td>" . $stateCheck['election_eligible'] . " " . $stateCheck['election_type']  . "</td>";
                    echo "<td id='$stateId-avail'>" . ($byStateResults['avail'][$stateId][1] ?: 0) . "</td>";
                    echo "<td id='$stateId-per-hrc'><input type='textbox' id='hrc-per-$stateId' class='hrc-per' value='0' /></td>";
                    echo "<td id='$stateId-per-bs'><input type='textbox' id='bs-per-$stateId' class='bs-per' value='0' /></td>";
                    echo "<td class='hrc-dels' id='$stateId-del-hrc'>0</td>";
                    echo "<td class='bs-dels' id='$stateId-del-bs'>0</td>";
                    echo "</tr>\n";
                }
            }
            ?>
            </tbody>
        </table>
    </div>

    <script type="application/javascript">
        var orgHrc  = <?php echo $totalHRC ?>;
        var orgBs = <?php echo $totalBS ?>;
        var delegatesHRCFuel = {
            "chart": {
                "caption": "Hillary Clinton Delegate Race Counter",
                "lowerLimit": "0",
                "upperLimit": "2383",
                "lowerLimitDisplay": "0",
                "numberSuffix": " Delegates",
                "upperLimitDisplay": "Wins Conventions!"

            },
            "colorRange": {
                "color": [
                    {
                        "minValue": "0",
                        "maxValue": "1500",
                        "code": "#88f001"
                    },
                    {
                        "minValue": "1500",
                        "maxValue": "2000",
                        "code": "#f2e719"
                    },
                    {
                        "minValue": "2000",
                        "maxValue": "2383",
                        "code": "#ff1a31"
                    }
                ]
            },
            "value":"<?php echo $totalHRC ?>"
        };
        var delegatesBSFuel = {
            "chart": {
                "caption": "Bernie Sanders Delegate Race Counter",
                "lowerLimit": "0",
                "upperLimit": "2383",
                "lowerLimitDisplay": "0",
                "numberSuffix": " Delegates",
                "upperLimitDisplay": "Wins Conventions!"

            },
            "colorRange": {
                "color": [
                    {
                        "minValue": "0",
                        "maxValue": "1500",
                        "code": "#88f001"
                    },
                    {
                        "minValue": "1500",
                        "maxValue": "2000",
                        "code": "#f2e719"
                    },
                    {
                        "minValue": "2000",
                        "maxValue": "2383",
                        "code": "#ff1a31"
                    }
                ]
            },
            "value":"<?php echo $totalBS ?>"
        };
        var delsHRCFuel = new FusionCharts({
            "type": "hled",
            "renderAt": "chart-delegates-hrc",
            "width": "1000",
            "height": "100",
            "dataFormat": "json",
            "dataSource":delegatesHRCFuel
        });
        var delsBSFuel = new FusionCharts({
            "type": "hled",
            "renderAt": "chart-delegates-bs",
            "width": "1000",
            "height": "100",
            "dataFormat": "json",
            "dataSource":delegatesBSFuel
        });
        delsHRCFuel.render();
        delsBSFuel.render();


        var delegateLogic = function(st, hrc, bs) {
            var hrcCurAdd = 0;
            var bsCurAdd= 0;
            var delTargetHrc = $('#'+st+'-del-hrc');
            var delTargetBS = $('#'+st+'-del-bs');

            var avails = Number($('#'+st+'-avail').text());
            var hrcR = Math.round(avails * (hrc/100));
            var bsR = Math.round(avails * (bs/100));

            delTargetHrc.text(hrcR);
            delTargetBS.text(bsR);
            $('.hrc-dels').each(function(key, value) {
                hrcCurAdd += Number(value.innerText);
            });
            $('.bs-dels').each(function(key, value) {
                bsCurAdd += Number(value.innerText);
            });
            var sumHrc = orgHrc+hrcCurAdd;
            var sumBS = orgBs+bsCurAdd
            if(sumHrc> 2383) {
                sumHrc = 2383;
            }
            if(sumBS > 2383) {
                sumBS = 2383;
            }
            delsHRCFuel.setData(sumHrc);
            delsBSFuel.setData(sumBS);
            return;
        };

        $('.hrc-per').change(function(){
            if(this.value > 100) {
                this.value = 100;
            }
            if(this.value < 0) {
                this.value = 0;
            }
            var stateId = this.id.split('-')[2];
            $('input#bs-per-'+stateId).val(100 - this.value);
            var shares = delegateLogic(stateId, this.value, $('input#bs-per-'+stateId).val());
        });

        $('.bs-per').change(function(){
            if(this.value > 100) {
                this.value = 100;
            }
            if(this.value < 0) {
                this.value = 0;
            }
            var stateId = this.id.split('-')[2];
            $('input#hrc-per-'+stateId).val(100 - this.value);
            var shares = delegateLogic(stateId, $('input#hrc-per-'+stateId).val(), this.value);
        })

        $('#bs-per-global').change(function(){
            if(this.value > 100) {
                this.value = 100;
            }
            if(this.value < 0) {
                this.value = 0;
            }
            $('#hrc-per-global').val(100 - this.value);
            var _this = this;
            $('.bs-per').each(function(ind, item) {
                item.value = _this.value;
                var stId = item.id.split('-')[2];
                $('input#hrc-per-'+stId).val(100 - _this.value);
                var shares = delegateLogic(stId, $('input#hrc-per-'+stId).val(), _this.value);
            })
        });

    </script>
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
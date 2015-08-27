<?PHP
	// Prep {
		// Groundwork
			$conf = $_POST ? $_POST : $_GET;
			
			
			
			// Defines and includes {
				$times				= substr_count($_SERVER['PHP_SELF'],"/");
				$rootaccess		= "";
				$i						= 1;
				
				while ($i < $times) {
					$rootaccess .= "../";
					$i++;
				}
				
				define("BASE", $rootaccess);
				
				include_once(BASE."/basefunctions/localdefines.php");
				include_once(BASE."/basefunctions/dbcontrols.php");
				include_once(BASE."/basefunctions/baseapis/manapi.php");
				include_once(BASE."Maxine/api/maxineapi.php");
				
				require_once(BASE."basefunctions/baseapis/fleetDayHandler.php");
				
				$link			= mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_SCHEMA) or die(mysqli_error($link));
				
				$fleetdayobj = new fleetDayHandler;
			// }
		// }
		
		// Sort functions {
			function cmpBudgets($a, $b) {
				if ($a["Date"] == $b["Date"]) {
					return 0;
				}
				return ($a["Date"] < $b["Date"]) ? -1 : 1;
			}
			
			function cmpScores($a, $b) {
				if ($a["score"] == $b["score"]) {
					return 0;
				}
				return ($a["score"] > $b["score"]) ? -1 : 1;
			}
		// }
		
		// Create date strings for query {
			$startday		= date("d");
			$startmonth		= date("m");
			$startyear		= date("Y");
			
			$startstring	= $startyear."-".$startmonth."-".$startday;
			
			$stopdate		= mktime(0, 0, 0, $startmonth, (date("d") + 1), $startyear);
			$stopday		= date("d", $stopdate);
			$stopmonth	 	= date("m", $stopdate);
			$stopyear		= date("Y", $stopdate);
			
			$stopstring	= $stopyear."-".$stopmonth."-".$stopday;
			
			$count		= 0;
			$rowcount	= 0;
		// }
		
		if($conf["maxwidth"]) {
			$maxwidth	= $conf["maxwidth"];
			
			if($maxwidth < 1000) {
				$factor = 0.8;
			} else if($maxwidth < 1300) {
				$factor	= 0.94;
			} else if($maxwidth > 1600) {
				$factor	= 1.4;
			} else {
				$factor	= 1;
			}
		} else {
			$factor	= 1;
		}
		$fleetlist	= $fleetdayobj->getIncomeFleets();
		$slidefleets = $fleetdayobj->getSlideFleets((int)$conf["dashid"]);

    	$flashstring	= "";
      
		// Pull the details for each fleet {
		
		foreach($slidefleets["fleets"] as $fleet){
			
			foreach ($fleetlist as $fleetkey=>$fleetval) {
				if($fleetval["id"] == $fleet["fleet_id"]) {
					$fleetdetails	= $fleetdayobj->getFleetScoreMonth($fleetval["id"]);
					$fleetid		= $fleetval["id"];
					
					$fleetscore[$fleetid]["id"]	= $fleetid;
					$fleetscore[$fleetid]["name"]	= $fleetval["name"];
					$flashstring = (string)"";
					$totincome = (float)0.00;
					$totbudget = (float)0.00;
					
					$fleetscore[$fleetid]["income"] = (float)0.00;
					$fleetscore[$fleetid]["kms"] = (float)0.00;
					$fleetscore[$fleetid]["budget"] = (float)0.00;
					$fleetscore[$fleetid]["flashstring"] = (string)"";
					foreach ($fleetdetails as $daykey=>$dayval) {
						$fleetscore[$fleetid]["income"]	+= $dayval["income"];
						$fleetscore[$fleetid]["kms"]	+= $dayval["kms"];
						$fleetscore[$fleetid]["budget"]	+= $dayval["budget"];
            
						//$fleetscore[$fleetid]["flashstring"]	.= "&ginput".$daykey."=".$dayval["income"];
						
						$totincome		+= $dayval["income"];
						$totbudget		+= $dayval["budget"];
						
						$flashstring	.= "&ginput".$daykey."=".$totincome;
						$flashstring	.= "&tinput".$daykey."=".$totbudget;
						
						$fleetscore[$fleetid]["flashstring"] = $flashstring;

					}
					if($fleetscore[$fleetid]["budget"] > 0) {
						$fleetscore[$fleetid]["score"]	= round($fleetscore[$fleetid]["income"] / $fleetscore[$fleetid]["budget"] * 100, 0);
					} else {
						$fleetscore[$fleetid]["score"]	= 0;
					}
					$fleetscore[$fleetid]["income"]	= round($fleetscore[$fleetid]["income"], 0);
					$fleetscore[$fleetid]["budget"]	= round($fleetscore[$fleetid]["budget"], 0);
					$rowcount++;
				}
			}
			
		}
		
		$fleetscore = array_values($fleetscore);

		uasort($fleetscore, "cmpScores");
		$slidertop	= $fleetdayobj->calcSliderTop($fleetscore[1]["budget"]);
		
		//get the slider name
		$slidername = $fleetdayobj->getSliderName((int)$conf["dashid"]);
		
		
		//calculate the total income and total budget
		$total_income = (float)0.00;
		$total_budget = (float)0.00;

		foreach($fleetscore as $fleet){
			$total_income = $total_income + $fleet["income"];
			$total_budget = $total_budget + $fleet["budget"];
		}	
		/* print('<pre>');
		print_r($slidefleets);
		print('</pre>'); */
		// }
	// }
?>


						<div id="comparison">
								
								<div class="upper">
										
										<div class="left">
												
												<div id="comparisonRoundGraph">
														
														<p class="comparisonTotal">R<?php echo number_format($total_income);?></p>
														
													<?php
											
														$perc = ((round($total_income, 0)) / (round(($total_budget > 0 ? $total_budget : 1), 0))) * 100;
													
														$perc = ($perc <= 100) ? $perc : 100;
													
													?>
														

														<div id="dailygraph" class="graph large">
															
															<div class="percentage"><?php echo (round($perc, 0));?>%</div>	
															
														</div>
		
														<script>
																$('#dailygraph').circleProgress({
																		value: <?php echo $perc / 100;?>,
																		size: 240,
																		fill: {
																			color: "#21a9e1"
																		},
																		thickness: 25,
																		startAngle: -4.75,
																		emptyFill: "#353e54",
																		animation: {
																				duration: 0
																		}
																});
														</script>	
																
														
												</div><!-- comparisonRoundGraph -->
												
												<div class="title">
                  
														<h2><?php echo $slidername;?></h2>
												
														<p class="datetime"><?php echo date("d F H:i");?></p>
												
												</div><!-- title -->
												
										</div><!-- left -->
										
										<div class="right">
												
												<div id="comparisonGraph">
														
														
														
														
												</div><!-- comparisonGraph -->
												
												
										</div><!-- right -->
										
										<div class="clear"></div>
										
								</div><!-- upper -->
								
								<div class="lower">
										
										<div class="inner">
												
												<ul>
													
													<?php
																									
													foreach ($fleetscore as $fskey=>$fsval):
													/* print('<pre>');
													print_r($fsval);
													print('</pre>'); */
													// ($fsval["budget"] > 0) && 
														if(($fskey != 29)) :
															
															$score = ($fsval["score"] <= 100) ? $fsval["score"] : 100;
			
													?>
														
														<li>
																
																<div class="percBar" style="width: <?php echo $score;?>%;"></div>
																
																<span class="name"><?php echo $fsval["name"];?></span>
																
																<span class="percValue"><?php echo $fsval["score"];?>%</span>
																
																<div class="clear"></div>
																
														</li>
														
													<?php
														endif;
														
													endforeach;	
														
													?>
														
												</ul>
												
										</div><!-- inner -->
										
								</div><!-- lower -->
								
								<script>
							
								setTimeout(function(){
									
								
							
										var chart_border_color = "#323a4f";
										var chart_color = "#21a9e1";
										var options,chart;
										var data = [];	
											
										<?php foreach($fleetscore as $fleet):?>	
											
											var fleet_<?php echo $fleet["id"];?> = [];
											
											
											<?php
											
											$graph_data = explode("&", $fleet["flashstring"]);
											
											$total_val = 0;
											//print_r($graph_data);
											$count = 1;
											for($x = 1; $x <= date('d')*2; $x += 2):
												if (array_key_exists($x, $graph_data) === FALSE) {continue;}
												$total_val =  (int) str_replace("=", "", strstr($graph_data[$x], '='));
												
											?>
											
											fleet_<?php echo $fleet["id"];?>.push([(new Date("<?php echo date("Y");?>/<?php echo date("m");?>/<?php echo $count;?>")).getTime(), <?php echo $total_val;?>]);
											//budgeted_data.push([(new Date("<?php echo date("Y");?>/07/<?php echo $count;?>")).getTime(), <?php echo (int) str_replace("=", "", strstr($graph_data[$x + 1], '='));?>]);
											
											
											<?php
												$count++;
											endfor;
											
											?>
											
											data.push({
													data:fleet_<?php echo $fleet["id"];?>,
													
													lines:{
														show:true,
														lineWidth : 1,
														fill : false,
														fillColor: '#21a9e1'
													},
													points: {
														show: true,
														fillColor: '#21a9e1'
													}
											});
										
										<?php endforeach;?>
													
										
										var options = {
											xaxis: {
												mode: "time",
												timeformat: "%d/%m",
												minTickSize: [1, "day"],
												tickColor: "#293042",
												timezone: "browser"
											},
											yaxis: {
												tickFormatter: function(x, axis) {
													return x.toString().replace(/\B(?=(?:\d{3})+(?!\d))/g, ",");
												}
											},
											grid : {
												hoverable : false,
												clickable : true,
												tickColor : chart_border_color,
												borderWidth : 1,
												borderColor : "#293042",
											},
											colors: ["#21a9e1"]
										};
										
										if ($(window).width() > 720) {
											var plot3 = $.plot($("#comparisonGraph"), data, options);
										}
										
										$("#comparisonGraph").animate({"opacity":"1"}, 200);
											
								}, 200);		
											
									</script>
								
						</div><!-- comparison -->

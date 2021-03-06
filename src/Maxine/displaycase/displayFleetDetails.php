<?PHP
	// Prep {
		// Groundwork {
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

				$fleetdayobj	= new fleetDayHandler();
				/** returnFleetTruckCount()
				 * get all fleets truck count
				 * @return array on success false otherwise
				 */
				function returnFleetTruckCount() {
					require_once(BASE."/basefunctions/baseapis/TableManager.php");
					$manager = new TableManager("fleet_truck_count");
					$manager->setCustomIndex("fleet_id");
					return $manager->selectMultiple();
				}
				$truckcount = returnFleetTruckCount();
			// }
		// }

		$count	= $conf["fleetcount"];
		$factor = findPageDimensionFactor((isset($conf["maxwidth"]) ? (int)$conf["maxwidth"] : NULL));

		$fleet	= $fleetdayobj->getFleetById($count);
		//print('<pre>');print_r($fleet);

		$today	= date("j");
		//$today = 30; *TEST

		if($fleet) {
			$fleetdetails	= $fleetdayobj->getFleetScoreMonth($fleet['id']);
		}
		// print('<pre>');print_r($fleetdetails);exit;

		$fleetlist		= $fleetdayobj->getIncomeFleets();

		$totincome		= 0;
		$subincome		= 0;
		$totbudget		= 0;
		$totkms				= 0;
		$subkms				= 0;

		$flashstring	= "";

		if($fleetdetails) {
			foreach ($fleetdetails as $fleetdaykey=>$fleetday) {
				if ($fleetday["day"] > $today) {
					continue;
				}
				$totincome		+= $fleetday["income"];
				$subincome		+= $fleetday['subbie_income'];
				$totbudget		+= $fleetday["budget"];
				$totkms			+= $fleetday["kms"];
				$subkms			+= $fleetday['subbie_kms'];

				$flashstring	.= "&ginput".$fleetdaykey."=".$totincome;
				$flashstring	.= "&tinput".$fleetdaykey."=".$totbudget;
			}
		}

		$todaybudget	=	$fleetdetails[$today]["budget"];
		$variance			= $totincome - $totbudget;
		if($totkms > 0) {
			$totcpk	= round($totincome / $totkms, 2);
		} else {
			$totcpk	= 0;
		}
		if($fleetdetails[$today]["kms"] > 0) {
			$daycpk	= round($fleetdetails[$today]["income"] / $fleetdetails[$today]["kms"], 2);
		} else {
			$daycpk	= 0;
		}
		if($fleetdetails[$today]["budkms"] > 0) {
			$budgetcpk	= round($fleetdetails[$today]["budget"] / $fleetdetails[$today]["budkms"], 2);
		} else {
			$budgetcpk	= 0;
		}

		$slidertop		= $fleetdayobj->calcSliderTop($todaybudget);
	// }

	
	
	$date = date("d");
	//$date = 30; *TEST
	
	if (date("d") < 10) {
		if (isset($fleetdetails[substr(date("d"), 1)]) && $fleetdetails[substr(date("d"), 1)]) {
			if (array_key_exists('subbie_kms', $fleetdetails[substr(date('d'), 1)]) && ($fleetdetails[substr(date('d'), 1)]['subbie_kms'] > 0))
			{
				$trucks = (int)($truckcount[$fleet['id']]["count"]-$truckcount[$fleet['id']]["subbie_count"]);
				$kms = ($fleetdetails[substr(date("d"), 1)]["kms"] ? round((($fleetdetails[substr(date("d"), 1)]["kms"]-$fleetdetails[substr(date('d'), 1)]['subbie_kms'])/($trucks > 0 ? $trucks : 1)),0) : 0);
			}
			else
			{
				$trucks = (int)($truckcount[$fleet['id']]["count"]-$truckcount[$fleet['id']]["subbie_count"]);
				$kms = ($fleetdetails[substr(date("d"), 1)]["kms"] ? round(($fleetdetails[substr(date("d"), 1)]["kms"]/($trucks > 0 ? $trucks : 1)),0) : 0);
			}
			$budkms = ($fleetdetails[substr(date("d"), 1)]["budkms"] ? round(($fleetdetails[substr(date("d"), 1)]["budkms"]/($trucks > 0 ? $trucks : 1)),0) : 0);
		}
	} else {
		if (isset($fleetdetails[$date]) && $fleetdetails[$date]) {
			if (array_key_exists('subbie_kms', $fleetdetails[$date]) && ($fleetdetails[$date]['subbie_kms'] > 0))
			{
				$trucks = (int)($truckcount[$fleet['id']]["count"]-$truckcount[$fleet['id']]["subbie_count"]);
				$kms = ($fleetdetails[$date]["kms"] ? round((($fleetdetails[$date]["kms"]-$fleetdetails[date('d')]['subbie_kms'])/($trucks > 0 ? $trucks : 1)),0) : 0);
			}
			else
			{
				if (is_string($date) === TRUE)
				{
					$trucks = (int)($truckcount[$fleet['id']]["count"]-$truckcount[$fleet['id']]["subbie_count"]);
			        $kms = ($fleetdetails[$date]["kms"] ? round(($fleetdetails[$date]["kms"]/($trucks > 0 ? $trucks : 1)),0) : 0);
			    }
			}
			if (is_string($date) === TRUE)
			{
				$trucks = (int)($truckcount[$fleet['id']]["count"]-$truckcount[$fleet['id']]["subbie_count"]);
				$budkms = ($fleetdetails[$date]["budkms"] ? round(($fleetdetails[$date]["budkms"]/($trucks > 0 ? $trucks : 1)),0) : 0);
			}
		}
	}
	$totkms = (float)0;
	$totbudkms = (float)0;
	foreach ($fleetdetails as $day=>$value) {
		$totkms += isset($value["kms"]) ? $value["kms"] : 0;
		if (isset($value["budkms"]) && $value["budkms"]) {
			$totbudkms += isset($value["budkms"]) ? $value["budkms"] : 0;
		}
	}
	$fkt = displayFleetKmsTable($fleetdetails[$today]["fleetid"], array(
	    "factor"=>$factor,
	    "kms"=>$kms,
	    "budkms"=>$budkms,
	    "totkms"=>$totkms,
	    "totbudkms"=>$totbudkms,
	    "truck_count"=>(isset($truckcount[$fleet['id']]) && array_key_exists("count", $truckcount[$fleet['id']]) ? ($truckcount[$fleet['id']]["count"]-$truckcount[$fleet['id']]["subbie_count"]) : NULL)
	));

	
	$graph_data = explode("&", $flashstring);
	

	date_default_timezone_set('Africa/Johannesburg');
?>
            
            <div id="dashboardMain">
              
              <div class="graphUpper">
                
                <div class="mainGraph">
                  
                  <div id="graph">
                    
                    
                    
                  </div><!-- graph -->
                  
                  
                </div><!-- mainGraph -->
                
                <div class="graphInfo">
                  
                  <div class="title">
                    
                    <h2><?php echo $fleet["name"];?></h2>
                    
                    <p class="datetime"><?php echo date("d F H:i");?></p>
                    
                  </div><!-- title -->
                  
                  <div class="graphStats">
                    
                    <ul>
                      
                      <li>
                        
                        <span class="label">Budget</span>
                        
                        <span class="value income">R<?php echo (number_format($totbudget));?></span>
                        
                      </li>
                      
                      <li>
                        
                        <span class="label">Actual</span>
                        
                        <span class="value actual">R<?php echo (number_format($totincome));?></span>
                        
                      </li>
                      
                      <li>
                        
                        <span class="label">Variance</span>
												
												<?php
												$prefix = "+R";
												
												if (strpos($variance,'-') !== false){
													$prefix = "-R";
													
												}
												$variance_value = str_replace("-", "",$variance);
												
												$variance_perc = ($totincome / ($totbudget > 0 ? $totbudget : 1)) * 100;
												?>
                        
                        <span class="value variance"><?php echo $prefix.number_format($variance_value);?></span>
												
												<span class="varianceDiff <?php if($variance_perc <= 100):?>red<?php endif;?>"><i class="fa fa-caret-<?php if($variance_perc <= 100):?>down<?php else:?>up<?php endif;?>"></i> <?php echo round($variance_perc);?>% of Budget</span>
                        
                      </li>
                      
                      
                    </ul>
                    
										<div class="clear"></div>
                    
                  </div><!-- graphStats -->
                  
                  <div class="clear"></div>
                  
                </div><!-- graphInfo -->
                
                
              </div><!-- graph upper -->
              
              <div class="graphLower">
                
                <div class="infoBlock daily">
                  
                  <div class="inner">
                    
                    <h3>Daily Income</h3>
                    
                    <div id="incomeGraph">
											
											<?php
											
												$perc = ((round(($fleetdetails[$today]["income"]), 0)) / (round(($fleetdetails[$today]["budget"] > 0 ? $fleetdetails[$today]["budget"] : 1), 0))) * 100;
												
												$perc_display = $perc;
											
												$perc = ($perc <= 100) ? $perc : 100;
											
											?>
		
												<div id="dailygraph" class="graph large">
													
													<div class="percentage"><?php echo (round($perc_display, 0));?>%</div>	
													
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
																		duration: 100
																}
														});
														
														$(window).resize(function(){
															
															$('#dailygraph').circleProgress({
																	value: <?php echo $perc / 100;?>,
																	size: ($("#dashboardMain .graphLower .infoBlock.daily").height() - 86),
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
																	
														});
														
												
												</script>
												
                    </div><!-- incomeGraph -->
                    
                    <div class="stat daily">
                      
                      <span class="label">Daily</span>
                      
                      <span class="value">R<?php echo number_format(round($fleetdetails[$today]["income"], 0));?></span>
                      
                    </div><!-- stat -->
                    
                    <div class="stat budget">
                      
                      <span class="label">Budget</span>
                      
                      <span class="value">R<?php echo number_format(round($fleetdetails[$today]["budget"], 0));?></span>
                      
                    </div><!-- stat -->
                    
                  </div><!-- inner -->
                  
                </div><!-- infoBlock -->
                
                <div class="infoBlock table">
                  
                  <div class="inner">
                    
                    <div class="tableWrap headers">
                    
                    <table cellpadding="0" cellspacing="0">
                      
                      <thead>
                        
                        <tr>
                          
                          <th class="kms">KMS / Truck</th>
                          
                          <th class="day">Current Day</th>
                          
                          <th class="mtd">MTD</th>
													
                          <th class="forecast">Forecast</th>
                          
                        </tr>
                        
                      </thead>                 
											  
                    </table>
                    
                    </div>
                    
                    <div class="tableWrap content">
                      
                      <table cellpadding="0" cellspacing="0" >
												
                        <tbody>
                          
                          <tr>
                            
                            <td class="kms">Actual</td>
                            
                            <td class="day"><?php echo $kms;?></td>
                            
                            <td class="mtd"><?php echo $fkt["mtd_actual"];?></td>
                            
                            <td class="forecast"><?php echo $fkt["mef_actual"];?></td>
                            
                          </tr>
                          
                          <tr>
                            
                            <td class="kms">Budget</td>
                            
                            <td class="day"><?php echo $budkms;?></td>
                            
                            <td class="mtd"><?php echo $fkt["mtd_budget"];?></td>
                            
                            <td class="forecast"><?php echo $fkt["mef_budget"];?></td>
                            
                          </tr>
														
                          <tr>
                            
                            <td class="kms">Variance</td>
                            
                            <td class="day"><?php echo ($kms - $budkms);?></td>
                            
                            <td class="mtd"><?php echo $fkt["mtd_variance"];?></td>
                            
                            <td class="forecast"><?php echo $fkt["mef_variance"];?></td>
                            
                          </tr>
                          
                          <tr>
                            
                            <td class="kms">% of Budget</td>
                            
                            <td class="day"><?php echo round((($kms / ($budkms > 0 ? $budkms : 1)) * 100), 0);?>%</td>
                            
                            <td class="mtd"><?php echo $fkt["mtd_percent"];?>%</td>
                            
                            <td class="forecast"><?php echo $fkt["mef_percent"];?>%</td>
                            
                          </tr>
                          
                        </tbody>
                        
                      </table>
                        
                      
                    </div>
                    
                  </div><!-- inner -->
                  
                </div><!-- infoBlock -->
                
                
                
                <div class="infoBlock cpk">
                  
                  <div class="inner">
                    
                    <h3>CPK Comparison</h3>
                    
                    <div class="cpkGraphWrapper">
                      
											<div class="today">
												
												<div class="stat day">
                      
													<span class="label">Today</span>
													
													<span class="value">R<?php echo  $daycpk;?></span>
													
												</div><!-- stat -->

												<?php
											

													$perc = ($daycpk / ($budgetcpk > 0 ? $budgetcpk : 1)) * 100;
													
													$symbol = "";
													

													if($perc < 100){
														
														$symbol = "down";
														$perc = 100-$perc;
														
														$prog = (round($perc, 2) / 100) * 0.5;
														
														$prog = 0.5 - $prog;
														
													}
													else{
														
														$symbol = "up";
														$perc = $perc-100;
														
														$prog = (round($perc, 2) / 100) * 0.5;
														
														$prog = 0.5 + $prog;
														
													}
												
												?>												
												
												<div class="graphWrapper">	
												 
													<div id="cpkday" class="graph small">
														
														<div class="percentage <?php echo $symbol;?>"><i class="fa fa-caret-<?php echo $symbol;?>"></i> <?php echo round($perc, 0);?>%</div>	
														
													</div><!-- day budget -->
													
													<div id="cpkdaybudget" class="graph smaller">
														
														
													</div><!-- day budget -->
													
												</div><!-- graphWrapper -->

												<script>
														$('#cpkday').circleProgress({
																value: <?php echo $prog;?>,
																size: 150,
																fill: {
																	color: "#21a9e1"
																},
																thickness: 15,
																startAngle: -4.73,
																emptyFill: "#353e54",
																animation: {
																		duration: 0
																}
														});
														$('#cpkdaybudget').circleProgress({
																value: 0.5,
																size: 135,
																fill: {
																	color: "#295573"
																},
																thickness: 15,
																startAngle: -4.73,
																emptyFill: "#2c3449",
																animation: {
																		duration: 0
																}
														});
												</script>
													
											</div><!-- today --> 
											
											<div class="month">
												
												<div class="stat tot">
                      
													<span class="label">Month</span>
													
													<span class="value">R<?php echo $totcpk;?></span>
													
												</div><!-- stat -->
												
												<?php
											
													$perc = ($totcpk / ($budgetcpk > 0 ? $budgetcpk : 1)) * 100;
													
													$symbol = "";
													
													
													
													if($perc < 100){
														
														$symbol = "down";
														$perc = 100-$perc;
														
														$prog = (round($perc, 2) / 100) * 0.5;
														
														$prog = 0.5 - $prog;
														
													}
													else{
														
														$symbol = "up";
														$perc = $perc-100;
														
														$prog = (round($perc, 2) / 100) * 0.5;
														
														$prog = 0.5 + $prog;
														
													}
												
													
													
												?>												
													
												<div class="graphWrapper">	
														
													<div id="cpkmonth" class="graph small">
														
														<div class="percentage <?php echo $symbol;?>"><i class="fa fa-caret-<?php echo $symbol;?>"></i> <?php echo round($perc, 0);?>%</div>	
														
													</div><!-- small -->
													
													<div id="cpkmonthbudget" class="graph smaller">
														
													</div><!-- inner -->
												
												</div><!-- graphWrapper -->
												
												<script>
														$('#cpkmonth').circleProgress({
																value: <?php echo $prog;?>,
																size: 150,
																fill: {
																	color: "#21a9e1"
																},
																thickness: 15,
																startAngle: -4.76,
																emptyFill: "#353e54",
																animation: {
																		duration: 0
																}
														});
														
														$('#cpkmonthbudget').circleProgress({
																value: 0.5,
																size: 135,
																fill: {
																	color: "#295573"
																},
																thickness: 15,
																startAngle: -4.76,
																emptyFill: "#2c3449",
																animation: {
																		duration: 0
																}
														});
												</script>
												
											</div><!-- month -->
											
											<div class="clear"></div>
                      
                    </div><!-- cpkGraphWrapper -->
                    
                    <div class="stat budget">
                      
                      <span class="label">Budget</span>
                      
                      <span class="value">R<?php echo $budgetcpk;?></span>
                      
                    </div><!-- stat -->
                      
                  </div><!-- inner -->
                  
                </div><!-- infoBlock -->
                
                <div class="clear"></div>
                
              </div><!-- graph lower -->
              
            </div><!-- dashboardMain -->
						
						<script>
							
							setTimeout(function(){
								

								var chart_border_color = "#323a4f";
								var chart_color = "#21a9e1";
								var data, options, chart;
									
									
								var actual_data = [];
								var budgeted_data = [];
								
								<?php
								
	
								$count = 1;
								for($x = 1; $x < (date('d')*2); $x += 2):
									
								?>
								
								actual_data.push([(new Date("<?php echo date("Y");?>/<?php echo date("m");?>/<?php echo $count;?>")).getTime(), <?php echo (int) str_replace("=", "", strstr($graph_data[$x], '='));?>]);
								budgeted_data.push([(new Date("<?php echo date("Y");?>/<?php echo date("m");?>/<?php echo $count;?>")).getTime(), <?php echo (int) str_replace("=", "", strstr($graph_data[$x + 1], '='));?>]);
								
								
								<?php
									$count++;
								endfor;
								
								
								?>
								
								data = [
									{
										data:budgeted_data,
										
										lines:{
											show:true,
											lineWidth : 1,
											fill : false,
											fillColor: '#b2cb35'
										},
										points: {
											show: true,
											fillColor: '#b2cb35'
										}
									},{
										data:actual_data,
										lines:{
											show:true,
											lineWidth : 1,
											fill : false,
											fillColor: '#b2cb35'
										},
										points: {
											show: true,
											fillColor: '#21a9e1'
										}
									}
								];
								
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
										hoverable : true,
										clickable : true,
										tickColor : chart_border_color,
										borderWidth : 1,
										borderColor : "#293042",
									},
									colors: ["#b2cb35", "#21a9e1"]
								};
								
								if ($(window).width() > 720 && budgeted_data.length > 0 && actual_data.length > 0) {
									var plot3 = $.plot($("#graph"), data, options);
									
									$(window).resize(function(){
										if ($(window).width() > 720) {
											var plot3 = $.plot($("#graph"), data, options);
										}
									});
									
									$("#graph").animate({"opacity":"1"}, 200);
									
								}
							
						
								
							}, 200);
							

							
						</script>
						
<?php
	//endif;
	
	//

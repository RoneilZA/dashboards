<?PHP
	// Prep {
		// Groundwork {
			$conf		= $_POST;
			
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
		
		$mindate			= mktime(1, 0, 0, (date("m") - 1), 1, (date("Y") - 1)); // This will ensure that only dates after one month and one year ago are pulled
		$maxdate			= mktime(1, 0, 0, (date("m") - 1), (date("d") - 10), date("Y")); // This will ensure that only dates before a month and 10 days ago are pulled
		
		$currentmonth	= mktime(0, 0, 0, (date("m")-1), 1, date("Y"));
		$prevmonth		= mktime(0, 0, 0, (date("m")-2), 1, date("Y"));
		
		if($maxdate > $currentdate) {
			$currentmonth	= mktime(0, 0, 0, (date("m")-2), 1, date("Y"));
			$prevmonth		= mktime(0, 0, 0, (date("m")-3), 1, date("Y"));
		}
		
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
		
		$greenscores	= sqlPull(array("table"=>"greenmile_scores", "where"=>"date>".$mindate." AND date<".$maxdate, "sort"=>"date ASC", "customkey"=>"date"));
		
		$monthstr			= "";
		$inputstr			= "";
		
		// Set up the trend images {
			$display["invoicefull"]		= "title1=Invoice&title2=In Full&score=".$greenscores[$currentmonth]["invoicefull"]."%25";
			$display["invoicefull"]		.= "&difference=".($greenscores[$currentmonth]["invoicefull"] - $greenscores[$prevmonth]["invoicefull"])."%25&sign=";
			if($greenscores[$currentmonth]["invoicefull"] > $greenscores[$prevmonth]["invoicefull"]) {
				$display["invoicefull"]		.= "up";
			} else if($greenscores[$currentmonth]["invoicefull"] < $greenscores[$prevmonth]["invoicefull"]) {
				$display["invoicefull"]		.= "down";
			} else {
				$display["invoicefull"]		.= "unchanged";
			}
			
			$display["pods"]					= "title1=P.O.D.&title2=On Time&score=".$greenscores[$currentmonth]["pods"]."%25";
			$display["pods"]					.= "&difference=".($greenscores[$currentmonth]["pods"] - $greenscores[$prevmonth]["pods"])."%25&sign=";
			if($greenscores[$currentmonth]["pods"] > $greenscores[$prevmonth]["pods"]) {
				$display["pods"]				.= "up";
			} else if($greenscores[$currentmonth]["pods"] < $greenscores[$prevmonth]["pods"]) {
				$display["pods"]				.= "down";
			} else {
				$display["pods"]				.= "unchanged";
			}
			
			$display["invoiceerrors"]	= "title1=Invoice&title2=Error Free&score=".$greenscores[$currentmonth]["invoiceerrors"]."%25";
			$display["invoiceerrors"]	.= "&difference=".($greenscores[$currentmonth]["invoiceerrors"] - $greenscores[$prevmonth]["invoiceerrors"])."%25&sign=";
			if($greenscores[$currentmonth]["invoiceerrors"] > $greenscores[$prevmonth]["invoiceerrors"]) {
				$display["invoiceerrors"]	.= "up";
			} else if($greenscores[$currentmonth]["invoiceerrors"] < $greenscores[$prevmonth]["invoiceerrors"]) {
				$display["invoiceerrors"]	.= "down";
			} else {
				$display["invoiceerrors"]	.= "unchanged";
			}
			
			$display["shortages"]			= "title1=Shortages %26&title2=Damages&score=".$greenscores[$currentmonth]["shortages"]."%25";
			$display["shortages"]			.= "&difference=".($greenscores[$currentmonth]["shortages"] - $greenscores[$prevmonth]["shortages"])."%25&sign=";
			if($greenscores[$currentmonth]["shortages"] > $greenscores[$prevmonth]["shortages"]) {
				$display["shortages"]		.= "up";
			} else if($greenscores[$currentmonth]["shortages"] < $greenscores[$prevmonth]["shortages"]) {
				$display["shortages"]		.= "down";
			} else {
				$display["shortages"]		.= "unchanged";
			}
			
			$display["complaints"]		= "title1=Complaints&title2=Resolution&score=".$greenscores[$currentmonth]["complaints"]."%25";
			$display["complaints"]		.= "&difference=".($greenscores[$currentmonth]["complaints"] - $greenscores[$prevmonth]["complaints"])."%25&sign=";
			if($greenscores[$currentmonth]["complaints"] > $greenscores[$prevmonth]["complaints"]) {
				$display["complaints"]	.= "up";
			} else if($greenscores[$currentmonth]["complaints"] < $greenscores[$prevmonth]["complaints"]) {
				$display["complaints"]	.= "down";
			} else {
				$display["complaints"]	.= "unchanged";
			}
			
			$display["defects"]				= "title1=Defect&title2=Free&score=".round($greenscores[$currentmonth]["defects"], 0);
			$display["defects"]				.= "&difference=".($greenscores[$currentmonth]["defects"] - $greenscores[$prevmonth]["defects"])."&sign=";
			if($greenscores[$currentmonth]["defects"] > $greenscores[$prevmonth]["defects"]) {
				$display["defects"]			.= "up";
			} else if($greenscores[$currentmonth]["defects"] < $greenscores[$prevmonth]["defects"]) {
				$display["defects"]			.= "down";
			} else {
				$display["defects"]			.= "unchanged";
			}
			$display["defects"]				.= "&sunken=1";
		// }
		
		$count	= 0;
		foreach ($greenscores as $greenkey=>$greenval) {
			$count++;
			if(($greenval["defects"] > 0) && ($greenval["opportunities"] > 0)) {
				$defectpercent	=	$greenval["defects"] / $greenval["opportunities"] * 100;
				$inputstr				.= "input".$count."=".$defectpercent."&";
			} else {
				$inputstr				.= "input".$count."=0&";
			}
		}
		
		for($i = 12; $i > 0; $i--) {
			$fetchmonth		= date("m") - (13- $i);
			$fetchdate		= mktime(0, 0, 0, $fetchmonth, 1, date("Y"));
			
			if($greenscores[$fetchdate]) {
				$monthstr			.= "month".$i."=".date("F", $fetchdate)."&";
			}
		}
		
		$firstmonth	= date("m", mktime(0, 0, 0, (date("m") - $count - 1), 1, date("Y")));
	// }
	
	print("<table height=100% width=100% cellpadding=0 cellspacing=0 border=0>");
	// Row 1, Header {
		print("<tr><td align='center'>");
		print("<embed src='".BASE."/images/Heading.swf'
			FlashVars='heading=SQM'
			quality='high'
			width='".(1300 * $factor)."px'
			height='".(85 * $factor)."px'
			name='header'
			wmode='transparent'
			allowScriptAccess='sameDomain'
			allowFullScreen='false'
			type='application/x-shockwave-flash'
			pluginspage='http://www.macromedia.com/go/getflashplayer' />");
		print("</td></tr>");
	// }
	
	// Row 2, Header {
		print("<tr><td align=center height=1px>");
		print("<embed src='".BASE."/images/Graph_Defects.swf'
			FlashVars='".$inputstr."count=".$count."&startmonth=".$firstmonth."'
			quality='high'
			width='".(1310 * $factor)."px'
			height='".(410 * $factor)."px'
			name='sqm1'
			wmode='transparent'
			allowScriptAccess='sameDomain'
			allowFullScreen='false'
			type='application/x-shockwave-flash'
			pluginspage='http://www.macromedia.com/go/getflashplayer' />");
		print("</td></tr>");
	// }
	
	// Row 3, Smaller trend images {
		print("<tr><td align=center>");
		print("<table cellpadding=0 cellspacing=0 width=100%>");
		
		print("<tr><td align='center'>");
		print("<embed src='".BASE."/images/Sqm.swf'
			FlashVars='".$display["invoicefull"]."'
			quality='high'
			width='".(220 * $factor)."px'
			height='".(100 * $factor)."px'
			name='sqm1'
			wmode='transparent'
			allowScriptAccess='sameDomain'
			allowFullScreen='false'
			type='application/x-shockwave-flash'
			pluginspage='http://www.macromedia.com/go/getflashplayer' />");
		print("</td>");
		
		print("<td align='center'>");
		print("<embed src='".BASE."/images/Sqm.swf'
			FlashVars='".$display["pods"]."'
			quality='high'
			width='".(220 * $factor)."px'
			height='".(100 * $factor)."px'
			name='sqm1'
			wmode='transparent'
			allowScriptAccess='sameDomain'
			allowFullScreen='false'
			type='application/x-shockwave-flash'
			pluginspage='http://www.macromedia.com/go/getflashplayer' />");
		print("</td>");
		
		print("<td align='center'>");
		print("<embed src='".BASE."/images/Sqm.swf'
			FlashVars='".$display["invoiceerrors"]."'
			quality='high'
			width='".(220 * $factor)."px'
			height='".(100 * $factor)."px'
			name='sqm1'
			wmode='transparent'
			allowScriptAccess='sameDomain'
			allowFullScreen='false'
			type='application/x-shockwave-flash'
			pluginspage='http://www.macromedia.com/go/getflashplayer' />");
		print("</td>");
		
		print("<td align='center'>");
		print("<embed src='".BASE."/images/Sqm.swf'
			FlashVars='".$display["shortages"]."'
			quality='high'
			width='".(220 * $factor)."px'
			height='".(100 * $factor)."px'
			name='sqm1'
			wmode='transparent'
			allowScriptAccess='sameDomain'
			allowFullScreen='false'
			type='application/x-shockwave-flash'
			pluginspage='http://www.macromedia.com/go/getflashplayer' />");
		print("</td>");
		
		print("<td align='center'>");
		print("<embed src='".BASE."/images/Sqm.swf'
			FlashVars='".$display["complaints"]."'
			quality='high'
			width='".(220 * $factor)."px'
			height='".(100 * $factor)."px'
			name='sqm1'
			wmode='transparent'
			allowScriptAccess='sameDomain'
			allowFullScreen='false'
			type='application/x-shockwave-flash'
			pluginspage='http://www.macromedia.com/go/getflashplayer' />");
		print("</td>");
		
		print("<td align='center'>");
		print("<embed src='".BASE."/images/Sqm.swf'
			FlashVars='".$display["defects"]."'
			quality='high'
			width='".(220 * $factor)."px'
			height='".(100 * $factor)."px'
			name='sqm1'
			wmode='transparent'
			allowScriptAccess='sameDomain'
			allowFullScreen='false'
			type='application/x-shockwave-flash'
			pluginspage='http://www.macromedia.com/go/getflashplayer' />");
		print("</td></tr>");
		
		print("</table>");
		print("</td></tr>");
	// }
	
	print("</table>");
?>

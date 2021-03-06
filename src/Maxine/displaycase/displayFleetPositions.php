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
		// }
		
		$trackfleets = array(
			22=>array("id"=>22, "name"=>"Fleet A"),
			23=>array("id"=>23, "name"=>"Fleet B"),
			55=>array("id"=>55, "name"=>"Africa Fleet A"),
			56=>array("id"=>56, "name"=>"Africa Fleet B"),
			78=>array("id"=>78, "name"=>"Africa Fleet C"),
			74=>array("id"=>74, "name"=>"Energy")
			);
		
		$top5	= sqlPull(array("table"=>"position_scores", "where"=>"1=1"));
	// }
	
	// Presentation {
	print("<div style='margin:auto; margin-top:15px;'>");
		print("<embed src='".BASE."/images/Heading.swf'
			FlashVars='heading=Fleet Positions Updates'
			quality='high'
			width='".(1300 * $factor)."px'
			height='".(85 * $factor)."px'
			name='header'
			wmode='transparent'
			allowScriptAccess='sameDomain'
			allowFullScreen='false'
			type='application/x-shockwave-flash'
			pluginspage='http://www.macromedia.com/go/getflashplayer' />");
		print("</div>");
		
		print("<table style='width:100%;'>");
		print("<tr>");
		
		foreach ($top5 as $top5key=>$top5val) {
			$varstring	= "heading=".$trackfleets[$top5val["fleet"]]["name"]."&percent=".$top5val["percent"].$top5val["trucks"].$top5val["times"].$top5val["sub"].$top5val["status"];
			print("<td style='width:20%;'>");
			print("<embed src='".BASE."images/positionspeedo.swf'
				FlashVars='".$varstring."'
				quality='high'
				width='".(220*$factor)."px'
				height='".(550*$factor)."px'
				name='graph'
				wmode='transparent'
				allowScriptAccess='sameDomain'
				allowFullScreen='false'
				type='application/x-shockwave-flash'
				pluginspage='http://www.macromedia.com/go/getflashplayer' />");
			print("</td>");
		}
		print("</tr>");
		print("</table>");
?>

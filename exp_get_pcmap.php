<?php

if(isset($_POST['getPCMAP']) && $_POST['getPCMAP'] == 'true'){
    // Build the request body for the MapRoutes API
		$ch = curl_init();
		$MapRoutesRequestBody = array(
			"Map" => [
                "Viewport" => [
                    "Center" => null,
                    "ScreenCenter" => null,
                    "ZoomRadius" => 0,
                    "CornerA" => null,
                    "CornerB" => null,
                    "Region" => 0,
                ],
                "Projection" => 0,
                "Style" => 11,
                "ImageOption" => 0,
                "Width" => 1000,
                "Height" => 800,
                "Drawers" => [
                    0,
                    2,
                    4,
                    6,
                    7,
                    8,
                    9,
                    10,
                    11,
                    16,
                    17,
                    18,
                    20,
                    21,
                    22,
                    23,
                    24,
                    26,
                    27
                ],
                "LegendDrawer" => [["Type" => 0, "DrawOnMap" => true]],
                "GeometryDrawer" => null,
                "PinDrawer" => [
                    "Pins" => $_POST['pin']
                ],
                "PinCategories" => null,
                "TrafficDrawer" => null,
                "MapLayering" => 2,
                "Language" => null,
                "ImageSource" => null,
            ],
            "Routes" => [
                [
                    "RouteId" => null,
                    "Stops" => $_POST['stops'],
                    "Options" => [
                        "AFSetIDs" => null,
                        "BordersOpen" => null,
                        "ClassOverrides" => 0,
                        "DistanceUnits" => 0,
                        "ElevLimit" => null,
                        "FerryDiscourage" => false,
                        "FuelRoute" => false,
                        "HazMatType" => 0,
                        "HighwayOnly" => true,
                        "HubRouting" => false,
                        "OverrideRestrict" => false,
                        "RouteOptimization" => 0,
                        "RoutingType" => 0,
                        "TollDiscourage" => false,
                        "TruckCfg" => null,
                        "VehicleType" => 0,
                    ],
                    "DrawLeastCost" => false,
                    "RouteLegOptions" => null,
                    "StopLabelDrawer" => 0,
                    "routeType" => "Practical",
                    "mode" => "Trucking",
                ],
            ],
		);
		// var_dump($MapRoutesRequestBody);
		curl_setopt_array($ch, array(
				CURLOPT_URL => 'https://pcmiler.alk.com/apis/rest/v1.0/service.svc/mapRoutes?authToken='.$_POST['apikey'],
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => 'POST',
				CURLOPT_POSTFIELDS =>json_encode($MapRoutesRequestBody),
				CURLOPT_HTTPHEADER => array(
					'Content-Type: application/json'
				),
		));

		$response = curl_exec($ch);
		$file_path = "map1.jpg";
		file_put_contents($file_path, $response);
		curl_close($ch);
        echo $file_path;
}
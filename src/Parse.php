<?php 

namespace JumpTwentyFour;

use Parse\ParseQuery;
use Parse\ParseObject;
use Parse\ParseGeoPoint;
use Parse\ParseException;
use Parse\ParseClient;

class Parse {


 	public function __construct($parse_app_id, $parse_rest_api_key, $parse_master_key)
 	{
 		ParseClient::initialize( $parse_app_id, $parse_rest_api_key, $parse_master_key );
 	}


}
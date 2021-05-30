<?php 
// dependancies
	include '../vendor/autoload.php';
	
	// blank array for times to figure out peak hours
	$times = [];
	$time_format = 'nn/jj/yy G:i';

	// days of week peaked
	$countDays=[];

	function calculate_peak_hours($strp_times){
		$results = [];
		foreach ($strp_times as $time) {
			if(isset($results[$time['hour']])){
				$results[$time['hour']] ++;
			}else{
				$results[$time['hour']] = 1;
			}
		}

		return $results;
	}

	echo 'Event Manager Initialized!' . "<br/>";
	## function to clean up zipcodes###########
	// ###########CLEAN ZIPCODES##############
	// ####input: 00 ## output: 00000 #######
	// ##Input: 55555555 ## output 55555####
	function clean_zip($zipcode){
				if(strlen($zipcode) > 5){

				$zipcode = substr($zipcode, 0, 5);

				}else if(strlen($zipcode) < 5){

					while(strlen($zipcode) != 5){
						$zipcode .= '0';
					}
				}

				return $zipcode;
	}

	// function to clean up names of officials array
	// INPUT : [name, name , name] OUPUT : "NAME, NAME, NAME"
	function print_names($arr, $string = ''){
				foreach ($arr as$value) {
					$string .= $value['name'] . ', ';
				}
				return $string;
	}

	// function to get legislators by zipcode 
	// INPUT : 51012(number) OUTPUT($error string or string of legislators for area)
	function legislators_by_zipcode($zip){

				$client = new Google\Client();

				$client->setApplicationName("Civic_Info_Reps");
				$client->setDeveloperKey('AIzaSyClRzDqDh5MsXwnCWi0kOiiBivP6JsSyBw');

				$service = new Google_Service_CivicInfo($client);
					//  if zipcodes has exactly 5 digits assume it is okay
				$representatives = $service->representatives;
				
				try{
				$optParams = [
					'address'=> $zip,
					'levels' => 'country',
					'roles'  => ['legislatorUpperBody', 'legislatorLowerBody']
				];

				$results = $representatives->representativeInfoByAddress($optParams);

				$officials = $results['officials'];
				$officials_names = print_names($officials);
				return 'Your officials are ' . $officials_names;
				}

				

				catch(Exception $e){
					return 'You can find your representatives by visiting www.commoncause.org/take-action/find-elected-officials ';
				}
	}

	// write personal letter to clients;
	function write_letter($personal_letter, $firstName, $officials_names){
				$personal_letter = str_replace('FIRST_NAME', $firstName, $personal_letter);
				$personal_letter = str_replace('LEGISLATORS', $officials_names, $personal_letter);
				return $personal_letter;
	}

	// SAVE letter to file;
	function save_thankyou_letter($id ,$personal_letter){
		// make filename from id
			$file_name = __DIR__."/output/thanks_${id}.html";
			if(!file_exists(__DIR__."/output/")){
				mkdir(__DIR__."/output/");
			}
			// create new file and open for writing
			$file = fopen($file_name, 'w+');
			// write to file
			fwrite($file, $personal_letter);
			// close file
			fclose($file);
	}

	function clean_phone_number($phone_number){
			$condition = strlen($phone_number) == 10 ? true : false;

			if($condition == false){
				if(strlen($phone_number) == 11 && substr($phone_number, 0, 1) == 1 ){
					$phone_number = substr($phone_number, 1);
					$condition == true;
				}
			}

			return $condition == true ? $phone_number : false;

	}

	// input days of week to numbers in k,v associate
	// input $empty countdays array
	// $day of week to add to count days
	function resolve_day_array($days, &$countDays, $day){
			// go through each day until the key == that day number
			// go through countdays and add that value to that keys value of count days array

			foreach($days as $dayNumber => $dayName){
				if($dayNumber == $day){
					if(isset($countDays[$dayName])){
						$countDays[$dayName] ++;
					}else{
						$countDays[$dayName] = 1;
					}

				}
			}
	}


	$attendees = [];
	// open file
	if($file = fopen('../event_attendees.csv', 'r')){

		// load file contents and export each line as an array;
		while(($data = fgetcsv($file)) !== false){

			// push it into our attendees array;
			$attendees[] = $data;

		}

		// close file
		fclose($file);

	}else{
		// error message
		echo 'Failed to load csv';

	}

	echo '<br>';

	foreach ($attendees as $key => $row) {
		$officials = [];
		$string = '';
		$officials_names = '';

		$days = [
			0 => 'Sunday',
			1 => 'Monday',
			2 => 'Tuesday',
			3 => 'Wednesday',
			4 => 'Thursday',
			5 => 'Friday',
			6 => 'Saturday'
		];

		

		

		// print out each sub array of results;
		if($key != 0){
			$id = $row[0];
			$date =   $row[1];
			$firstName = $row[2];
			$lastName = $row[3];
			$email = $row[4];
			$phone = clean_phone_number($row[5]);
			$stAdd = $row[6];
			$city = $row[7];
			$state = $row[8];
			$zip = clean_zip($row[9]);
			$officials_names = legislators_by_zipcode($zip);

			echo "Hi $firstName $lastName. Your zipcode is $zip. " . $officials_names . " <br>";
			// Template our letter for client

			// un comment

			$personal_letter = write_letter(
				 file_get_contents('../resources/templates/letter_attendees.php') ,
				 $firstName ,
				 $officials_names
			);			
			
			$times[] = date_parse_from_format("n/j/y G:i", $date);
			$dayNumber =  date('w', strtotime($date));

			// add day to count days array for days people signed up
			resolve_day_array($days, $countDays, $dayNumber);

			// save the thankyou letter to dir
			save_thankyou_letter($id, $personal_letter);

			
		}

	}

	

	echo '<br>';

	// CALCULATE PEAK HOURS THAT PEOPLE HAVE SIGNED UP
	$peak_hours = calculate_peak_hours($times);
	
	arsort($peak_hours, 1);
	// print out peak hours
	foreach($peak_hours as $hour => $count){
		echo $count . ' people signed up at ' . $hour . 'hrs. <br>';
	}

	// print out peak days
	echo '<br>';
	foreach ($countDays as $day => $value) {
		echo $value . " people signed up on a " . $day . '.<br>';
	}


?>
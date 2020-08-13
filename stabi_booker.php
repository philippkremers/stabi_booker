<?php

// #############################################################################################################
// IMPORTANT: ADJUST THESE PARAMETERS BEFORE RUNNING THE SCRIPT! 

// Please note that Staatsbibliothek only allows to sign up for either afternoon or morning sessions
// set config_only_mornings to FALSE if you want ONLY AFTERNOON sessions and to TRUE if you want ONLY MORNING sessions

$users = array(
    array("first_name" => "Beau", "second_name" => "Brummell", "email" => "beau.brummell@oriel.ox.ac.uk", "stabi_user_number" => "1234567", "config_only_mornings" => true),
    array("first_name" => "Margaret", "second_name" => "Brummell", "email" => "margaret.brummell@oriel.ox.ac.uk", "stabi_user_number" => "7654321", "config_only_mornings" => false)
);

exit; // FINALLY, REMOVE THIS LINE AFTER YOU ADJUSTED THE PARAMETERS 

// #############################################################################################################
// function to check for available slots that match the morning/afternoon preference and return the slot IDs
function new_matching_slot_IDs($source_landing_page, $config_only_mornings) {
    $matching_IDs = array();

    // select raw HTML string of each bookable slot
    $relevant_part_source_landing_page = end(explode('rel="">Terminbuchung Lesesaal</a></h3>', $source_landing_page));
    $raw_html_of_slots = explode('" class="intern">anmelden</a> (noch ', $relevant_part_source_landing_page);
    array_pop($raw_html_of_slots);

    // loop through all bookable slots
    foreach($raw_html_of_slots as $raw_html_of_slot) {
        $skip_ID = false;

        // check if open slot is during morning and terminate function if it does not fit the morning/afternoon preference
        $slot_datetime_wrapper = end(explode("<tr>", $raw_html_of_slot));
        if(strpos($slot_datetime_wrapper, "8.00 Uhr,") !== false) {
            if($config_only_mornings == false) {
                $skip_ID = true;
            }
        } else {
            if($config_only_mornings == true) {
                $skip_ID = true;
            }
        } 

        // if no preference mismatch was detected, add ID number of the available slot returned array
        if ($skip_ID === false) {
            array_push($matching_IDs, end(explode('vor-ort/oeffnungszeiten/terminbuchung/terminbuchung-lesesaal/buchungsformular-lesesaal/?tx_sbbknowledgeworkshop_pi1%5Binput_event%5D=', $slot_datetime_wrapper)));
        }
    }

    return $matching_IDs;
}

// function to book Leesesaal slot for a specific person 
function book_slot($id, $first_name, $second_name, $email, $stabi_user_number) {
    $curl_session = curl_init();
    curl_setopt($curl_session, CURLOPT_URL, "https://staatsbibliothek-berlin.de/vor-ort/oeffnungszeiten/terminbuchung/terminbuchung-lesesaal/buchungsformular-lesesaal/");
    curl_setopt($curl_session, CURLOPT_POST, 1);
    curl_setopt($curl_session, CURLOPT_POSTFIELDS, "no_cache=1&tx_sbbknowledgeworkshop_pi1%5Binput_comments%5D=&tx_sbbknowledgeworkshop_pi1%5Binput_data%5D=on&tx_sbbknowledgeworkshop_pi1%5Binput_email%5D=".$email."&tx_sbbknowledgeworkshop_pi1%5Binput_event%5D=".$id."&tx_sbbknowledgeworkshop_pi1%5Binput_gender%5D=&tx_sbbknowledgeworkshop_pi1%5Binput_init%5D=1&tx_sbbknowledgeworkshop_pi1%5Binput_institution%5D=".$stabi_user_number."&tx_sbbknowledgeworkshop_pi1%5Binput_name%5D=".$first_name."&tx_sbbknowledgeworkshop_pi1%5Binput_phone%5D=&tx_sbbknowledgeworkshop_pi1%5Binput_submit%5D=Absenden&tx_sbbknowledgeworkshop_pi1%5Binput_surname%5D=".$second_name."&tx_sbbknowledgeworkshop_pi1%5Binput_title%5D=1");
    curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);
    $server_output = curl_exec($curl_session);
    curl_close ($curl_session);
    return $server_output;
}

// function to add slot ID to booking log
function add_slot_ID_to_record($id, $stabi_user_number) {
    $current_booking_records = file_get_contents('past_bookings_'.$stabi_user_number.'.txt');
    file_put_contents("past_bookings_".$stabi_user_number.".txt", $current_booking_records.",".$id);
}

// function to check if slot ID is already booked
function is_booked_already($id, $stabi_user_number) {
    $past_bookings_string = file('past_bookings_'.$stabi_user_number.'.txt');
    $past_bookings = explode(",", $past_bookings_string[0]);
    return in_array($id, $past_bookings);
}

// function to add to logfile to track the activities of the booker
function add_to_logfile($new_line) {
    // create new logfile if necessary
    if (!file_exists('logfile.txt')) {
        touch('logfile.txt');
    }

    $current_logfile = file_get_contents('logfile.txt');
    file_put_contents("logfile.txt", $current_logfile.$new_line."\n");
}

// main program starts

// get entire source code of Staatsbibliothek page
$source_landing_page = file_get_contents('https://staatsbibliothek-berlin.de/vor-ort/oeffnungszeiten/terminbuchung/');

// loop through all users
foreach ($users as $user) {
    
    // create new past booking log for user if necessary
    if (!file_exists('past_bookings_'.$user['stabi_user_number'].'.txt')) {
        touch('past_bookings_'.$user['stabi_user_number'].'.txt');
    }

    // loop through all slots that fit the preferences
    $slot_ids = new_matching_slot_IDs($source_landing_page, $user['config_only_mornings']);
    foreach($slot_ids as $slot_id) {

        // check if the bookable slot is already booked
        if(!is_booked_already($slot_id, $user['stabi_user_number'])) {
            book_slot($slot_id, $user['first_name'], $user['second_name'], $user['email'], $user['stabi_user_number']);
            add_slot_ID_to_record($slot_id, $user['stabi_user_number']);
            add_to_logfile(date('Y-m-d H:i:s').": I booked a slot with ID $slot_id for ".$user['first_name'].'!');
        }
    }
}

?>

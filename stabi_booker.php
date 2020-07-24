<?php

// #############################################################################################################
// IMPORTANT: ADJUST THESE PARAMETERS BEFORE RUNNING THE SCRIPT!

// Please note that Staatsbibliothek only allows to sign up for either afternoon or morning sessions
// set $config_only_mornings to FALSE if you want ONLY AFTERNOON sessions and to TRUE if you want ONLY MORNING sessions

$config_first_name = "Beau";
$config_second_name = "Brummell";
$config_email = "beau.brummell@oriel.ox.ac.uk";
$config_stabi_user_number = "7212345";
$config_only_mornings = true;
exit; // FINALLY, REMOVE THIS LINE AFTER YOU ADJUSTED THE PARAMETERS 

// #############################################################################################################

// function to check for available slots that match the morning/afternoon preference and return the slot ID
function new_matching_slot_ID() 
{
    // get entire source code and select the part relevant for reservations of Lesesaal
    $source_landing_page = file_get_contents('https://staatsbibliothek-berlin.de/vor-ort/oeffnungszeiten/terminbuchung/');
    $relevant_part_source_landing_page = end(explode('rel="">Terminbuchung Lesesaal</a></h3>', $source_landing_page));

    // find last occurence of link for Anmeldung
    if(strrpos($relevant_part_source_landing_page, '" class="intern">anmelden</a> (noch ') !== false)
    {
        $slot_data_wrapper = end(explode('" class="intern">anmelden</a> (noch ', $relevant_part_source_landing_page, -1));

        // check if open slot is during morning and terminate function if it does not fit the morning/afternoon preference
        $slot_datetime_wrapper = end(explode("<tr>", $slot_data_wrapper));
        if(strpos($slot_datetime_wrapper, "8.00 Uhr,") !== false) {
            if($GLOBALS['config_only_mornings'] == false) {
                return false;
            }
        } else {
            if($GLOBALS['config_only_mornings'] == true) {
                return false;
            }
        }

        // if no preference mismatch was detected, return ID number of the available slot
        $slot_id = end(explode('vor-ort/oeffnungszeiten/terminbuchung/terminbuchung-lesesaal/buchungsformular-lesesaal/?tx_sbbknowledgeworkshop_pi1%5Binput_event%5D=', $slot_data_wrapper));
        return $slot_id;
    } else {
        return false;
    }
}

// function to remove Umlauts and other funny characters
function remove_umlauts($string) {
    return iconv("utf-8", "ascii//TRANSLIT", $string);
}

// function to  book Leesesaal slot for a specific person 
function book_slot($id, $first_name, $second_name, $email, $stabi_user_number) {
    $curl_session = curl_init();
    curl_setopt($curl_session, CURLOPT_URL, "https://staatsbibliothek-berlin.de/vor-ort/oeffnungszeiten/terminbuchung/terminbuchung-lesesaal/buchungsformular-lesesaal/");
    curl_setopt($curl_session, CURLOPT_POST, 1);
    curl_setopt($curl_session, CURLOPT_POSTFIELDS, "no_cache=1&tx_sbbknowledgeworkshop_pi1%5Binput_comments%5D=&tx_sbbknowledgeworkshop_pi1%5Binput_data%5D=on&tx_sbbknowledgeworkshop_pi1%5Binput_email%5D=".remove_umlauts($email)."&tx_sbbknowledgeworkshop_pi1%5Binput_event%5D=".$id."&tx_sbbknowledgeworkshop_pi1%5Binput_gender%5D=&tx_sbbknowledgeworkshop_pi1%5Binput_init%5D=1&tx_sbbknowledgeworkshop_pi1%5Binput_institution%5D=".$stabi_user_number."&tx_sbbknowledgeworkshop_pi1%5Binput_name%5D=".remove_umlauts($first_name)."&tx_sbbknowledgeworkshop_pi1%5Binput_phone%5D=&tx_sbbknowledgeworkshop_pi1%5Binput_submit%5D=Absenden&tx_sbbknowledgeworkshop_pi1%5Binput_surname%5D=".remove_umlauts($second_name)."&tx_sbbknowledgeworkshop_pi1%5Binput_title%5D=1");
    curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);
    $server_output = curl_exec($curl_session);
    curl_close ($curl_session);
    return $server_output;
}

// function to add slot ID to booking log
function add_slot_ID_to_record($id)
{
    $current_booking_records = file_get_contents("past_bookings.txt");
    file_put_contents("past_bookings.txt", $current_booking_records.",".$id);
}

// function to check if slot ID is already booked
function is_booked_already($id)
{
    $past_bookings_string = file("past_bookings.txt");
    $past_bookings = explode(",", $past_bookings_string[0]);
    return in_array($id, $past_bookings);
}

// main program starts
echo "<!DOCTYPE html><html lang='en'><head><meta charset='utf-8' /><title>stabi_booker</title><meta http-equiv='refresh' content='300'></head><body>";

// check if there is a new bookable slot that matches morning/afternoon preference
$slot_id = new_matching_slot_ID();
if ($slot_id !== false)
{
    echo "<h2>There is a library seat available ".($config_only_mornings ? "in the morning" : "in the afternoon")." with the ID ".$slot_id.".</h2>";
    
    // check if the bookable slot is already booked
    if(is_booked_already($slot_id)) {
        echo "<p>My records show that you already have a reservation.</p>";
    } else {
        book_slot($slot_id, $config_first_name, $config_second_name, $config_email, $config_stabi_user_number);
        echo "<p>I booked the seat for you! :)</p>";
        add_slot_ID_to_record($slot_id);
    }

} else {
    echo "<h1>There are no new library seats available ".($config_only_mornings ? "in the morning" : "in the afternoon")."! :(</h1>";
}

echo "</body></html>";

?>
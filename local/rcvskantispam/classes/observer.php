<?php
namespace local_rcvskantispam;
class local_rcvskantispam_observer
{
	//Users observers
	public static function user_created(\core\event\user_created $event)
    {
        //echo("user created");
        $event_data = $event->get_data();
        error_log("user created - even picked up.",0);
        var_dump(json_encode($event_data));
        die();
    }
	
	public static function user_loggedin(\core\event\user_loggedin $event)
    {
        //echo("user_authenticated");
        $event_data = $event->get_data();
        error_log("user logged in - even picked up.",0);
		var_dump(json_encode($event_data));
        die();
    }	
}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Form-Project</title>
</head>
<body>

<?php

include_once('databaseConnector.php');

session_start();

$userId = $_POST['userId'];
$event = $_POST['event'];
$tickets = $_POST['tickets'];
$subject = "Event Confirmation";
$url = '<a href="">Click here to go back to form</a>';

$db = db_connect();
if (!empty($_POST['tickets'])) {

    if ($db !== false) {

        $qry = $db->prepare("SELECT tickets_available FROM events WHERE event_name = :event");
        $qry->bindParam(':event', $event);
        $qry->execute();
        $result = $qry->fetch(PDO::FETCH_ASSOC);
        if($qry->rowCount() === 1){
            $tickets_avail = $result['tickets_available'];
            if ($tickets_avail < $tickets){
                die("The amount of tickets you have chosen are not available. There are only " .
                    $tickets_avail . " tickets available.");
            } else{
                $qry = $db->prepare("UPDATE events SET tickets_available = tickets_available - :tickets
                WHERE event_name = :event");
                $qry->bindParam(':tickets', $tickets);
                $qry->bindParam(':event', $event);
                $qry->execute();
            }
        } else{

            $error = "The event you have selected does not exist. ";
            die($error . $url);
        }




        //date and time and destination and ticket cost
        $qry = $db->prepare("SELECT DATE_FORMAT(date,'%d/%m/%Y') as date, DATE_FORMAT(time, '%h:%i') as time,
                        CONCAT(venue, ', ', street, ', ', county) as address, ticket_cost, event_id
		FROM events WHERE event_name = :event");
        $qry->bindParam(':event', $event);
        $qry->execute();
        $result = $qry->fetch(PDO::FETCH_ASSOC);

        if ($qry->rowCount() === 1) {
            $date = $result['date'];
            $time = $result['time'];
            $venue = $result['address'];
            $cost = $result['ticket_cost'];
            $eventId = $result['event_id'];
        } else {
            $qry = $db->prepare("UPDATE events SET tickets_available = tickets_available + :tickets
                WHERE event_name = :event");
            $qry->bindParam(':tickets', $tickets);
            $qry->bindParam(':event', $event);
            $qry->execute();

            $error = "The event you have selected does not exist. ";
            die($error . $url);
        }
        //user name and email
        $qry = $db->prepare("SELECT first_name, last_name, email
    FROM user WHERE user_id = :user_id");
        $qry->bindParam(':user_id', $userId);
        $qry->execute();
        $result = $qry->fetch(PDO::FETCH_ASSOC);

        if ($qry->rowCount() === 1) {
            $firstName = $result['first_name'];
            $lastName = $result['last_name'];
            $email = $result['email'];
        } else {
            $qry = $db->prepare("UPDATE events SET tickets_available = tickets_available + :tickets
                WHERE event_name = :event");
            $qry->bindParam(':tickets', $tickets);
            $qry->bindParam(':event', $event);
            $qry->execute();

            $error = "You do not have a valid user id. ";
            die($error . $url);
        }


        //confirmation number
        date_default_timezone_set('Europe/Dublin');
        $currentDate = date('m/d/Y h:i:s a', time());

        $currentDate .= $eventId;
        $currentDate .= $userId;
        $confirmationNo = sha1($currentDate);
        //amount owed
        $amount = $tickets*$cost;

        $qry = $db->prepare("INSERT INTO going_to (user_id, event_id, confirmation_no, amount_owed)
    VALUES (:user_id, :event_id, :confirmation, :amount)");
        $qry->bindParam(':user_id', $userId);
        $qry->bindParam(':event_id', $eventId);
        $qry->bindParam(':confirmation', $confirmationNo);
        $qry->bindParam(':amount', $amount);
        $qry->execute();

        $message = "
		<html>
		<head>
		<meta charset='utf-8'/>
		<title>Booking Confirmation</title>
		</head>
		<body>
			<p> Your registration number is " . $confirmationNo . "</p>
			---------------------------------------------------
			<p>" . $firstName . " " . $lastName . "</p>
			<p>Number of Tickets: " . $tickets . "</p>
			<p>Amount owed: " . $amount . "</p>
			<p>Event: " . $event . "</p>
			<p>Place of Event: " . $venue . "</p>
			<p>Date: " . $date . " </p>
			<p>Time: " . $time . "</p>
			---------------------------------------------------------------------
			<p>Thank you for your booking.
			In order to attend the event you must print this page and  be able to
			present it at the gates of the event. </p>

		</body>
		</html>
		";
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: therow17@gmail.com" . "\r\n";
        mail($email, $subject, $message, $headers);
    }
} else{
    echo "Please select the amount of tickets you would like for this event";
}
?>
</body>
</html>


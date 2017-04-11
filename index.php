<?php



require($_SERVER['DOCUMENT_ROOT'] . "/includes/config.php");

$table = new \DB\MySQLi\MySQLi_Table($sql, "sessions");
$fields = ['session_id', 'session_date_started', 'session_ip'];
$values = [session_id(), $date_started, $ip];
echo $table->insert($fields, $values);


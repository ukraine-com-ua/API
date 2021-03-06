<?php
require_once 'HostingAPI.class.php';

try {
	$HostingAPI = new HostingAPI('YOUR KEY');
	$HostingAPI->addDomain('DOMAIN NAME');
} catch (Exception $e) {
	// В случае ошибки выведет сообщение
	echo $e->getMessage();
}
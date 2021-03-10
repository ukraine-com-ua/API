<?php
require_once 'HostingAPI.class.php';

try {
	$HostingAPI = new HostingAPI('YOUR KEY');
	$HostingAPI->addDomain('DOMAIN NAME');
	$domains = $HostingAPI->getDomains();
	$dns = $HostingAPI->getDNS($domains['test.xyz']['id']);
	$HostingAPI->addDNS($domains['test.xyz']['id'], 'TXT', '@', 'test');
	$HostingAPI->deleteDNS($dns[100]['id']);
} catch (Exception $e) {
	// В случае ошибки выведет сообщение
	echo $e->getMessage();
}
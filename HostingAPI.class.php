<?php

/**
 * Hosting Ukraine API
 */
class HostingAPI {
	/**
	 * Ключ доступа
	 * @var string
	 */
	private $key = '';

	/**
	 * Последнее сообщение об успешном выполнении операции
	 * @var array
	 */
	private $success = [];

	/**
	 * HostingAPI constructor.
	 * @param string $key
	 */
	public function __construct(string $key) {
		$this->key = $key;
	}

	/**
	 * Получаем последнее сообщение системы об успешном выполнении операции
	 * Этот метод может потребоваться при отладке
	 */
	public function getLastSuccessMessage():array {
		return $this->success;
	}

	/**
	 * Добавление домена на NS сервера
	 * @param string $domain - доменное имя
	 * @return array
	 */
	public function addDomain(string $domain):int {
		$response = $this->apiCall('dns/add_foreign_domain', ['domain_name' => $domain]);
		return (int)$response['domain_id'];
	}

	/**
	 * DNS записи домена
	 * @param int $domain_id - id домена из getDomains()
	 * @return array
	 */
	public function getDNS(int $domain_id):array {
		$response = $this->apiCall('dns/records_list', ['domain_id' => $domain_id]);
		return $response['response']['list'];
	}

	/**
	 * Добавляет запись на DNS сервере
	 * @param int $domain_id - идентификатор домена из getDomains()
	 * @param string $type - A, AAAA, ALIAS, CAA, CNAME, MX, NS, TXT
	 * @param string $record - наименование субдомена: www, @, mail ... для внесения записей в основной домен указывается @
	 * @param string $data - IP адрес, доменное имя или текстовая запись
	 * @param int $priority - приоритет, для MX записи
	 */
	public function addDNS(int $domain_id, string $type, string $record, string $data, int $priority = 0):void {
		$this->apiCall('dns/records_add', [
			'domain_id' => $domain_id,
			'type' => strtoupper($type),
			'record' => $record,
			'priority' => $priority,
			'data' => $data
		]);
	}

	/**
	 * Удаление записи с DNS сервера
	 * @param int $subdomain_id - значение берем из getDNS()['id']
	 */
	public function deleteDNS(int $subdomain_id):void {
		$this->apiCall('dns/record_delete', ['subdomain_id' => $subdomain_id]);
	}

	/**
	 * Список доменов
	 * @return array
	 */
	public function getDomains():array {
		$response = $this->apiCall('dns/list', []);
		return $response['response']['list'];
	}

	/**
	 * Запрос на сервер с API
	 * @param string $action
	 * @param array $post
	 * @return mixed
	 */
	private function apiCall(string $action, array $post) {
		// Отправляем запрос на сервер хостинг провайдера
		$ch = curl_init('https://adm.tools/action/'.$action.'/');
		curl_setopt_array($ch, [
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => array("Authorization: Bearer ".$this->key),
			CURLOPT_POSTFIELDS => $post,
			CURLOPT_VERBOSE => true
		]);
		$json = curl_exec($ch);
		$response = json_decode($json, true);

		// В случае ошибки выбрасываем исключение
		if ($response['result'] == false) {
			throw new Exception(implode("|", $response['messages']['error']));
		}

		// Сохраняем последнее сообщение об успешном выполнении операции
		$this->success = $response['messages']['success'] ?? [];

		return $response;
	}
}
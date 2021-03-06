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
		$response = $this->apiCall('action/dns/add_foreign_domain/', ['domain_name' => $domain]);
		return (int)$response['domain_id'];
	}

	/**
	 * Запрос на сервер с API
	 * @param string $action
	 * @param array $post
	 * @return mixed
	 */
	private function apiCall(string $action, array $post) {
		// Отправляем запрос на сервер хостинг провайдера
		$ch = curl_init("https://adm.tools/".$action);
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
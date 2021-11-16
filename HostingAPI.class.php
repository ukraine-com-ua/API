<?php
/**
 * Hosting Ukraine API
 */

class HostingAPI {
	/**
	 * Ключ доступа
	 * @var string
	 */
	private $auth_token = '';

	/**
	 * Результат работы API запроса
	 *
	 * @return bool
	 */
	public $result = true;

	/**
	 * HostingAPI constructor.
	 *
	 * @param string $auth_token
	 */
	public function __construct(string $auth_token) {
		$this->auth_token = $auth_token;
	}

	/**
	 * Получение ID записи по её значению
	 * https://epp.ua/get_id
	 *
	 * @param string $object_type
	 * @param string $object_name
	 * @return int
	 * @throws Exception
	 */
	public function getObjectId(string $object_type, string $object_name): ?int {
		$result = $this->apiCall('get_id', [
			'type' => $object_type,
			'name' => $object_name,
		]);

		if (!$this->result) {
			return null;
		}

		$id = current($result['response']);

		return (int) $id;
	}

	/**
	 * Текущий баланс учётной записи
	 * https://epp.ua/billing/balance_get
	 *
	 * @return float
	 * @throws Exception
	 */
	public function getBalance(): float {
		$result = $this->apiCall('billing/balance_get');
		return isset($result['response']['balance']) ? floatval($result['response']['balance']) : 0;
	}

	/**
	 * Получение списка доменных зон
	 * https://epp.ua/domain/zones
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getZones(): array {
		$result = $this->apiCall('domain/zones');
		return isset($result['response']['list']) ? $result['response']['list'] : [];
	}

	/**
	 * Проверка доступности домена
	 * https://epp.ua/domain/check
	 *
	 * @param string $domain
	 * @return array
	 * @throws Exception
	 */
	public function checkDomain(string $domain): array {
		$result = $this->apiCall('domain/check', ['domain' => $domain]);
		return array_merge([
			'is_available' => $this->result,
		], $result['response']);
	}

	/**
	 * Добавление домена на NS сервера
	 * https://epp.ua/dns/add_foreign_domain
	 *
	 * @param string $domain - доменное имя
	 * @return int
	 * @throws Exception
	 */
	public function addDomain(string $domain): int {
		$result = $this->apiCall('dns/add_foreign_domain', ['domain_name' => $domain]);
		return (int) $result['response']['domain_id'];
	}

	/**
	 * DNS записи домена
	 * https://epp.ua/dns/records_list
	 *
	 * @param int $domain_id - id домена из getDomains()
	 * @return array
	 * @throws Exception
	 */
	public function getDNS(int $domain_id): array {
		$result = $this->apiCall('dns/records_list', ['domain_id' => $domain_id]);
		return $result['response']['list'];
	}

	/**
	 * Добавляет запись на DNS сервере
	 * https://epp.ua/dns/record_add
	 *
	 * @param int $domain_id - идентификатор домена из getDomains()
	 * @param string $type - A, AAAA, ALIAS, CAA, CNAME, MX, NS, TXT
	 * @param string $record - наименование субдомена: www, @, mail ... для внесения записей в основной домен указывается @
	 * @param string $data - IP-адрес, доменное имя или текстовая запись
	 * @param int $priority - приоритет, для MX записи
	 * @return bool
	 * @throws Exception
	 */
	public function addDNS(int $domain_id, string $type, string $record, string $data, int $priority = 0): bool {
		$this->apiCall('dns/record_add', [
			'domain_id' => $domain_id,
			'type' => strtoupper($type),
			'record' => $record,
			'priority' => $priority,
			'data' => $data
		]);
		return $this->result;
	}

	/**
	 * Удаление записи с DNS сервера
	 * https://epp.ua/dns/record_delete
	 *
	 * @param int $subdomain_id - значение берем из getDNS()['id']
	 * @return bool
	 * @throws Exception
	 */
	public function deleteDNS(int $subdomain_id): bool {
		$this->apiCall('dns/record_delete', ['subdomain_id' => $subdomain_id]);
		return $this->result;
	}

	/**
	 * Список доменов учетной записи
	 * https://epp.ua/dns/list
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getDomains(): array {
		$result = $this->apiCall('dns/list', []);
		return $result['response']['list'];
	}

	/**
	 * Запрос на сервер с API
	 *
	 * @param string $action
	 * @param array $post
	 * @return mixed
	 * @throws Exception
	 */
	public function apiCall(string $action, array $post = []): ?array {
		$action = self::fixActionPath($action);
		
		// Отправляем запрос на сервер хостинг провайдера
		$ch = curl_init("https://adm.tools/action/{$action}/");
		curl_setopt_array($ch, [
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => ["Authorization: Bearer {$this->auth_token}"],
			CURLOPT_POSTFIELDS => http_build_query($post),
			CURLOPT_NOPROGRESS => true,
			CURLOPT_VERBOSE => false
		]);
		$json = curl_exec($ch);
		$response = @json_decode($json, true);

		$this->result = isset($response['result']) && $response['result'];

		return $response;
	}

	/**
	 * Преобразование пути обработчика к стандартному виду
	 *
	 * @param string $action
	 * @return string
	 * @throws Exception
	 */
	private static function fixActionPath(string $action): string {
		$action = preg_replace('/^(https?:\/\/(www)?adm\.tools)?\/action/', '', $action);
		$action = trim($action, '/');

		if (empty($action)) {
			throw new Exception('Empty action name');
		}

		return $action;
	}
}

<?php

declare(strict_types=1);
namespace Controllers;

class Controller
{
	protected $payload = null;
	protected $command = null;

	public function __construct()
	{
		$body = file_get_contents('php://input');
		if (!empty($body)) {
			$payload = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
			if (!empty($payload['command'])) {
				$this->command = $payload['command'][0];
				$this->payload = $payload['message'];
			} else {
				$this->payload = $payload;
			}
		}
	}

	protected function argument(): ?string
	{
		if (!$this->command) return null;

		$content = $this->payload['content'] ?? null;
		if (!$content) return null;

		// TODO: find instead the first pos of the first command[]
		// and determine the prefix to be anything before, to account
		// for multichar prefixes
		// OR get the prefix from env/config
		$prefix = $content[0] ?? null;
		if (!$prefix) return null;

		$known = $prefix . $this->command;
		if (strpos($content, $known) !== 0) return null;
		return trim(substr($content, strlen($known)));
	}

	protected function help(): void
	{
		if (preg_match('/^-{0,2}help$/i', $this->argument())) {
			$this->show_help();
		}
	}

	protected const HELP_NAME = null;
	protected function show_help(): void
	{
		$klass = explode("\\", static::class);
		$klass = end($klass);
		$name = static::HELP_NAME ?? strtolower($klass);
		$this->redirect("/command/help/$name");
	}

	protected function end(int $status = 200): void
	{
		throw new \Exceptions\End($status);
	}

	protected function redirect(string $url, int $code = 307): void
	{
		header('location: '.$url);
		$this->end($code);
	}

	private $type_sent = false;
	protected function send_type(string $type): void
	{
		if (!$this->type_sent) {
			header("content-type: $type");
			$this->type_sent = $type;
		} else if ($this->type_sent != $type) {
			throw new \Exceptions\ReplyTypeMismatch($this->type_sent, $type);
		}
	}

	protected function reply(string $content, int $channel_id = null, bool $once = false): void
	{
		$this->send_type('application/json');
		$act = new \Acts\CreateMessage($content, $channel_id);

		if ($once) {
			$act = "$act";
			header('content-length: ' . strlen($act));
			echo $act;
			$this->end();
		} else {
			$act->send();
		}
	}

	protected function assign_role(int $role_id, int $user_id, ?int $server_id = null, ?string $reason = null): void
	{
		$this->send_type('application/json');
		(new \Acts\AssignRole($role_id, $user_id, $server_id, $reason))->send();
	}

	protected function remove_role(int $role_id, int $user_id, ?int $server_id = null, ?string $reason = null): void
	{
		$this->send_type('application/json');
		(new \Acts\RemoveRole($role_id, $user_id, $server_id, $reason))->send();
	}
}

<?php

/// plot (prompt) - Get a random plot/prompt.
///
/// You can optionally filter by theme. Currently available:
/// `general`, `fantasy`, `sci-fi`, `crime`.

declare(strict_types=1);
namespace Controllers\Command;

class Plot extends \Controllers\Controller
{
	public function post(): void
	{
		$this->help();

		$q = \Models\Plot::query();

		if (!empty($arg = $this->argument())) {
			$q = $q->where('theme', 'LIKE', "%{$arg}%");
		}

		$plot = $q->inRandomOrder()->first();

		$this->reply(
			sprintf("> %s\n— %s [**%s**]", $plot->text, $plot->author, $plot->theme),
			null,
			true,
		);
	}
}

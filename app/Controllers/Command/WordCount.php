<?php

/// wc (wordcount) - Get your wordcount from the nano website.
///
/// You need to set your novel "ID" to setup. When you go to your
/// your nanowrimo pages and open the main page for your project,
/// the URL will look like `https://nanowrimo.org/participants/your-name/projects/some-name`.
/// Your project ID is the `some-name` part. Copy just that and tell
/// me about it with `!wc set novel some-name`.
///
/// Thereafter, get your wordcount and stats with `!wc`.
///
/// During november, your goal is not editable on the site. You can
/// override it here to get correct stats with `!wc set goal WORDS`.

declare(strict_types=1);
namespace Controllers\Command;

use Models\Novel;

class WordCount extends \Controllers\Controller
{
	public function post(): void
	{
		$this->help();

		$userid = $_SERVER['HTTP_ACCORD_AUTHOR_ID'] ?? $_SERVER['HTTP_ACCORD_USER_ID'] ?? null;
		if (!$userid) throw new \Exception('no user id, cannot proceed?!');

		if (!empty($arg = $this->argument())) {
			$args = preg_split('/\s+/', $arg);
			switch (trim("{$args[0]} {$args[1]}")) {
			case '':
				break;

			case 'set goal':
				$novel = Novel::where('discord_user_id', $userid)->first();
				if (!$novel) {
					$this->reply('🛑 no novel set', null, true);
					return;
				}

				$goal = (int) str_replace('k', '000', $args[2] ?? '');

				if ($goal) {
					$novel->goal_override = $goal;
					$novel->save();
				} else {
					$this->reply('that doesn’t look like a number to me', null, true);
					return;
				}
				break;

			case 'unset goal':
				$novel = Novel::where('discord_user_id', $userid)->first();
				if (!$novel) {
					$this->reply('🛑 no novel set', null, true);
					return;
				}

				$novel->goal_override = null;
				$novel->save();
				break;

			case 'set novel':
			default:
				Novel::updateOrCreate(['discord_user_id' => $userid], ['novel' => $args[2] ?? $args[0]]);
			}
		}

		$novel = Novel::where('discord_user_id', $userid)->first();
		if (!$novel) $this->show_help();

		try {
			$title = $novel->title();
			$count = $novel->wordcount();
			$goal = $novel->goal();
			$progress = $novel->progress();

			$is_pal = Palindrome::is_pal($count);
			$paldeco = $is_pal ? '✨' : '';

			$deets = implode(', ', array_filter([
				round($progress->percent, 2) . '% done',
				static::on_track($progress->today->diff ?? null, ' today'),
				static::on_track($progress->live->diff ?? null, ' live'),
				($goal == $novel->default_goal() ? null : (static::numberk($goal).' goal')),
				($is_pal ? null : ((Palindrome::next($count) - $count) . ' to next pal')),
			]));

			$this->reply("“{$title}”: **{$paldeco}{$count}{$paldeco}** words ($deets)", null, true);
		} catch (\GuzzleHttp\Exception\ClientException $err) {
			$res = $err->getResponse();
			$this->reply("⚠️ Error: {$res->getStatusCode()} {$res->getReasonPhrase()}", null, true);
		}
	}

	private static function numberk(int $count): string
	{
		if ($count < 1000) {
			return "{$count}";
		} else if ($count < 10000) {
			return round($count / 1000, 1).'k';
		} else {
			return round($count / 1000).'k';
		}
	}

	private static function on_track(?int $diff, string $append = ''): ?string
	{
		if (is_null($diff)) return null;

		if ($diff == 0) {
			return 'on track';
		} else if ($diff < 0) {
			$diff = abs($diff);
			$state = 'behind';
		} else {
			$state = 'ahead';
		}

		return static::numberk($diff) . " {$state}{$append}";
	}
}

<?php

namespace W7\App\Controller;

use W7\App\Model\Entity\Fortune;
use W7\App\Model\Entity\World;
use W7\Core\Controller\ControllerAbstract;
use W7\Http\Message\Server\Request;

class TestController extends ControllerAbstract {
	public function json(Request $request) {
		return [
			'message' => 'Hello, World!'
		];
	}

	public function db() {
		return World::query()->where('id', \mt_rand(1, 10000))->first()->toArray(); // to compare with find()
	}

	public function queries(Request $request, $queries = 1) {
		$rows = [];
		$numbers = $this->getUniqueRandomNumbers($this->clamp($queries));
		foreach ($numbers as $id) {
			$rows[] = World::query()->find($id);
		}

		return $rows;
	}

	public function fortunes() {
		$rows = Fortune::all();

		$insert = new Fortune();
		$insert->id = 0;
		$insert->message = 'Additional fortune added at request time.';

		$rows->add($insert);
		$rows = $rows->sortBy('message');

		return $this->render('result', [
			'message' => $rows->toArray()
		]);
	}

	public function updates(Request $request, $queries = 1) {
		$rows = [];

		$numbers = $this->getUniqueRandomNumbers($this->clamp($queries));
		foreach ($numbers as $id) {
			$row = World::query()->find($id);
			$oldId = $row->randomNumber;
			do {
				$newId = mt_rand(1, 10000);
			} while ($oldId === $newId);
			$row->randomNumber = $newId;
			do {
				try {
					$saved = $row->save();
				} catch (\Exception $e) {
					$saved = false;
				}
			} while (! $saved);
			$rows[] = $row;
		}

		return $rows;
	}

	public function plaintext() {
		return $this->response()->withHeader('Content-Type', 'text/plain')->withContent('Hello, World!');
	}

	private function clamp($value): int {
		if (! \is_numeric($value) || $value < 1) {
			return 1;
		} else if ($value > 500) {
			return 500;
		} else {
			return $value;
		}
	}

	private function getUniqueRandomNumbers($count) {
		$res = [];
		do {
			$res[\mt_rand(1, 10000)] = 1;
		} while (\count($res) < $count);
		\ksort($res);
		return \array_keys($res);
	}
}
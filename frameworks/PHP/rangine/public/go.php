<?php

require_once dirname(__DIR__, 1) . '/vendor/autoload.php';

use W7\App;
use Spiral\RoadRunner\PSR7Client;
use Spiral\Goridge\RelayInterface;
use W7\Contract\Event\EventDispatcherInterface;
use W7\Core\Route\RouteDispatcher;
use W7\Core\Route\RouteMapping;
use W7\Core\Server\ServerEvent;
use W7\Fpm\Server\Dispatcher;
use W7\Http\Session\Middleware\SessionMiddleware;
use W7\Http\Message\Outputer\FpmResponseOutputer;
use W7\Http\Message\Server\Request;
use W7\Http\Message\Server\Response as Psr7Response;
use Laminas\Diactoros\ServerRequest;

class Server extends \W7\Fpm\Server\Server {
	public function getType() {
		return 'go';
	}

	public function start() {
		$psr7_client = $this->createPsr7Client($this->createStreamRelay());
		while ($req = $psr7_client->acceptRequest()) {
			try {
				$request = $this->loadFromGoRequest($req);

				$response = new Psr7Response();
				$response->setOutputer(new FpmResponseOutputer());
				$response = $this->dispatch($request, $response);

				$psr7_client->respond($response);
			} catch (Throwable $e) {
				$psr7_client->getWorker()->error($e->getMessage());
			}
		}
	}

	public function loadFromGoRequest(ServerRequest $goRequest) {
		$protocol = $goRequest->getProtocolVersion() ?? '1.1';
		$protocol = str_replace('HTTP/', '', $protocol);
		$body = $goRequest->getBody();

		$request = new Request(
			$goRequest->getMethod() ?? 'GET',
			$goRequest->getUri()->getPath(),
			$goRequest->getHeaders(),
			$body,
			$protocol
		);

		return $request->withCookieParams([])
			->withCookieParams($goRequest->getCookieParams())
			->withServerParams($goRequest->getServerParams())
			->withQueryParams($goRequest->getQueryParams())
			->withParsedBody($goRequest->getParsedBody())
			->withBodyParams($goRequest->getBody()->getContents())
			->withUploadedFiles($goRequest->getUploadedFiles());
	}

	protected function dispatch($request, $response) {
		/**
		 * @var Dispatcher $dispatcher
		 */
		$dispatcher = App::getApp()->getContainer()->singleton(Dispatcher::class);
		$dispatcher->getMiddlewareMapping()->addBeforeMiddleware(SessionMiddleware::class);
		$dispatcher->setRouterDispatcher(RouteDispatcher::getDispatcherWithRouteMapping(RouteMapping::class, $this->getType()));

		App::getApp()->getContainer()->singleton(EventDispatcherInterface::class)->dispatch(ServerEvent::ON_USER_BEFORE_REQUEST, [$request, $response, $this->getType()]);

		$response = $dispatcher->dispatch($request, $response);

		App::getApp()->getContainer()->singleton(EventDispatcherInterface::class)->dispatch(ServerEvent::ON_USER_AFTER_REQUEST, [$request, $response, $this->getType()]);

		return $response;
	}

	/**
	 * @param resource|mixed $in Must be readable
	 * @param resource|mixed $out Must be writable
	 *
	 * @return RelayInterface
	 */
	protected function createStreamRelay($in = \STDIN, $out = \STDOUT): RelayInterface {
		return new \Spiral\Goridge\StreamRelay($in, $out);
	}

	/**
	 * @param RelayInterface $stream_relay
	 *
	 * @return PSR7Client
	 */
	protected function createPsr7Client(RelayInterface $stream_relay): PSR7Client {
		return new PSR7Client(new \Spiral\RoadRunner\Worker($stream_relay));
	}
}

new App();

(new Server())->start();

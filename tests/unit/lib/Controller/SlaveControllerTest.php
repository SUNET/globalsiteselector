<?php
/**
 * @copyright Copyright (c) 2017 Bjoern Schiessle <bjoern@schiessle.org>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\GlobalSiteSelector\Tests\Unit\Controller;

use Firebase\JWT\JWT;
use OCA\GlobalSiteSelector\AppInfo\Application;
use OCA\GlobalSiteSelector\Controller\SlaveController;
use OCA\GlobalSiteSelector\GlobalSiteSelector;
use OCA\GlobalSiteSelector\Service\SlaveService;
use OCA\GlobalSiteSelector\TokenHandler;
use OCA\GlobalSiteSelector\UserBackend;
use OCP\IConfig;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Security\ICrypto;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class SlaveControllerTest extends TestCase {
	private IRequest $request;
	private GlobalSiteSelector $gss;
	private LoggerInterface $logger;
	private IUserSession $userSession;
	private IURLGenerator $urlGenerator;
	private ICrypto $crypto;
	private TokenHandler $tokenHandler;
	private IUserManager $userManager;
	private UserBackend $userBackend;
	private ISession $session;
	private SlaveService $slaveService;
	private IConfig $config;

	public function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->gss = $this->getMockBuilder(GlobalSiteSelector::class)
			->disableOriginalConstructor()->getMock();
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->crypto = $this->createMock(ICrypto::class);
		$this->tokenHandler = $this->getMockBuilder(TokenHandler::class)
			->disableOriginalConstructor()->getMock();
		$this->userManager = $this->createMock(IUserManager::class);
		$this->userBackend = $this->getMockBuilder(UserBackend::class)
			->disableOriginalConstructor()->getMock();
		$this->session = $this->createMock(ISession::class);
		$this->slaveService = $this->createMock(SlaveService::class);
		$this->config = $this->createMock(IConfig::class);
	}

	/**
	 * @param array $mockMathods
	 * @return SlaveController|\PHPUnit_Framework_MockObject_MockObject
	 */
	private function getInstance(array $mockMathods = []) {
		return $this->getMockBuilder(SlaveController::class)
			->setConstructorArgs(
				[
					'gss-tests',
					$this->request,
					$this->gss,
					$this->userSession,
					$this->urlGenerator,
					$this->crypto,
					$this->tokenHandler,
					$this->userManager,
					$this->userBackend,
					$this->session,
					$this->slaveService,
					$this->config,
					$this->logger
				]
			)->setMethods($mockMathods)->getMock();
	}

	public function testDecodeJwt() {
		$controller = $this->getInstance();
		$jwtKey = 'jwtkey';
		$encryptedPassword = 'password-encrypted';
		$plainPassword = 'password';

		$token = [
			'uid' => 'user',
			'password' => $encryptedPassword,
			'options' => json_encode(['option1' => 'foo']),
			'exp' => time() + 300, // expires after 5 minutes
		];

		$jwt = JWT::encode($token, $jwtKey, Application::JWT_ALGORITHM);

		$this->gss->expects($this->any())->method('getJwtKey')->willReturn($jwtKey);
		$this->crypto->expects($this->once())->method('decrypt')->with($encryptedPassword, $jwtKey)
			->willReturn($plainPassword);

		list($uid, $password, $options) = $this->invokePrivate($controller, 'decodeJwt', [$jwt]);

		$this->assertSame('user', $uid);
		$this->assertSame($plainPassword, $password);
		$this->assertSame($options, ['option1' => 'foo']);
	}
}

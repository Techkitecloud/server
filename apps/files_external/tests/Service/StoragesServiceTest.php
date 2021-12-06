<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Files_External\Tests\Service;

use OC\Files\Filesystem;

use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\Auth\InvalidAuth;
use OCA\Files_External\Lib\Backend\Backend;
use OCA\Files_External\Lib\Backend\InvalidBackend;
use OCA\Files_External\Lib\StorageConfig;
use OCA\Files_External\NotFoundException;
use OCA\Files_External\Service\BackendService;
use OCA\Files_External\Service\DBConfigService;
use OCA\Files_External\Service\StoragesService;
use OCP\AppFramework\IAppContainer;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IStorage;
use OCP\IMemcache;
use OCP\IUser;

class CleaningDBConfig extends DBConfigService {
	private $mountIds = [];

	public function addMount($mountPoint, $storageBackend, $authBackend, $priority, $type) {
		$id = parent::addMount($mountPoint, $storageBackend, $authBackend, $priority, $type); // TODO: Change the autogenerated stub
		$this->mountIds[] = $id;
		return $id;
	}

	public function clean() {
		foreach ($this->mountIds as $id) {
			$this->removeMount($id);
		}
	}
}

/**
 * @group DB
 */
abstract class StoragesServiceTest extends \Test\TestCase {

	/**
	 * @var StoragesService
	 */
	protected $service;

	/** @var BackendService */
	protected $backendService;

	/**
	 * Data directory
	 *
	 * @var string
	 */
	protected $dataDir;

	/** @var  CleaningDBConfig */
	protected $dbConfig;

	/**
	 * Hook calls
	 *
	 * @var array
	 */
	protected static $hookCalls;

	/**
	 * @var \PHPUnit\Framework\MockObject\MockObject|\OCP\Files\Config\IUserMountCache
	 */
	protected $mountCache;

	protected function setUp(): void {
		parent::setUp();
		$this->dbConfig = new CleaningDBConfig(\OC::$server->getDatabaseConnection(), \OC::$server->getCrypto());
		self::$hookCalls = [];
		$config = \OC::$server->getConfig();
		$this->dataDir = $config->getSystemValue(
			'datadirectory',
			\OC::$SERVERROOT . '/data/'
		);
		\OCA\Files_External\MountConfig::$skipTest = true;

		$this->mountCache = $this->createMock(IUserMountCache::class);

		// prepare BackendService mock
		$this->backendService =
			$this->getMockBuilder('\OCA\Files_External\Service\BackendService')
				->disableOriginalConstructor()
				->getMock();

		$authMechanisms = [
			'identifier:\Auth\Mechanism' => $this->getAuthMechMock('null', '\Auth\Mechanism'),
			'identifier:\Other\Auth\Mechanism' => $this->getAuthMechMock('null', '\Other\Auth\Mechanism'),
			'identifier:\OCA\Files_External\Lib\Auth\NullMechanism' => $this->getAuthMechMock(),
		];
		$this->backendService->method('getAuthMechanism')
			->willReturnCallback(function ($class) use ($authMechanisms) {
				if (isset($authMechanisms[$class])) {
					return $authMechanisms[$class];
				}
				return null;
			});
		$this->backendService->method('getAuthMechanismsByScheme')
			->willReturnCallback(function ($schemes) use ($authMechanisms) {
				return array_filter($authMechanisms, function ($authMech) use ($schemes) {
					return in_array($authMech->getScheme(), $schemes, true);
				});
			});
		$this->backendService->method('getAuthMechanisms')
			->willReturn($authMechanisms);

		$sftpBackend = $this->getBackendMock('\OCA\Files_External\Lib\Backend\SFTP', '\OCA\Files_External\Lib\Storage\SFTP');
		$backends = [
			'identifier:\OCA\Files_External\Lib\Backend\DAV' => $this->getBackendMock('\OCA\Files_External\Lib\Backend\DAV', '\OC\Files\Storage\DAV'),
			'identifier:\OCA\Files_External\Lib\Backend\SMB' => $this->getBackendMock('\OCA\Files_External\Lib\Backend\SMB', '\OCA\Files_External\Lib\Storage\SMB'),
			'identifier:\OCA\Files_External\Lib\Backend\SFTP' => $sftpBackend,
			'identifier:sftp_alias' => $sftpBackend,
		];
		$backends['identifier:\OCA\Files_External\Lib\Backend\SFTP']->method('getLegacyAuthMechanism')
			->willReturn($authMechanisms['identifier:\Other\Auth\Mechanism']);
		$this->backendService->method('getBackend')
			->willReturnCallback(function ($backendClass) use ($backends) {
				if (isset($backends[$backendClass])) {
					return $backends[$backendClass];
				}
				return null;
			});
		$this->backendService->method('getBackends')
			->willReturn($backends);
		$this->overwriteService(BackendService::class, $this->backendService);

		\OCP\Util::connectHook(
			Filesystem::CLASSNAME,
			Filesystem::signal_create_mount,
			get_class($this), 'createHookCallback');
		\OCP\Util::connectHook(
			Filesystem::CLASSNAME,
			Filesystem::signal_delete_mount,
			get_class($this), 'deleteHookCallback');

		$containerMock = $this->createMock(IAppContainer::class);
		$containerMock->method('query')
			->willReturnCallback(function ($name) {
				if ($name === 'OCA\Files_External\Service\BackendService') {
					return $this->backendService;
				}
			});
	}

	protected function tearDown(): void {
		\OCA\Files_External\MountConfig::$skipTest = false;
		self::$hookCalls = [];
		if ($this->dbConfig) {
			$this->dbConfig->clean();
		}
	}

	protected function getBackendMock($class = '\OCA\Files_External\Lib\Backend\SMB', $storageClass = '\OCA\Files_External\Lib\Storage\SMB') {
		$backend = $this->getMockBuilder(Backend::class)
			->disableOriginalConstructor()
			->getMock();
		$backend->method('getStorageClass')
			->willReturn($storageClass);
		$backend->method('getIdentifier')
			->willReturn('identifier:' . $class);
		return $backend;
	}

	protected function getAuthMechMock($scheme = 'null', $class = '\OCA\Files_External\Lib\Auth\NullMechanism') {
		$authMech = $this->getMockBuilder(AuthMechanism::class)
			->disableOriginalConstructor()
			->getMock();
		$authMech->method('getScheme')
			->willReturn($scheme);
		$authMech->method('getIdentifier')
			->willReturn('identifier:' . $class);

		return $authMech;
	}

	/**
	 * Creates a StorageConfig instance based on array data
	 *
	 * @param array $data
	 *
	 * @return StorageConfig storage config instance
	 */
	protected function makeStorageConfig($data) {
		$storage = new StorageConfig();
		if (isset($data['id'])) {
			$storage->setId($data['id']);
		}
		$storage->setMountPoint($data['mountPoint']);
		if (!isset($data['backend'])) {
			// data providers are run before $this->backendService is initialised
			// so $data['backend'] can be specified directly
			$data['backend'] = $this->backendService->getBackend($data['backendIdentifier']);
		}
		if (!isset($data['backend'])) {
			throw new \Exception('oops, no backend');
		}
		if (!isset($data['authMechanism'])) {
			$data['authMechanism'] = $this->backendService->getAuthMechanism($data['authMechanismIdentifier']);
		}
		if (!isset($data['authMechanism'])) {
			throw new \Exception('oops, no auth mechanism');
		}
		$storage->setBackend($data['backend']);
		$storage->setAuthMechanism($data['authMechanism']);
		$storage->setBackendOptions($data['backendOptions']);
		if (isset($data['applicableUsers'])) {
			$storage->setApplicableUsers($data['applicableUsers']);
		}
		if (isset($data['applicableGroups'])) {
			$storage->setApplicableGroups($data['applicableGroups']);
		}
		if (isset($data['priority'])) {
			$storage->setPriority($data['priority']);
		}
		if (isset($data['mountOptions'])) {
			$storage->setMountOptions($data['mountOptions']);
		}
		return $storage;
	}


	protected function ActualNonExistingStorageTest() {
		$backend = $this->backendService->getBackend('identifier:\OCA\Files_External\Lib\Backend\SMB');
		$authMechanism = $this->backendService->getAuthMechanism('identifier:\Auth\Mechanism');
		$storage = new StorageConfig(255);
		$storage->setMountPoint('mountpoint');
		$storage->setBackend($backend);
		$storage->setAuthMechanism($authMechanism);
		$this->service->updateStorage($storage);
	}

	public function testNonExistingStorage() {
		$this->expectException(\OCA\Files_External\NotFoundException::class);

		$this->ActualNonExistingStorageTest();
	}

	public function deleteStorageDataProvider() {
		return [
			// regular case, can properly delete the oc_storages entry
			[
				[
					'host' => 'example.com',
					'user' => 'test',
					'password' => 'testPassword',
					'root' => 'someroot',
				],
				'webdav::test@example.com//someroot/'
			],
			[
				[
					'host' => 'example.com',
					'user' => '$user',
					'password' => 'testPassword',
					'root' => 'someroot',
				],
				'webdav::someone@example.com//someroot/'
			],
		];
	}

	/**
	 * @dataProvider deleteStorageDataProvider
	 */
	public function testDeleteStorage($backendOptions, $rustyStorageId) {
		$backend = $this->backendService->getBackend('identifier:\OCA\Files_External\Lib\Backend\DAV');
		$authMechanism = $this->backendService->getAuthMechanism('identifier:\Auth\Mechanism');
		$storage = new StorageConfig(255);
		$storage->setMountPoint('mountpoint');
		$storage->setBackend($backend);
		$storage->setAuthMechanism($authMechanism);
		$storage->setBackendOptions($backendOptions);

		$newStorage = $this->service->addStorage($storage);
		$id = $newStorage->getId();

		// manually trigger storage entry because normally it happens on first
		// access, which isn't possible within this test
		$storageCache = new \OC\Files\Cache\Storage($rustyStorageId);

		/** @var IUserMountCache $mountCache */
		$mountCache = \OC::$server->get(IUserMountCache::class);
		$mountCache->clear();
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('test');
		$cache = $this->cache = $this->createMock(IMemcache::class);
		;
		$storage = $this->createMock(IStorage::class);
		$storage->method('getCache')->willReturn($cache);
		$mount = $this->createMock(IMountPoint::class);
		$mount->method('getStorage')
			->willReturn($storage);
		$mount->method('getStorageId')
			->willReturn($rustyStorageId);
		$mount->method('getNumericStorageId')
			->willReturn($storageCache->getNumericId());
		$mount->method('getStorageRootId')
			->willReturn(1);
		$mount->method('getMountPoint')
			->willReturn('dummy');
		$mount->method('getMountId')
			->willReturn($id);
		$mountCache->registerMounts($user, [
			$mount
		]);

		// get numeric id for later check
		$numericId = $storageCache->getNumericId();

		$this->service->removeStorage($id);

		$caught = false;
		try {
			$this->service->getStorage(1);
		} catch (NotFoundException $e) {
			$caught = true;
		}

		$this->assertTrue($caught);

		// storage id was removed from oc_storages
		$qb = \OC::$server->getDatabaseConnection()->getQueryBuilder();
		$storageCheckQuery = $qb->select('*')
			->from('storages')
			->where($qb->expr()->eq('numeric_id', $qb->expr()->literal($numericId)));

		$result = $storageCheckQuery->execute();
		$storages = $result->fetchAll();
		$result->closeCursor();
		$this->assertCount(0, $storages, "expected 0 storages, got " . json_encode($storages));
	}

	protected function actualDeletedUnexistingStorageTest() {
		$this->service->removeStorage(255);
	}

	public function testDeleteUnexistingStorage() {
		$this->expectException(\OCA\Files_External\NotFoundException::class);

		$this->actualDeletedUnexistingStorageTest();
	}

	public function testCreateStorage() {
		$mountPoint = 'mount';
		$backendIdentifier = 'identifier:\OCA\Files_External\Lib\Backend\SMB';
		$authMechanismIdentifier = 'identifier:\Auth\Mechanism';
		$backendOptions = ['param' => 'foo', 'param2' => 'bar'];
		$mountOptions = ['option' => 'foobar'];
		$applicableUsers = ['user1', 'user2'];
		$applicableGroups = ['group'];
		$priority = 123;

		$backend = $this->backendService->getBackend($backendIdentifier);
		$authMechanism = $this->backendService->getAuthMechanism($authMechanismIdentifier);

		$storage = $this->service->createStorage(
			$mountPoint,
			$backendIdentifier,
			$authMechanismIdentifier,
			$backendOptions,
			$mountOptions,
			$applicableUsers,
			$applicableGroups,
			$priority
		);

		$this->assertEquals('/' . $mountPoint, $storage->getMountPoint());
		$this->assertEquals($backend, $storage->getBackend());
		$this->assertEquals($authMechanism, $storage->getAuthMechanism());
		$this->assertEquals($backendOptions, $storage->getBackendOptions());
		$this->assertEquals($mountOptions, $storage->getMountOptions());
		$this->assertEquals($applicableUsers, $storage->getApplicableUsers());
		$this->assertEquals($applicableGroups, $storage->getApplicableGroups());
		$this->assertEquals($priority, $storage->getPriority());
	}

	public function testCreateStorageInvalidClass() {
		$storage = $this->service->createStorage(
			'mount',
			'identifier:\OC\Not\A\Backend',
			'identifier:\Auth\Mechanism',
			[]
		);
		$this->assertInstanceOf(InvalidBackend::class, $storage->getBackend());
	}

	public function testCreateStorageInvalidAuthMechanismClass() {
		$storage = $this->service->createStorage(
			'mount',
			'identifier:\OCA\Files_External\Lib\Backend\SMB',
			'identifier:\Not\An\Auth\Mechanism',
			[]
		);
		$this->assertInstanceOf(InvalidAuth::class, $storage->getAuthMechanism());
	}

	public function testGetStoragesBackendNotVisible() {
		$backend = $this->backendService->getBackend('identifier:\OCA\Files_External\Lib\Backend\SMB');
		$backend->expects($this->once())
			->method('isVisibleFor')
			->with($this->service->getVisibilityType())
			->willReturn(false);
		$authMechanism = $this->backendService->getAuthMechanism('identifier:\Auth\Mechanism');
		$authMechanism->method('isVisibleFor')
			->with($this->service->getVisibilityType())
			->willReturn(true);

		$storage = new StorageConfig(255);
		$storage->setMountPoint('mountpoint');
		$storage->setBackend($backend);
		$storage->setAuthMechanism($authMechanism);
		$storage->setBackendOptions(['password' => 'testPassword']);

		$newStorage = $this->service->addStorage($storage);

		$this->assertCount(1, $this->service->getAllStorages());
		$this->assertEmpty($this->service->getStorages());
	}

	public function testGetStoragesAuthMechanismNotVisible() {
		$backend = $this->backendService->getBackend('identifier:\OCA\Files_External\Lib\Backend\SMB');
		$backend->method('isVisibleFor')
			->with($this->service->getVisibilityType())
			->willReturn(true);
		$authMechanism = $this->backendService->getAuthMechanism('identifier:\Auth\Mechanism');
		$authMechanism->expects($this->once())
			->method('isVisibleFor')
			->with($this->service->getVisibilityType())
			->willReturn(false);

		$storage = new StorageConfig(255);
		$storage->setMountPoint('mountpoint');
		$storage->setBackend($backend);
		$storage->setAuthMechanism($authMechanism);
		$storage->setBackendOptions(['password' => 'testPassword']);

		$newStorage = $this->service->addStorage($storage);

		$this->assertCount(1, $this->service->getAllStorages());
		$this->assertEmpty($this->service->getStorages());
	}

	public static function createHookCallback($params) {
		self::$hookCalls[] = [
			'signal' => Filesystem::signal_create_mount,
			'params' => $params
		];
	}

	public static function deleteHookCallback($params) {
		self::$hookCalls[] = [
			'signal' => Filesystem::signal_delete_mount,
			'params' => $params
		];
	}

	/**
	 * Asserts hook call
	 *
	 * @param array $callData hook call data to check
	 * @param string $signal signal name
	 * @param string $mountPath mount path
	 * @param string $mountType mount type
	 * @param string $applicable applicable users
	 */
	protected function assertHookCall($callData, $signal, $mountPath, $mountType, $applicable) {
		$this->assertEquals($signal, $callData['signal']);
		$params = $callData['params'];
		$this->assertEquals(
			$mountPath,
			$params[Filesystem::signal_param_path]
		);
		$this->assertEquals(
			$mountType,
			$params[Filesystem::signal_param_mount_type]
		);
		$this->assertEquals(
			$applicable,
			$params[Filesystem::signal_param_users]
		);
	}

	public function testUpdateStorageMountPoint() {
		$backend = $this->backendService->getBackend('identifier:\OCA\Files_External\Lib\Backend\SMB');
		$authMechanism = $this->backendService->getAuthMechanism('identifier:\Auth\Mechanism');

		$storage = new StorageConfig();
		$storage->setMountPoint('mountpoint');
		$storage->setBackend($backend);
		$storage->setAuthMechanism($authMechanism);
		$storage->setBackendOptions(['password' => 'testPassword']);

		$savedStorage = $this->service->addStorage($storage);

		$newAuthMechanism = $this->backendService->getAuthMechanism('identifier:\Other\Auth\Mechanism');

		$updatedStorage = new StorageConfig($savedStorage->getId());
		$updatedStorage->setMountPoint('mountpoint2');
		$updatedStorage->setBackend($backend);
		$updatedStorage->setAuthMechanism($newAuthMechanism);
		$updatedStorage->setBackendOptions(['password' => 'password2']);

		$this->service->updateStorage($updatedStorage);

		$savedStorage = $this->service->getStorage($updatedStorage->getId());

		$this->assertEquals('/mountpoint2', $savedStorage->getMountPoint());
		$this->assertEquals($newAuthMechanism, $savedStorage->getAuthMechanism());
		$this->assertEquals('password2', $savedStorage->getBackendOption('password'));
	}
}

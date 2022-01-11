<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
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

namespace OC\Core\Command\User;

use OC\Core\Service\UserExportService;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

// TODO: Should this export directly or trigger a job?
class Export extends Command {

	/** @var IUserManager */
	private $userManager;

	/** @var UserExportService */
	private $exportService;

	public function __construct(IUserManager $userManager,
								UserExportService $exportService
								) {
		parent::__construct();
		$this->userManager = $userManager;
		$this->exportService = $exportService;
	}

	protected function configure() {
		$this
			->setName('user:export')
			->setDescription('Export a user.')
			->addArgument(
				'user',
				InputArgument::REQUIRED,
				'user to export'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$userObject = $this->userManager->get($input->getArgument('user'));

		if (!$userObject instanceof IUser) {
			$output->writeln("<error>Unknown user " . $input->getArgument('user') . "</error>");
			return 1;
		}

		try {
			$this->exportService->export($userObject, $output);
		} catch (\Exception $e) {
			$output->writeln("<error>" . $e->getMessage() . "</error>");
			return $e->getCode() !== 0 ? $e->getCode() : 1;
		}

		return 0;
	}
}

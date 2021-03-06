<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCP\UserMigration;

use OCP\Files\Folder;

/**
 * @since 24.0.0
 */
interface IImportSource {

	/**
	 * Reads a file from the export
	 *
	 * @param string $path Full path to the file in the export archive.
	 * @return string The full content of the file.
	 *
	 * @since 24.0.0
	 */
	public function getFileContents(string $path): string;

	/**
	 * Reads a file from the export as a stream
	 *
	 * @param string $path Full path to the file in the export archive.
	 * @return resource A stream resource to read from to get the file content.
	 *
	 * @since 24.0.0
	 */
	public function getFileAsStream(string $path);

	/**
	 * Copy files from the export to a Folder
	 *
	 * Folder $destination folder to copy into
	 * string $sourcePath path in the export archive
	 *
	 * @since 24.0.0
	 */
	public function copyToFolder(Folder $destination, string $sourcePath): bool;

	/**
	 * Called after import is complete
	 *
	 * @since 24.0.0
	 */
	public function close(): void;
}

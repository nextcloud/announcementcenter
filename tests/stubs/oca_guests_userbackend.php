<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Guests;

use OCP\EventDispatcher\IEventDispatcher;
use OCP\IDBConnection;
use OCP\Security\IHasher;
use OCP\User\Backend\ABackend;
use OCP\User\Backend\ICheckPasswordBackend;
use OCP\User\Backend\ICountUsersBackend;
use OCP\User\Backend\IGetDisplayNameBackend;
use OCP\User\Backend\IGetHomeBackend;
use OCP\User\Backend\IGetRealUIDBackend;
use OCP\User\Backend\IPasswordHashBackend;
use OCP\User\Backend\ISetDisplayNameBackend;
use OCP\User\Backend\ISetPasswordBackend;

/**
 * Class for user management in a SQL Database (e.g. MySQL, SQLite)
 */
class UserBackend extends ABackend implements
	ISetPasswordBackend,
	ISetDisplayNameBackend,
	IGetDisplayNameBackend,
	ICheckPasswordBackend,
	IGetHomeBackend,
	ICountUsersBackend,
	IGetRealUIDBackend,
	IPasswordHashBackend {

	public function __construct(
		private IEventDispatcher $eventDispatcher,
		private IDBConnection $dbConn,
		private Config $config,
		private IHasher $hasher,
	) {
	}

	public function setAllowListing(bool $allow): void {
	}

	public function createUser(string $uid, string $password): bool {
	}

	public function deleteUser($uid): bool {
	}

	public function setPassword(string $uid, string $password): bool {
	}

	public function getPasswordHash(string $userId): ?string {
	}

	public function setPasswordHash(string $userId, string $passwordHash): bool {
	}

	public function setDisplayName(string $uid, string $displayName): bool {
	}

	public function getDisplayName($uid): string {
	}

	public function getDisplayNames($search = '', $limit = null, $offset = null): array {
	}

	public function checkPassword(string $loginName, string $password) {
	}

	public function getUsers($search = '', $limit = null, $offset = null): array {
	}

	public function userExists($uid): bool {
	}

	public function getHome(string $uid) {
	}

	public function hasUserListings(): bool {
	}

	public function countUsers() {
	}

	public function loginName2UserName($loginName) {
	}

	public function getBackendName(): string {
	}

	public function getRealUID(string $uid): string {
	}
}

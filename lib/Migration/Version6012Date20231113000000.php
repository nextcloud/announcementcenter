<?php

declare(strict_types=1);

namespace OCA\AnnouncementCenter\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use OCP\DB\Types;

class Version6012Date20231113000000 extends SimpleMigrationStep
{
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure
	 * @param array   $options
	 *
	 * @return ISchemaWrapper|null
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper
	{
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->getTable('announcements_attach');
		if (!$table->hasColumn('file_id')) {
			$table->addColumn('file_id', Types::INTEGER, [
				'notnull' => false,
			]);
		}
		return $schema;
	}
}

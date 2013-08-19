<?php defined('C5_EXECUTE') or die('Access denied.');

require_once dirname(__FILE__) . '/base/old_files.php';

class OldTrashFilesClearFilesProvider extends OldFilesClearFilesProvider {

	public function getName() {
		return t('Old trashed files');
	}

	protected function getAgeLimit() {
		return 259200;
	}
	
	protected function getAbsFolder() {
		return DIR_FILES_TRASH;
	}
}

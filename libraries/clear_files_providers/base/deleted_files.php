<?php defined('C5_EXECUTE') or die('Access denied.');

require_once dirname(__FILE__) . '/base/empty_folders.php';

class DeletedFilesClearFilesProvider extends EmptyFoldersClearFilesProvider {

	public function getName() {
		return t('Deleted files');
	}

	protected function getAbsFolder() {
		return DIR_FILES_UPLOADED;
	}

}

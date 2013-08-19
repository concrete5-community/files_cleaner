<?php defined('C5_EXECUTE') or die('Access denied.');

require_once dirname(__FILE__) . '/base/old_files.php';

class OldTempFilesClearFilesProvider extends OldFilesClearFilesProvider {

	public function getName() {
		return t('Old temporary files');
	}

	protected function getAgeLimit() {
		return 259200;
	}
	
	protected function getAbsFolder() {
		$fh = Loader::helper('file');
		return $fh->getTemporaryDirectory();
	}
}

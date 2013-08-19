<?php defined('C5_EXECUTE') or die('Access denied.');

require_once dirname(__FILE__) . '/base/empty_folders.php';

class DeletedThumbnailsClearFilesProvider extends EmptyFoldersClearFilesProvider {

	public function getName() {
		return t('Deleted thumbnails');
	}

	protected function getAbsFolders() {
		return array(
			'' => DIR_FILES_UPLOADED_THUMBNAILS,
			'LEVEL2' => DIR_FILES_UPLOADED_THUMBNAILS_LEVEL2,
			'LEVEL3' => DIR_FILES_UPLOADED_THUMBNAILS_LEVEL3
		);
	}
	
	protected function getRelFolderName($index) {
		return $index;
	}

}

<?php defined('C5_EXECUTE') or die('Access denied.');

class OldC5VersionsClearFilesProvider extends ClearFilesProvider {

	public function getName() {
		return t('Previous concrete5 versions');
	}

	protected function getProviderContent($absolutePaths) {
		$result = array('files' => array(), 'dirs' => array());;
		$myVersion = Config::get('SITE_APP_VERSION');
		Loader::library('update');
		$updates = array();
		foreach(self::getDirContent(DIR_APP_UPDATES, false, 'dirs') as $dir) {
			if (is_dir(DIR_APP_UPDATES . '/' . $dir)) {
				$c5version = ApplicationUpdate::get($dir);
				if (is_object($c5version)) {
					if(version_compare($c5version->getUpdateVersion(), '0.0.0.1', '>') && version_compare($c5version->getUpdateVersion(), APP_VERSION, '<')) {
						$comment = '';
						if($dir != 'concrete' . $c5version->getUpdateVersion()) {
							$comment = 'concrete5 ' . $c5version->getUpdateVersion();
						}
						$result['dirs'][] = array('name' => ($absolutePaths ? (DIR_APP_UPDATES . '/') : '') . $dir, 'size' => self::getDirSize(DIR_APP_UPDATES . '/' . $dir), 'comment' => $comment);
					}
				}
			}
		}
		return $result;
	}

}

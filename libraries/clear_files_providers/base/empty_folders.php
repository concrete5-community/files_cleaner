<?php defined('C5_EXECUTE') or die('Access denied.');

/**
 * @abstract
 */
class EmptyFoldersClearFilesProvider extends ClearFilesProvider {

	/** Retrieves the folder containing the empty folders to be deleted.
	* @return array[string]
	* @abstract
	*/
	protected function getAbsFolders() {
		throw new Exception(sprintf(t('Method \'%1$s\' not implemented in class \'%2$s\''), __FUNCTION__, get_class($this)));
	}

	/** Retrieves the shown name of a specific absolute folder.
	* @param int $index The index of the folder (as returned from getAbsFolders).
	* @return string
	*/
	protected function getRelFolderName($index) {
		return '';
	}

	protected function getProviderContent($absolutePaths) {
		$found = array('dirs' => array(), 'files' => array());
		foreach($this->getAbsFolders() as $index => $root) {
			$root = rtrim($root, '\\');
			$relName =  rtrim($this->getRelFolderName($index), '/\\') . '/';
			foreach(self::getDirContent($root, false, 'dirs') as $prefix0) {
				self::ParseFolder($absolutePaths, $root . '/', $relName, 0, $prefix0, $found);
			}
		}
		return $found;
	}

	private static function ParseFolder($absolutePaths, $parentPathFull, $parentPathRel, $level, $dir, &$found) {
		switch($level) {
			case 0:
			case 1:
			case 2:
				if(!preg_match('/^[0-9]{4}$/', $dir)) {
					return false;
				}
				break;
			default:
				return false;
		}
		$content = self::getDirContent($parentPathFull . $dir);
		$subFiles = $content['files'];
		$subDirs = $content['dirs'];
		$imEmpty = true;
		switch(count($subFiles)) {
			case 0:
				break;
			case 1:
				if(($subFiles[0] !== 'index.html') || (filesize($parentPathFull . $dir . '/' . $subFiles[0]) !== 0)) {
					$imEmpty = false;
				}
				break;
			default:
				$imEmpty = false;
				break;
		}
		$subfoldersFound = array('files' => array(), 'dirs' => array());
		foreach($subDirs as $subDir) {
			if(!self::ParseFolder($absolutePaths, $parentPathFull . $dir . '/', $parentPathRel . $dir . '/', $level + 1, $subDir, $subfoldersFound)) {
				$imEmpty = false;
			}
		}
		if($imEmpty) {
			$found['dirs'][] = array('name' => ($absolutePaths ? $parentPathFull : $parentPathRel) . $dir . '/', size => 0);
		} else {
			$found['files'] = array_merge($found['files'], $subfoldersFound['files']);
			$found['dirs'] = array_merge($found['dirs'], $subfoldersFound['dirs']);
		}
		return $imEmpty;
	}

}

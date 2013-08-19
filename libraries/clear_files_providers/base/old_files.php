<?php defined('C5_EXECUTE') or die('Access denied.');

/**
* @abstract
*/
class OldFilesClearFilesProvider extends ClearFilesProvider {

	public function getNote() {
		return sprintf(t('We consider as "old" the files (created or modified) older than %s.'), self::FormatAge($this->getAgeLimit()));
	}

	/** Retrieves the minimum age (in seconds) of deletable files.
	 * @return int
	 * @abstract
	 */
	protected function getAgeLimit() {
		throw new Exception(sprintf(t('Method \'%1$s\' not implemented in class \'%2$s\''), __FUNCTION__, get_class($this)));
	}

	/** Retrieves the folder containing old files/folders to be deleted.
	* @return string.
	* @abstract
	*/
	protected function getAbsFolder() {
		throw new Exception(sprintf(t('Method \'%1$s\' not implemented in class \'%2$s\''), __FUNCTION__, get_class($this)));
	}

	protected function getProviderContent($absolutePaths) {
		$now = time();
		$limit = $now - $this->getAgeLimit();
		$result = array('files' => array(), 'dirs' => array());;
		$parentFolder = rtrim($this->getAbsFolder(), '/\\') . '/';
		$content = self::getDirContent($parentFolder);
		foreach($content['dirs'] as $dir) {
			$newest = self::GetNewestTimestamp($parentFolder . $dir);
			if(is_null($newest) || (is_int($newest) && ($newest < $limit))) {
				$result['dirs'][] = array('name' => ($absolutePaths ? $parentFolder : '') . $dir, 'size' => self::getDirSize($parentFolder . $dir), 'comment' => self::FormatAgeFromTimestamp($newest, true, $now));
			}
		}
		foreach($content['files'] as $file) {
			if($file !== 'index.html') {
				$newest = self::GetNewestTimestamp($parentFolder . $file);
				if(is_int($newest) && ($newest < $limit)) {
					$result['files'][] = array('name' => ($absolutePaths ? $parentFolder : '') . $file, 'size' => filesize($parentFolder . $file), 'comment' => self::FormatAgeFromTimestamp($newest, true, $now));
				}
			}
		}
		return $result;
	}

	private static function GetNewestTimestamp($name) {
		if(is_file($name)) {
			$tsC = filectime($name);
			$tsM = filemtime($name);
			if($tsC && $tsM) {
				return max($tsC, $tsM);
			}
			elseif($tsC) {
				return $tsC;
			}
			elseif($tsM) {
				return $tsC;
			}
			else {
				return false;
			}
		} else if(is_dir($name)) {
			$content = self::getDirContent($base . $rel . $name);
			$newest = null;
			foreach($content['dirs'] as $dir) {
				$subNewest = self::GetNewestTimestamp($name . '/' . $dir);
				if($subNewest === false) {
					return false;
				}
				if(is_int($subNewest)) {
					$newest = is_int($newest) ? max($newest, $subNewest) : $subNewest;
				}
			}
			foreach($content['files'] as $file) {
				$subNewest = self::GetNewestTimestamp($name . '/' . $file);
				if($subNewest === false) {
					return false;
				}
				if(is_int($subNewest)) {
					$newest = is_int($newest) ? max($newest, $subNewest) : $subNewest;
				}
			}
			return $newest;
		} else {
			throw new Exception('Not found: ' . $name);
		}
	}
}

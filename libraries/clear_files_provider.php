<?php defined('C5_EXECUTE') or die('Access denied.');

/** Abstract class that provides functionality to delete unuseful files.
 * @abstract
 */
class ClearFilesProvider {

	/** Retrieves all the available providers, ie instances that manage single deletable content.
	* @throws Exception Throws an Exception in case of errors.
	* @return array[ClearFilesProvider]
	*/
	public static function getProviders() {
		$providers = array();
		$dir = dirname(__FILE__) . '/clear_files_providers';
		$fh = Loader::helper('file');
		foreach($fh->getDirectoryContents($dir) as $item) {
			$file = $dir . '/' . $item;
			if(is_file($file) && (strcasecmp(substr($item, -4), '.php') === 0)) {
				$handle = substr($item, 0, -4);
				require_once $file;
				$className = Object::camelcase($handle) . 'ClearFilesProvider';
				if(!class_exists($className)) {
					throw new Exception("$className class doesn't exist.");
				}
				$providers[$handle] = new $className();
			}
		}
		return $providers;
	}

	/** Retrieves the name of the provider.
	* @return string.
	* @abstract
	*/
	public function getName() {
		throw new Exception(sprintf(t('Method \'%1$s\' not implemented in class \'%2$s\''), __FUNCTION__, get_class($this)));
	}
	
	/** Get some extra info.
	* @return string.
	* @abstract
	*/
	public function getNote() {
		return '';
	}

	/** Get the deletable content, as array of directories and files.
	* @param bool $absolutePaths Set to true to retrieve absolute paths, false to retrieve only relative paths (in <i>name</i> field of returned arrays).
	* @return array Returns an array with two keys: <b>dirs</b> and <b>files</b>. Each of both is a list of arrays with these keys:<ul>
	*	<li>string <b>name</b> The name (required).</li>
	*	<li>int <b>size</b> The size (optional).</li>
	*	<li>string <b>comment</b> Optional notes (optional).</li>
	* </ul>
	* @throws Exception Throws an Exception in case of errors.
	* @abstract
	*/
	protected function getProviderContent($absolutePaths) {
		throw new Exception(sprintf(t('Method \'%1$s\' not implemented in class \'%2$s\''), __FUNCTION__, get_class($this)));
	}
	
	/** Get the deletable content, as array of directories and files.
	* @param bool $absolutePaths Set to true to retrieve absolute paths, false (default) to retrieve only relative paths (in <i>name</i> field of returned arrays).
	* @return array Returns an array with two keys: <b>dirs</b> and <b>files</b>. Each of both is a list of arrays with these keys:<ul>
	*	<li>string <b>name</b> The name (required).</li>
	*	<li>int <b>size</b> The size (optional).</li>
	*	<li>string <b>comment</b> Optional notes (optional).</li>
	* </ul>
	* @throws Exception Throws an Exception in case of errors.
	* @abstract
	*/
	public function getContent($absolutePaths = false) {
		@clearstatcache();
		return $this->getProviderContent($absolutePaths);
	}

	/** Delete the deletable content.
	* @throws Exception Throws an Exception in case of errors.
	*/
	public function cleanContent() {
		$list = $this->getContent(true);
		foreach($list['dirs'] as $dir) {
			self::DeleteDirectory($dir['name']);
		}
		foreach($list['files'] as $file) {
			self::DeleteFile($file['name']);
		}
	}

	/** Retrieves the content of a folder.
	* @param string $dir The absolute path of the folder for which you want the content.
	* @param bool $buildFullPath Set to true to return the absolute paths of found items, false [default] if you just want item names. 
	* @param string $justType Set to 'dirs' to retrieve only directories, 'files' for files, '' [default] for both.
	* @return array If $justType is empty the array will contain two sub-arrays, names <b>dirs</b> and <b>files</b>. Otherwise it'll be a flat array.
	* @throws Exception Throws an Exception in case of errors.
	*/
	protected static function getDirContent($dir, $buildFullPath = false, $justType = '') {
		$result = empty($justType) ? array('files' => array(), 'dirs' => array()) : array();
		if(!($hDir = @opendir($dir))) {
			throw new Exception(sprintf(t('Unable to open folder %s'), $dir));
		}
		while(($entry = @readdir($hDir)) !== false) {
			switch($entry) {
				case '.':
				case '..':
					break;
				default:
					$fullPath = rtrim($dir, '/\\') . '/' . $entry;
					$type = is_dir($fullPath) ? 'dirs' : 'files';
					if($justType == '') {
						$result[$type][] = $buildFullPath ? $fullPath : $entry;
					} elseif($justType == $type) {
						$result[] = $buildFullPath ? $fullPath : $entry;
					}
					break;
			}
		}
		closedir($hDir);
		return $result;
	}

	/** Retrieves the size (in bytes) of a directory.
	* @param string $fullPath The absolute path of the folder for which you want the size.
	* @return int
	* @throws Exception Throws an Exception in case of errors.
	*/
	protected static function getDirSize($fullPath) {
		$size = 0;
		$items = self::getDirContent($fullPath, true);
		foreach($items['files'] as $file) {
			$filesize = @filesize($file);
			if($filesize === false) {
				throw new Exception("Unable to inspect $file");
			}
			$size += $filesize;
		}
		foreach($items['dirs'] as $dir) {
			$size += self::getDirSize($dir);
		}
		return $size;
	}

	/** Deletes a directory and all its content.
	* @param string $fullPath The absolute path of the directory to be deleted. 
	* @throws Exception Throws an Exception in case of errors.
	*/
	protected static function DeleteDirectory($fullPath) {
		Loader::helper('file')->removeAll($fullPath);
		if(!is_dir($fullPath)) {
			throw new Exception(sprintf(t('Error deleting folder %s'), $fullPath));
		}
	}

	/** Deletes a file.
	* @param string $fullPath The absolute path of the file to be deleted.
	* @throws Exception Throws an Exception in case of errors.
	*/
	protected static function DeleteFile($fullPath) {
		if(!@unlink($fullPath)) {
			throw new Exception(sprintf(t('Error deleting file %s'), $fullPath));
		}
	}

	/** Formats a timestamp, returning a description of its age.
	* @param int $timestamp The timestamp for which you want the age description.
	* @param bool $approximate Set to true to give less but more readable info, false [default] to have all the info. 
	* @param int|null $now The relative timestamp for calculating the age. In empty (default) we'll use the current system time.
	* @return string
	*/
	public static function FormatAgeFromTimestamp($timestamp, $approximate = false, $now = null) {
		if(empty($timestamp) || (!is_numeric($timestamp))) {
			$age = null;
		} else {
			$age = (empty($now) ? time() : $now) - $timestamp;
		}
		return self::FormatAge($age, $approximate);
	}

	/** Formats a second interval, returning a description of its age.
	 * @param int $age The age (in seconds).
	 * @param bool $approximate Set to true to give less but more readable info, false [default] to have all the info.
	 * @param int|null $now The relative timestamp for calculating the age. In empty (default) we'll use the current system time.
	 * @return string
	 */
	public static function FormatAge($age, $approximate = false) {
		if(!is_numeric($age)) {
			return '';
		}
		$age = intval($age);
		$years = floor($age / (365*60*60*24));
		$months = floor(($age - $years * 365*60*60*24) / (30*60*60*24));
		$days = floor(($age - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24));
		$hours = floor(($age - $years * 365*60*60*24 - $months*30*60*60*24 - $days*60*60*24)/ (60*60));
		$minutes = floor(($age - $years * 365*60*60*24 - $months*30*60*60*24 - $days*60*60*24 - $hours*60*60)/ 60);
		$seconds = floor(($age - $years * 365*60*60*24 - $months*30*60*60*24 - $days*60*60*24 - $hours*60*60 - $minutes*60));
		$yearsText = function_exists('t2') ? t2('%d year', '%d years', $years, $years) : t('%d years', $years);
		$monthsText = function_exists('t2') ? t2('%d month', '%d months', $months, $months) : t('%d months', $months);
		$daysText = function_exists('t2') ? t2('%d day', '%d days', $days, $days) : t('%d days', $days);
		$hoursText = function_exists('t2') ? t2('%d hour', '%d hours', $hours, $hours) : t('%d hours', $hours);
		$minutesText = function_exists('t2') ? t2('%d minute', '%d minutes', $minutes, $minutes) : t('%d minutes', $minutes);
		$secondsText = function_exists('t2') ? t2('%d second', '%d seconds', $seconds, $seconds) : t('%d seconds', $seconds);
		if($approximate) {
			if($years > 0) {
				$s =  $yearsText;
				if($years < 3) {
					$s .= ', ' . $monthsText;
				}
				return $s;
			}
			if($months > 0) {
				$s = t2('%d month', '%d month', $months, $months);
				if($months < 3) {
					$s .= ', ' . $daysText;
				}
				return $s;
			}
			if($days) {
				$s = $daysText;;
				if($days < 3) {
					$s .= ', ' . $hoursText;
				}
				return $s;
			}
			if($hours) {
				$s = $hoursText;
				if($hours < 3) {
					$s .= ', ' . $minutesText;
				}
				return $s;
			}
			if($minutes) {
				$s = $minutesText;
				if($minutes < 3) {
					$s .= ', ' . $secondsText;
				}
				return $s;
			}
			return $secondsText;
		}
		else {
			$s = array();
			if((!empty($s)) || ($years > 0)) {
				$s[] = $yearsText;
			}
			if((!empty($s)) || ($months > 0)) {
				$s[] = $monthsText;
			}
			if((!empty($s)) || ($days > 0)) {
				$s[] = $daysText;
			}
			if((!empty($s)) || ($hours > 0)) {
				$s[] = $hoursText;
			}
			if((!empty($s)) || ($minutes > 0)) {
				$s[] = $minutesText;
			}
			$s[] = $secondsText;
			return implode($s, ', ');
		}
	}

}

<?php defined('C5_EXECUTE') or die('Access denied.');

/** The FilesCleaner package controller. */
class FilesCleanerPackage extends Package {
	/** Minimum version of concrete5.
	* @var string
	*/
	protected $appVersionRequired = '5.6.1';

	/** The package handle.
	* @var string
	*/
	protected $pkgHandle = 'files_cleaner';

	/** The package version.
	* @var string
	*/
	protected $pkgVersion = '0.9.2';

	/** Retrieves the package name.
	* @return string
	*/
	public function getPackageName() {
		return t('Files Cleaner');
	}

	/** Retrieves the package description.
	* @return string
	*/
	public function getPackageDescription() {
		return t('**USE AT YOUR OWN RISK**') . ' ' . t('Helps you removing temporary files & unuseful files (you’ll find it under Dashboard -> System & Settings -> Optimization -> Clear Files).');
	}

	/** Install the package. */
	public function install() {
		$pkg = parent::install();
		$this->installDo($pkg, '');
	}

	/** Update the package. */
	public function upgrade() {
		$currentVersion = $this->getPackageVersion();
		parent::upgrade();
		$this->installDo($this, $currentVersion);
	}

	/** Install or upgrade the package.
	* @param Package $pkg The Package instance returned from Package::install or Package::upgrade
	* @param string $fromVersion The previous version if we're upgrading, empty string (default) if installing.
	*/
	private function installDo($pkg, $fromVersion = '') {
		Loader::model('single_page');
		$fromScratch = strlen($fromVersion) ? false : true;
		if($fromScratch) {
			SinglePage::add('/dashboard/system/optimization/clear_files', $pkg);
		}
	}
}

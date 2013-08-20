<?php defined('C5_EXECUTE') or die('Access denied.');

/** Controller for Clear Files page. */
class DashboardSystemOptimizationClearFilesController extends DashboardBaseController {

	public function view() {
		Loader::library('clear_files_provider', 'files_cleaner');
		$providers = array();
		foreach(ClearFilesProvider::getProviders() as $handle => $provider) {
			$providers[] = array('handle' => $handle, 'name' => $provider->getName(), 'note' => $provider->getNote());
		}
		$this->set('providers', $providers);
	}

	public function get_content() {
		try {
			$handle = $this->post('provider');
			if(!strlen($handle)) {
				throw new Exception(sprintf(t('Invalid parameter: %s'), 'clearable'));
			}
			Loader::library('clear_files_provider', 'files_cleaner');
			$providers = ClearFilesProvider::getProviders();
			if(!array_key_exists($handle, $providers)) {
				throw new Exception(sprintf(t('Invalid parameter: %s'), 'clearable'));
			}
			$content = $providers[$handle]->getContent(false);
			@ob_end_clean();
			header('Content-Type: text/javascript; charset=utf-8');
			die(Loader::helper('json')->encode($content));
		} catch(Exception $x) {
			@ob_end_clean();
			header('HTTP/1.1 400 Bad Request', true, 400);
			header('Content-Type: text/plain; charset: utf-8');
			die($x->getMessage());
		}
	}

	public function do_clean() {
		try {
			$handle = $this->post('provider');
			if(!strlen($handle)) {
				throw new Exception(sprintf(t('Invalid parameter: %s'), 'clearable'));
			}
			Loader::library('clear_files_provider', 'files_cleaner');
			$providers = ClearFilesProvider::getProviders();
			if(!array_key_exists($handle, $providers)) {
				throw new Exception(sprintf(t('Invalid parameter: %s'), 'clearable'));
			}
			$content = $providers[$handle]->cleanContent();
			@ob_end_clean();
			header('Content-Type: text/javascript; charset=utf-8');
			die(Loader::helper('json')->encode(true));
		} catch(Exception $x) {
			@ob_end_clean();
			header('HTTP/1.1 400 Bad Request', true, 400);
			header('Content-Type: text/plain; charset: utf-8');
			die($x->getMessage());
		}
	}

}

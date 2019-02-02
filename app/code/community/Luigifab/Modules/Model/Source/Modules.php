<?php
/**
 * Created L/21/07/2014
 * Updated M/15/01/2019
 *
 * Copyright 2012-2019 | Fabrice Creuzot (luigifab) <code~luigifab~fr>
 * https://www.luigifab.fr/magento/modules
 *
 * This program is free software, you can redistribute it or modify
 * it under the terms of the GNU General Public License (GPL) as published
 * by the free software foundation, either version 2 of the license, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but without any warranty, without even the implied warranty of
 * merchantability or fitness for a particular purpose. See the
 * GNU General Public License (GPL) for more details.
 */

class Luigifab_Modules_Model_Source_Modules extends Varien_Data_Collection {

	public function getCollection() {

		// getName() = le nom du tag xml
		// => /config/modules/Luigifab_Modules
		// <modules>
		//  <Luigifab_Modules>                                 <= $config
		//   <active>true</active>
		//   <codePool>community</codePool>
		//   <update>https://www.luigifab.fr/magento/rss.xml
		$config  = Mage::getModel('core/config')->loadBase()->loadModules()->loadDb();
		$nodes   = $config->getXpath('/config/modules/*');
		$connect = $this->readDownloaderCache();

		foreach ($nodes as $config) {

			if (!in_array($config->codePool, array('local', 'community')))
				continue;

			$moduleName = (string) $config->getName();
			$check = array('status' => ($config->active != 'true') ? 'disabled' : 'unknown');

			if (Mage::getStoreConfigFlag('modules/general/last')) {

				if (!empty($config->update)) {
					$check += $this->checkUpdate($moduleName, $config->update);
				}
				else if (($moduleName != 'Phoenix_Moneybookers') && (mb_strpos($moduleName, 'Mage_') === false)) {
					foreach ($connect as $key => $data) {
						if (mb_strpos($data['xml'], $moduleName) !== false) {
							$check += $this->checkConnect($data['name'], $data['url']);
							unset($connect[$key]); // car il y en a plus besoin
							break;
						}
					}
				}
			}

			$item = new Varien_Object();
			$item->setData('name', str_replace('_', '/', $moduleName));
			$item->setData('code_pool', $config->codePool);
			$item->setData('current_version', $config->version);
			$item->setData('last_version', !empty($check['version']) ? $check['version'] : false);
			$item->setData('last_date', !empty($check['date']) ? $check['date'] : false);
			$item->setData('url', !empty($check['url']) ? $check['url'] : false);
			$item->setData('status', $check['status']);

			$this->addItem($item);
		}

		$this->addMagento();

		foreach ($this as $item) {

			if (($item->getData('status') != 'unknown') || empty($item->getData('current_version')) || empty($item->getData('last_version')))
				continue;

			if (version_compare($item->getData('last_version'), $item->getData('current_version'), '>'))
				$item->setData('status', 'toupdate');
			else if (version_compare($item->getData('last_version'), $item->getData('current_version'), '<'))
				$item->setData('status', 'beta');
			else
				$item->setData('status', 'uptodate');
		}

		usort($this->_items, function ($a, $b) {
			$test = strcmp($a->getData('code_pool'), $b->getData('code_pool'));
			return ($test === 0) ? strcmp($a->getData('name'), $b->getData('name')) : $test;
		});

		return $this;
	}

	private function addMagento() {

		$check = array('status' => 'unknown');

		if (Mage::getStoreConfigFlag('modules/general/last'))
			$check += $this->checkConnect('Mage_Downloader');

		$item = new Varien_Object();
		$item->setData('name', 'MAGENTO');
		$item->setData('code_pool', 'core');
		$item->setData('current_version', Mage::getVersion());
		$item->setData('last_version', !empty($check['version']) ? $check['version'] : false);
		$item->setData('last_date', !empty($check['date']) ? $check['date'] : false);
		$item->setData('url', 'https://magento.com/download');
		$item->setData('status', $check['status']);

		$this->addItem($item);
	}

	private function readDownloaderCache() {

		$data  = array();
		$model = BP.'/downloader/lib/Mage/Connect/Singleconfig.php';
		$cache = BP.'/downloader/cache.cfg'; //Mage_Connect_Singleconfig::DEFAULT_SCONFIG_FILENAME;

		if (is_file($model) && is_file($cache)) {

			if (!class_exists('Mage_Connect_Singleconfig', false))
				require_once($model);

			$config = new Mage_Connect_Singleconfig($cache);
			$config->load(false);

			$channels = $config->getData();
			$channels = !empty($channels['channels_by_name']) ? $channels['channels_by_name'] : array();

			foreach ($channels as $channel) {

				$url = !empty($channel['uri']) ? 'https://'.$channel['uri'].'/' : null;
				$packages = !empty($channel['packages']) ? $channel['packages'] : array();

				foreach ($packages as $key => $item) {
					if (!empty($item['xml']))
						$data[$key] = array('url' => $url, 'name' => $key, 'xml' => $item['xml']);
				}
			}
		}
		else {
			Mage::log('Can not read the downloader/cache.cfg file to check modules update.', Zend_Log::ERR, 'modules.log');
		}

		return $data;
	}

	private function checkUpdate($name, $url) {

		$data = array();
		$key  = md5($url);

		try {
			if (empty($this->cache) || !is_array($this->cache))
				$this->cache = array();

			if (empty($this->cache[$key])) {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 6);
				curl_setopt($ch, CURLOPT_TIMEOUT, 15);
				$this->cache[$key] = curl_exec($ch);
				curl_close($ch);
			}

			$response = $this->cache[$key];

			// lecture du fichier XML de la balise <update>
			if ((mb_strpos($response, '<modules>') !== false) && (mb_strpos($response, '</modules>') !== false)) {

				$dom = new DomDocument();
				$dom->loadXML($response);
				$qry = new DOMXPath($dom);

				$nodes = $qry->query('/modules/'.mb_strtolower($name).'/*');
				foreach ($nodes as $node)
					$data[$node->nodeName] = $node->nodeValue;

				if (empty($data)) {
					$nodes = $qry->query('/config/modules/'.$name.'/*');
					foreach ($nodes as $node)
						$data[$node->nodeName] = $node->nodeValue;
				}
			}
		}
		catch (Exception $e) {
			Mage::log(sprintf('%s for %s (%s)', $e->getMessage(), $url, $name), Zend_Log::ERR, 'modules.log');
		}

		return $data;
	}

	private function checkConnect($name, $url = null) {

		$data = array();

		try {
			$url = (empty($url) ? 'https://connect20.magentocommerce.com/community/' : $url).$name.'/releases.xml';
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 6);
			curl_setopt($ch, CURLOPT_TIMEOUT, 6);
			$response = curl_exec($ch);
			curl_close($ch);

			// lecture du fichier XML de la liste des versions du module sur magento connect
			// pour l'expression du xpath voir https://www.freeformatter.com/xpath-tester.html
			if ((mb_strpos($response, '<releases>') !== false) && (mb_strpos($response, '</releases>') !== false)) {

				$dom = new DomDocument();
				$dom->loadXML($response);
				$qry = new DOMXPath($dom);
				$nodes = $qry->query('(//s[text()="stable"])/../v');

				foreach ($nodes as $nodeV) {
					$nodeD = $nodeV->parentNode->getElementsByTagName('d')[0];
					$data[$nodeV->nodeValue] = array(
						'version' => $nodeV->nodeValue,
						'date'    => $nodeD->nodeValue
					);
				}

				// trie du plus grand au plus petit (donc de la plus récente à la plus ancienne)
				// puis récupère la version la plus récente
				usort($data, function ($a, $b) {
					return ($a['version'] == $b['version']) ? 0 : (version_compare($a['version'], $b['version'], '>') ? -1 : 1);
				});

				$data = array_shift($data);
				if (!is_array($data))
					$data = array();
			}
		}
		catch (Exception $e) {
			Mage::log(sprintf('%s for %s (%s)', $e->getMessage(), $url, $name), Zend_Log::ERR, 'modules.log');
		}

		return $data;
	}
}
<?php
/**
 * Created L/21/07/2014
 * Updated V/19/06/2020
 *
 * Copyright 2012-2020 | Fabrice Creuzot (luigifab) <code~luigifab~fr>
 * https://www.luigifab.fr/openmage/modules
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
		//  <Luigifab_Modules>                               <= $config
		//   <active>true</active>
		//   <codePool>community</codePool>
		//   <update>https://www.luigifab.fr/openmage/rss.xml
		$config = Mage::getModel('core/config')->loadBase()->loadModules()->loadDb();
		$nodes  = $config->getXpath('/config/modules/*');

		(stripos(file_get_contents(BP.'/app/Mage.php'), 'getOpenMageVersion') === false) ? // pas de mb_stripos
			$this->addMagento() : $this->addOpenMage();

		foreach ($nodes as $config) {

			if (!in_array($config->codePool, ['local', 'community']))
				continue;

			$moduleName = (string) $config->getName();
			$check = ['status' => ($config->active != 'true') ? 'disabled' : 'unknown'];

			if (Mage::getStoreConfigFlag('modules/general/last') && !empty($config->update))
				$check = array_merge($check, $this->checkUpdate($moduleName, $config->update));

			$item = new Varien_Object();
			$item->setData('name', str_replace('_', '/', $moduleName));
			$item->setData('code_pool', $config->codePool);
			$item->setData('current_version', $config->version);
			$item->setData('last_version', empty($check['version']) ? false : $check['version']);
			$item->setData('last_date', empty($check['date']) ? false : $check['date']);
			$item->setData('url', empty($check['url']) ? false : $check['url']);
			$item->setData('status', $check['status']);

			$this->addItem($item);
		}

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

		usort($this->_items, static function ($a, $b) {
			$test = strnatcasecmp($a->getData('code_pool'), $b->getData('code_pool'));
			return ($test == 0) ? strnatcasecmp($a->getData('name'), $b->getData('name')) : $test;
		});

		return $this;
	}

	private function addMagento() {

		$check = ['status' => 'unknown'];

		if (Mage::getStoreConfigFlag('modules/general/last')) {

			try {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/repos/OpenMage/magento-mirror/releases');
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
				curl_setopt($ch, CURLOPT_TIMEOUT, 18);
				curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64; rv:76.0) Gecko/20100101 Firefox/76.0');
				$response = curl_exec($ch);
				$response = ((curl_errno($ch) !== 0) || ($response === false)) ? 'CURL_ERROR_'.curl_errno($ch).' '.curl_error($ch) : $response;
				curl_close($ch);

				if (mb_stripos($response, '"tag_name": "') !== false) {
					$response = @json_decode($response, true);
					if (!empty($response[0]['tag_name']) && !empty($response[0]['created_at'])) {
						$check['version'] = preg_replace('#[^0-9.]+#', '', $response[0]['tag_name']);
						$check['date'] = $response[0]['created_at'];
					}
				}
			}
			catch (Exception $e) {
				Mage::log(sprintf('%s for %s (%s)', $e->getMessage(), 'api.github.com', 'magento'), Zend_Log::ERR, 'modules.log');
			}
		}

		$item = new Varien_Object();
		$item->setData('name', 'MAGENTO');
		$item->setData('code_pool', 'core');
		$item->setData('current_version', Mage::getVersion());
		$item->setData('last_version', empty($check['version']) ? false : $check['version']);
		$item->setData('last_date', empty($check['date']) ? false : $check['date']);
		$item->setData('url', 'https://magento.com/download');
		$item->setData('status', $check['status']);

		$this->addItem($item);
	}

	private function addOpenMage() {

		$check = ['status' => 'unknown'];

		if (Mage::getStoreConfigFlag('modules/general/last')) {

			try {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/repos/OpenMage/magento-lts/releases');
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
				curl_setopt($ch, CURLOPT_TIMEOUT, 18);
				curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64; rv:76.0) Gecko/20100101 Firefox/76.0');
				$response = curl_exec($ch);
				$response = ((curl_errno($ch) !== 0) || ($response === false)) ? 'CURL_ERROR_'.curl_errno($ch).' '.curl_error($ch) : $response;
				curl_close($ch);

				if (mb_stripos($response, '"tag_name": "') !== false) {
					$response = @json_decode($response, true);
					if (!empty($response[0]['tag_name']) && !empty($response[0]['created_at'])) {
						$check['version'] = preg_replace('#[^0-9.]+#', '', $response[0]['tag_name']);
						$check['date'] = $response[0]['created_at'];
					}
				}
			}
			catch (Exception $e) {
				Mage::log(sprintf('%s for %s (%s)', $e->getMessage(), 'api.github.com', 'openmage'), Zend_Log::ERR, 'modules.log');
			}
		}

		$item = new Varien_Object();
		$item->setData('name', 'OPENMAGE');
		$item->setData('code_pool', 'core');
		$item->setData('current_version', Mage::getOpenMageVersion());
		$item->setData('last_version', empty($check['version']) ? false : $check['version']);
		$item->setData('last_date', empty($check['date']) ? false : $check['date']);
		$item->setData('url', 'https://github.com/OpenMage/magento-lts/releases');
		$item->setData('status', $check['status']);

		$this->addItem($item);
	}

	private function checkUpdate($name, $url) {

		$data = [];
		$key  = md5($url);

		try {
			if (empty($this->cache) || !is_array($this->cache))
				$this->cache = [];

			if (empty($this->cache[$key])) {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
				curl_setopt($ch, CURLOPT_TIMEOUT, 18);
				$response = curl_exec($ch);
				$response = ((curl_errno($ch) !== 0) || ($response === false)) ? 'CURL_ERROR_'.curl_errno($ch).' '.curl_error($ch) : $response;
				curl_close($ch);
				$this->cache[$key] = $response;
			}

			$response = $this->cache[$key];

			// lecture du fichier XML de la balise <update>
			if ((mb_stripos($response, '<modules>') !== false) && (mb_stripos($response, '</modules>') !== false)) {

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
			else {
				Mage::throwException($response);
			}
		}
		catch (Exception $e) {
			Mage::log(sprintf('%s for %s (%s)', $e->getMessage(), $url, $name), Zend_Log::ERR, 'modules.log');
		}

		return $data;
	}
}
<?php
/**
 * Created L/21/07/2014
 * Updated D/03/12/2023
 *
 * Copyright 2012-2024 | Fabrice Creuzot (luigifab) <code~luigifab~fr>
 * https://github.com/luigifab/openmage-modules
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

	protected static $_cache = [];

	public function getCollection() {

		$search = Mage::getStoreConfigFlag('modules/general/last');
		$this->addOpenMage($search);

		// getName() = xml tag name
		// => /config/modules/Luigifab_Modules
		// <modules>
		//  <Luigifab_Modules>                               <= $node
		//   <active>true</active>
		//   <codePool>community</codePool>
		//   <update>https://www.luigifab.fr/openmage/rss.xml
		$nodes = Mage::getModel('core/config')->loadBase()->loadModules()->loadDb();
		$nodes = $nodes->getXpath('/config/modules/*');

		foreach ($nodes as $node) {

			if (!in_array($node->codePool, ['local', 'community']))
				continue;

			$moduleName = $node->getName();
			$check = ['status' => ($node->active != 'true') ? 'disabled' : 'unknown'];

			if ($search && !empty($node->update))
				$check = array_merge($check, $this->checkUpdate($moduleName, (string) $node->update));

			$item = new Varien_Object();
			$item->setData('name', $moduleName);
			$item->setData('code_pool', (string) $node->codePool);
			$item->setData('current_version', (string) $node->version);
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

		$this->_setIsLoaded();
		return $this;
	}

	protected function addOpenMage(bool $search) {

		$check = ['status' => 'unknown'];

		if ($search) {

			try {
				$result = $this->sendRequest('https://api.github.com/repos/OpenMage/magento-lts/releases');

				if (str_contains($result, '"tag_name": "')) {
					$result = @json_decode($result, true);
					if (!empty($result[0]['tag_name']) && !empty($result[0]['created_at'])) {
						$check['version'] = substr($result[0]['tag_name'], 1); // vx.x.x-rcx // not mb_substr
						$check['date'] = $result[0]['created_at'];
					}
				}
			}
			catch (Throwable $t) {
				Mage::log(sprintf('%s for %s (%s)', $t->getMessage(), 'api.github.com', 'OpenMage'), Zend_Log::ERR, 'modules.log');
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

	protected function checkUpdate(string $name, string $url) {

		$data = [];
		$key  = md5($url);

		try {
			if (empty(self::$_cache[$key]))
				self::$_cache[$key] = $this->sendRequest($url);

			$result = self::$_cache[$key];

			if (str_contains($result, '<modules>') && str_contains($result, '</modules>')) {

				$dom = new DomDocument();
				$dom->loadXML($result);
				$qry = new DOMXPath($dom);

				$nodes = $qry->query('/modules/'.strtolower($name).'/*'); // not mb_strtolower
				foreach ($nodes as $node)
					$data[$node->nodeName] = $node->nodeValue;

				if (empty($data)) {
					$nodes = $qry->query('/config/modules/'.$name.'/*');
					foreach ($nodes as $node)
						$data[$node->nodeName] = $node->nodeValue;
				}
			}
			else {
				Mage::throwException($result);
			}
		}
		catch (Throwable $t) {
			Mage::log(sprintf('%s for %s (%s)', $t->getMessage(), $url, $name), Zend_Log::ERR, 'modules.log');
		}

		return $data;
	}

	protected function sendRequest(string $url) {

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		curl_setopt($ch, CURLOPT_ENCODING , ''); // @see https://stackoverflow.com/q/17744112/2980105
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/114.0');

		$result = curl_exec($ch);
		$result = (($result === false) || (curl_errno($ch) !== 0)) ? trim('CURL_ERROR '.curl_errno($ch).' '.curl_error($ch)) : $result;
		curl_close($ch);

		return $result;
	}
}
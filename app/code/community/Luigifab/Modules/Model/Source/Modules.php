<?php
/**
 * Created L/21/07/2014
 * Updated L/24/07/2017
 *
 * Copyright 2012-2017 | Fabrice Creuzot (luigifab) <code~luigifab~info>
 * https://www.luigifab.info/magento/modules
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
		//   <update>https://www.luigifab.info/magento/rss.xml
		$nodes = Mage::getConfig()->getXpath('/config/modules/*');

		foreach ($nodes as $config) {

			if (!in_array($config->codePool, array('local', 'community')))
				continue;

			$moduleName = $config->getName();
			$check = array('status' => 'unknown');

			if (Mage::getStoreConfigFlag('modules/general/last')) {

				if (!empty($config->update))
					$check += $this->checkUpdate($moduleName, $config->update);

				else if ((strpos($moduleName, 'Mage_') === false) && ($moduleName != 'Phoenix_Moneybookers') &&
				         ($config->codePool == 'community')) // pas de !== et === ici
					$check += $this->checkConnect($moduleName);
			}

			if ($config->active != 'true') { // pas de !== ici
				$check['status'] = 'disabled';
			}
			else if (is_array($check)) {
				if (!empty($check['version']) && version_compare($check['version'], $config->version, '>'))
					$check['status'] = 'toupdate';
				else if (!empty($check['version']) && version_compare($check['version'], $config->version, '<'))
					$check['status'] = 'beta';
				else if (!empty($check['version']))
					$check['status'] = 'uptodate';
			}

			$item = new Varien_Object();
			$item->setData('name', str_replace('_', '/', $moduleName));
			$item->setData('code_pool', $config->codePool);
			$item->setData('current_version', $config->version);
			$item->setData('last_version', (!empty($check['version'])) ? $check['version'] : false);
			$item->setData('last_date', (!empty($check['date'])) ? $check['date'] : false);
			$item->setData('url', (!empty($check['url'])) ? $check['url'] : false);
			$item->setData('status', $check['status']);

			$this->addItem($item);
		}

		usort($this->_items, function ($a, $b) {
			$test = strcmp($a->getData('code_pool'), $b->getData('code_pool'));
			return ($test === 0) ? strcmp($a->getData('name'), $b->getData('name')) : $test;
		});

		return $this;
	}

	private function checkUpdate($name, $url) {

		$key = md5($url);

		try {
			if (empty($this->cache) || !is_array($this->cache))
				$this->cache = array();

			if (empty($this->cache[$key])) {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
				curl_setopt($ch, CURLOPT_TIMEOUT, 5);
				$this->cache[$key] = curl_exec($ch);
				curl_close($ch);
			}

			$response = $this->cache[$key];

			// lecture du fichier XML de la balise <update>
			if ((strpos($response, '<modules>') !== false) && (strpos($response, '</modules>') !== false)) {

				$data = array();

				$dom = new DomDocument();
				$dom->loadXML($response);
				$query = new DOMXPath($dom);
				$nodes = $query->query('/modules/'.strtolower($name).'/*');

				foreach ($nodes as $node)
					$data[$node->nodeName] = $node->nodeValue;

				return $data;
			}
		}
		catch (Exception $e) {
			Mage::log(sprintf('%s for %s (%s)', $e->getMessage(), $url, $name), Zend_Log::ERR, 'modules.log');
		}

		return array();
	}

	private function checkConnect($name) {

		try {
			$url = 'https://connect20.magentocommerce.com/community/'.$name.'/releases.xml'; // Owebia_Shipping2
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
			curl_setopt($ch, CURLOPT_TIMEOUT, 5);
			$response = curl_exec($ch);
			curl_close($ch);

			// lecture du fichier XML de la liste des versions du module sur magento connect
			// pour l'expression du xpath voir http://www.freeformatter.com/xpath-tester.html
			if ((strpos($response, '<releases>') !== false) && (strpos($response, '</releases>') !== false)) {

				$data = array();

				$dom = new DomDocument();
				$dom->loadXML($response);
				$query = new DOMXPath($dom);
				$nodes = $query->query('(//s[text()="stable"])/../v');

				foreach ($nodes as $nodeV) {
					$nodeD = $nodeV->parentNode->getElementsByTagName('d')[0];
					$data[$nodeV->nodeValue] = array(
						'version' => $nodeV->nodeValue,
						'date'    => $nodeD->nodeValue
					);
				}

				// trie du plus grand au plus petit (donc de la plus récente à la plus ancienne version)
				// puis récupère la plus récente
				usort($data, function ($a, $b) {
					return ($a['version'] == $b['version']) ? 0 : (version_compare($a['version'], $b['version'], '>') ? -1 : 1);
				});
				$data = array_shift($data);

				// vérification si c'est le bon module
				// avec le connect 2 vérifie le contenu du fichier package.xml
				// avec le connect 1 ne fait rien
				if (!empty($data['version'])) {

					$url = 'https://connect20.magentocommerce.com/community/'.$name.'/'.$data['version'].'/package.xml';
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $url);
					curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
					curl_setopt($ch, CURLOPT_TIMEOUT, 5);
					$response = curl_exec($ch);
					curl_close($ch);

					if (strpos($response, $name) !== false)
						return $data;
					else if (strpos($response, 'Not Found') !== false)
						return $data;
				}
			}
		}
		catch (Exception $e) {
			Mage::log(sprintf('%s for %s (%s)', $e->getMessage(), $url, $name), Zend_Log::ERR, 'modules.log');
		}

		return array();
	}
}
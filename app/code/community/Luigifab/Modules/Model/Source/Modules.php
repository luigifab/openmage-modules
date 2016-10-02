<?php
/**
 * Created L/21/07/2014
 * Updated W/21/09/2016
 * Version 21
 *
 * Copyright 2012-2016 | Fabrice Creuzot (luigifab) <code~luigifab~info>
 * https://redmine.luigifab.info/projects/magento/wiki/modules
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

	private $cache = array();

	public function getCollection() {

		// getName() = le nom du tag xml
		// => /config/modules/Luigifab_Modules
		// <modules>
		//  <Luigifab_Modules>                                 <= $config
		//   <active>true</active>
		//   <codePool>community</codePool>
		//   <update>http://www.luigifab.info/magento/rss.xml
		$nodes = Mage::getConfig()->getXpath('/config/modules/*');

		foreach ($nodes as $config) {

			if (!in_array($config->codePool, array('local', 'community')))
				continue;

			$moduleName = $config->getName();
			$check = array('status' => 'unknown');

			if (Mage::getStoreConfigFlag('modules/general/last')) {

				if (strlen($config->update) > 10)
					$check += $this->checkUpdate($moduleName, $config->update);

				else if ((strpos($moduleName, 'Mage_') === false) && ($moduleName != 'Phoenix_Moneybookers') &&
				         ($config->codePool == 'community')) // pas de !== et === ici
					$check += $this->checkConnect($moduleName);
			}

			if ($config->active != 'true') { // pas de !== ici
				$check['status'] = 'disabled';
			}
			else if (is_array($check)) {
				if (isset($check['version']) && version_compare($check['version'], $config->version, '>'))
					$check['status'] = 'toupdate';
				else if (isset($check['version']) && version_compare($check['version'], $config->version, '<'))
					$check['status'] = 'beta';
				else if (isset($check['version']))
					$check['status'] = 'uptodate';
			}

			$item = new Varien_Object();
			$item->setName(str_replace('_', '/', $moduleName));
			$item->setCodePool($config->codePool);
			$item->setCurrentVersion($config->version);
			$item->setLastVersion((isset($check['version'])) ? $check['version'] : false);
			$item->setLastDate((isset($check['date'])) ? $check['date'] : false);
			$item->setUrl((isset($check['url'])) ? $check['url'] : false);
			$item->setStatus($check['status']);

			$this->addItem($item);
		}

		usort($this->_items, array($this, 'sortModules'));
		return $this;
	}

	private function sortModules($a, $b) {
		$test = strcmp($a->getCodePool(), $b->getCodePool());
		return ($test === 0) ? strcmp($a->getName(), $b->getName()) : $test;
	}

	private function checkUpdate($name, $url) {

		$key = md5($url);

		try {
			if (!isset($this->cache[$key])) {
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
			Mage::log($e->getMessage().' for '.$url.' ('.$name.')', Zend_Log::ERR, 'modules.log');
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
			// pour l'expression du xpath voir http://www.freeformatter.com/xpath-tester.html#ad-output
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

				// trie du plus grand au plus petit
				// (donc de la plus récente à la plus ancienne version)
				usort($data, array($this, 'sortVersions'));
				$data = array_shift($data);

				// vérification si c'est le bon module
				// avec le connect 2 vérifie le contenu du fichier package.xml
				// avec le connect 1 ne fait rien
				if (isset($data['version'])) {

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
			Mage::log($e->getMessage().' for '.$url.' ('.$name.')', Zend_Log::ERR, 'modules.log');
		}

		return array();
	}

	private function sortVersions($a, $b) {
		return ($a['version'] == $b['version']) ? 0 : (version_compare($a['version'], $b['version'], '>') ? -1 : 1);
	}
}
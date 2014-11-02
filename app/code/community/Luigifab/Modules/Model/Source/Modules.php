<?php
/**
 * Created L/21/07/2014
 * Updated S/30/08/2014
 * Version 13
 *
 * Copyright 2012-2014 | Fabrice Creuzot (luigifab) <code~luigifab~info>
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

	public function getCollection() {

		// getName() = le nom du tag xml
		// => /config/modules/Luigifab_Cronlog
		// <modules>
		//  <Luigifab_Cronlog>                                 <= $config
		//   <active>true</active>
		//   <codePool>community</codePool>
		//   <update>http://www.luigifab.info/magento/rss.xml
		$nodes = Mage::getConfig()->getXpath('/config/modules/*');

		foreach ($nodes as $config) {

			if (in_array($config->codePool, array('', 'core')))
				continue;

			$moduleName = $config->getName();
			$check  = (strlen($config->update) > 10) ? $this->checkModuleVersion($moduleName, $config->update) : array();
			$status = 'unknown';

			if ($config->active->__toString() !== 'true') {
				$status = 'disabled';
			}
			else if (is_array($check)) {

				if (isset($check['version']) && version_compare($check['version'], $config->version, '>'))
					$status = 'toupdate';
				else if (isset($check['version']) && version_compare($check['version'], $config->version, '<'))
					$status = 'beta';
				else if (isset($check['version']))
					$status = 'uptodate';
			}

			$item = new Varien_Object();
			$item->setName(str_replace('_', '/', $moduleName));
			$item->setCodePool($config->codePool);
			$item->setCurrentVersion($config->version);
			$item->setLastVersion((isset($check['version'])) ? $check['version'] : false);
			$item->setUrl((isset($check['url'])) ? $check['url'] : false);
			$item->setStatus($status);

			$this->addItem($item);
		}

		usort($this->_items, array($this, 'sort'));
		return $this;
	}

	private function sort($a, $b) {
		$test = strcmp($a->getCodePool(), $b->getCodePool());
		return ($test === 0) ? strcmp($a->getName(), $b->getName()) : $test;
	}

	private function checkModuleVersion($name, $url) {

		try {
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			$response = curl_exec($curl);
			curl_close($curl);

			if ((strpos($response, '<modules>') !== false) && (strpos($response, '</modules>') !== false)) {

				$data = array('version' => null, 'url' => null);

				$dom = new DomDocument();
				$dom->loadXML($response);

				$qry = new DOMXPath($dom);
				$nodes = $qry->query('/modules/'.strtolower($name).'/*');

				foreach ($nodes as $node)
					$data[$node->nodeName] = $node->nodeValue;

				return $data;
			}
		}
		catch (Exception $e) {
			Mage::log($e->getMessage().' for '.$url.' ('.$name.')', Zend_Log::ERR, 'modules.log');
		}

		return false;
	}
}
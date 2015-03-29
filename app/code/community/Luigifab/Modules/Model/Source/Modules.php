<?php
/**
 * Created L/21/07/2014
 * Updated L/23/03/2015
 * Version 14
 *
 * Copyright 2012-2015 | Fabrice Creuzot (luigifab) <code~luigifab~info>
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
		// => /config/modules/Luigifab_Modules
		// <modules>
		//  <Luigifab_Modules>                                 <= $config
		//   <active>true</active>
		//   <codePool>community</codePool>
		//   <update>http://www.luigifab.info/magento/rss.xml
		$nodes = Mage::getConfig()->getXpath('/config/modules/*');

		foreach ($nodes as $config) {

			if (in_array($config->codePool, array('', 'core')))
				continue;

			$moduleName = $config->getName();
			$check  = array();
			$status = 'unknown';

			if (Mage::getStoreConfig('modules/general/last') === '1') {

				if (strlen($config->update) > 10)
					$check = $this->checkUpdate($moduleName, $config->update);

				else if ((strpos($moduleName, 'Mage_') === false) && ($moduleName !== 'Phoenix_Moneybookers') &&
				         ($config->codePool->__toString() === 'community'))
					$check = $this->checkConnect($moduleName);
			}

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
			$item->setLastDate((isset($check['date'])) ? $check['date'] : false);
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

	private function checkUpdate($name, $url) {

		try {
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_TIMEOUT, 10);
			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			$response = curl_exec($curl);
			curl_close($curl);

			// lecture du fichier XML de la balise <update>
			if ((strpos($response, '<modules>') !== false) && (strpos($response, '</modules>') !== false)) {

				$data = array();

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

	private function checkConnect($name) {

		try {
			$channel = Mage::getStoreConfig('modules/general/channel');

			$key = $name; // Owebia_Shipping2
			$url = 'http://connect20.magentocommerce.com/community/'.$key.'/releases.xml';
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_TIMEOUT, 10);
			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			$response = curl_exec($curl);
			curl_close($curl);

			if (strpos($response, 'Not Found') !== false) {
				$key = substr($name, 0, -1).'_'.substr($name, -1); // Owebia_Shipping_2
				$url = 'http://connect20.magentocommerce.com/community/'.$key.'/releases.xml';
				$curl = curl_init();
				curl_setopt($curl, CURLOPT_URL, $url);
				curl_setopt($curl, CURLOPT_TIMEOUT, 10);
				curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				$response = curl_exec($curl);
				curl_close($curl);
			}
			if (strpos($response, 'Not Found') !== false) {
				$key = str_replace('_', '', $name); // OwebiaShipping2
				$url = 'http://connect20.magentocommerce.com/community/'.$key.'/releases.xml';
				$curl = curl_init();
				curl_setopt($curl, CURLOPT_URL, $url);
				curl_setopt($curl, CURLOPT_TIMEOUT, 10);
				curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				$response = curl_exec($curl);
				curl_close($curl);
			}
			if (strpos($response, 'Not Found') !== false) {
				$key = substr($name, strpos($name, '_') + 1); // Owebia
				$url = 'http://connect20.magentocommerce.com/community/'.$key.'/releases.xml';
				$curl = curl_init();
				curl_setopt($curl, CURLOPT_URL, $url);
				curl_setopt($curl, CURLOPT_TIMEOUT, 10);
				curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				$response = curl_exec($curl);
				curl_close($curl);
			}

			// lecture du fichier XML de la liste des versions du module sur magento connect
			// test du xpath : http://www.freeformatter.com/xpath-tester.html#ad-output
			// avec l'option 2 : http://connect20.magentocommerce.com/community/BankPayment/releases.xml
			if ((strpos($response, '<releases>') !== false) && (strpos($response, '</releases>') !== false)) {

				$data = array();

				$dom = new DomDocument();
				$dom->loadXML($response);
				$qry = new DOMXPath($dom);
				$nodes = $qry->query('(//s[text()="'.$channel.'"])[last()]/../*'); // au lieu de /releases/r[last()]/*

				foreach ($nodes as $node) {

					if ($node->nodeName == 'v')
						$data['version'] = $node->nodeValue;
					else if ($node->nodeName == 'd')
						$data['date'] = $node->nodeValue;
				}

				// vérification si c'est le bon module
				// avec le connect 2 : vérifie le contenu du fichier package.xml
				// avec le connect 1 : ne fait rien
				if (isset($data['version'])) {

					$url = 'http://connect20.magentocommerce.com/community/'.$key.'/'.$data['version'].'/package.xml';
					$curl = curl_init();
					curl_setopt($curl, CURLOPT_URL, $url);
					curl_setopt($curl, CURLOPT_TIMEOUT, 10);
					curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
					curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
					$response = curl_exec($curl);
					curl_close($curl);

					if (strpos($response, $key) !== false) // connect 2 : le fichier existe
						return $data;
					else if (strpos($response, 'Not Found') !== false) // connect 1 : le fichier n'existe pas
						return $data;
				}
			}
		}
		catch (Exception $e) {
			Mage::log($e->getMessage().' for '.$url.' ('.$name.')', Zend_Log::ERR, 'modules.log');
		}

		return false;
	}
}
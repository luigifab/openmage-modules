<?php
/**
 * Created V/20/07/2012
 * Updated V/19/10/2012
 * Version 5
 *
 * Copyright 2012 | Fabrice Creuzot (luigifab) <code~luigifab~info>
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

class Luigifab_Modules_Helper_Data extends Mage_Core_Helper_Abstract {

	public function getVersion() {
		return (string) Mage::getConfig()->getModuleConfig('Luigifab_Modules')->version;
	}

	public function getModulesList($core = false) {

		$data = array();

		foreach (Mage::getConfig()->getNode('modules')->children() as $module) {

			$codepool = (string) $module->codePool;
			$version = (string) $module->version;
			$update = (string) $module->update;
			$check = array();

			if (strlen($update) > 0)
				$check = $this->checkModuleVersion($module->getName(), $update);

			// module à jour ou pas
			if (is_array($check) && !empty($check)) {
				$data[$codepool][] = array(
					'name' => str_replace('_', '/', $module->getName()),
					'currentVersion' => $version,
					'lastVersion' => $check['lastVersion'],
					'url' => $check['url']
				);
			}
			// module sans information
			else {
				$data[$codepool][] = array(
					'name' => str_replace('_', '/', $module->getName()),
					'currentVersion' => $version,
					'lastVersion' => false,
					'url' => false
				);

			}
		}

		if (!$core)
			unset($data['core']);

		ksort($data);
		return $data;
	}

	private function checkModuleVersion($name, $url) {

		try {
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			$response = curl_exec($curl);
			curl_close($curl);

			if (strpos($response, '<modules>') !== false) {

				$xml = new DomDocument();
				$xml->loadXML($response);

				foreach ($xml->getElementsByTagName(strtolower($name)) as $module) {
					return array('lastVersion' => $module->getElementsByTagName('version')->item(0)->firstChild->nodeValue, 'url' => $module->getElementsByTagName('url')->item(0)->firstChild->nodeValue);
				}

				return array();
			}
		}
		catch (Exception $e) {
			Mage::log($e->getMessage().' for '.$url.' ('.$name.')', Zend_Log::ERR);
		}

		return false;
	}
}
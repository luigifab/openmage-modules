<?php
/**
 * Created J/23/11/2023
 * Updated S/02/12/2023
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

class Luigifab_Modules_Model_Source_Routes extends Varien_Data_Collection {

	public function getCollection($type = null) {

		// getName() = xml tag name
		// => /config/frontend/routers/test
		// <frontend>
		//  <routers>
		//   <test>                              <= $nodes
		//    <use>standard</use>
		//    <args>
		//     <module>Luigifab_Modules</module>
		//     <frontName>test</frontName>
		$nodes = Mage::getModel('core/config')->loadBase()->loadModules()->loadDb();
		$nodes = $nodes->getXpath('/config/frontend/routers/*');

		foreach ($nodes as $node) {

			$moduleName = (string) $node->args->module;
			$frontName  = (string) $node->args->frontName;
			$routeType  = (string) $node->use;

			$item = new Varien_Object();
			$item->setData('module', $moduleName);
			$item->setData('scope', 'frontend');
			$item->setData('type', $routeType);
			$item->setData('route', $frontName);
			$item->setData('status', 'enabled');

			$this->addItem($item);
		}

		usort($this->_items, static function ($a, $b) {
			$test = strnatcasecmp($a->getData('module'), $b->getData('module'));
			return ($test == 0) ? strnatcasecmp($a->getData('type'), $b->getData('type')) : $test;
		});

		$this->_setIsLoaded();
		return $this;
	}
}
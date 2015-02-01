<?php
/**
 * Created S/02/08/2014
 * Updated D/31/08/2014
 * Version 7
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

class Luigifab_Modules_Model_Source_Observers extends Varien_Data_Collection {

	public function getCollection() {

		// getName() = le nom du tag xml
		// => /config/adminhtml/events/admin_system_config_changed_section_cronlog/observers/cronlog
		// <adminhtml>                                       <= $config/../../../../$scope
		//  <events>
		//   <admin_system_config_changed_section_cronlog>   <= $config/../../$event
		//    <observers>
		//     <cronlog>                                     <= $config
		//      <class>cronlog/observer</class>
		//      <method>updateConfig</method>
		//      <type>disabled</type>
		$nodes = Mage::getConfig()->getXpath('/config/*/events/*/observers/*');

		foreach ($nodes as $config) {

			$moduleName = Mage::getConfig()->getModelClassName($config->class);
			$moduleName = substr($moduleName, 0, strpos($moduleName, '_', strpos($moduleName, '_') + 1));
			$moduleName = str_replace('_', '/', $moduleName);

			$scope  = $config->getParent()->getParent()->getParent()->getParent()->getName();
			$event  = $config->getParent()->getParent()->getName();

			$item = new Varien_Object();
			$item->setModule($moduleName);
			$item->setEvent($event);
			$item->setScope($scope);
			$item->setModel($config->class.'::'.$config->method);
			$item->setStatus(($config->type === 'disabled') ? 'disabled' : 'enabled');

			$this->addItem($item);
		}

		usort($this->_items, array($this, 'sort'));
		return $this;
	}

	private function sort($a, $b) {
		$test = strcmp($a->getScope(), $b->getScope());
		if ($test === 0)
			$test = strcmp($a->getEvent(), $b->getEvent());
		if ($test === 0)
			$test = strcmp($a->getModel(), $b->getModel());
		return $test;
	}
}
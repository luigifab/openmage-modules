<?php
/**
 * Created S/02/08/2014
 * Updated J/19/07/2018
 *
 * Copyright 2012-2018 | Fabrice Creuzot (luigifab) <code~luigifab~info>
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

class Luigifab_Modules_Model_Source_Observers extends Varien_Data_Collection {

	public function getCollection() {

		// getName() = le nom du tag xml
		// => /config/adminhtml/events/admin_system_config_changed_section_modules/observers/modules
		// <adminhtml>                                       <= $config/../../../../$scope
		//  <events>
		//   <admin_system_config_changed_section_modules>   <= $config/../../$event
		//    <observers>
		//     <modules>                                     <= $config
		//      <class>modules/observer</class>
		//      <method>updateConfig</method>
		//      <type>disabled</type>
		$config = Mage::getModel('core/config')->loadBase()->loadModules()->loadDb();
		$nodes  = $config->getXpath('/config/*/events/*/observers/*');

		foreach ($nodes as $config) {

			$moduleName = Mage::getConfig()->getModelClassName(($config->class) ? $config->class : $config->model);

			if (!empty($moduleName)) {
				$moduleName = substr($moduleName, 0, strpos($moduleName, '_', strpos($moduleName, '_') + 1));
				$moduleName = str_replace('_', '/', $moduleName);
			}

			$scope = $config->getParent()->getParent()->getParent()->getParent()->getName();
			$event = $config->getParent()->getParent()->getName();

			$item = new Varien_Object();
			$item->setData('module', $moduleName);
			$item->setData('event', $event);
			$item->setData('scope', $scope);
			$item->setData('model', (!empty($moduleName)) ? $config->class.'::'.$config->method : '');
			$item->setData('status', (empty($moduleName) || ($config->type == 'disabled')) ? 'disabled' : 'enabled');

			$this->addItem($item);
		}

		usort($this->_items, function ($a, $b) {
			$test = strcmp($a->getData('scope'), $b->getData('scope'));
			if ($test === 0)
				$test = strcmp($a->getData('event'), $b->getData('event'));
			if ($test === 0)
				$test = strcmp($a->getData('model'), $b->getData('model'));
			return $test;
		});

		return $this;
	}
}
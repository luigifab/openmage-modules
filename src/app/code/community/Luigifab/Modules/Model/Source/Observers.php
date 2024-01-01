<?php
/**
 * Created S/02/08/2014
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

class Luigifab_Modules_Model_Source_Observers extends Varien_Data_Collection {

	public function getCollection() {

		// getName() = xml tag name
		// => /config/adminhtml/events/admin_system_config_changed_section_modules/observers/modules
		// <adminhtml>                                       <= $node/../../../../$scope
		//  <events>
		//   <admin_system_config_changed_section_modules>   <= $node/../../$event
		//    <observers>
		//     <modules>                                     <= $node
		//      <class>modules/observer</class>
		//      <method>updateConfig</method>
		//      <type>disabled</type>
		$xml   = Mage::getModel('core/config')->loadBase()->loadModules()->loadDb();
		$nodes = $xml->getXpath('/config/*/events/*/observers/*');

		foreach ($nodes as $node) {

			$className  = Mage::getConfig()->getModelClassName((string) $node->class);
			$methodName = (string) $node->method;
			$moduleName = mb_substr($className, 0, mb_strpos($className, '_', mb_strpos($className, '_') + 1));

			$scope = $node->getParent()->getParent()->getParent()->getParent()->getName();
			$event = $node->getParent()->getParent()->getName();

			$ofe  = $this->getOpenFileEditorData($className, $methodName);
			$item = new Varien_Object();
			$item->setData('ofe_file', $ofe['file'] ?? null);
			$item->setData('ofe_line', $ofe['line'] ?? null);
			$item->setData('class_name', $className);
			$item->setData('module', $moduleName);
			$item->setData('event', $event);
			$item->setData('scope', $scope);
			$item->setData('model', $this->getShortClassName($xml, (string) $node->class).'::'.$node->method);
			$item->setData('status', (empty($moduleName) || ($node->type == 'disabled')) ? 'disabled' : 'enabled');

			$this->addItem($item);
		}

		usort($this->_items, static function ($a, $b) {
			$test = strnatcasecmp($a->getData('scope'), $b->getData('scope'));
			if ($test == 0)
				$test = strnatcasecmp($a->getData('event'), $b->getData('event'));
			if ($test == 0)
				$test = strnatcasecmp($a->getData('model'), $b->getData('model'));
			return $test;
		});

		$this->_setIsLoaded();
		return $this;
	}

	protected function getShortClassName(object $xml, string $name, string $scope = 'models') {

		// $name = Luigifab_Modules_Model_Rewrite_Demo
		if (str_contains($name, '/'))
			return $name;

		// module actif
		// config/global/models/modules/class => Luigifab_Modules_Model
		$nodes = $xml->getXpath('/config/*/'.$scope.'/*');
		foreach ($nodes as $node) {
			// $node->getName = modules
			// $node->class   = Luigifab_Modules_Model
			// result         = modules/rewrite_demo
			if (!empty($node->class) && (mb_stripos($name, (string) $node->class) === 0))
				return $node->getName().'/'.implode('_', array_map('lcfirst', explode('_', str_replace($node->class.'_', '', $name))));
		}

		// module inactif
		return '*'.$name;
	}

	protected function getOpenFileEditorData(string $className, string $methodName) {

		try {
			$reflector = new ReflectionClass($className);
			$file = $reflector->getFileName();
			try {
				$reflector = $reflector->getMethod($methodName);
				$line = empty($methodName) ? 0 : (int) $reflector->getStartLine();
				if ($line > 0)
					$file = $reflector->getFileName();
			}
			catch (Throwable $tm) {
				$line = 0;
			}

			return ['file' => $file, 'line' => $line];
		}
		catch (Throwable $t) {
			return [];
		}
	}
}
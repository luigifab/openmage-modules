<?php
/**
 * Created D/10/02/2013
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

class Luigifab_Modules_Model_Source_Jobs extends Varien_Data_Collection {

	public function getCollection($type = null) {

		// getName() = xml tag name
		// => /config/crontab/jobs/modules_send_report
		// <crontab>
		//  <jobs>
		//   <modules_send_report>                       <= $node
		//    <run>
		//     <model>modules/observer::sendMail</model>
		//    <schedule>
		//     <disabled>1</disabled>
		$nodes = Mage::getModel('core/config')->loadBase()->loadModules()->loadDb();
		$nodes = $nodes->getXpath('/config/crontab/jobs/*');

		foreach ($nodes as $node) {

			$key    = $node->getName();
			$config = Mage::getConfig()->getNode('default/crontab/jobs/'.$key);

			$expr = empty($node->schedule->config_path) ? null  : Mage::getStoreConfig((string) $node->schedule->config_path);
			$expr = empty($node->schedule->cron_expr)   ? $expr : $node->schedule->cron_expr;
			$expr = empty($config->schedule->config_path) ? $expr : Mage::getStoreConfig((string) $config->schedule->config_path);
			$expr = empty($config->schedule->cron_expr)   ? $expr : $config->schedule->cron_expr;
			$expr = empty($expr) ? null : trim((string) $expr);

			$model = empty($node->run->model) ? null : $node->run->model;
			$model = empty($config->run->model) ? $model : $config->run->model;
			$model = (string) $model;

			$className  = Mage::getConfig()->getModelClassName($model);
			$methodName = mb_substr($className, mb_strpos($className, ':') + 2);
			$className  = mb_substr($className, 0, mb_strpos($className, ':'));
			$moduleName = mb_substr($className, 0, mb_strpos($className, '_', mb_strpos($className, '_') + 1));

			// tâche désactivée si
			// - pas de programmation (= ni balise config_path/cron_expr, ni configuration config_path/cron_expr)
			// - balise disabled
			// - configuration disabled
			$isDisabled = (empty($expr) || !empty($node->schedule->disabled) || !empty($config->schedule->disabled)) ?
				'disabled' : 'enabled';

			// tâche en lecture seule si
			// - pas de programmation (= ni balise config_path/cron_expr, ni configuration config_path/cron_expr)
			// - balise disabled
			// - pas de balise de programmation (= pas de balise config_path/cron_expr)
			$isReadOnly = empty($expr) ||
			              !empty($node->schedule->disabled) ||
			              (empty($node->schedule->config_path) && empty($node->schedule->cron_expr));

			$ofe  = $this->getOpenFileEditorData($className, $methodName);
			$item = new Varien_Object();
			$item->setData('ofe_file', $ofe['file'] ?? null);
			$item->setData('ofe_line', $ofe['line'] ?? null);
			$item->setData('class_name', $className);
			$item->setData('module', $moduleName);
			$item->setData('job_code', $key);
			$item->setData('cron_expr', $expr);
			$item->setData('model', $model);
			$item->setData('status', $isDisabled);
			$item->setData('is_read_only', $isReadOnly);

			if ((($type == 'ro') && $isReadOnly) || (($type == 'rw') && !$isReadOnly) || empty($type))
				$this->addItem($item);
		}

		usort($this->_items, static function ($a, $b) {
			return strnatcasecmp($a->getData('job_code'), $b->getData('job_code'));
		});

		$this->_setIsLoaded();
		return $this;
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

	public function toOptionArray() {
		return $this->getCollection()->_toOptionArray('job_code', 'job_code');
	}
}
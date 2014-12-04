<?php
/**
 * Created S/22/11/2014
 * Updated S/29/11/2014
 * Version 3
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

class Luigifab_Modules_Model_Observer extends Luigifab_Modules_Helper_Data {

	public function sendMail() {

		Mage::getSingleton('core/translate')->setLocale(Mage::getStoreConfig('general/locale/code'))->init('adminhtml', true);

		// préparation de l'email
		// génération du code HTML du détail des mises à jour
		$modules = Mage::getModel('modules/source_modules')->getCollection();
		$updates = array();

		foreach ($modules as $module) {

			if ($module->getStatus() !== 'toupdate')
				continue;

			array_push($updates, sprintf('(%d) <strong>%s %s</strong><br/>➩ %s (%s)', count($updates) + 1, $module->getName(), $module->getCurrentVersion(), $module->getLastVersion(), $module->getLastDate()));
		}

		// envoie des emails
		// avec les variables du template
		$this->send(array(
			'list'   => (count($updates) > 0) ? implode('</li><li style="margin:0.8em 0 0.5em;">', $updates) : '',
			'config' => str_replace('//admin', '/admin', $this->getUrl('adminhtml/system_config/edit', array('section' => 'modules')))
		));
	}

	public function updateConfig() {

		try {
			$config = Mage::getModel('core/config_data');
			$config->load('crontab/jobs/modules_send_report/schedule/cron_expr', 'path');

			if (Mage::getStoreConfig('modules/email/enabled') === '1') {

				// hebdomadaire, tous les lundi à 1h00 (hebdomadaire/weekly)
				// minute hour day-of-month month-of-year day-of-week (Dimanche = 0, Lundi = 1...)
				// 0	     1    *            *             0|1         => weekly
				$config->setValue('0 1 * * '.Mage::getStoreConfig('general/locale/firstday'));
				$config->setPath('crontab/jobs/modules_send_report/schedule/cron_expr');
				$config->save();

				// email de test
				$this->sendMail();
			}
			else {
				$config->delete();
			}
		}
		catch (Exception $e) {
			Mage::throwException($e->getMessage());
		}
	}

	private function send($vars) {

		$emails = explode(' ', trim(Mage::getStoreConfig('modules/email/recipient_email')));

		foreach ($emails as $email) {

			// sendTransactional($templateId, $sender, $recipient, $name, $vars = array(), $storeId = null)
			$template = Mage::getModel('core/email_template');
			$template->sendTransactional(
				Mage::getStoreConfig('modules/email/template'),
				Mage::getStoreConfig('modules/email/sender_email_identity'),
				trim($email), null, $vars
			);

			if (!$template->getSentSuccess())
				Mage::throwException($this->__('Can not send email report to %s.', $email));

			//exit($template->getProcessedTemplate($vars));
		}
	}
}
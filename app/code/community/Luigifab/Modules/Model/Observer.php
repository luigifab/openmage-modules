<?php
/**
 * Created S/22/11/2014
 * Updated J/04/01/2018
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

class Luigifab_Modules_Model_Observer extends Luigifab_Modules_Helper_Data {

	// EVENT admin_system_config_changed_section_modules (adminhtml)
	public function updateConfig() {

		try {
			$config = Mage::getModel('core/config_data');
			$config->load('crontab/jobs/modules_send_report/schedule/cron_expr', 'path');

			if (Mage::getStoreConfigFlag('modules/email/enabled')) {

				// hebdomadaire, tous les lundi à 1h00 (hebdomadaire/weekly)
				// minute hour day-of-month month-of-year day-of-week (Dimanche = 0, Lundi = 1...)
				// 0	     1    *            *             0|1         => weekly
				$config->setData('value', '0 1 * * '.Mage::getStoreConfig('general/locale/firstday'));
				$config->setData('path', 'crontab/jobs/modules_send_report/schedule/cron_expr');
				$config->save();

				// email de test
				// si demandé ou si le cookie maillog_print_email est présent
				$cookie = (Mage::getSingleton('core/cookie')->get('maillog_print_email') == 'yes') ? true : false;

				if (!empty(Mage::app()->getRequest()->getPost('modules_test_email')) || $cookie)
					$this->sendEmailReport(true);
			}
			else {
				$config->delete();
			}
		}
		catch (Exception $e) {
			Mage::throwException($e->getMessage());
		}
	}


	// CRON modules_send_report
	public function sendEmailReport($force = false) {

		$oldLocale = Mage::getSingleton('core/translate')->getLocale();
		$newLocale = (Mage::app()->getStore()->isAdmin()) ? $oldLocale : Mage::getStoreConfig('general/locale/code');
		Mage::getSingleton('core/translate')->setLocale($newLocale)->init('adminhtml', true);

		// chargement des modules
		$modules = Mage::getModel('modules/source_modules')->getCollection();
		$updates = array();

		foreach ($modules as $module) {

			if ($module->getData('status') != 'toupdate')
				continue;

			array_push($updates, sprintf('(%d) <strong>%s %s</strong><br />➤ %s - %s',
				count($updates) + 1,
				$module->getData('name'),
				$module->getData('current_version'),
				$module->getData('last_version'),
				Mage::getSingleton('core/locale')->date($module->getData('last_date'))->toString(Zend_Date::DATE_LONG)
			));
		}

		// envoi des emails
		if (!empty($updates) || ($force === true))
			$this->sendReportToRecipients($newLocale, array('list' => (count($updates) > 0) ? implode('</li><li style="margin:0.8em 0 0.5em;">', $updates) : ''));

		if ($newLocale != $oldLocale)
			Mage::getSingleton('core/translate')->setLocale($oldLocale)->init('adminhtml', true);
	}

	private function getEmailUrl($url, $params = array()) {

		if (Mage::getStoreConfigFlag('web/seo/use_rewrites'))
			return preg_replace('#/[^/]+\.php[0-9]*/#', '/', Mage::helper('adminhtml')->getUrl($url, $params));
		else
			return preg_replace('#/[^/]+\.php([0-9]*)/#', '/index.php$1/', Mage::helper('adminhtml')->getUrl($url, $params));
	}

	private function sendReportToRecipients($locale, $vars) {

		$emails = preg_split('#\s#', Mage::getStoreConfig('modules/email/recipient_email'));
		$vars['config'] = $this->getEmailUrl('adminhtml/system/config');
		$vars['config'] = substr($vars['config'], 0, strrpos($vars['config'], '/system/config'));

		foreach ($emails as $email) {

			if (in_array($email, array('hello@example.org', 'hello@example.com', '')))
				continue;

			// sendTransactional($templateId, $sender, $recipient, $name, $vars = array(), $storeId = null)
			// fait en manuel (identique de Magento 1.4 à 1.9) pour utiliser la locale que l'on veut
			// car le setLocale utilisé plus haut ne permet pas d'utiliser le template email de la langue choisie
			$sender = Mage::getStoreConfig('modules/email/sender_email_identity');
			$template = Mage::getModel('core/email_template');

			//$template->sendTransactional(
			//	Mage::getStoreConfig('modules/email/template'),
			//	Mage::getStoreConfig('modules/email/sender_email_identity'),
			//	$email, null, $vars
			//);

			$template->setSentSuccess(false);
			$template->loadDefault('modules_email_template', $locale);
			$template->setSenderName(Mage::getStoreConfig('trans_email/ident_'.$sender.'/name'));
			$template->setSenderEmail(Mage::getStoreConfig('trans_email/ident_'.$sender.'/email'));
			$template->setSentSuccess($template->send($email, null, $vars));

			if (!$template->getSentSuccess())
				Mage::throwException($this->__('Can not send the report by email to %s.', $email));

			//exit($template->getProcessedTemplate($vars));
		}
	}
}
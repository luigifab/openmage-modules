<?php
/**
 * Created S/22/11/2014
 * Updated D/17/12/2023
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

class Luigifab_Modules_Model_Report extends Luigifab_Modules_Model_Observer {

	// CRON modules_send_report
	public function send($cron = null, bool $test = false, bool $preview = false) {

		$modules   = Mage::getModel('modules/source_modules')->getCollection();
		$oldLocale = Mage::getSingleton('core/translate')->getLocale();
		$newLocale = Mage::app()->getStore()->isAdmin() ? $oldLocale : Mage::getStoreConfig('general/locale/code');
		$locales   = [];

		// search locales and emails
		$data = Mage::getStoreConfig('modules/email/recipient_email');
		if ($preview) {
			$locales = [$oldLocale => ['hack@example.org']];
		}
		else if (!empty($data) && ($data != 'a:0:{}')) {
			if (str_contains($data, '{')) {
				$data = @unserialize($data, ['allowed_classes' => false]);
				if (!empty($data)) {
					foreach ($data as $datum) {
						if (!in_array($datum['email'], ['hello@example.org', 'hello@example.com', '']))
							$locales[empty($datum['locale']) ? $newLocale : $datum['locale']][] = $datum['email'];
					}
				}
			}
			else {
				// compatibility with previous version
				$data = array_filter(preg_split('#\s+#', $data));
				foreach ($data as $datum) {
					if (!in_array($datum, ['hello@example.org', 'hello@example.com', '']))
						$locales[$newLocale][] = $datum;
				}
			}
		}

		// generate and send the report
		foreach ($locales as $locale => $recipients) {

			if (!$preview)
				Mage::getSingleton('core/translate')->setLocale($locale)->init('adminhtml', true);

			$updates = [];
			foreach ($modules as $module) {

				if ($module->getData('status') != 'toupdate')
					continue;

				$updates[] = sprintf('(%d) <strong>%s %s</strong><br />➤ %s - %s',
					count($updates) + 1,
					$module->getData('name'),
					$module->getData('current_version'),
					$module->getData('last_version'),
					Mage::getSingleton('core/locale')->date($module->getData('last_date'))->toString(Zend_Date::DATE_LONG)
				);
			}

			if (!empty($updates) || $test) {

				$list = empty($updates) ? '' : implode('</li><li style="margin:0.8em 0 0.5em;">', $updates);
				$html = $this->sendEmailToRecipients($locale, $recipients, ['list' => $list], $preview);

				if ($preview)
					return $html;
			}
			else {
				unset($locales[$locale]);
			}
		}

		Mage::getSingleton('core/translate')->setLocale($oldLocale)->init('adminhtml', true);

		if (is_object($cron))
			$cron->setData('messages', 'memory: '.((int) (memory_get_peak_usage(true) / 1024 / 1024)).'M (max: '.ini_get('memory_limit').')'."\n".print_r($locales, true));

		return $locales;
	}

	protected function getEmailUrl(string $url, array $params = []) {

		if (Mage::getStoreConfigFlag('web/seo/use_rewrites'))
			return preg_replace('#/[^/]+\.php\d*/#', '/', Mage::helper('adminhtml')->getUrl($url, $params));

		return preg_replace('#/[^/]+\.php(\d*)/#', '/index.php$1/', Mage::helper('adminhtml')->getUrl($url, $params));
	}

	protected function sendEmailToRecipients(string $locale, array $emails, array $vars = [], bool $preview = false) {

		$vars['config'] = $this->getEmailUrl('adminhtml/system/config');
		$vars['config'] = mb_substr($vars['config'], 0, mb_strrpos($vars['config'], '/system/config'));
		$sender = Mage::getStoreConfig('modules/email/sender_email_identity');

		foreach ($emails as $email) {

			$template = Mage::getModel('core/email_template');
			$template->setDesignConfig(['store' => null]);
			$template->loadDefault('modules_email_template', $locale);

			if ($preview)
				return $template->getProcessedTemplate($vars);

			$template->setSenderName(Mage::getStoreConfig('trans_email/ident_'.$sender.'/name'));
			$template->setSenderEmail(Mage::getStoreConfig('trans_email/ident_'.$sender.'/email'));
			$template->setSentSuccess($template->send($email, null, $vars));
			//exit($template->getProcessedTemplate($vars));

			if (!$template->getSentSuccess())
				Mage::throwException($this->__('Can not send the report by email to %s.', $email));
		}

		return true;
	}
}
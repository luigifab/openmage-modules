<?php
/**
 * Created V/21/11/2014
 * Updated S/29/11/2014
 * Version 2
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

class Luigifab_Modules_Modules_IndexController extends Mage_Adminhtml_Controller_Action {

	protected function _isAllowed() {
		return Mage::getSingleton('admin/session')->isAllowed('tools/modules');
	}

	public function indexAction() {

		$this->setUsedModuleName('Luigifab_Modules');

		$html = '<div class="content-header"><table cellspacing="0"><tbody><tr><td><h3 class="icon-head head-adminhtml-modules">'.$this->__('Modules list').'</h3></td></tr></tbody></table></div>';

		$block = Mage::getBlockSingleton('modules/adminhtml_modules_grid');
		$html .= '<div class="modules"><h4>'.$this->__('Modules list').' ('.$block->getCount().')</h4>'.$block->toHtml().'</div>';

		$block = Mage::getBlockSingleton('modules/adminhtml_jobs_grid');
		$html .= '<div class="jobs"><h4>'.$this->__('Cron jobs list').' ('.$block->getCount().')</h4>'.$block->toHtml().'</div>';

		$block = Mage::getBlockSingleton('modules/adminhtml_observers_grid');
		$html .= '<div class="observers"><h4>'.$this->__('Observers list').' ('.$block->getCount().')</h4>'.$block->toHtml().'</div>';

		$block = Mage::getBlockSingleton('modules/adminhtml_rewrites_grid');
		$html .= '<div class="rewrites"><h4>'.$this->__('Rewrites list').' ('.$block->getCount().')</h4>'.$block->toHtml().'</div>';

		$this->loadLayout()->_setActiveMenu('tools/modules');
		$this->getLayout()->getBlock('content')->append($this->getLayout()->createBlock('core/text')->setText($html));
		$this->renderLayout();
	}
}
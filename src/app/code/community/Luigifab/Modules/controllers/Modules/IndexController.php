<?php
/**
 * Created V/21/11/2014
 * Updated V/24/07/2020
 *
 * Copyright 2012-2021 | Fabrice Creuzot (luigifab) <code~luigifab~fr>
 * https://www.luigifab.fr/openmage/modules
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

	public function getUsedModuleName() {
		return 'Luigifab_Modules';
	}

	public function loadLayout($ids = null, $generateBlocks = true, $generateXml = true) {
		$this->_title($this->__('Tools'))->_title($this->__('Installed modules'));
		parent::loadLayout($ids, $generateBlocks, $generateXml);
		$this->_setActiveMenu('tools/modules');
		return $this;
	}

	public function indexAction() {

		$this->loadLayout();

		$block = $this->getLayout()->createBlock('adminhtml/widget_button')
			->setData('type', 'button')
			->setData('label', $this->__('Reset Filter'))
			->setData('onclick', 'modules.reset();');

		$html = '<div class="content-header"><table cellspacing="0"><tbody><tr><td><h3 class="icon-head head-adminhtml-modules">'.$this->__('Installed modules').'</h3></td><td class="form-buttons"><input type="search" spellcheck="false" autocomplete="off" class="input-text" oninput="modules.action(this);" placeholder="'.$this->__('Search').'" /> '.$block->toHtml().'</td></tr></tbody></table></div>';

		$block = Mage::getBlockSingleton('modules/adminhtml_modules_grid');
		$html .= '<div class="modules"><h4>'.$this->__('Modules list').' ('.$block->getCount().')</h4> '.$block->toHtml().'</div>';

		$block = Mage::getBlockSingleton('modules/adminhtml_jobs_grid');
		$html .= '<div class="jobs"><h4>'.$this->__('Cron jobs list').' ('.$block->getCount().')</h4> '.$block->toHtml().'</div>';

		$block = Mage::getBlockSingleton('modules/adminhtml_observers_grid');
		$html .= '<div class="observers"><h4>'.$this->__('Observers list').' ('.$block->getCount().')</h4> '.$block->toHtml().'</div>';

		$block = Mage::getBlockSingleton('modules/adminhtml_rewrites_grid');
		$html .= '<div class="rewrites"><h4>'.$this->__('Rewrites list').' ('.$block->getCount().')</h4> '.$block->toHtml().'</div>';

		$this->getLayout()->getBlock('content')->append($this->getLayout()->createBlock('core/text')->setText($html));
		$this->renderLayout();
	}
}
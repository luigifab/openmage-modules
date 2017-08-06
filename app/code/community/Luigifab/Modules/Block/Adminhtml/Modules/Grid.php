<?php
/**
 * Created L/21/07/2014
 * Updated D/16/04/2017
 *
 * Copyright 2012-2017 | Fabrice Creuzot (luigifab) <code~luigifab~info>
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

class Luigifab_Modules_Block_Adminhtml_Modules_Grid extends Mage_Adminhtml_Block_Widget_Grid {

	public function __construct() {

		parent::__construct();

		$this->setId('modules_modules_grid');

		$this->setUseAjax(false);
		$this->setSaveParametersInSession(false);
		$this->setPagerVisibility(false);
		$this->setFilterVisibility(true);

		$this->setCollection(Mage::getModel('modules/source_modules')->getCollection());
	}

	protected function _prepareCollection() {
		// $this->setCollection() in __construct()
		return parent::_prepareCollection();
	}

	protected function _prepareColumns() {

		$this->addColumn('name', array(
			'header'    => $this->__('Module name'),
			'index'     => 'name',
			'filter'    => false,
			'sortable'  => false,
			'header_css_class' => 'default n2 txt sort',
			'frame_callback' => array($this, 'decorateName')
		));

		$this->addColumn('code_pool', array(
			'header'    => $this->__('Type'),
			'index'     => 'code_pool',
			'align'     => 'center',
			'width'     => '130px',
			'filter'    => false,
			'sortable'  => false,
			'header_css_class' => 'default n1 txt sort'
		));

		$this->addColumn('current_version', array(
			'header'    => $this->__('Installed version'),
			'index'     => 'current_version',
			'align'     => 'center',
			'width'     => '130px',
			'filter'    => false,
			'sortable'  => false,
			'header_css_class' => 'txt sort'
		));

		$this->addColumn('last_version', array(
			'header'    => $this->__('Last version'),
			'index'     => 'last_version',
			'align'     => 'center',
			'width'     => '130px',
			'filter'    => false,
			'sortable'  => false,
			'header_css_class' => 'txt sort'
		));

		$this->addColumn('last_date', array(
			'header'    => $this->__('Latest version of'),
			'index'     => 'last_date',
			'type'      => 'date',
			'format'    => Mage::getSingleton('core/locale')->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_LONG),
			'align'     => 'center',
			'width'     => '180px',
			'filter'    => false,
			'sortable'  => false
		));

		$this->addColumn('status', array(
			'header'    => $this->__('Status'),
			'index'     => 'status',
			'type'      => 'options',
			'options'   => array(
				'uptodate' => $this->__('Up to date'),
				'toupdate' => $this->__('To update'),
				'beta'     => $this->__('Beta'),
				'unknown'  => '?',
				'disabled' => $this->__('Disabled')
			),
			'align'     => 'status',
			'width'     => '120px',
			'filter'    => false,
			'sortable'  => false,
			'header_css_class' => 'txt sort',
			'frame_callback' => array($this, 'decorateStatus')
		));

		return parent::_prepareColumns();
	}

	public function getCount() {
		return $this->getCollection()->getSize();
	}


	public function getRowClass($row) {
		return ($row->getData('status') === 'disabled') ? 'disabled' : '';
	}

	public function getRowUrl($row) {
		return false;
	}

	public function decorateStatus($value, $row, $column, $isExport) {
		return sprintf('<span class="grid-%s">%s</span>', $row->getData('status'), $value);
	}

	public function decorateName($value, $row, $column, $isExport) {

		$url = $row->getData('url');
		$name = $row->getData('name');

		return (is_string($url)) ? sprintf('<a href="%s" onclick="window.open(this.href); return false;">%s</a>', $url, $name) : $name;
	}
}
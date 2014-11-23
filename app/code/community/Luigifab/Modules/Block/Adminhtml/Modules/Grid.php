<?php
/**
 * Created L/21/07/2014
 * Updated S/22/11/2014
 * Version 15
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

class Luigifab_Modules_Block_Adminhtml_Modules_Grid extends Mage_Adminhtml_Block_Widget_Grid {

	public function __construct() {

		parent::__construct();

		$this->setId('modules_modules_grid');

		$this->setUseAjax(false);
		$this->setSaveParametersInSession(false);
		$this->setPagerVisibility(false);
		$this->setFilterVisibility(false);

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
			'renderer'  => 'modules/adminhtml_modules_name',
			'filter'    => false,
			'sortable'  => false,
			'header_css_class' => 'defaultTsort n2 txt'
		));

		$this->addColumn('code_pool', array(
			'header'    => $this->__('Type'),
			'index'     => 'code_pool',
			'align'     => 'center',
			'width'     => '120px',
			'filter'    => false,
			'sortable'  => false,
			'header_css_class' => 'defaultTsort n1 txt'
		));

		$this->addColumn('current_version', array(
			'header'    => $this->__('Installed version'),
			'index'     => 'current_version',
			'align'     => 'center',
			'width'     => '120px',
			'filter'    => false,
			'sortable'  => false,
			'header_css_class' => 'txt'
		));

		$this->addColumn('last_version', array(
			'header'    => $this->__('Last version'),
			'index'     => 'last_version',
			'align'     => 'center',
			'width'     => '120px',
			'filter'    => false,
			'sortable'  => false,
			'header_css_class' => 'txt'
		));

		$this->addColumn('last_date', array(
			'header'    => $this->__('Latest version of'),
			'index'     => 'last_date',
			'type'      => 'date',
			'align'     => 'center',
			'filter'    => false,
			'sortable'  => false
		));

		$this->addColumn('status', array(
			'header'    => $this->helper('adminhtml')->__('Status'),
			'index'     => 'status',
			'type'      => 'options',
			'renderer'  => 'modules/adminhtml_modules_status',
			'options'   => array(
				'uptodate' => $this->__('Up to date'),
				'toupdate' => $this->__('To update'),
				'beta'     => $this->__('Beta'),
				'unknown'  => '?',
				'disabled' => $this->helper('adminhtml')->__('Disabled')
			),
			'align'     => 'status',
			'width'     => '120px',
			'filter'    => false,
			'sortable'  => false,
			'header_css_class' => 'txt'
		));

		return parent::_prepareColumns();
	}

	public function getRowClass($row) {
		return ($row->getStatus() === 'disabled') ? 'disabled' : '';
	}

	public function getRowUrl($row) {
		return false;
	}

	public function getCount() {
		return $this->getCollection()->getSize();
	}
}
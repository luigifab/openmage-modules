<?php
/**
 * Created M/22/07/2014
 * Updated M/19/08/2014
 * Version 14
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

class Luigifab_Modules_Block_Adminhtml_Observers_Grid extends Mage_Adminhtml_Block_Widget_Grid {

	public function __construct() {

		parent::__construct();

		$this->setId('modules_observers_grid');

		$this->setUseAjax(false);
		$this->setSaveParametersInSession(false);
		$this->setPagerVisibility(false);
		$this->setFilterVisibility(false);

		$this->setCollection(Mage::getModel('modules/source_observers')->getCollection());
	}

	protected function _prepareCollection() {
		// $this->setCollection() in __construct()
		return parent::_prepareCollection();
	}

	protected function _prepareColumns() {

		$this->addColumn('module', array(
			'header'    => $this->__('Module name'),
			'index'     => 'module',
			'filter'    => false,
			'sortable'  => false,
			'header_css_class' => 'case'
		));

		$this->addColumn('scope', array(
			'header'    => $this->__('Scope'),
			'index'     => 'scope',
			'align'     => 'center',
			'filter'    => false,
			'sortable'  => false,
			'header_css_class' => 'defaultTsort n1 case'
		));

		$this->addColumn('event', array(
			'header'    => $this->__('Event'),
			'index'     => 'event',
			'filter'    => false,
			'sortable'  => false,
			'header_css_class' => 'defaultTsort n2 case'
		));

		$this->addColumn('model', array(
			'header'    => 'Model',
			'index'     => 'model',
			'width'     => '40%',
			'filter'    => false,
			'sortable'  => false,
			'header_css_class' => 'defaultTsort n3 case'
		));

		$this->addColumn('status', array(
			'header'    => $this->helper('adminhtml')->__('Status'),
			'index'     => 'status',
			'type'      => 'options',
			'renderer'  => 'modules/adminhtml_observers_status',
			'options'   => array(
				'enabled'  => $this->helper('adminhtml')->__('Enabled'),
				'disabled' => $this->helper('adminhtml')->__('Disabled')
			),
			'align'     => 'status',
			'width'     => '120px',
			'filter'    => false,
			'sortable'  => false,
			'header_css_class' => 'case'
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
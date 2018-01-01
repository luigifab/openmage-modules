<?php
/**
 * Created M/22/07/2014
 * Updated S/11/11/2017
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

class Luigifab_Modules_Block_Adminhtml_Observers_Grid extends Mage_Adminhtml_Block_Widget_Grid {

	public function __construct() {

		parent::__construct();

		$this->setId('modules_observers_grid');

		$this->setUseAjax(false);
		$this->setSaveParametersInSession(false);
		$this->setPagerVisibility(false);
		$this->setFilterVisibility(true);

		$this->setCollection(Mage::getModel('modules/source_observers')->getCollection());
	}

	protected function _prepareCollection() {
		// $this->setCollection() dans __construct() pour getCount()
		return parent::_prepareCollection();
	}

	protected function _prepareColumns() {

		$this->addColumn('module', array(
			'header'    => $this->__('Module name'),
			'index'     => 'module',
			'filter'    => false,
			'sortable'  => false,
			'header_css_class' => 'txt sort'
		));

		$this->addColumn('scope', array(
			'header'    => $this->__('Scope'),
			'index'     => 'scope',
			'align'     => 'center',
			'width'     => '90px',
			'filter'    => false,
			'sortable'  => false,
			'header_css_class' => 'default n1 txt sort'
		));

		$this->addColumn('event', array(
			'header'    => $this->__('Event'),
			'index'     => 'event',
			'filter'    => false,
			'sortable'  => false,
			'header_css_class' => 'default n2 txt sort'
		));

		$this->addColumn('model', array(
			'header'    => 'Model',
			'index'     => 'model',
			'filter'    => false,
			'sortable'  => false,
			'header_css_class' => 'default n3 txt sort'
		));

		$this->addColumn('status', array(
			'header'    => $this->__('Status'),
			'index'     => 'status',
			'type'      => 'options',
			'options'   => array(
				'enabled'  => $this->__('Enabled'),
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
		return ($row->getData('status') == 'disabled') ? 'disabled' : '';
	}

	public function getRowUrl($row) {
		return false;
	}

	public function canDisplayContainer() {
		return false;
	}

	public function getMessagesBlock() {
		return Mage::getBlockSingleton('core/template');
	}

	public function decorateStatus($value, $row, $column, $isExport) {
		return sprintf('<span class="grid-%s">%s</span>', $row->getData('status'), $value);
	}
}
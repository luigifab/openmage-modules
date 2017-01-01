<?php
/**
 * Created M/22/07/2014
 * Updated M/08/11/2016
 *
 * Copyright 2012-2017 | Fabrice Creuzot (luigifab) <code~luigifab~info>
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

class Luigifab_Modules_Block_Adminhtml_Rewrites_Grid extends Mage_Adminhtml_Block_Widget_Grid {

	public function __construct() {

		parent::__construct();

		$this->setId('modules_rewrites_grid');

		$this->setUseAjax(false);
		$this->setSaveParametersInSession(false);
		$this->setPagerVisibility(false);
		$this->setFilterVisibility(true);

		$this->setCollection(Mage::getModel('modules/source_rewrites')->getCollection());
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
			'header_css_class' => 'txt sort'
		));

		$this->addColumn('scope', array(
			'header'    => $this->__('Scope'),
			'index'     => 'scope',
			'align'     => 'center',
			'width'     => '80px',
			'filter'    => false,
			'sortable'  => false,
			'header_css_class' => 'default n1 txt sort'
		));

		$this->addColumn('type', array(
			'header'    => $this->__('Type'),
			'index'     => 'type',
			'align'     => 'center',
			'width'     => '80px',
			'filter'    => false,
			'sortable'  => false,
			'header_css_class' => 'default n2 txt sort'
		));

		$this->addColumn('core_class', array(
			'header'    => $this->__('Source'),
			'index'     => 'core_class',
			'filter'    => false,
			'sortable'  => false,
			'header_css_class' => 'default n3 txt sort'
		));

		$this->addColumn('rewrite_class', array(
			'header'    => $this->__('Destination'),
			'index'     => 'rewrite_class',
			'filter'    => false,
			'sortable'  => false,
			'header_css_class' => 'txt sort'
		));

		$this->addColumn('status', array(
			'header'    => $this->__('Status'),
			'index'     => 'status',
			'type'      => 'options',
			'options'   => array(
				'enabled'  => $this->helper('modules')->_('Enabled'),
				'disabled' => $this->__('Conflict')
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
		return '';
	}

	public function getRowUrl($row) {
		return false;
	}

	public function decorateStatus($value, $row, $column, $isExport) {
		return '<span class="grid-'.$row->getData('status').'">'.$value.'</span>';
	}
}
<?php
/*
** Zabbix
** Copyright (C) 2001-2021 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


/**
 * @var CView $this
 * @var array $data
 */

$this->addJsFile('layout.mode.js');
$this->addJsFile('class.tagfilteritem.js');
$this->addJsFile('class.calendar.js');
$this->addJsFile('multiselect.js');
$this->addJsFile('textareaflexible.js');
$this->addJsFile('class.tab-indicators.js');

$this->includeJsFile('monitoring.service.list.js.php');

$breadcrumbs = [];
$filter = null;

if (count($data['breadcrumbs']) > 1) {
	while ($path_item = array_shift($data['breadcrumbs'])) {
		$breadcrumbs[] = (new CSpan())
			->addItem(array_key_exists('curl', $path_item)
				? new CLink($path_item['name'], $path_item['curl'])
				: $path_item['name']
			)
			->addClass(!$data['breadcrumbs'] ? ZBX_STYLE_SELECTED : null);
	}
}

$filter = (new CFilter())
	->addVar('action', 'service.list.edit')
	->addVar('serviceid', $data['service'] !== null ? $data['service']['serviceid'] : null)
	->setResetUrl($data['view_curl'])
	->setProfile('web.service.filter')
	->setActiveTab($data['active_tab']);

if ($data['service'] !== null && !$data['is_filtered']) {
	$parents = [];
	while ($parent = array_shift($data['service']['parents'])) {
		$parents[] = (new CLink($parent['name'], (new CUrl('zabbix.php'))
			->setArgument('action', 'service.list.edit')
			->setArgument('serviceid', $parent['serviceid'])
		))->setAttribute('data-serviceid', $parent['serviceid']);
		$parents[] = CViewHelper::showNum($parent['children']);

		if (!$data['service']['parents']) {
			break;
		}

		$parents[] = ', ';
	}

	if (in_array($data['service']['status'], [TRIGGER_SEVERITY_INFORMATION, TRIGGER_SEVERITY_NOT_CLASSIFIED])) {
		$service_status = _('OK');
		$service_status_style_class = null;
	}
	else {
		$service_status = getSeverityName($data['service']['status']);
		$service_status_style_class = 'service-status-'.getSeverityStyle($data['service']['status']);
	}

	$filter
		->addTab(
			(new CLink(_('Info'), '#tab_info'))->addClass(ZBX_STYLE_BTN_INFO),
			(new CDiv())
				->setId('tab_info')
				->addClass(ZBX_STYLE_FILTER_CONTAINER)
				->addItem(
					(new CDiv())
						->addClass(ZBX_STYLE_SERVICE_INFO)
						->addClass($service_status_style_class)
						->addItem([
							(new CDiv($data['service']['name']))->addClass(ZBX_STYLE_SERVICE_NAME)
						])
						->addItem([
							(new CDiv(_('Parents')))->addClass(ZBX_STYLE_SERVICE_INFO_LABEL),
							(new CDiv($parents))->addClass(ZBX_STYLE_SERVICE_INFO_VALUE)
						])
						->addItem([
							(new CDiv(_('Status')))->addClass(ZBX_STYLE_SERVICE_INFO_LABEL),
							(new CDiv((new CDiv($service_status))->addClass(ZBX_STYLE_SERVICE_STATUS)))
								->addClass(ZBX_STYLE_SERVICE_INFO_VALUE)
						])
						->addItem([
							(new CDiv(_('SLA')))->addClass(ZBX_STYLE_SERVICE_INFO_LABEL),
							(new CDiv(($data['service']['showsla'] == SERVICE_SHOW_SLA_ON)
								? sprintf('%.4f', $data['service']['goodsla'])
								: ''
							))
								->addClass(ZBX_STYLE_SERVICE_INFO_VALUE)
						])
						->addItem([
							(new CDiv(_('Tags')))->addClass(ZBX_STYLE_SERVICE_INFO_LABEL),
							(new CDiv($data['service']['tags']))->addClass(ZBX_STYLE_SERVICE_INFO_VALUE)
						])
				)
		);
}

$filter->addFilterTab(_('Filter'), [
	(new CFormGrid())
		->addClass(CFormGrid::ZBX_STYLE_FORM_GRID_LABEL_WIDTH_TRUE)
		->addItem([
			new CLabel(_('Name'), 'filter_name'),
			new CFormField(
				(new CTextBox('filter_name', $data['filter']['name']))
					->setWidth(ZBX_TEXTAREA_FILTER_STANDARD_WIDTH)
			)
		])
		->addItem([
			new CLabel(_('Status')),
			new CFormField(
				(new CRadioButtonList('filter_status', (int) $data['filter']['status']))
					->addValue(_('Any'), SERVICE_STATUS_ANY)
					->addValue(_('OK'), SERVICE_STATUS_OK)
					->addValue(_('Problem'), SERVICE_STATUS_PROBLEM)
					->setModern(true)
			)
		])
		->addItem([
			new CLabel(_('Only services without children'), 'filter-without-children'),
			new CFormField(
				(new CCheckBox('filter_without_children'))
					->setChecked($data['filter']['without_children'])
					->setId('filter-without-children')
			)
		])
		->addItem([
			new CLabel(_('Only services without problem tags'), 'filter-without-problem-tags'),
			new CFormField(
				(new CCheckBox('filter_without_problem_tags'))
					->setChecked($data['filter']['without_problem_tags'])
					->setId('filter-without-problem-tags')
			)
		]),
	(new CFormGrid())
		->addClass(CFormGrid::ZBX_STYLE_FORM_GRID_LABEL_WIDTH_TRUE)
		->addItem([
			new CLabel(_('Tags')),
			new CFormField([
				(new CRadioButtonList('filter_tag_source', (int) $data['filter']['tag_source']))
					->addValue(_('Any'), ZBX_SERVICE_FILTER_TAGS_ANY)
					->addValue(_('Service'), ZBX_SERVICE_FILTER_TAGS_SERVICE)
					->addValue(_('Problem'), ZBX_SERVICE_FILTER_TAGS_PROBLEM)
					->setModern(true)
					->addStyle('margin-bottom: 10px;'),
				CTagFilterFieldHelper::getTagFilterField([
					'evaltype' => $data['filter']['evaltype'],
					'tags' => $data['filter']['tags'] ?: [
						['tag' => '', 'value' => '', 'operator' => TAG_OPERATOR_LIKE]
					]
				])
			])
		])
]);

$form = (new CForm())->setName('service_form');

$header = [
	(new CColHeader(
		(new CCheckBox('all_services'))->onClick("checkAll('".$form->getName()."', 'all_services', 'serviceids');")
	))->addClass(ZBX_STYLE_CELL_WIDTH)
];

if ($data['is_filtered']) {
	$path = null;

	$header[] = (new CColHeader(_('Parent services')))->addStyle('width: 15%');
	$header[] = (new CColHeader(_('Name')))->addStyle('width: 10%');
}
else {
	$path = $data['path'];
	if ($data['service'] !== null) {
		$path[] = $data['service']['serviceid'];
	}

	$header[] = (new CColHeader(_('Name')))->addStyle('width: 25%');
}

$table = (new CTableInfo())
	->setHeader(array_merge($header, [
		(new CColHeader(_('Status')))->addStyle('width: 14%'),
		(new CColHeader(_('Root cause')))->addStyle('width: 24%'),
		(new CColHeader(_('SLA')))->addStyle('width: 14%'),
		(new CColHeader(_('Tags')))->addClass(ZBX_STYLE_COLUMN_TAGS_3),
		(new CColHeader())
	]));

foreach ($data['services'] as $serviceid => $service) {
	$row = [new CCheckBox('serviceids['.$serviceid.']', $serviceid)];

	if ($data['is_filtered']) {
		$parents = [];
		$count = 0;
		while ($parent = array_shift($service['parents'])) {
			$parents[] = (new CLink($parent['name'], (new CUrl('zabbix.php'))
				->setArgument('action', 'service.list')
				->setArgument('serviceid', $parent['serviceid'])
			))->setAttribute('data-serviceid', $parent['serviceid']);

			$count++;
			if ($count >= $data['max_in_table'] || !$service['parents']) {
				break;
			}

			$parents[] = ', ';
		}

		$row[] = $parents;
	}

	$table->addRow(new CRow(array_merge($row, [
		($service['children'] > 0)
			? [
				(new CLink($service['name'], (new CUrl('zabbix.php'))
					->setArgument('action', 'service.list.edit')
					->setArgument('path', $path)
					->setArgument('serviceid', $serviceid)
				))->setAttribute('data-serviceid', $serviceid),
				CViewHelper::showNum($service['children'])
			]
			: $service['name'],
		in_array($service['status'], [TRIGGER_SEVERITY_INFORMATION, TRIGGER_SEVERITY_NOT_CLASSIFIED])
			? (new CCol(_('OK')))->addClass(ZBX_STYLE_GREEN)
			: (new CCol(getSeverityName($service['status'])))->addClass(getSeverityStyle($service['status'])),
		'',
		($service['showsla'] == SERVICE_SHOW_SLA_ON) ? sprintf('%.4f', $service['goodsla']) : '',
		array_key_exists($serviceid, $data['tags']) ? $data['tags'][$serviceid] : 'tags',
		(new CCol([
			(new CButton(null))
				->addClass(ZBX_STYLE_BTN_ADD)
				->addClass('js-add-child-service')
				->setAttribute('data-serviceid', $serviceid),
			(new CButton(null))
				->addClass(ZBX_STYLE_BTN_EDIT)
				->addClass('js-edit-service')
				->setAttribute('data-serviceid', $serviceid),
			(new CButton(null))
				->addClass(ZBX_STYLE_BTN_REMOVE)
				->addClass('js-remove-service')
				->setAttribute('data-serviceid', $serviceid)
		]))->addClass(ZBX_STYLE_LIST_TABLE_ACTIONS)
	])));
}

(new CWidget())
	->setTitle(_('Services'))
	->setControls(
		(new CTag('nav', true,
			(new CList())
				->addItem(
					(new CSimpleButton(_('Create service')))
						->addClass('js-create-service')
						->setAttribute('data-serviceid', $data['service'] !== null
							? $data['service']['serviceid']
							: null
						)
				)
				->addItem(
					(new CRadioButtonList('list_mode', ZBX_LIST_MODE_EDIT))
						->addValue(_('View'), ZBX_LIST_MODE_VIEW)
						->addValue(_('Edit'), ZBX_LIST_MODE_EDIT)
						->setModern(true)
				)
		))->setAttribute('aria-label', _('Content controls'))
	)
	->setNavigation(
		$breadcrumbs ? new CList([new CBreadcrumbs($breadcrumbs)]) : null
	)
	->addItem([
		$filter,
		$form->addItem([
			$table,
			$data['paging'],
			new CActionButtonList('action', 'serviceids', [
				'popup.massupdate.service' => [
					'content' => (new CButton('', _('Mass update')))
						->onClick("return openMassupdatePopup(this, 'popup.massupdate.service');")
						->addClass(ZBX_STYLE_BTN_ALT)
						->removeAttribute('id')
				],
				'service.delete' => ['name' => _('Delete'), 'confirm' => _('Delete selected services?')]
			])
		])
	])
	->show();

(new CScriptTag('
	initializeView(
		'.json_encode($data['path'] ?: null).',
		'.json_encode($data['service'] !== null ? $data['service']['serviceid'] : null).',
		null
	);
'))
	->setOnDocumentReady()
	->show();

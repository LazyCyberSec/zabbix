<?php declare(strict_types = 0);
/*
** Zabbix
** Copyright (C) 2001-2023 Zabbix SIA
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


use Zabbix\Widgets\Fields\CWidgetFieldColumnsList;

?>

window.tophosts_column_edit_form = new class {

	init({form_name, thresholds, thresholds_colors}) {
		this._$widget_form = $(`form[name="${form_name}"]`);
		this._$thresholds_table = this._$widget_form.find('#thresholds_table');
		this._item_time = document.getElementById('item_time');
		this._aggregate_function = document.getElementById('aggregate_function');

		for (const element of this._$widget_form[0].querySelectorAll('.override-time')) {
			element.style.display = Number(this._aggregate_function.value) === 0 ? 'none' : '';
		}

		$('[name="data"], [name="aggregate_function"], [name="display"], [name="history"], [name="item_time"]', this._$widget_form)
			.on('change', () => this._update());

		colorPalette.setThemeColors(thresholds_colors);

		this._$thresholds_table.dynamicRows({
			rows: thresholds,
			template: '#thresholds-row-tmpl',
			allow_empty: true,
			dataCallback: (row_data) => {
				if (!('color' in row_data)) {
					const colors = this._$widget_form[0].querySelectorAll('.<?= ZBX_STYLE_COLOR_PICKER ?> input');
					const used_colors = [];

					for (const color of colors) {
						if (color.value !== '') {
							used_colors.push(color.value);
						}
					}

					row_data.color = colorPalette.getNextColor(used_colors);
				}
			}
		});

		$('tr.form_row input[name$="[color]"]', this._$thresholds_table).each((i, colorpicker) => {
			$(colorpicker).colorpicker({appendTo: $(colorpicker).closest('.input-color-picker')});
		});

		this._$thresholds_table
			.on('afteradd.dynamicRows', e => {
				const $colorpicker = $('tr.form_row:last input[name$="[color]"]', e.target);

				$colorpicker.colorpicker({appendTo: $colorpicker.closest('.input-color-picker')});

				this._update();
			})
			.on('afterremove.dynamicRows', () => this._update());

		this._$widget_form.on('process.form', (e, overlay) => {
			this.handleFormSubmit(e, overlay);
		});

		// Initialize form elements accessibility.
		this._update();

		this._$widget_form[0].style.display = '';
		this._$widget_form[0].querySelector('[name="name"]').focus();
	}

	_update() {
		const display_as_is = ($('[name="display"]:checked').val() == <?= CWidgetFieldColumnsList::DISPLAY_AS_IS ?>);
		const history_data_trends = ($('[name="history"]:checked').val() ==
			<?= CWidgetFieldColumnsList::HISTORY_DATA_TRENDS ?>);
		const data_item_value = ($('[name="data"]').val() == <?= CWidgetFieldColumnsList::DATA_ITEM_VALUE ?>);
		const data_text = ($('[name="data"]').val() == <?= CWidgetFieldColumnsList::DATA_TEXT ?>);
		const aggregate_function_avg = $('[name="aggregate_function"]').val() == <?= AGGREGATE_AVG ?>;
		const aggregate_function_min = $('[name="aggregate_function"]').val() == <?= AGGREGATE_MIN ?>;
		const aggregate_function_max = $('[name="aggregate_function"]').val() == <?= AGGREGATE_MAX ?>;
		const aggregate_function_sum = $('[name="aggregate_function"]').val() == <?= AGGREGATE_SUM ?>;
		const aggregate_options = document.getElementById('aggregate_function');
		const override_fields = document.querySelectorAll('.override-time');

		$('#item', this._$widget_form).multiSelect(data_item_value ? 'enable' : 'disable');
		$('[name="aggregate_function"]', this._$widget_form).attr('disabled', !data_item_value);
		$('[name="display"],[name="history"]', this._$widget_form).attr('disabled', !data_item_value);
		$('[name="text"]', this._$widget_form).attr('disabled', !data_text);
		$('[name="min"],[name="max"]', this._$widget_form).attr('disabled', display_as_is || !data_item_value);
		$('[name="decimal_places"]', this._$widget_form).attr('disabled', !data_item_value);
		this._$thresholds_table.toggleClass('disabled', !data_item_value);

		// Toggle warning icons for non-numeric items settings.
		if (data_item_value) {
			document.getElementById('tophosts-column-aggregate-function-warning').style.display = aggregate_function_avg
				|| aggregate_function_min || aggregate_function_max || aggregate_function_sum ? '' : 'none';
			document.getElementById('tophosts-column-display-warning').style.display = display_as_is ? 'none' : '';
			document.getElementById('tophosts-column-history-data-warning').style.display = history_data_trends
				? ''
				: 'none';
		}

		// Displays or hides override time period fields based on 'Data' value change.
		document.querySelector('[name="data"]').onchange = () => (
			data_item_value
				? aggregate_options.dispatchEvent(new Event('change'))
				: override_fields.forEach(element => element.style.display = 'none')
		);

		// Toggle visibility of disabled form elements.
		$('.form-grid > label', this._$widget_form).each((i, elm) => {
			const except_fields = 'override-time';
			const form_field = $(elm).next();

			if (!form_field.hasClass(except_fields)) {
				const is_visible = (form_field.find(':disabled,.disabled').length == 0);

				$(elm).toggle(is_visible);
				form_field.toggle(is_visible);
			}
		});

		const time_period_fields = [
			'#time_from',
			'#time_from_calendar',
			'#time_to',
			'#time_to_calendar'
		];

		if (this._aggregate_function.value == <?= AGGREGATE_NONE ?>) {
			time_period_fields.push('#item_time');

			for (const element of document.querySelectorAll(time_period_fields)) {
				element.disabled = true;
			}
		}
		else {
			document.querySelector('#item_time').disabled = false;

			for (const element of document.querySelectorAll(time_period_fields)) {
				element.disabled = !this._item_time.checked;
			}

			['#time_from', '#time_to'].forEach(field => {
				const element = document.querySelector(field);

				if (element) {
					element.addEventListener('focusout', () => {
						element.value = element.value.trim();
					});
				}
			});
		}

		aggregate_options.addEventListener('change', function() {
			for (const element of override_fields) {
				element.style.display = (Number(this.value) === <?= AGGREGATE_NONE ?>) ? 'none' : 'block';
			}
		});
	}

	handleFormSubmit(e, overlay) {
		fetch(new Curl(e.target.getAttribute('action')).getUrl(), {
			method: 'POST',
			body: new URLSearchParams(new FormData(e.target))
		})
			.then(response => response.json())
			.then(response => {
				if ('error' in response) {
					throw {error: response.error};
				}

				overlayDialogueDestroy(overlay.dialogueid);

				overlay.$dialogue[0].dispatchEvent(new CustomEvent('dialogue.submit', {detail: response}));
			})
			.catch((exception) => {
				const form = this._$widget_form[0];

				for (const element of form.parentNode.children) {
					if (element.matches('.msg-good, .msg-bad, .msg-warning')) {
						element.parentNode.removeChild(element);
					}
				}

				let title, messages;

				if (typeof exception === 'object' && 'error' in exception) {
					title = exception.error.title;
					messages = exception.error.messages;
				}
				else {
					messages = [<?= json_encode(_('Unexpected server error.')) ?>];
				}

				const message_box = makeMessageBox('bad', messages, title)[0];

				form.parentNode.insertBefore(message_box, form);
			})
			.finally(() => {
				overlay.unsetLoading();
			});
	}
};

<?php
/*
** Zabbix
** Copyright (C) 2001-2022 Zabbix SIA
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

require_once dirname(__FILE__).'/behaviors/CMessageBehavior.php';

/**
 * Base class for Tags function tests.
 *
 * @backup config
 */
class testFormGeographicalMaps extends CWebTest {

	private $sql = 'SELECT * FROM config';

	/**
	 * Attach MessageBehavior to the test.
	 *
	 * @return array
	 */
	public function getBehaviors() {
		return [CMessageBehavior::class];
	}

	public function getLayoutData() {
		return [
			[
				[
					'Tile provider' => 'OpenStreetMap Mapnik',
					'Tile URL' => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
					'Attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
					'Max zoom level' => '19'
				]
			],
			[
				[
					'Tile provider' => 'OpenTopoMap',
					'Tile URL' => 'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png',
					'Attribution' => 'Map data: &copy; <a href="https://www.openstreetmap.org/copyright">'.
							'OpenStreetMap</a> contributors, <a href="http://viewfinderpanoramas.org">SRTM</a> | '.
							'Map style: &copy; <a href="https://opentopomap.org">OpenTopoMap</a> '.
							'(<a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a>)',
					'Max zoom level' => '17'
				]
			],
			[
				[
					'Tile provider' => 'Stamen Toner Lite',
					'Tile URL' => 'https://stamen-tiles-{s}.a.ssl.fastly.net/toner-lite/{z}/{x}/{y}{r}.png',
					'Attribution' => 'Map tiles by <a href="http://stamen.com">Stamen Design</a>, <a href='.
							'"http://creativecommons.org/licenses/by/3.0">CC BY 3.0</a> &mdash; Map data &copy; '.
							'<a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
					'Max zoom level' => '20'
				]
			],
			[
				[
					'Tile provider' => 'Stamen Terrain',
					'Tile URL' => 'https://stamen-tiles-{s}.a.ssl.fastly.net/terrain/{z}/{x}/{y}{r}.png',
					'Attribution' => 'Map tiles by <a href="http://stamen.com">Stamen Design</a>, <a href='.
							'"http://creativecommons.org/licenses/by/3.0">CC BY 3.0</a> &mdash; Map data &copy; '.
							'<a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
					'Max zoom level' => '18'
				]
			],
			[
				[
					'Tile provider' => 'USGS US Topo',
					'Tile URL' => 'https://basemap.nationalmap.gov/arcgis/rest/services/USGSTopo/MapServer/tile/{z}/{y}/{x}',
					'Attribution' => 'Tiles courtesy of the <a href="https://usgs.gov/">U.S. Geological Survey</a>',
					'Max zoom level' => '20'
				]
			],
			[
				[
					'Tile provider' => 'USGS US Imagery',
					'Tile URL' => 'https://basemap.nationalmap.gov/arcgis/rest/services/USGSImageryOnly/MapServer/tile/{z}/{y}/{x}',
					'Attribution' => 'Tiles courtesy of the <a href="https://usgs.gov/">U.S. Geological Survey</a>',
					'Max zoom level' => '20'
				]
			],
			[
				[
					'Tile provider' => 'Other',
					'Tile URL' => '',
					'Attribution' => '',
					'Max zoom level' => '0'
				]
			]
		];
	}

	/**
	 * @dataProvider getLayoutData
	 */
	public function testFormGeographicalMaps_Layout($data) {
		$this->page->login()->open('zabbix.php?action=geomaps.edit');
		$form = $this->query('id:geomaps-form')->waitUntilReady()->asForm()->one();

		// Check dropdown options presence.
		$this->assertEquals(['OpenStreetMap Mapnik', 'OpenTopoMap', 'Stamen Toner Lite', 'Stamen Terrain', 'USGS US Topo',
				'USGS US Imagery', 'Other'], $form->getField('Tile provider')->asZDropdown()->getOptions()->asText()
		);

		// Open hintboxes and compare text.
		$hintboxes = [
			[
				'Tile URL' => "The URL template is used to load and display the tile layer on geographical maps.".
						"\n".
						"\nExample: https://{s}.example.com/{z}/{x}/{y}{r}.png".
						"\n".
						"\nThe following placeholders are supported:".
						"\n{s} represents one of the available subdomains;".
						"\n{z} represents zoom level parameter in the URL;".
						"\n{x} and {y} represent tile coordinates;".
						"\n{r} can be used to add \"@2x\" to the URL to load retina tiles."
			],
			[
				'Attribution' => 'Tile provider attribution data displayed in a small text box on the map.'
			],
			[
				'Max zoom level' => 'Maximum zoom level of the map.'
			]
		];

		foreach ($hintboxes as $field => $text) {
			$form->query('xpath://label[text()='.CXPathHelper::escapeQuotes($field).']//span')->one()->click();
			$hint = $form->query('xpath://div[@class="overlay-dialogue"]')->waitUntilPresent();
			$this->assertEquals($text, $hint->one()->getText());
			$hint->asOverlayDialog()->one()->close();
		}

		$form->fill(['Tile provider' => $data['Tile provider']]);
		$form->checkValue($data);

		// Check Service tab fields' maxlengths.
		$limits = [
			'Tile URL' => 1024,
			'Attribution' => 1024,
			'Max zoom level' => 10
		];
		foreach ($limits as $field => $max_length) {
			$this->assertEquals($max_length, $form->getField($field)->getAttribute('maxlength'));
		}

		// Check empty string in "Other" case when selected right after another provider.
		if ($data['Tile provider'] === 'Other') {
			$form->fill(['Tile provider' => 'OpenStreetMap Mapnik']);
			$form->fill(['Tile provider' => 'Other']);
			$form->checkValue(['Max zoom level' => '']);;
		}
		else {
			// Take all fields except dropdown and check they are disabled.
			array_shift($data);
			$fields = array_keys($data);
			foreach ($fields as $field) {
				$this->assertFalse($form->getField($field)->isEnabled());
			}
		}
	}

	public function getFormData() {
		return [
			[
				[
					'fields' => [
						'Tile provider' => 'OpenStreetMap Mapnik'
					],
					'db' => 'OpenStreetMap.Mapnik'
				]
			],
			[
				[
					'fields' => [
						'Tile provider' => 'OpenTopoMap'
					],
					'db' => 'OpenTopoMap'
				]
			],
			[
				[
					'fields' => [
						'Tile provider' => 'Stamen Toner Lite'
					],
					'db' => 'Stamen.TonerLite'
				]
			],
			[
				[
					'fields' => [
						'Tile provider' => 'Stamen Terrain'
					],
					'db' => 'Stamen.Terrain'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Tile provider' => 'Other'
					],
					'error' => [
						'Incorrect value for field "geomaps_tile_url": cannot be empty.',
						'Incorrect value for field "geomaps_max_zoom": cannot be empty.'
					]
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Tile provider' => 'Other',
						'Tile URL' => '123',
						'Max zoom level' => ''
					],
					'error' => 'Incorrect value for field "geomaps_max_zoom": cannot be empty.'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Tile provider' => 'Other',
						'Tile URL' => 'bbb',
						'Max zoom level' => '0'
					],
					'error' => 'Incorrect value for field "geomaps_max_zoom": value must be no less than "1".'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Tile provider' => 'Other',
						'Tile URL' => 'bbb',
						'Max zoom level' => '31'
					],
					'error' => 'Incorrect value for field "geomaps_max_zoom": value must be no greater than "30".'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Tile provider' => 'Other',
						'Tile URL' => 'bbb',
						'Max zoom level' => 'aa'
					],
					'error' => 'Incorrect value "aa" for "geomaps_max_zoom" field.'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Tile provider' => 'Other',
						'Tile URL' => 'bbb',
						'Max zoom level' => '!%:'
					],
					'error' => 'Incorrect value "!%:" for "geomaps_max_zoom" field.'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Tile provider' => 'Other',
						'Tile URL' => 'bbb',
						'Max zoom level' => '-1'
					],
					'error' => 'Incorrect value for field "geomaps_max_zoom": value must be no less than "1".'
				]
			],
			[
				[
					'fields' => [
						'Tile provider' => 'Other',
						'Tile URL' => 'bbb',
						'Max zoom level' => '29'
					]
				]
			],
			[
				[
					'fields' => [
						'Tile provider' => 'Other',
						'Tile URL' => 'bbb',
						'Attribution' => 'aaa',
						'Max zoom level' => '20'
					]
				]
			],
			[
				[
					'fields' => [
						'Tile provider' => 'Other',
						'Tile URL' => '111',
						'Attribution' => '222',
						'Max zoom level' => '13'
					]
				]
			],
			[
				[
					'fields' => [
						'Tile provider' => 'Other',
						'Tile URL' => 'йцу',
						'Attribution' => 'кен',
						'Max zoom level' => '13'
					]
				]
			],
			[
				[
					'fields' => [
						'Tile provider' => 'Other',
						'Tile URL' => 'https://tileserver.memomaps.de/tilegen/{z}/{x}/{y}.png',
						'Attribution' => 'Map <a href="https://memomaps.de/">memomaps.de</a> '.
								'<a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, '.
								'map data &copy; <a href="https://www.openstreetmap.org/copyright">'.
								'OpenStreetMap</a> contributors',
						'Max zoom level' => '13'
					]
				]
			]
		];
	}

	/**
	 * @dataProvider getFormData
	 */
	public function testFormGeographicalMaps_Form($data) {
		if (CTestArrayHelper::get($data, 'expected', TEST_GOOD) === TEST_BAD) {
			$old_hash = CDBHelper::getHash($this->sql);
		}

		$this->page->login()->open('zabbix.php?action=geomaps.edit');
		$form = $this->query('id:geomaps-form')->waitUntilReady()->asForm()->one();
		$form->fill($data['fields']);
		$form->submit();
		$this->page->waitUntilReady();

		if (CTestArrayHelper::get($data, 'expected', TEST_GOOD) === TEST_BAD) {
			$this->assertMessage(TEST_BAD, 'Cannot update configuration', $data['error']);

			// Check that DB hash is not changed.
			$this->assertEquals($old_hash, CDBHelper::getHash($this->sql));
		}
		else {
			$this->assertMessage(TEST_GOOD, 'Configuration updated');

			// Check frontend form.
			$this->page->login()->open('zabbix.php?action=geomaps.edit');
			$form->invalidate();
			$form->checkValue($data['fields']);

			// Check db values.
			if ($data['fields']['Tile provider'] === 'Other') {
				$expected_db = [[
					'geomaps_tile_provider' => '',
					'geomaps_tile_url' => $data['fields']['Tile URL'],
					'geomaps_attribution' => CTestArrayHelper::get($data['fields'], 'Attribution', ''),
					'geomaps_max_zoom' => $data['fields']['Max zoom level']
				]];
			}
			else {
				$expected_db = [[
					'geomaps_tile_provider' => $data['db'],
					'geomaps_tile_url' => '',
					'geomaps_attribution' => '',
					'geomaps_max_zoom' => '0'
				]];
			}

			$this->assertEquals($expected_db, CDBHelper::getAll('SELECT geomaps_tile_provider, geomaps_tile_url, '.
					'geomaps_attribution, geomaps_max_zoom FROM config'
				)
			);
		}
	}
}

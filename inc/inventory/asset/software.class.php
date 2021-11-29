<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace Glpi\Inventory\Asset;

use DBmysqlIterator;
use Dropdown;
use Entity;
use Glpi\Inventory\Conf;
use QueryParam;
use RuleDictionnarySoftwareCollection;
use Software as GSoftware;
use SoftwareVersion;
use Toolbox;

class Software extends InventoryAsset
{
   const SEPARATOR = '$$$$';

   private $softwares = [];
   private $versions = [];
   private $current_versions = [];
   private $entities_id_software;

   /** @var array */
   protected $extra_data = [
      OperatingSystem::class => null
   ];

   public function prepare() :array {
      $mapping = [
         'publisher'       => 'manufacturers_id',
         'comments'        => 'comment',
         'install_date'     => 'date_install',
         'system_category' => '_system_category'
      ];

      //Dictionary for software
      $rulecollection = new RuleDictionnarySoftwareCollection();

      //Get the default entity for software, as defined in entity configuration
      $entities_id = $this->entities_id;
      $entities_id_software = Entity::getUsedConfig(
         'entities_id_software',
         $entities_id
      );

      //By default a software is not recursive
      $is_recursive = 0;

      //Configuration says that software can be created in the computer's entity
      if ($entities_id_software < 0) {
         //inherit from main asset's entity
         $entities_id_software = $entities_id;
      } else if ($entities_id_software != $entities_id) {
         //Software created in a different entity than main asset one
         $is_recursive = 1;
      }
      $this->entities_id_software = $entities_id_software;

      //Count the number of software dictionary rules
      $count_rules = \countElementsInTable("glpi_rules",
         [
            'sub_type'  => 'RuleDictionnarySoftware',
            'is_active' => 1,
         ]
      );

      $with_manufacturer = [];
      $without_manufacturer = [];
      $mids = []; //keep trace of handled ids

      foreach ($this->data as $k => &$val) {
         foreach ($mapping as $origin => $dest) {
            if (property_exists($val, $origin)) {
               $val->$dest = $val->$origin;
            }
         }

         if (!property_exists($val, 'name')
            || ($val->name == ''
               || str_starts_with(Toolbox::slugify($val->name), 'nok_')
            )
         ) {
            if (property_exists($val, 'guid') && $val->guid != '') {
               $val->name = $val->guid;
            }
         }

         //If the software name exists and is defined
         if (property_exists($val, 'name') && $val->name != '') {
            $val->name = trim(preg_replace('/\s+/', ' ', $val->name));

            $res_rule       = [];

            //Only play rules engine if there's at least one rule
            //for software dictionary
            if ($count_rules > 0) {
               $rule_input = [
                  "name"               => $val->name,
                  "manufacturer"       => $val->manufacturers_id ?? 0,
                  "old_version"        => $val->version ?? null,
                  "entities_id"        => $entities_id_software,
                  "_system_category"   => $val->_system_category ?? null
               ];
               $res_rule = $rulecollection->processAllRules($rule_input);
            }

            if (isset($res_rule['_ignore_import']) && $res_rule['_ignore_import'] == 1) {
               //ignored by rules
               unset($this->data[$k]);
               continue;
            }

            //If the name has been modified by the rules engine
            if (isset($res_rule["name"])) {
               $val->name = $res_rule["name"];
            }
            //If the version has been modified by the rules engine
            if (isset($res_rule["version"])) {
               $val->version = $res_rule["version"];
            }

            //If the manufacturer has been modified or set by the rules engine
            if (isset($res_rule["manufacturer"])) {
               $val->manufacturers_id = Dropdown::import(
                  'Manufacturer',
                  ['name' => $res_rule['manufacturer']]
               );
            } else if (property_exists($val, 'manufacturers_id')
               && $val->manufacturers_id != ''
               && $val->manufacturers_id != '0'
            ) {
               if (!isset($mids[$val->manufacturers_id])) {
                  $new_value = Dropdown::importExternal(
                     'Manufacturer',
                     addslashes($val->manufacturers_id),
                     $this->entities_id
                  );
                  $mids[$val->manufacturers_id] = $new_value;
               }
               $val->manufacturers_id = $mids[$val->manufacturers_id];
            } else {
               $val->manufacturers_id = 0;
            }

            //The rules engine has modified the entity
            //(meaning that the software is recursive and defined
            //in an upper entity)
            if (isset($res_rule['new_entities_id'])) {
               $val->entities_id = $res_rule['new_entities_id'];
               $is_recursive    = 1;
            }

            //Entity is not set, get from configuration
            if (!property_exists($val, 'entities_id') || $val->entities_id == '') {
               $val->entities_id = $entities_id_software;
            }
            //version is undefined, set it to blank
            if (!property_exists($val, 'version')) {
               $val->version = '';
            }
            //arch is undefined, set it to blankk
            if (!property_exists($val, 'arch')) {
               $val->arch = '';
            }

            //not a template, not deleted, ...
            $val->is_template_item = 0;
            $val->is_deleted_item = 0;
            $val->operatingsystems_id = 0;

            //Store recursivity
            $val->is_recursive = $is_recursive;

            //String with the manufacturer
            $comp_key = $this->getSimpleCompareKey($val);

            if ($val->manufacturers_id == 0) {
               //soft w/o manufacturer. Keep it to see later if one exists with manufacturer
               $without_manufacturer[$comp_key] = $k;
            } else {
               $with_manufacturer[$comp_key] = true;
            }
         }

         //ensure all columns are present
         if (!property_exists($val, 'comment')) {
            $val->comment = null;
         }
      }

      //NOTE: A same software may have a manufacturer or not. Keep the one with manufacturer.
      foreach ($without_manufacturer as $comp_key => $data_index) {
         if (isset($with_manufacturer[$comp_key])) {
            //same software do exists with a manufacturer, remove current duplicate
            unset($this->data[$data_index]);
         }
      }

      return $this->data;
   }

   public function handle() {
      global $DB;

      //Get configured entity
      $entities_id  = $this->entities_id_software;
      //Get operating system
      $operatingsystems_id = 0;

      if (isset($this->extra_data[OperatingSystem::class])) {
         $os = $this->extra_data[OperatingSystem::class];
         $operatingsystems_id = $os->getId();
      }

      $db_software = [];

      //Load existing software versions from db. Grab required fields
      //to build comparison key @see getFullCompareKey
      $iterator = $DB->request([
         'SELECT' => [
            'glpi_items_softwareversions.id as sid',
            'glpi_softwares.name',
            'glpi_softwareversions.name AS version',
            'glpi_softwareversions.arch',
            'glpi_softwares.manufacturers_id',
            'glpi_softwareversions.entities_id',
            'glpi_softwareversions.operatingsystems_id',
         ],
         'FROM'      => 'glpi_items_softwareversions',
         'LEFT JOIN' => [
            'glpi_softwareversions' => [
               'ON'  => [
                  'glpi_items_softwareversions' => 'softwareversions_id',
                  'glpi_softwareversions'       => 'id'
               ]
            ],
            'glpi_softwares'        => [
               'ON'  => [
                  'glpi_softwareversions' => 'softwares_id',
                  'glpi_softwares'        => 'id'
               ]
            ]
         ],
         'WHERE'     => [
            'glpi_items_softwareversions.items_id' => $this->item->fields['id'],
            'glpi_items_softwareversions.itemtype'    => $this->item->getType(),
            'glpi_items_softwareversions.is_dynamic'  => 1
         ]
      ]);

      foreach ($iterator as $data) {
         $softid = $data['sid'];
         unset($data['sid']);
         $db_software[$this->getFullCompareKey((object)$data)] = $softid;
      }

      //check for existing links
      $count_import = count($this->data);
      foreach ($this->data as $k => &$val) {
         //operating system id is not known before handle(); set it in value
         $val->operatingsystems_id = $operatingsystems_id;
         $key = $this->getFullCompareKey($val);
         $dedup_vkey = $key . $this->getVersionKey($val, 0);
         if (isset($db_software[$key])) {
            //link already exists in database, drop it
            unset($this->data[$k]);
            unset($db_software[$key]);
            $this->current_versions[$dedup_vkey] = true;
         } else {
            if (isset($this->current_versions[$dedup_vkey])) {
               unset($this->data[$k]);
            } else {
               $this->current_versions[$dedup_vkey] = true;
            }
         }
      }

      //not found version means soft has been removed or updated, drop it
      if (count($db_software) > 0 && (!$this->main_asset || !$this->main_asset->isPartial() || $this->main_asset->isPartial() && $count_import)) {
         $DB->delete(
            'glpi_items_softwareversions', [
               'id' => $db_software
            ]
         );
      }

      if (!count($this->data)) {
         //nothing to do!
         return;
      }

      try {
         $this->populateSoftware();
         $this->storeSoftware();
         $this->populateVersions();
         $this->storeVersions();
         $this->storeAssetLink();
      } catch (\Exception $e) {
         throw $e;
      }
   }

   public function getOsForKey($val) {
      if (!$this->main_asset || !$this->main_asset->isPartial()) {
         return $val->operatingsystems_id;
      } else {
         return '%';
      }
   }

   /**
    * Get software comparison key
    *
    * @param string  $name             Software name
    * @param integer $manufacturers_id Manufacturers id
    *
    * @return string
    */
   protected function getSoftwareKey($name, $manufacturers_id): string {
      return $this->getCompareKey([Toolbox::slugify($name), $manufacturers_id]);
   }

   /**
    * Get software version comparison key
    *
    * @param stdClass $val          Version name
    * @param integer   $softwares_id Software id
    *
    * @return string
    */
   protected function getVersionKey($val, $softwares_id): string {
      return $this->getCompareKey([
         strtolower($val->version),
         $softwares_id,
         strtolower($val->arch ?? '%'),
         $this->getOsForKey($val)
      ]);
   }

   /**
    * Get full comparison keys for a software (including manufacturer and operating system)
    *
    * @param \stdClass $val Object values
    *
    * @return string
    */
   protected function getFullCompareKey(\stdClass $val): string {
      return $this->getCompareKey([
         Toolbox::slugify($val->name),
         strtolower($val->version),
         strtolower($val->arch ?? ''),
         $val->manufacturers_id,
         $val->entities_id,
         $this->getOsForKey($val)
      ]);
   }

   /**
    * Get full comparison keys for a software (including operating system but not manufacturer)
    *
    * @param \stdClass $val Object values
    *
    * @return string
    */
   protected function getSimpleCompareKey(\stdClass $val): string {
      return $this->getCompareKey([
         Toolbox::slugify($val->name),
         strtolower($val->version),
         strtolower($val->arch ?? ''),
         $val->entities_id,
         $this->getOsForKey($val)
      ]);
   }

   /**
    * Build comparison key from values
    *
    * @param array $parts Values parts
    *
    * @return string
    */
   protected function getCompareKey(array $parts): string {
      return implode(
         self::SEPARATOR,
         $parts
      );
   }

   /**
    * Populates software list
    *
    * @return  void
    */
   private function populateSoftware() {
      global $DB;
      $entities_id  = $this->entities_id_software;

      $criteria = [
         'SELECT' => ['id', 'name', 'manufacturers_id'],
         'FROM'   => \Software::getTable(),
         'WHERE'  => [
            'entities_id'        => $entities_id,
            'name'               => new QueryParam(),
            'manufacturers_id'   => new QueryParam()
         ]
      ];

      $it = new DBmysqlIterator(null);
      $it->buildQuery($criteria);
      $query = $it->getSql();
      $stmt = $DB->prepare($query);

      foreach ($this->data as $val) {
         $key = $this->getSoftwareKey(
            $val->name,
            $val->manufacturers_id
         );

         if (isset($this->softwares[$key])) {
            //already loaded
            continue;
         }

         $stmt->bind_param(
            'ss',
            $val->name,
            $val->manufacturers_id
         );
         $DB->executeStatement($stmt);
         $results = $stmt->get_result();

         while ($row = $results->fetch_object()) {
            $this->softwares[$key] = $row->id;
         }
      }
      $stmt->close();
   }

   /**
    * Populates software versions list
    *
    * @return  void
    */
   private function populateVersions() {
      global $DB;
      $entities_id  = $this->entities_id_software;

      if (!count($this->softwares)) {
         //no existing software, no existing versions :)
         return;
      }

      $criteria = [
         'SELECT' => ['id', 'name', 'arch', 'softwares_id', 'operatingsystems_id'],
         'FROM'   => \SoftwareVersion::getTable(),
         'WHERE'  => [
            'entities_id'           => $entities_id,
            'name'                  => new QueryParam(),
            'arch'                  => new QueryParam(),
            'softwares_id'          => new QueryParam(),
            'operatingsystems_id'   => new QueryParam()
         ]
      ];

      $it = new DBmysqlIterator(null);
      $it->buildQuery($criteria);
      $query = $it->getSql();
      $stmt = $DB->prepare($query);

      foreach ($this->data as $val) {
         $skey = $this->getSoftwareKey(
            $val->name,
            $val->manufacturers_id
         );

         if (!isset($this->softwares[$skey])) {
            continue;
         }

         $softwares_id = $this->softwares[$skey];

         $key = $this->getVersionKey(
            $val,
            $softwares_id
         );

         if (isset($this->versions[$key])) {
            //already loaded
            continue;
         }

         $osid = $this->getOsForKey($val);
         $arch = $val->arch ?? '';
         $stmt->bind_param(
            'ssss',
            $val->version,
            $arch,
            $softwares_id,
            $osid
         );
         $DB->executeStatement($stmt);
         $results = $stmt->get_result();

         while ($row = $results->fetch_object()) {
            $this->versions[$key] = $row->id;
         }
      }
      $stmt->close();
   }

   /**
    * Store software
    *
    * @return void
    */
   private function storeSoftware() {
      global $DB;

      $software = new GSoftware();
      $soft_fields = $DB->listFields($software->getTable());
      $stmt = $stmt_types = null;

      foreach ($this->data as $val) {
         $skey = $this->getSoftwareKey($val->name, $val->manufacturers_id);
         if (!isset($this->softwares[$skey])) {
            $stmt_columns = $this->cleanInputToPrepare((array)$val, $soft_fields);

            if ($stmt === null) {
               $stmt_types = str_repeat('s', count($stmt_columns));
               $reference = array_fill_keys(
                  array_keys($stmt_columns),
                  new QueryParam()
               );
               $insert_query = $DB->buildInsert(
                  $software->getTable(),
                  $reference
               );
               $stmt = $DB->prepare($insert_query);
            }

            $software->handleCategoryRules($stmt_columns);
            $stmt_values = array_values($stmt_columns);
            $stmt->bind_param($stmt_types, ...$stmt_values);
            $DB->executeStatement($stmt);
            $softwares_id = $DB->insertId();
            $this->softwares[$skey] = $softwares_id;
         }
      }

      if ($stmt) {
         $stmt->close();
      }
   }

   /**
    * Store software versions
    *
    * @return void
    */
   private function storeVersions() {
      global $DB;

      $version = new SoftwareVersion();
      $version_fields = $DB->listFields($version->getTable());
      $stmt = $stmt_types = null;

      foreach ($this->data as $val) {
         $skey = $this->getSoftwareKey($val->name, $val->manufacturers_id);
         $softwares_id = $this->softwares[$skey];

         $input = (array)$val;
         $input['softwares_id']  = $softwares_id;
         $input['_no_history']   = true;
         $input['name']          = $val->version;

         $vkey = $this->getVersionKey(
            $val,
            $softwares_id
         );

         if (!isset($this->versions[$vkey])) {
            $version_name = $val->version;
            $stmt_columns = $this->cleanInputToPrepare((array)$val, $version_fields);
            $stmt_columns['name'] = $version_name;
            $stmt_columns['softwares_id'] = $softwares_id;
            if ($stmt === null) {
               $stmt_types = str_repeat('s', count($stmt_columns));
               $reference = array_fill_keys(
                  array_keys($stmt_columns),
                  new QueryParam()
               );
               $insert_query = $DB->buildInsert(
                  $version->getTable(),
                  $reference
               );
               $stmt = $DB->prepare($insert_query);
            }

            $stmt_values = array_values($stmt_columns);
            $stmt->bind_param($stmt_types, ...$stmt_values);
            $DB->executeStatement($stmt);
            $versions_id = $DB->insertId();
            $this->versions[$vkey] = $versions_id;
         }
      }

      if ($stmt) {
         $stmt->close();
      }
   }

   /**
    * Clean input data
    *
    * @param array $input        Input data
    * @param array $known_fields Table fields
    *
    * @return array
    */
   private function cleanInputToPrepare(array $input, array $known_fields) {
      foreach (array_keys($input) as $column) {
         if (!isset($known_fields[$column])) {
            unset($input[$column]);
         }
      }
      ksort($input);
      return $input;
   }

   /**
    * Store asset link to software
    *
    * @return void
    */
   private function storeAssetLink() {
      global $DB;

      if (!count($this->data)) {
         return;
      }

      $stmt = null;
      foreach ($this->data as $val) {
         $skey = $this->getSoftwareKey($val->name, $val->manufacturers_id);
         $softwares_id = $this->softwares[$skey];

         $vkey = $this->getVersionKey(
            $val,
            $softwares_id,
         );
         $versions_id = $this->versions[$vkey];

         if ($stmt === null) {
            $insert_query = $DB->buildInsert(
               'glpi_items_softwareversions', [
                  'itemtype'              => $this->item->getType(),
                  'items_id'              => $this->item->fields['id'],
                  'softwareversions_id'   => new QueryParam(),
                  'is_dynamic'            => new QueryParam(),
                  'entities_id'           => new QueryParam(),
                  'date_install'          => new QueryParam()
               ]
            );
            $stmt = $DB->prepare($insert_query);
         }

         $input = [
            'softwareversions_id'   => $versions_id,
            'is_dynamic'            => 1,
            'entities_id'           => $this->item->fields['entities_id'],
            'date_install'          => $val->date_install ?? null
         ];

         $stmt->bind_param(
            'ssss',
            $input['softwareversions_id'],
            $input['is_dynamic'],
            $input['entities_id'],
            $input['date_install']
         );
         $DB->executeStatement($stmt);
      }
   }

   public function checkConf(Conf $conf): bool {
      return $conf->import_software == 1;
   }
}

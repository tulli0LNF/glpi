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

use Glpi\Application\View\TemplateRenderer;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Not managed devices from inventory
 */
class Unmanaged extends CommonDBTM {

   // From CommonDBTM
   public $dohistory                   = true;
   static $rightname                   = 'config';

   static function getTypeName($nb = 0) {
      return _n('Unmanaged device', 'Unmanaged devices', $nb);
   }

   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong)
         ->addStandardTab('NetworkPort', $ong, $options)
         ->addStandardTab('Log', $ong, $options);
      return $ong;
   }


   /**
    * Print the unmanagemed form
    *
    * @param $ID integer ID of the item
    * @param $options array
    *     - target filename : where to go when done.
    *     - withtemplate boolean : template or basic item
    *
    * @return boolean item found
   **/
   function showForm($ID, array $options = []) {
      $this->initForm($ID, $options);
      TemplateRenderer::getInstance()->display('pages/assets/unmanaged.html.twig', [
         'item'   => $this,
         'params' => $options,
      ]);
      return true;
   }


   function rawSearchOptions() {
      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'        => '2',
         'table'     => $this->getTable(),
         'field'     => 'id',
         'name'      => __('ID'),
      ];

      $tab[] = [
         'id'        => '3',
         'table'     => 'glpi_locations',
         'field'     => 'name',
         'linkfield' => 'locations_id',
         'name'      => Location::getTypeName(1),
         'datatype'  => 'dropdown',
      ];

      $tab[] = [
         'id'           => '4',
         'table'        => $this->getTable(),
         'field'        => 'serial',
         'name'         => __('Serial Number'),
      ];

      $tab[] = [
         'id'           => '5',
         'table'        => $this->getTable(),
         'field'        => 'otherserial',
         'name'         => __('Inventory number'),
      ];

      $tab[] = [
         'id'           => '6',
         'table'        => $this->getTable(),
         'field'        => 'contact',
         'name'         => Contact::getTypeName(1),
      ];

      $tab[] = [
         'id'        => '7',
         'table'     => $this->getTable(),
         'field'     => 'hub',
         'name'      => __('Network hub'),
         'datatype'  => 'bool',
      ];

      $tab[] = [
         'id'        => '8',
         'table'     => 'glpi_entities',
         'field'     => 'completename',
         'linkfield' => 'entities_id',
         'name'      => Entity::getTypeName(1),
         'datatype'  => 'dropdown',
      ];

      $tab[] = [
         'id'        => '9',
         'table'     => 'glpi_domains',
         'field'     => 'name',
         'linkfield' => 'domains_id',
         'name'      => Domain::getTypeName(1),
         'datatype'  => 'dropdown',
      ];

      $tab[] = [
         'id'        => '10',
         'table'     => $this->getTable(),
         'field'     => 'comment',
         'name'      => __('Comments'),
         'datatype'  => 'text',
      ];

      $tab[] = [
         'id'        => '13',
         'table'     => $this->getTable(),
         'field'     => 'itemtype',
         'name'      => _n('Type', 'Types', 1),
         'datatype'  => 'dropdown',
      ];

      $tab[] = [
         'id'        => '14',
         'table'     => $this->getTable(),
         'field'     => 'date_mod',
         'name'      => __('Last update'),
         'datatype'  => 'datetime',
      ];

      $tab[] = [
         'id'        => '15',
         'table'     => $this->getTable(),
         'field'     => 'sysdescr',
         'name'      => __('Sysdescr'),
         'datatype'  => 'text',
      ];

      $tab[] = [
         'id'           => '18',
         'table'        => $this->getTable(),
         'field'        => 'ip',
         'name'         => __('IP'),
      ];

      return $tab;
   }

   function cleanDBonPurge() {

      $this->deleteChildrenAndRelationsFromDb(
         [
            NetworkPort::class
         ]
      );
   }

   static function getIcon() {
      return "ti ti-question-mark";
   }

   function getSpecificMassiveActions($checkitem = null) {
      $actions = [];
      if (self::canUpdate()) {
         $actions['Unmanaged'.MassiveAction::CLASS_ACTION_SEPARATOR.'convert']    = __('Convert');
      }
      return $actions;
   }

   static function getMassiveActionsForItemtype(
      array &$actions,
      $itemtype,
      $is_deleted = 0,
      CommonDBTM $checkitem = null
   ) {
      if (self::canUpdate()) {
         $actions['Unmanaged'.MassiveAction::CLASS_ACTION_SEPARATOR.'convert']    = __('Convert');
      }
   }

   static function showMassiveActionsSubForm(MassiveAction $ma) {
      global $CFG_GLPI;
      switch ($ma->getAction()) {
         case 'convert':
            echo __('Select an itemtype: ') . ' ';
            Dropdown::showFromArray('itemtype', array_combine($CFG_GLPI['inventory_types'], $CFG_GLPI['inventory_types']));
            break;
      }
      return parent::showMassiveActionsSubForm($ma);
   }

   static function processMassiveActionsForOneItemtype(
      MassiveAction $ma,
      CommonDBTM $item,
      array $ids
   ) {
      global $CFG_GLPI;
      switch ($ma->getAction()) {
         case 'convert':
            $unmanaged = new self();
            foreach ($ids as $id) {
               $itemtype = $CFG_GLPI['inventory_types'][$_POST['itemtype']];
               $unmanaged->convert($id, $itemtype);
               $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
            }
            break;

      }
   }

   /**
    * Convert to a managed asset
    */
   public function convert($items_id, $itemtype) {
      global $DB;

      $this->getFromDB($items_id);
      $netport = new NetworkPort();

      $iterator = $DB->request([
         'SELECT' => ['id'],
         'FROM' => NetworkPort::getTable(),
         'WHERE' => [
            'itemtype' => self::getType(),
            'items_id' => $items_id
         ]
      ]);

      if (!empty($this->fields['itemtype'])) {
         $itemtype = $this->fields['itemtype'];
      }

      $asset = new $itemtype;
      $asset_data = [
         'name'          => $this->fields['name'],
         'entities_id'   => $this->fields['entities_id'],
         'serial'        => $this->fields['serial'],
         'uuid'          => $this->fields['uuid'],
         'is_dynamic'    => 1
      ];
      $assets_id = $asset->add(Toolbox::addslashes_deep($asset_data));

      foreach ($iterator as $row) {
         $row += [
            'items_id' => $assets_id,
            'itemtype' => $itemtype
         ];
         $netport->update(Toolbox::addslashes_deep($row));
      }
      $this->deleteFromDB(1);
   }
}

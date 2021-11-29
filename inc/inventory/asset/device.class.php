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

use CommonDBTM;
use Item_Devices;

abstract class Device extends InventoryAsset
{
   protected $id_class;

   /**
    * Constructor
    *
    * @param CommonDBTM $item    Item instance
    * @param array      $data    Data part
    * @param string     $idclass Item device class
    */
   public function __construct(CommonDBTM $item, array $data = null, $id_class) {
      parent::__construct($item, $data);
      $this->id_class = $id_class;
   }

   /**
    * Get existing entries from database
    *
    * @return array
    */
   protected function getExisting($itemdevicetable, $fk): array {
      global $DB;

      $db_existing = [];

      $iterator = $DB->request([
         'SELECT'    => [
            "$itemdevicetable.$fk",
            "is_dynamic"
         ],
         'FROM'      => $itemdevicetable,
         'WHERE'     => [
            "$itemdevicetable.items_id"     => $this->item->fields['id'],
            "$itemdevicetable.itemtype"     => $this->item->getType()
         ]
      ]);

      foreach ($iterator as $row) {
         $db_existing[$row[$fk]] = $row;
      }

      return $db_existing;
   }

   public function handle() {
      global $DB;

      $devicetypes = Item_Devices::getItemAffinities($this->item->getType());

      $itemdevicetype = $this->id_class;
      if (in_array($this->id_class, $devicetypes)) {
         $value = $this->data;
         $itemdevice = new $itemdevicetype;

         $itemdevicetable = getTableForItemType($itemdevicetype);
         $devicetype      = $itemdevicetype::getDeviceType();
         $device          = new $devicetype;
         $devicetable     = getTableForItemType($devicetype);
         $fk              = getForeignKeyFieldForTable($devicetable);

         $existing = $this->getExisting($itemdevicetable, $fk);
         $deleted_items = [];

         foreach ($value as $val) {
            if (!isset($val->designation) || $val->designation == '') {
               //cannot be empty
               $val->designation = $itemdevice->getTypeName(1);
            }

            //create device or get existing device ID
            $device_id = $device->import(\Toolbox::addslashes_deep((array)$val));

            //remove all existing instances
            if (!isset($deleted_items[$device_id])) {
               $DB->delete(
                  $itemdevice->getTable(), [
                     $fk => $device_id,
                     'items_id'     => $this->item->fields['id'],
                     'itemtype'     => $this->item->getType(),
                  ]
               );
               $deleted_items[$device_id] = $device_id;
            }

            $itemdevice_data = \Toolbox::addslashes_deep([
               $fk                  => $device_id,
               'itemtype'           => $this->item->getType(),
               'items_id'           => $this->item->fields['id'],
               'is_dynamic'         => 1
            ] + (array)$val);
            $itemdevice->add($itemdevice_data, [], isset($existing[$device_id]) ? $this->withHistory() : false);
            $this->itemdeviceAdded($itemdevice, $val);
            unset($existing[$device_id]);
         }

         foreach ($existing as $deviceid => $data) {
            //first, remove items
            if ($data['is_dynamic'] == 1) {
               $DB->delete(
                  $itemdevice->getTable(), [
                     $fk => $deviceid
                  ]
               );
            }
         }
      }
   }

   protected function itemdeviceAdded(Item_Devices $itemdevice, $val) {
      //to be overrided
   }
}

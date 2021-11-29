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
use Glpi\Inventory\Conf;

class GraphicCard extends Device
{
   protected $ignored = ['controllers' => null];

   public function __construct(CommonDBTM $item, array $data = null) {
      parent::__construct($item, $data, 'Item_DeviceGraphicCard');
   }

   public function prepare() :array {
      $mapping = [
         'name'   => 'designation'
      ];

      foreach ($this->data as $k => &$val) {
         if (property_exists($val, 'name')) {
            foreach ($mapping as $origin => $dest) {
               if (property_exists($val, $origin)) {
                  $val->$dest = $val->$origin;
               }
            }

            $this->ignored['controllers'][$val->name] = $val->name;
            if (isset($val->chipset)) {
               $this->ignored['controllers'][$val->chipset] = $val->chipset;
            }

            $val->is_dynamic = 1;
         } else {
            unset($this->data[$k]);
         }
      }
      return $this->data;
   }

   public function checkConf(Conf $conf): bool {
      return $conf->component_graphiccard == 1;
   }
}

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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use Glpi\Application\View\TemplateRenderer;

/**
 * SNMP credentials
 */
class SNMPCredential extends CommonDBTM {

   // From CommonDBTM
   public $dohistory                   = true;
   static $rightname = 'computer';

   static function getTypeName($nb = 0) {
      return _n('SNMP credential', 'SNMP credentials', $nb);
   }

   public function rawSearchOptions() {
      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'            => '2',
         'table'         => $this->getTable(),
         'field'         => 'community',
         'name'          => __('Community'),
         'datatype'      => 'string',
         'massiveaction' => false,
      ];

      return $tab;
   }

   /**
    * Define tabs to display on form page
    *
    * @param array $options
    * @return array containing the tabs name
    */
   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }

   public function showForm($ID, $options = []) {
      $this->initForm($ID, $options);
      TemplateRenderer::getInstance()->display('components/form/snmpcredential.html.twig', [
         'item'   => $this,
         'params' => $options,
      ]);

      return true;
   }

   /**
    * Real version of SNMP
    *
    * @return string
    */
   public function getRealVersion(): string {
      switch ($this->fields['snmpversion']) {
         case 1:
         case 3:
            return (string)$this->fields['snmpversion'];
         case 2:
            return '2c';
         default:
            return '';

      }
   }

   /**
    * Get SNMP authentication protocol
    *
    * @return string
    */
   function getAuthProtocol(): string {
      switch ($this->fields['authentication']) {
         case 1:
            return 'MD5';
         case 2:
            return 'SHA';
         default:
            return '';
      }
      return '';
   }

   /**
    * Get SNMP encryption protocol
    *
    * @return string
    */
   function getEncryption(): string {
      switch ($this->fields['encryption']) {
         case 1:
            return 'DES';
         case 2:
            return 'AES';
         case 5:
            return '3DES';
         default:
            return '';
      }
   }

   protected function prepareInputs(array $input): array {
      $key = new GLPIKey();
      if (isset($input['auth_passphrase'])) {
         $input['auth_passphrase'] = $key->encrypt($input['auth_passphrase']);
      }

      if (isset($input['priv_passphrase'])) {
         $input['priv_passphrase'] = $key->encrypt($input['priv_passphrase']);
      }

      return $input;
   }

   public function prepareInputForAdd($input) {
      $input = parent::prepareInputForAdd($input);
      return $this->prepareInputs($input);
   }

   public function prepareInputForUpdate($input) {
      $input = parent::prepareInputForUpdate($input);
      return $this->prepareInputs($input);
   }

   public static function getIcon() {
      return "ti ti-key";
   }
}

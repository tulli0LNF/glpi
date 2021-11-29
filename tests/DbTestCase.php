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

use Glpi\Toolbox\Sanitizer;

// Generic test classe, to be extended for CommonDBTM Object

class DbTestCase extends \GLPITestCase {

   public function beforeTestMethod($method) {
      global $DB;
      $DB->beginTransaction();
      parent::beforeTestMethod($method);
   }

   public function afterTestMethod($method) {
      global $DB;
      $DB->rollback();
      parent::afterTestMethod($method);
   }


   /**
    * Connect (using the test user per default)
    *
    * @param string $user_name User name (defaults to TU_USER)
    * @param string $user_pass user password (defaults to TU_PASS)
    * @param bool $noauto disable autologin (from CAS by example)
    * @param bool $expected bool result expected from login return
    *
    * @return \Auth
    */
   protected function login(
      string $user_name = TU_USER,
      string $user_pass = TU_PASS,
      bool $noauto = true,
      bool $expected = true
   ): \Auth {
      \Session::destroy();
      \Session::start();

      $auth = new Auth();
      $this->boolean($auth->login($user_name, $user_pass, $noauto))->isEqualTo($expected);

      return $auth;
   }

   /**
    * change current entity
    *
    * @param int|string $entityname Name of the entity (or its id)
    * @param boolean $subtree   Recursive load
    *
    * @return void
    */
   protected function setEntity($entityname, $subtree) {
      $entity_id = is_int($entityname) ? $entityname : getItemByTypeName('Entity', $entityname, true);
      $res = Session::changeActiveEntities($entity_id, $subtree);
      $this->boolean($res)->isTrue();
   }

   /**
    * Generic method to test if an added object is corretly inserted
    *
    * @param  Object $object The object to test
    * @param  int    $id     The id of added object
    * @param  array  $input  the input used for add object (optionnal)
    *
    * @return void
    */
   protected function checkInput(CommonDBTM $object, $id = 0, $input = []) {
      $input = \Toolbox::stripslashes_deep($input); // slashes in input should not be stored in DB

      $this->integer((int)$id)->isGreaterThan(0);
      $this->boolean($object->getFromDB($id))->isTrue();
      $this->variable($object->getField('id'))->isEqualTo($id);

      if (count($input)) {
         foreach ($input as $k => $v) {
            $this->variable($object->fields[$k])->isEqualTo(
               $v,
               "
                '$k' key current value '{$object->fields[$k]}' (".gettype($object->fields[$k]).")
                is not equal to '$v' (".gettype($v).")"
            );
         }
      }
   }

   /**
    * Get all classes in folder inc/
    *
    * @param boolean $function Whether to look for a function
    * @param array   $excludes List of classes to exclude
    *
    * @return array
    */
   protected function getClasses($function = false, array $excludes = []) {
      // Add deprecated classes to excludes to prevent test failure
      $excludes = array_merge(
         $excludes,
         [
            'TicketFollowup', // Deprecated
            '/^RuleImportComputer.*/', // Deprecated
         ]
      );

      $classes = [];
      foreach (new \DirectoryIterator('inc/') as $fileInfo) {
         if (!$fileInfo->isFile()) {
            continue;
         }

         $php_file = file_get_contents("inc/".$fileInfo->getFilename());
         $tokens = token_get_all($php_file);
         $class_token = false;
         foreach ($tokens as $token) {
            if (is_array($token)) {
               if ($token[0] == T_CLASS) {
                  $class_token = true;
               } else if ($class_token && $token[0] == T_STRING) {
                  $classname = $token[1];

                  foreach ($excludes as $exclude) {
                     if ($classname === $exclude || @preg_match($exclude, $classname) === 1) {
                        break 2; // Class is excluded from results, go to next file
                     }
                  }

                  if ($function) {
                     if (method_exists($classname, $function)) {
                        $classes[] = $classname;
                     }
                  } else {
                     $classes[] = $classname;
                  }

                  break; // Assume there is only one class by file
               }
            }
         }
      }
      return array_unique($classes);
   }

   /**
    * Create an item of the given class
    *
    * @param string $itemtype
    * @param array $input
    *
    * @return CommonDBTM
    */
   protected function createItem($itemtype, $input): CommonDBTM {
      $item = new $itemtype();
      $input = Sanitizer::sanitize($input);
      $id = $item->add($input);
      $this->integer($id)->isGreaterThan(0);

      // Remove special fields
      $input = array_filter($input, function($key) {
         return strpos($key, '_') !== 0;
      }, ARRAY_FILTER_USE_KEY);

      $this->checkInput($item, $id, $input);

      return $item;
   }

   /**
    * Create an item of the given class
    *
    * @param string $itemtype
    * @param array $input
    */
   protected function updateItem($itemtype, $id, $input) {
      $item = new $itemtype();
      $input['id'] = $id;
      $input = Sanitizer::sanitize($input);
      $success = $item->update($input);
      $this->boolean($success)->isTrue();

      // Remove special fields
      $input = array_filter($input, function($key) {
         return strpos($key, '_') !== 0;
      }, ARRAY_FILTER_USE_KEY);

      $this->checkInput($item, $id, $input);
   }

   /**
    * Create multiples items of the given class
    *
    * @param string $itemtype
    * @param array $inputs
    */
   protected function createItems($itemtype, $inputs) {
      foreach ($inputs as $input) {
         $this->createItem($itemtype, $input);
      }
   }
}

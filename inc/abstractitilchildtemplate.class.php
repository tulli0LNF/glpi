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

use Glpi\ContentTemplates\ParametersPreset;
use Glpi\ContentTemplates\TemplateManager;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Base template class
 *
 * @since 10.0.0
 */
abstract class AbstractITILChildTemplate extends CommonDropdown
{
   function showForm($ID, $options = []) {
      parent::showForm($ID, $options);

      // Add autocompletion for ticket properties (twig templates)
      $parameters = ParametersPreset::getForAbstractTemplates();
      Html::activateUserTemplateAutocompletion(
         'textarea[name=content]',
         TemplateManager::computeParameters($parameters)
      );

      // Add related documentation
      Html::addTemplateDocumentationLinkJS(
         'textarea[name=content]',
         ParametersPreset::ITIL_CHILD_TEMPLATE
      );
   }

   function prepareInputForAdd($input) {
      $input = parent::prepareInputForUpdate($input);

      if (!$this->validateContentInput($input)) {
         return false;
      }

      return $input;
   }

   function prepareInputForUpdate($input) {
      $input = parent::prepareInputForUpdate($input);

      if (!$this->validateContentInput($input)) {
         return false;
      }

      return $input;
   }

   /**
    * Validate 'content' field from input.
    *
    * @param array $input
    *
    * @return bool
    */
   protected function validateContentInput(array $input): bool {
      if (!isset($input['content'])) {
         return true;
      }

      $err_msg = null;
      if (!TemplateManager::validate($input['content'], $err_msg)) {
         Session::addMessageAfterRedirect(
            sprintf('%s: %s', __('Content'), $err_msg),
            false,
            ERROR
         );
         $this->saveInput();
         return false;
      }

      return true;
   }

   /**
    * Get content rendered by template engine, using given ITIL item to build parameters.
    *
    * @param CommonITILObject $itil_item
    *
    * @return string
    */
   public function getRenderedContent(CommonITILObject $itil_item): string {
      $html = TemplateManager::renderContentForCommonITIL(
         $itil_item,
         $this->fields['content']
      );

      if (!$html) {
         $html = $this->fields['content'];
      }

      return $html;
   }
}

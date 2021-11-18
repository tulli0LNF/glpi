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

namespace Glpi\Plugin;

class Hooks
{
   // Boolean hooks
   const CSRF_COMPLIANT = 'csrf_compliant';

   // File hooks
   const ADD_CSS        = 'add_css';
   const ADD_JAVASCRIPT = 'add_javascript';

   // Function hooks with no parameters
   const CHANGE_ENTITY    = 'change_entity';
   const CHANGE_PROFILE   = 'change_profile';
   const DISPLAY_LOGIN    = 'display_login';
   const DISPLAY_CENTRAL  = 'display_central';
   const INIT_SESSION     = 'init_session';
   const POST_INIT        = 'post_init';

   // Specific function hooks with parameters
   const RULE_MATCHED        = 'rule_matched';
   const VCARD_DATA          = 'vcard_data';

   // Function hooks with parameters and output
   const DISPLAY_LOCKED_FIELDS         = 'display_locked_fields';
   const MIGRATE_TYPES                 = 'migratetypes';
   const POST_KANBAN_CONTENT           = 'post_kanban_content';
   const PRE_KANBAN_CONTENT            = 'pre_kanban_content';
   const REDEFINE_MENUS                = 'redefine_menus';
   const RETRIEVE_MORE_DATA_FROM_LDAP  = 'retrieve_more_data_from_ldap';
   const RETRIEVE_MORE_FIELD_FROM_LDAP = 'retrieve_more_field_from_ldap';
   const RESTRICT_LDAP_AUTH            = 'restrict_ldap_auth';
   const UNLOCK_FIELDS                 = 'unlock_fields';
   const UNDISCLOSED_CONFIG_VALUE      = 'undiscloseConfigValue';

   // Item hooks expecting an 'item' parameter
   const ADD_RECIPIENT_TO_TARGET   = 'add_recipient_to_target';
   const AUTOINVENTORY_INFORMATION = 'autoinventory_information';
   const INFOCOM                   = 'infocom';
   const ITEM_ACTION_TARGETS       = 'item_action_targets';
   const ITEM_ADD                  = 'item_add';
   const ITEM_ADD_TARGETS          = 'item_add_targets';
   const ITEM_CAN                  = 'item_can';
   const ITEM_EMPTY                = 'item_empty';
   const ITEM_DELETE               = 'item_delete';
   const ITEM_GET_DATA             = 'item_get_datas';
   const ITEM_GET_EVENTS           = 'item_get_events';
   const ITEM_UPDATE               = 'item_update';
   const ITEM_PURGE                = 'item_purge';
   const ITEM_RESTORE              = 'item_restore';
   const POST_PREPAREADD           = 'post_prepareadd';
   const PRE_ITEM_ADD              = 'pre_item_add';
   const PRE_ITEM_UPDATE           = 'pre_item_update';
   const PRE_ITEM_DELETE           = 'pre_item_delete';
   const PRE_ITEM_PURGE            = 'pre_item_purge';
   const PRE_ITEM_RESTORE          = 'pre_item_restore';
   const SHOW_ITEM_STATS           = 'show_item_stats';

   // Item hooks expecting an array parameter (available keys: item, options)
   const ITEM_TRANSFER           = 'item_transfer';
   const POST_ITEM_FORM          = 'post_item_form';
   const POST_SHOW_ITEM          = 'post_show_item';
   const POST_SHOW_TAB           = 'post_show_tab';
   const PRE_ITEM_FORM           = 'pre_item_form';
   const PRE_SHOW_ITEM           = 'pre_show_item';
   const PRE_SHOW_TAB            = 'pre_show_tab';
   const TIMELINE_ACTIONS        = 'timeline_actions';  // (keys: item, rand)
   const TIMELINE_ANSWER_ACTIONS = 'timeline_answer_actions';  // (keys: item)
   const SHOW_IN_TIMELINE        = 'show_in_timeline';  // (keys: item)

   // Security hooks (data to encypt)
   const SECURED_FIELDS  = 'secured_fields';
   const SECURED_CONFIGS = 'secured_configs';

   // Inventory hooks
   const PROLOG_RESPONSE = 'prolog_response';
   const NETWORK_DISCOVERY = 'network_discovery';
   const NETWORK_INVENTORY = 'network_inventory';

   /**
    * Get file hooks
    *
    * @return array
    */
   public static function getFileHooks(): array {
      return [
         self::ADD_CSS,
         self::ADD_JAVASCRIPT,
      ];
   }

   /**
    * Get functionnals hooks
    *
    * @return array
    */
   public static function getFunctionalHooks(): array {
      return [
         self::CHANGE_ENTITY,
         self::CHANGE_PROFILE,
         self::DISPLAY_LOCKED_FIELDS,
         self::DISPLAY_LOGIN,
         self::DISPLAY_CENTRAL,
         self::INIT_SESSION,
         self::MIGRATE_TYPES,
         self::POST_KANBAN_CONTENT,
         self::PRE_KANBAN_CONTENT,
         self::POST_INIT,
         self::RETRIEVE_MORE_DATA_FROM_LDAP,
         self::RETRIEVE_MORE_FIELD_FROM_LDAP,
         self::RESTRICT_LDAP_AUTH,
         self::RULE_MATCHED,
         self::UNDISCLOSED_CONFIG_VALUE,
         self::UNLOCK_FIELDS,
         self::VCARD_DATA,
      ];
   }

   /**
    * Get items hooks
    *
    * @return array
    */
   public static function getItemHooks(): array {
      return [
         self::ADD_RECIPIENT_TO_TARGET,
         self::ITEM_ACTION_TARGETS,
         self::ITEM_ADD,
         self::ITEM_ADD_TARGETS,
         self::ITEM_CAN,
         self::ITEM_EMPTY,
         self::ITEM_DELETE,
         self::ITEM_GET_DATA,
         self::ITEM_GET_EVENTS,
         self::ITEM_UPDATE,
         self::ITEM_PURGE,
         self::ITEM_RESTORE,
         self::ITEM_TRANSFER,
         self::PRE_ITEM_ADD,
         self::PRE_ITEM_UPDATE,
         self::PRE_ITEM_DELETE,
         self::PRE_ITEM_FORM,
         self::PRE_ITEM_PURGE,
         self::PRE_ITEM_RESTORE,
         self::PRE_SHOW_ITEM,
         self::PRE_SHOW_TAB,
         self::POST_ITEM_FORM,
         self::POST_SHOW_ITEM,
         self::POST_SHOW_TAB,
         self::POST_PREPAREADD,
         self::SHOW_ITEM_STATS,
         self::TIMELINE_ACTIONS,
      ];
   }
}

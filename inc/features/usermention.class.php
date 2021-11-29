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

namespace Glpi\Features;

use CommonITILActor;
use CommonITILObject;
use CommonITILTask;
use CommonITILValidation;
use Glpi\Toolbox\Sanitizer;
use ITILFollowup;
use ITILSolution;
use NotificationEvent;
use SimpleXMLElement;
use User;

trait UserMention {

   /**
    * Handle user mentions.
    * Add newly mention users to observers and send them a notification.
    *
    * @return void
    */
   protected function handleUserMentions(): void {

      $content_fields = $this instanceof CommonITILValidation
         ? ['comment_submission', 'comment_validation']
         : ['content'];

      $previously_mentionned_actors_ids = [];
      $mentionned_actors_ids = [];

      foreach ($content_fields as $content_field) {
         if (property_exists($this, 'oldvalues') && array_key_exists($content_field, $this->oldvalues)) {
            // Update case: content field was updated
            $previous_value = $this->oldvalues[$content_field];
         } else if (property_exists($this, 'updates')) {
            // Update case: content field was not updated
            $previous_value = $this->fields[$content_field];
         } else {
            // Creation case
            $previous_value = null;
         }

         $new_value = $this->input[$content_field] ?? null;

         if ($new_value !== null) {
            $mentionned_actors_ids = array_merge(
               $mentionned_actors_ids,
               $this->getUserIdsFromUserMentions($new_value)
            );
         }

         if ($previous_value !== null) {
            $previously_mentionned_actors_ids = array_merge(
               $previously_mentionned_actors_ids,
               $this->getUserIdsFromUserMentions($previous_value)
            );
         }
      }

      // Keep only newly mentionned actors
      $mentionned_actors_ids = array_diff($mentionned_actors_ids, $previously_mentionned_actors_ids);

      if (empty($mentionned_actors_ids)) {
         return;
      }

      // Retrieve main item
      $item = $this;
      $options = [];
      if ($this instanceof CommonITILTask) {
         $options = [
            'task_id'    => $this->fields['id'],
            'is_private' => $this->isPrivate(),
         ];

         $item = $this->getItem();
      } else if ($this instanceof CommonITILValidation) {
         $options = [
            'validation_id'     => $this->fields['id'],
            'validation_status' => $this->fields['status']
         ];

         $item = getItemForItemtype($this->getItilObjectItemType());
         $item->getFromDB($this->fields[$this::$items_id]);
      } else if ($this instanceof ITILFollowup) {
         $options = [
            'followup_id' => $this->fields['id'],
            'is_private'  => $this->isPrivate(),
         ];

         $item = getItemForItemtype($this->fields['itemtype']);
         $item->getFromDB($this->fields['items_id']);
      } else if ($this instanceof ITILSolution) {
         $item = getItemForItemtype($this->fields['itemtype']);
         $item->getFromDB($this->fields['items_id']);
      }

      // Send a "you have been mentionned" notification
      foreach ($mentionned_actors_ids as $user_id) {
         $options['users_id'] = $user_id;
         NotificationEvent::raiseEvent('user_mention', $item, $options);
      }

      if ($item instanceof CommonITILObject) {
         if (empty($item->userlinkclass) || !class_exists($item->userlinkclass)) {
            return; // Cannot add observers
         }

         // Retrieve current actors list
         $userlink = new $item->userlinkclass();
         $current_actors_ids = [];
         $current_actors = $userlink->getActors($item->fields['id']);
         foreach ($current_actors as $actors) {
            foreach ($actors as $actor) {
               $current_actors_ids[] = $actor['users_id'];
            }
         }

         // Add newly mentionned actors as observers
         foreach ($mentionned_actors_ids as $user_id) {
            if (in_array($user_id, $current_actors_ids)) {
               continue;
            }

            $input = [
               'type'                            => CommonITILActor::OBSERVER,
               'users_id'                        => $user_id,
               $item->getForeignKeyField()       => $item->fields['id'],
               '_do_not_compute_takeintoaccount' => true,
               '_from_object'                    => true,
            ];
            $userlink->add($input);
         }
      }
   }

   /**
    * Extract ids of mentionned users.
    *
    * @param string $content
    *
    * @return int[]
    */
   protected function getUserIdsFromUserMentions(string $content) {
      $ids = [];

      try {
         $content = Sanitizer::getVerbatimValue($content);

         libxml_use_internal_errors(true);
         $content_as_xml = new SimpleXMLElement('<div>' . $content . '</div>');
      } catch (\Throwable $e) {
         // Sanitize process does not handle correctly `<` and `>` chars that are not surrounding html tags.
         // This generates invalid HTML that cannot be loaded by `SimpleXMLElement`.
         return [];
      }

      $mention_elements = $content_as_xml->xpath('//*[@data-user-mention="true"]');
      foreach ($mention_elements as $mention_element) {
         $ids[] = (int)$mention_element->attributes()->{'data-user-id'};
      }

      return $ids;
   }

   /**
    * Refresh user mentions HTML in order to display them.
    * User name is updated, and a link to user page could be added on mention.
    *
    * @param string $content
    *
    * @return string
    */
   protected function refreshUserMentionsHtmlToDisplay(string $content, bool $add_link = true): string {

      $mentionned_users_ids = $this->getUserIdsFromUserMentions($content);

      foreach ($mentionned_users_ids as $user_id) {
         $user = new User();
         if (!$user->getFromDB($user_id)) {
            // User does not exists anymore, keep the mention but do not add link.
            continue;
         }

         $pattern = '/'
             // <span data-user-mention="true" ...>
            . '<span[^>]*'
            . '('
            . 'data-user-mention="true"[^>]+data-user-id="' . $user_id . '"'
            . '|'
            . 'data-user-id="' . $user_id . '"[^>]+data-user-mention="true"'
            . ')'
            . '[^>]*>'
            // @Name
            . '@[^>]+'
            // span closing
            . '<\/span>'
            . '/';
         $replacement = sprintf(
            '<a class="user-mention" href="%s">@%s</a>',
            $user->getLinkURL(),
            $user->getFriendlyName()
         );
         $content = preg_replace($pattern, $replacement, $content);
      }

      return $content;
   }
}

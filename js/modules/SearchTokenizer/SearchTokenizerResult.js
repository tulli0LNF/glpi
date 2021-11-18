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

export default class SearchTokenizerResult {

   constructor() {
      /**
       * @type {SearchToken[]}
       */
      this.tokens = [];
   }

   /**
    * Get all tokens with a specific tag
    * @param name
    * @return {SearchToken[]}
    */
   getTag(name) {
      return this.tokens.filter(t => t.tag === name);
   }

   /**
    * Get all tokens with a tag
    * @return {SearchToken[]}
    */
   getTaggedTerms() {
      return this.tokens.filter(t => t.tag !== null);
   }

   /**
    * Get all tokens without a tag
    * @return {SearchToken[]}
    */
   getUntaggedTerms() {
      return this.tokens.filter(t => t.tag === null);
   }

   /**
    * Get all untagged terms as a concatenated string
    *
    * The terms in the resulting string should be in the same order they appeared in the tokenizer input string.
    * @return {string}
    */
   getFullPhrase() {
      let full_phrase = '';
      this.getUntaggedTerms().forEach(t => full_phrase += ' ' + t.term);
      return full_phrase.trim();
   }
}

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

namespace Glpi\Console\Cache;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use Glpi\Cache\CacheManager;
use Glpi\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @since 10.0.0
 */
class SetNamespacePrefixCommand extends AbstractCommand {

   /**
    * Error code returned if cache configuration file cannot be write.
    *
    * @var int
    */
   const ERROR_UNABLE_TO_WRITE_CONFIG = 1;

   protected $requires_db = false;

   /**
    * Cache manager.
    * @var CacheManager
    */
   private $cache_manager;

   public function __construct(string $name = null) {
      $this->cache_manager = new CacheManager();

      parent::__construct();
   }

   protected function configure() {

      $this->setName('glpi:cache:set_namespace_prefix');
      $this->setAliases(['cache:set_namespace_prefix']);
      $this->setDescription('Define cache namespace prefix');

      $this->addArgument('prefix', InputArgument::REQUIRED, 'Namespace prefix');
   }

   protected function execute(InputInterface $input, OutputInterface $output) {

      $prefix = $input->getArgument('prefix');

      // Store configuration
      $success = $this->cache_manager->setNamespacePrefix($prefix);

      if (!$success) {
         throw new \Glpi\Console\Exception\EarlyExitException(
            '<error>' . __('Unable to write cache configuration file.') . '</error>',
            self::ERROR_UNABLE_TO_WRITE_CONFIG
         );
      }

      $output->writeln(
         '<info>' . __('Cache configuration saved successfully.') . '</info>',
         OutputInterface::VERBOSITY_NORMAL
      );

      return 0; // Success
   }
}

<?php

/**
 * Bootstrap file
 * 
 * This is the Pheeder bootstrap file. Include <i>only</i> this file to use Pheeder.
 * Create a new feed by instantiating the <b>{@link RssFeed}</b> class.
 * 
 * <i>LICENSE:</i>
 * 
 * <i>This library is free software; you can redistribute it
 * and/or modify it under the terms of the GNU Lesser General
 * Public License as published by the Free Software Foundation;
 * either version 2.1 of the License, or (at your option) any
 * later version.</i>
 * 
 * <i>This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.</i>
 * 
 * <i>You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA</i>
 *
 * @author      Jure Merhar <dev@merhar.si>
 * @copyright   Copyright (c) 2008, Jure Merhar
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Id: pheeder.php 3 2008-03-18 10:06:37Z jmerhar $
 * @version     1.0-beta
 * @link        http://pheeder.sourceforge.net/
 * @package     Pheeder
 */


/**
 * Pheeder root (lib) directory
 */
define('PHEEDER_ROOT_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR);

/**
 * Include RSS Common
 */
require_once PHEEDER_ROOT_DIR . 'RssCommon.php';

/**
 * Include RSS Feed
 */
require_once PHEEDER_ROOT_DIR . 'RssFeed.php';

/**
 * Include RSS Item
 */
require_once PHEEDER_ROOT_DIR . 'RssItem.php';

/**
 * Include RSS Exception
 */
require_once PHEEDER_ROOT_DIR . 'RssException.php';


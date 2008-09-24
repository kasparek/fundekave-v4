<?php

/**
 * RSS Exception class file
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
 * @copyright   Copyright (c) 2008, Jure Merhar
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Id: RssException.php 3 2008-03-18 10:06:37Z jmerhar $
 * @link        http://pheeder.sourceforge.net/
 * @package     Pheeder
 */

/**
 * RSS Exception
 * 
 * This is a custom exception, used by the RSS library.
 *
 * @author      Jure Merhar <dev@merhar.si>
 * @version     See {@link pheeder.php} for the version number of this library
 * @package     Pheeder
 */
class RssException extends Exception
{
  /**
   * Constructor
   *
   * Creates an RSS exception object.
   *
   * @param string $message The error message
   * @param int $code The error code (optional)
   * @author Jure Merhar <dev@merhar.si>
   * @return RssException Returns a new instance of this class
   */
  public function __construct($message, $code = 0)
  {
    $message = 'RSS error: ' . $message;
    parent::__construct($message, $code);
  }
}
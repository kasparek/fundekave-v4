<?php

/**
 * RSS Item class file
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
 * @version     $Id: RssItem.php 3 2008-03-18 10:06:37Z jmerhar $
 * @link        http://pheeder.sourceforge.net/
 * @package     Pheeder
 */

/**
 * RSS Item
 * 
 * Objects of this class represent a single item of an RSS feed each.
 *
 * This class implements the ArrayAccess interface for easier manipulation:
 * 
 * Item tags can be accessed as if the object was an associative array.
 * For a list of item tags, please see {@link fromArray}.
 * <code>
 * $item['title'] = 'RSS Item Title';
 * echo $item['title'];
 * if (isset($item['author'])) unset($item['author']);
 * </code>
 * 
 * @author      Jure Merhar <dev@merhar.si>
 * @link        http://www.rssboard.org/rss-2-0-10 RSS 2.0 Specification
 * @version     See {@link pheeder.php} for the version number of this library
 * @package     Pheeder
 */
class RssItem extends RssCommon
{
  /**
   * The author tag
   * 
   * Email address of the author of the item.
   * {@link http://www.rssboard.org/rss-2-0-10#ltauthorgtSubelementOfLtitemgt More}.
   * 
   * Example: <code>'lawyer@boyer.net (Lawyer Boyer)'</code>
   *
   * @var string
   */
  protected $author = null;

  /**
   * The comments tag
   * 
   * URL of a page for comments relating to the item.
   * {@link http://www.rssboard.org/rss-2-0-10#ltcommentsgtSubelementOfLtitemgt More}.
   * 
   * Example: <code>'http://ekzemplo.com/entry/4403/comments'</code>
   *
   * @var string
   */
  protected $comments = null;

  /**
   * The enclosure tag
   * 
   * Describes a media object that is attached to the item.
   * {@link http://www.rssboard.org/rss-2-0-10#ltenclosuregtSubelementOfLtitemgt More}.
   * 
   * If the length and/or type are not provided, they will be extracted from the
   * HTTP headers of the provided url.
   * 
   * Example 1: <code>'weatherReportSuite.mp3'</code>
   * Example 2:
   * <code>
   * array(
   *   'url'    => 'http://www.scripting.com/mp3s/weatherReportSuite.mp3',
   *   'length' => '12216320',
   *   'type'   => 'audio/mpeg'
   * )
   * </code>
   *
   * @var array
   */
  protected $enclosure = null;

  /**
   * The guid tag
   * 
   * A string that uniquely identifies the item.
   * {@link http://www.rssboard.org/rss-2-0-10#ltguidgtSubelementOfLtitemgt More}.
   * 
   * If the permaLink attribute is not specified, it defaults to true.
   * 
   * Example 1: <code>'http://some.server.com/weblogItem3207'</code>
   * Example 2:
   * <code>
   * array(
   *   'isPermaLink' => false,
   *   'value'       => 'http://inessential.com/2002/09/01.php#a2'
   * )
   * </code>
   *
   * @var array
   */
  protected $guid = null;

  /**
   * The source tag
   * 
   * The RSS channel that the item came from.
   * {@link http://www.rssboard.org/rss-2-0-10#ltsourcegtSubelementOfLtitemgt More}.
   * 
   * Example:
   * <code>
   * array(
   *   'url'   => 'http://www.tomalak.org/links2.xml',
   *   'value' => 'Tomalak's Realm'
   * )
   * </code>
   *
   * @var array
   */
  protected $source = null;

  /**
   * @ignore
   */
  protected $depth = 3;

  /**
   * @ignore
   */
  protected $tags = array(
    'title', 'link', 'description', 'author', 'category',
    'comments', 'enclosure', 'guid', 'pubDate', 'source',
  );
  /**
   * @ignore
   */
  protected $attributes = array(
    'enclosure' => array('url', 'length', 'type'),
    'guid'      => array('isPermaLink', 'value'),
    'source'    => array('url', 'value'),
  );

  /**
   * Get the item as XML
   * 
   * Returns the item as an XML string. This method is called if the object
   * is cast to string.
   *
   * @author Jure Merhar <dev@merhar.si>
   * @return string The RSS feed item as XML
   * @throws {@link RssException}
   */
  public function asXml()
  {
    if (is_null($this->title) && is_null($this->description)) {
      throw new RssException('The item title or description is mandatory');
    }
    $output  = $this->tabs(2) . '<item>' . $this->nl;
    foreach ($this->tags as $tag) {
      $output .= $this->getTagValue($tag);
    }
    $output .= $this->tabs(2) . '</item>' . $this->nl;
    return $output;
  }


  /**
   * Set author
   * 
   * The setter method for {@link $author}.
   *
   * @param string $v The new value of author
   * @author Jure Merhar <dev@merhar.si>
   * @uses $author
   */
  public function setAuthor($v)
  {
    $this->author = $v;
  }

  /**
   * Set comments
   * 
   * The setter method for {@link $comments}.
   *
   * @param string $v The new value of comments
   * @author Jure Merhar <dev@merhar.si>
   * @uses $comments
   */
  public function setComments($v)
  {
    $this->comments = $this->createUrl($v);
  }

  /**
   * Set enclosure
   * 
   * The setter method for {@link $enclosure}.
   *
   * @param string|array $v The new value of enclosure
   * @author Jure Merhar <dev@merhar.si>
   * @uses $enclosure
   */
  public function setEnclosure($v)
  {
    if (!is_array($v)) $v = array('url' => $v);
    if (!isset($v['url'])) {
      throw new RssException('Insufficient enclosure parameters provided');
    }
    if (!isset($v['length'])) {
      if ($size = @filesize($v['url'])) $v['length'] = $size;
    }
    $v['url'] = $this->createUrl($v['url']);
    if (!isset($v['length']) || !isset($v['type'])) {
      $headers = @get_headers($v['url'], 1);
      if (strpos($headers[0], '200') !== false) {
        if (!isset($v['length']) && isset($headers['Content-Length'])) {
          $v['length'] = $headers['Content-Length'];
        }
        if (!isset($v['type']) && isset($headers['Content-Type'])) {
          list($type) = explode(';', $headers['Content-Type']);
          $v['type'] = $type;
        }
      }
    }
    if (!isset($v['length']) || !isset($v['type'])) {
      throw new RssException('Insufficient enclosure parameters provided');
    }
    $this->enclosure = $v;
  }

  /**
   * Set guid
   * 
   * The setter method for {@link $guid}.
   *
   * @param string|array $v The new value of guid
   * @author Jure Merhar <dev@merhar.si>
   * @uses $guid
   */
  public function setGuid($v)
  {
    if (!is_array($v)) $v = array('value' => $v);
    if (!isset($v['isPermaLink'])) $v['isPermaLink'] = true;
    if ($v['isPermaLink']) $v['value'] = $this->createUrl($v['value']);
    $v['isPermaLink'] = $v['isPermaLink'] ? 'true' : 'false';
    $this->guid = $v;
  }

  /**
   * Set source
   * 
   * The setter method for {@link $source}.
   *
   * @param array $v The new value of source
   * @author Jure Merhar <dev@merhar.si>
   * @uses $source
   */
  public function setSource($v)
  {
    if (!is_array($v)) throw new RssException('Please provide an array of source parameters');
    if (!isset($v['url']) || !isset($v['value'])) {
      throw new RssException('Insufficient source parameters provided');
    }
    $v['url'] = $this->createUrl($v['url']);
    $this->source = $v;
  }


  /**
   * Get author
   * 
   * The getter method for {@link $author}.
   * 
   * @param bool $output Retrieve in XML format
   * @author Jure Merhar <dev@merhar.si>
   * @return string The value of author
   * @uses $author
   */
  public function getAuthor($output = false)
  {
    return $this->get('author', $output);
  }

  /**
   * Get comments
   * 
   * The getter method for {@link $comments}.
   * 
   * @param bool $output Retrieve in XML format
   * @author Jure Merhar <dev@merhar.si>
   * @return string The value of comments
   * @uses $comments
   */
  public function getComments($output = false)
  {
    return $this->get('comments', $output);
  }

  /**
   * Get enclosure
   * 
   * The getter method for {@link $enclosure}.
   * 
   * @param bool $output Retrieve in XML format
   * @author Jure Merhar <dev@merhar.si>
   * @return string|array The value of enclosure
   * @uses $enclosure
   */
  public function getEnclosure($output = false)
  {
    return $this->get('enclosure', $output);
  }

  /**
   * Get guid
   * 
   * The getter method for {@link $guid}.
   * 
   * @param bool $output Retrieve in XML format
   * @author Jure Merhar <dev@merhar.si>
   * @return string|array The value of guid
   * @uses $guid
   */
  public function getGuid($output = false)
  {
    return $this->get('guid', $output);
  }

  /**
   * Get source
   * 
   * The getter method for {@link $source}.
   * 
   * @param bool $output Retrieve in XML format
   * @author Jure Merhar <dev@merhar.si>
   * @return string|array The value of source
   * @uses $source
   */
  public function getSource($output = false)
  {
    return $this->get('source', $output);
  }

}
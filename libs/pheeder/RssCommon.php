<?php

/**
 * RSS Common class file
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
 * @version     $Id: RssCommon.php 3 2008-03-18 10:06:37Z jmerhar $
 * @link        http://pheeder.sourceforge.net/
 * @package     Pheeder
 */

/**
 * RSS Common
 *
 * This class holds the common properties and methods of the {@link RssFeed}
 * and {@link RssItem} classes.
 *
 * @author      Jure Merhar <dev@merhar.si>
 * @link        http://www.rssboard.org/rss-2-0-10 RSS 2.0 Specification
 * @version     See {@link pheeder.php} for the version number of this library
 * @package     Pheeder
 */
abstract class RssCommon implements ArrayAccess
{
  /**
   * The title tag
   * 
   * In {@link RssFeed}, this is the name of the channel. It's how people refer
   * to your service. If you have an HTML website that contains the same information
   * as your RSS file, the title of your channel should be the same as the title
   * of your website. This tag is required.
   * 
   * Example: <code>'GoUpstate.com News Headlines'</code>
   * 
   * In {@link RssItem}, this is the title of the item. Either this tag or the
   * {@link $description description} tag must be present.
   * 
   * Example: <code>'Venice Film Festival Tries to Quit Sinking'</code>
   *
   * @var string
   */
  protected $title = null;

  /**
   * The link tag
   * 
   * In {@link RssFeed}, this is the URL to the HTML website corresponding
   * to the channel. It defaults to the site's domain.
   * 
   * Example: <code>'http://www.goupstate.com/'</code>
   * 
   * In {@link RssItem}, this is the URL of the item.
   * 
   * Example: <code>'http://nytimes.com/2004/12/07FEST.html'</code>
   *
   * @var string
   */
  protected $link = null;

  /**
   * The description tag
   * 
   * In {@link RssFeed}, this is a phrase or sentence describing the channel.
   * This tag is required.
   * 
   * Example:
   * <code>
   * 'The latest news from GoUpstate.com,
   * a Spartanburg Herald-Journal Web site.'
   * </code>
   * 
   * In {@link RssItem}, this is the item synopsis. Either this tag or the
   * {@link $title title} tag must be present.
   * 
   * Example:
   * <code>
   * 'Some of the most heated chatter at the Venice Film Festival this week
   * was about the way that the arrival of the stars at the Palazzo del Cinema
   * was being staged.'
   * </code>
   *
   * @var string
   */
  protected $description = null;

  /**
   * The pubDate tag
   * 
   * In {@link RssFeed}, this is the publication date for the content in the channel.
   * For example, the New York Times publishes on a daily basis, the publication date
   * flips once every 24 hours. That's when the pubDate of the channel changes.
   * 
   * In {@link RssItem}, it indicates when the item was published.
   * {@link http://www.rssboard.org/rss-2-0-10#ltpubdategtSubelementOfLtitemgt More}.
   * 
   * Can be supplied in any format accepted by
   * {@link http://www.php.net/manual/en/function.strtotime.php strtotime()}.
   * 
   * Example:
   * <code>
   * 'Sat, 07 Sep 2002 00:00:01 GMT'
   * '2002-09-07 00:00:01'
   * </code>
   *
   * @var string
   */
  protected $pubDate = null;

  /**
   * The category tag
   * 
   * In {@link RssFeed}, it specifies one or more categories that the channel belongs to.
   * Follows the same rules as the item-level category element.
   * {@link http://www.rssboard.org/rss-2-0-10#syndic8 More info}.
   * 
   * In {@link RssItem}, it includes the item in one or more categories.
   * The value of each element is a forward-slash-separated string that identifies
   * a hierarchic location in the indicated taxonomy. Processors may establish
   * conventions for the interpretation of categories. It has one optional attribute,
   * domain, a string that identifies a categorization taxonomy.
   * 
   * Example:
   * <code>
   * array(
   *   'Grateful Dead',
   *   array('domain' => 'http://www.fool.com/cusips', 'value' => 'MSFT')
   * )
   * </code>
   *
   * @var array
   */
  protected $category = array();

  /**
   * @ignore
   */
  protected $tags = array();
  /**
   * @ignore
   */
  protected $attributes = array();

  /**
   * @ignore
   */
  protected $nl = "\n";
  /**
   * @ignore
   */
  protected $tab = "\t";
  /**
   * @ignore
   */
  protected $depth = 0;

  /**
   * Constructor
   *
   * Initializes an RSS feed or item.
   * The optional $data parameter can be an array for the {@link fromArray()} method.
   *
   * @param array $data Optional array of attributes and/or items
   * @author Jure Merhar <dev@merhar.si>
   * @return RssCommon Returns a new instance of {@link RssFeed} or {@link RssItem}
   * @uses fromArray()
   */
  public function __construct($data = null)
  {
    if (!is_null($data)) $this->fromArray($data);
  }

  /**
   * @ignore
   */
  protected function getUri($full = true)
  {
    if (empty($_SERVER)) return null;
    $ssl = (isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) == 'on' || $_SERVER['HTTPS'] == 1));
    return 'http' . ($ssl ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . ($full ? $_SERVER['REQUEST_URI'] : '/');
  }

  /**
   * @ignore
   */
  protected function createUrl($url)
  {
    if (strpos($url, '://') === false) {
      $parts = parse_url($this->getUri());
      $prefix = $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? (':' . $parts['port']) : '');
      if (substr($url, 0, 1) != '/') $prefix .= dirname($parts['path']) . '/';
      $url = $prefix . $url;
    }
    return $url;
  }

  /**
   * @ignore
   */
  abstract public function asXml();

  /**
   * Convert the object to string
   * 
   * Returns the feed or item as an XML string. This method is called
   * if the object is cast to string.
   *
   * @author Jure Merhar <dev@merhar.si>
   * @return string The RSS feed or item as XML
   * @uses RssFeed::asXML()
   * @uses RssItem::asXML()
   */
  public function __toString()
  {
    try {
      return $this->asXml();
    } catch (Exception $e) {
      trigger_error($e->getMessage(), E_USER_WARNING);
      return '';
    } 
  }

  /**
   * @ignore
   */
  protected function tabs($n)
  {
    return str_repeat($this->tab, $n);
  }

  /**
   * @ignore
   */
  protected function escape($string)
  {
    return is_string($string) ? htmlspecialchars($string) : $string;
  }

  /**
   * @ignore
   */
  protected function get($var, $output = false)
  {
    $value = $this->$var;
    if (!$output) return $value;
    if (isset($this->attributes[$var])) {
      $attributes = $this->attributes[$var];
      $xml  = $this->tabs($this->depth) . "<$var";
      foreach ($attributes as $attr) {
        if (($attr != 'value') && isset($value[$attr])) {
          $xml .= ' ' . $attr . '="' . $this->escape($value[$attr]) . '"';
        }
      }
      if (in_array('value', $attributes) && isset($value['value'])) {
        $xml .= '>' . $this->escape($value['value']) . "</$var>";
      } else {
        $xml .= ' />';
      }
      $xml .= $this->nl;
      return $xml;
    } else {
      return $this->tabs($this->depth) . "<$var>" . $this->escape($this->$var) . "</$var>" . $this->nl;
    }
  }

  /**
   * @ignore
   */
  protected function getTagValue($var)
  {
    if (is_null($this->$var)) return '';
    $func = 'get' . ucfirst($var);
    $value = call_user_func(array($this, $func), true);
    return $value;
  }

  /**
   * @ignore
   */
  protected function convertDate($v)
  {
    return date_create($v)->format('r');
  }

  /**
   * Load the feed or item from an array
   * 
   * Loads the data for the feed or item from an associative array of tags.
   * The following tags are allowed for an RSS feed item:
   * <ul>
   *   <li>{@link RssCommon::$title title}</li>
   *   <li>{@link RssCommon::$link link}</li>
   *   <li>{@link RssCommon::$description description}</li>
   *   <li>{@link RssItem::$author author}</li>
   *   <li>{@link RssCommon::$category category}</li>
   *   <li>{@link RssItem::$comments comments}</li>
   *   <li>{@link RssItem::$enclosure enclosure}</li>
   *   <li>{@link RssItem::$guid guid}</li>
   *   <li>{@link RssCommon::$pubDate pubDate}</li>
   *   <li>{@link RssItem::$source source}</li>
   * </ul>
   * 
   * For a list of allowed tags for an RSS feed, please see {@link RssFeed::fromArray()}.
   * 
   * @param array $array An array of tags
   * @author Jure Merhar <dev@merhar.si>
   * @see RssFeed::fromArray()
   * @throws {@link RssException}
   */
  public function fromArray($array)
  {
    if (!is_array($array)) throw new RssException('Please provide an array');
    foreach ($this->tags as $tag) {
      if (array_key_exists($tag, $array)) {
        $func = 'set' . ucfirst($tag);
        $value = call_user_func(array($this, $func), $array[$tag]);
      }
    }
  }


  /**
   * Set title
   * 
   * The setter method for {@link $title}.
   *
   * @param string $v The new value of title
   * @author Jure Merhar <dev@merhar.si>
   * @uses $title
   */
  public function setTitle($v)
  {
    $this->title = $v;
  }

  /**
   * Set link
   * 
   * The setter method for {@link $link}.
   *
   * @param string $v The new value of link
   * @author Jure Merhar <dev@merhar.si>
   * @uses $link
   */
  public function setLink($v)
  {
    $this->link = $v;
  }

  /**
   * Set description
   * 
   * The setter method for {@link $description}.
   *
   * @param string $v The new value of description
   * @author Jure Merhar <dev@merhar.si>
   * @uses $description
   */
  public function setDescription($v)
  {
    $this->description = $v;
  }

  /**
   * Set pubDate
   * 
   * The setter method for {@link $pubDate}.
   *
   * @param string $v The new value of pubDate
   * @author Jure Merhar <dev@merhar.si>
   * @uses $pubDate
   */
  public function setPubDate($v)
  {
    $this->pubDate = $this->convertDate($v);
  }

  /**
   * Set category
   * 
   * The setter method for {@link $category}.
   *
   * @param array $v The new value of category
   * @author Jure Merhar <dev@merhar.si>
   * @uses $category
   * @throws {@link RssException}
   */
  public function setCategory($v)
  {
    if (!is_array($v)) throw new RssException('Please provide an array of categories');
    foreach ($v as $key => $cat) {
      if (!is_array($cat)) $v[$key] = array('value' => $cat);
      if (!isset($v[$key]['value'])) throw new RssException('Missing category value');
    }
    $this->category = $v;
  }


  /**
   * Get title
   * 
   * The getter method for {@link $title}.
   * 
   * @param bool $output Retrieve in XML format
   * @author Jure Merhar <dev@merhar.si>
   * @return string The value of title
   * @uses $title
   */
  public function getTitle($output = false)
  {
    return $this->get('title', $output);
  }

  /**
   * Get link
   * 
   * The getter method for {@link $link}.
   * 
   * @param bool $output Retrieve in XML format
   * @author Jure Merhar <dev@merhar.si>
   * @return string The value of link
   * @uses $link
   */
  public function getLink($output = false)
  {
    return $this->get('link', $output);
  }

  /**
   * Get description
   * 
   * The getter method for {@link $description}.
   * 
   * @param bool $output Retrieve in XML format
   * @author Jure Merhar <dev@merhar.si>
   * @return string The value of description
   * @uses $description
   */
  public function getDescription($output = false)
  {
    return $this->get('description', $output);
  }

  /**
   * Get pubDate
   * 
   * The getter method for {@link $pubDate}.
   * 
   * @param bool $output Retrieve in XML format
   * @author Jure Merhar <dev@merhar.si>
   * @return string The value of pubDate
   * @uses $pubDate
   */
  public function getPubDate($output = false)
  {
    return $this->get('pubDate', $output);
  }

  /**
   * Get category
   * 
   * The getter method for {@link $category}.
   * 
   * @param bool $output Retrieve in XML format
   * @author Jure Merhar <dev@merhar.si>
   * @return string|array The value of category
   * @uses $category
   */
  public function getCategory($output = false)
  {
    $categories = $this->get('category', false);
    if (!$output) return $categories;
    $xml = '';
    foreach ($categories as $category) {
      $xml .= $this->tabs($this->depth) . '<category';
      if (isset($category['domain'])) $xml .= ' domain="' . $this->escape($category['domain']) . '"';
      $xml .= '>' . $this->escape($category['value']) . '</category>' . $this->nl;
    }
    return $xml;
  }


  /* ArrayAccess Interface */

  /**
   * Offset exists
   * 
   * Checks whether the specified tag is valid. This method is also used as part of
   * the ArrayAccess interface to allow using the object as an associative array
   * to access the tags within it.
   *
   * @param string $offset The tag name
   * @author Jure Merhar <dev@merhar.si>
   * @return bool true if the tag is valid, false otherwise
   * @see offsetGet()
   * @see offsetSet()
   * @see offsetUnset()
   */
  public function offsetExists($offset)
  {
    return in_array($offset, $this->tags);
  }

  /**
   * Offset get
   * 
   * Retrieves the specified tag's value. This method is also used as part of
   * the ArrayAccess interface to allow using the object as an associative array
   * to access the tags within it.
   *
   * @param string $offset The tag name
   * @author Jure Merhar <dev@merhar.si>
   * @return string|array The value of the specified tag
   * @see offsetExists()
   * @see offsetSet()
   * @see offsetUnset()
   * @throws {@link RssException}
   */
  public function offsetGet($offset)
  {
    if (!$this->offsetExists($offset)) throw new RssException('Invalid tag ' . $offset);
    $func = 'get' . ucfirst($offset);
    return call_user_func(array($this, $func));
  }

  /**
   * Offset set
   * 
   * Sets the specified tag's value. This method is also used as part of
   * the ArrayAccess interface to allow using the object as an associative array
   * to access the tags within it.
   *
   * @param string $offset The tag name
   * @param string $v The new value for the tag
   * @author Jure Merhar <dev@merhar.si>
   * @see offsetExists()
   * @see offsetGet()
   * @see offsetUnset()
   * @throws {@link RssException}
   */
  public function offsetSet($offset, $v)
  {
    if (!$this->offsetExists($offset)) throw new RssException('Invalid tag ' . $offset);
    $func = 'set' . ucfirst($offset);
    call_user_func(array($this, $func), $v);
  }

  /**
   * Offset unset
   * 
   * Unsets the specified tag's value. This method is also used as part of
   * the ArrayAccess interface to allow using the object as an associative array
   * to access the tags within it.
   *
   * @param string $offset The tag name
   * @author Jure Merhar <dev@merhar.si>
   * @see offsetExists()
   * @see offsetGet()
   * @see offsetSet()
   * @throws {@link RssException}
   */
  public function offsetUnset($offset)
  {
    if (!$this->offsetExists($offset)) throw new RssException('Invalid tag ' . $offset);
    $this->$offset = null;
  }
}
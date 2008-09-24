<?php

/**
 * RSS Feed class file
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
 * @version     $Id: RssFeed.php 3 2008-03-18 10:06:37Z jmerhar $
 * @link        http://pheeder.sourceforge.net/
 * @package     Pheeder
 */

/**
 * RSS Feed
 *
 * Use this class to create a new RSS feed.
 * 
 * This class implements several built-in interfaces for easier manipulation:
 * 
 * - <b>ArrayAccess</b>: Channel tags can be accessed as if the object was an
 *   associative array. For a list of channel tags, please see {@link fromArray}.
 *   <code>
 *   $feed['title'] = 'My RSS Feed';
 *   echo $feed['title'];
 *   if (isset($feed['copyright'])) unset($feed['copyright']);
 *   </code>
 * 
 * - <b>Iterator</b>: You can iterate through feed items by putting the object
 *   in a foreach loop.
 *   <code>
 *   foreach ($feed as $item) {
 *     $item->setAuthor('Jure Merhar');
 *   }
 *   </code>
 * 
 * - <b>Countable</b>: The number of feed items can be retrieved by using the PHP
 *   {@link http://www.php.net/count count()} function on the object.
 *   <code> echo count($feed);</code>
 * 
 * @author      Jure Merhar <dev@merhar.si>
 * @link        http://www.rssboard.org/rss-2-0-10 RSS 2.0 Specification
 * @version     See {@link pheeder.php} for the version number of this library
 * @package     Pheeder
 */
class RssFeed extends RssCommon implements Iterator, Countable
{
  /**
   * Character set encoding
   * 
   * Specifies the character set in which the output will be encoded.
   * Defaults to UTF-8 if not specified.
   * Use the {@link setEncoding()} method to modify it.
   *
   * @var string
   */
  protected $encoding = 'utf-8';

  /**
   * RSS version
   * 
   * Specifies the RSS version of the feed. Defaults to 2.0 if not specified.
   * Use the {@link setVersion()} method to modify it.
   * 
   * Please note that it is recommended to leave the version at it's default (2.0)
   * if you wish to have all the functionallity that this class provides.
   *
   * @var string
   */
  protected $version = '2.0';


  /**
   * The language tag
   *
   * The language the channel is written in. This allows aggregators to group
   * all Italian language sites, for example, on a single page. A list of allowable
   * values for this element, as provided by Netscape,
   * is {@link http://www.rssboard.org/rss-language-codes here}. You may also use
   * {@link http://www.w3.org/TR/REC-html40/struct/dirlang.html#langcodes values defined}
   * by the W3C.
   * 
   * Example: <code>'en-us'</code>
   *
   * @var string
   */
  protected $language = null;

  /**
   * The copyright tag
   * 
   * Copyright notice for content in the channel.
   * 
   * Example: <code>'Copyright 2002, Spartanburg Herald-Journal'</code>
   *
   * @var string
   */
  protected $copyright = null;

  /**
   * The managingEditor tag
   * 
   * Email address for person responsible for editorial content.
   * 
   * Example: <code>'geo@herald.com (George Matesky)'</code>
   *
   * @var string
   */
  protected $managingEditor = null;

  /**
   * The webMaster tag
   * 
   * Email address for person responsible for technical issues relating to channel.
   * 
   * Example: <code>'betty@herald.com (Betty Guernsey)'</code>
   *
   * @var string
   */
  protected $webMaster = null;

  /**
   * The lastBuildDate tag
   * 
   * The last time the content of the channel changed. Can be supplied in any format
   * accepted by {@link http://www.php.net/manual/en/function.strtotime.php strtotime()}.
   * 
   * Example:
   * <code>
   * 'Sat, 07 Sep 2002 09:42:31 GMT'
   * '2002-09-07 09:42:31'
   * </code>
   *
   * @var string
   */
  protected $lastBuildDate = null;

  /**
   * The generator tag
   * 
   * A string indicating the program used to generate the channel.
   * 
   * Defaults to 'Pheeder RSS Generator by Jure Merhar ({@link http://pheeder.sourceforge.net/})'.
   * 
   * Example: <code>'MightyInHouse Content System v2.3'</code>
   *
   * @var string
   */
  protected $generator = 'Pheeder RSS Generator by Jure Merhar (http://pheeder.sourceforge.net/)';

  /**
   * The docs tag
   * 
   * A URL that points to the documentation for the format used in the RSS file.
   * It's for people who might stumble across an RSS file on a Web server
   * 25 years from now and wonder what it is.
   * 
   * Defaults to {@link http://www.rssboard.org/rss-specification}.
   * 
   * Example: <code>'http://www.rssboard.org/rss-specification'</code>
   *
   * @var string
   */
  protected $docs = 'http://www.rssboard.org/rss-specification';

  /**
   * The cloud tag
   * 
   * Allows processes to register with a cloud to be notified of updates
   * to the channel, implementing a lightweight publish-subscribe protocol
   * for RSS feeds. More info
   * {@link http://www.rssboard.org/rss-2-0-10#ltcloudgtSubelementOfLtchannelgt here}.
   * 
   * Instead of providing each value for domain, post and path, you can
   * provide only a url in the form of http://domain:port/path.
   * 
   * Example 1:
   * <code>
   * array(
   *   'url' => 'http://rpc.sys.com/RPC2',
   *   'registerProcedure' => 'pingMe'
   * )
   * </code>
   * Example 2:
   * <code>
   * array(
   *   'domain' => 'rpc.sys.com',
   *   'port' => '80',
   *   'path' => '/RPC2',
   *   'registerProcedure' => 'pingMe',
   *   'protocol' => 'soap'
   * )
   * </code>
   *
   * @var array
   */
  protected $cloud = null;

  /**
   * The ttl tag
   * 
   * ttl stands for time to live. It's a number of minutes that indicates how long
   * a channel can be cached before refreshing from the source. More info
   * {@link http://www.rssboard.org/rss-2-0-10#ltttlgtSubelementOfLtchannelgt here}.
   * 
   * Example: <code>'60'</code>
   *
   * @var string
   */
  protected $ttl = null;

  /**
   * The image tag
   * 
   * Specifies a GIF, JPEG or PNG image that can be displayed with the channel. More info
   * {@link http://www.rssboard.org/rss-2-0-10#ltimagegtSubelementOfLtchannelgt here}.
   * 
   * The title, link and description tags are filled automatically with the
   * corresponding channel tags. The width and height tags are filled with
   * values collected from the image itself, if {@link $imageSize} is set to true.
   * You can do this by calling the {@link enableImageSize()} method. Please note that
   * enabling this option can impact performance if the image is located on a
   * remote server.
   * 
   * Example 1: <code>'rss_feed.png'</code>
   * Example 2: <code>'http://www.example.org/rss_feed.png'</code>
   *
   * @var array
   * @see enableImageSize()
   */
  protected $image = null;

  /**
   * The rating tag
   * 
   * The {@link http://www.w3.org/PICS/ PICS} rating for the channel.
   * 
   * @var string
   */
  protected $rating = null;

  /**
   * The textInput tag
   * 
   * Specifies a text input box that can be displayed with the channel. More info
   * {@link http://www.rssboard.org/rss-2-0-10#lttextinputgtSubelementOfLtchannelgt here}.
   * 
   * Example:
   * <code>
   * array(
   *   'title'       => 'Title',
   *   'description' => 'Description',
   *   'name'        => 'Name',
   *   'link'        => 'http://www.example.org/textInput'
   * )
   * </code>
   * 
   * Please note: The RSS specification actively discourages publishers from using
   * the textInput element, calling its purpose "something of a mystery" and stating
   * that "most aggregators ignore it." Fewer than one percent of surveyed RSS feeds
   * included the element. The only aggregators known to support it are BottomFeeder
   * and Liferea.
   * 
   * For this reason, publishers should not expect it to be supported in most aggregators.
   *
   * @var array
   * @deprecated Avoid Text Input
   */
  protected $textInput = null;

  /**
   * The skipHours tag
   * 
   * A hint for aggregators telling them which hours they can skip. More info
   * {@link http://www.rssboard.org/skip-hours-days#skiphours here}.
   * 
   * Example: <code>array(10, 11, 12, 21, 22)</code>
   *
   * @var array
   */
  protected $skipHours = null;

  /**
   * The skipDays tag
   * 
   * A hint for aggregators telling them which days they can skip. More info
   * {@link http://www.rssboard.org/skip-hours-days#skipdays here}.
   * 
   * Example: <code>array('Saturday', 'Sunday')</code>
   *
   * @var array
   */
  protected $skipDays = null;


  /**
   * The atom:link tag
   * 
   * The "atom:link" element defines a reference from an entry or feed to a Web resource.
   * More info {@link http://atompub.org/rfc4287.html#element.link here}.
   * 
   * Defaults to the current URL.
   * 
   * Example: <code>'http://www.example.org/rss'</code>
   *
   * @var string
   */
  protected $atomLink = null;

  /**
   * Check image size
   *
   * Specifies whether the width and height tags should be filled with
   * values collected from the image. You can enable this by calling the
   * {@link enableImageSize()} method. Please note that enabling this option
   * can impact performance if the image is located on a remote server.
   * 
   * Defaults to false.
   * 
   * @var bool
   * @see $image
   */
  protected $imageSize = false;

  /**
   * RSS items
   * 
   * Holds the RSS feed items. You can add a single item with the {@link addItem()}
   * method or an array of items with the {@link addItems()} method.
   * 
   * @var array
   * @see RssItem
   */
  protected $items = array();

  /**
   * @ignore
   */
  protected $depth = 2;

  /**
   * @ignore
   */
  protected $tags = array(
    'title', 'link', 'description', 'language', 'copyright',
    'managingEditor', 'webMaster', 'pubDate', 'lastBuildDate',
    'category', 'generator', 'docs', 'cloud', 'ttl',
    'image', 'rating', 'textInput', 'skipHours', 'skipDays',
  );
  /**
   * @ignore
   */
  protected $attributes = array(
    'cloud' => array('domain', 'port', 'path', 'registerProcedure', 'protocol'),
  );

  /**
   * Constructor
   *
   * Initializes an RSS feed.
   * The optional $data parameter can be an array for the {@link fromArray()} method.
   *
   * @param array $data Optional array of attributes and items
   * @author Jure Merhar <dev@merhar.si>
   * @return RssFeed Returns a new instance of this class
   */
  public function __construct($data = null)
  {
    $this->setLink($this->getUri(false));
    $this->setAtomLink($this->getUri());
    parent::__construct($data);
  }

  /**
   * Send HTTP headers
   *
   * Sends the Content-Type HTTP header for RSS.
   *
   * @author Jure Merhar <dev@merhar.si>
   */
  public function sendHeaders()
  {
    header('Content-Type: application/rss+xml; charset=' . $this->getEncoding());
  }

  /**
   * Output the feed
   *
   * Sends the HTTP headers, outputs the feed and exits the application.
   *
   * @author Jure Merhar <dev@merhar.si>
   * @uses sendHeaders()
   */
  public function run()
  {
    $this->sendHeaders();
    echo $this;
    exit;
  }

  /**
   * Save the feed
   *
   * Saves the feed to a file.
   *
   * @param string The file to write to
   * @author Jure Merhar <dev@merhar.si>
   */
  public function save($file)
  {
    $bytes = @file_put_contents($file, $this);
    if ($bytes === false) throw new RssException('Error writing file.');
  }

  /**
   * Get the feed as XML
   * 
   * Returns the feed as an XML string. This method is called if the object
   * is cast to string.
   *
   * @author Jure Merhar <dev@merhar.si>
   * @return string The RSS feed as XML
   * @throws {@link RssException}
   */
  public function asXml()
  {
    if (is_null($this->title) || is_null($this->link) || is_null($this->description)) {
      throw new RssException('The channel title, link and description are mandatory');
    }
    $output  = '<?xml version="1.0" encoding="' . $this->getEncoding() . '"?>' . $this->nl;
    $output .= '<rss version="' . $this->getVersion() . '" xmlns:atom="http://www.w3.org/2005/Atom">' . $this->nl;
    $output .= $this->tabs(1) . '<channel>' . $this->nl;
    foreach ($this->tags as $tag) {
      $output .= $this->getTagValue($tag);
    }
    $output .= $this->tabs(2) . '<atom:link href="' . $this->escape($this->getAtomLink());
    $output .= '" rel="self" type="application/rss+xml" />' . $this->nl;
    foreach ($this as $item) {
      $output .= $item;
    }
    $output .= $this->tabs(1) . '</channel>' . $this->nl;
    $output .= '</rss>';
    return $output;
  }

  /**
   * Add an item
   * 
   * Adds a single item to the feed. The $item parameter can either be an object
   * of the class RssItem or an associative array of item tags. The following tags
   * are allowed:
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
   * @param array|RssItem $item The RSS item
   * @author Jure Merhar <dev@merhar.si>
   * @uses RssItem
   * @uses $items
   * @throws {@link RssException}
   */
  public function addItem($item)
  {
    if (is_array($item)) $item = new RssItem($item);
    if (!($item instanceof RssItem)) throw new RssException('Invalid item type');
    $this->items[] = $item;
  }

  /**
   * Add multiple items
   * 
   * Adds an array of items to the feed. Each of the items in the array must
   * be compatible with the {@link addItem} method's parameter.
   *
   * @param array $array An array of RSS items
   * @author Jure Merhar <dev@merhar.si>
   * @uses addItem()
   * @throws {@link RssException}
   */
  public function addItems($array)
  {
    if (!is_array($array)) throw new RssException('Please provide an array');
    foreach ($array as $item) {
      $this->addItem($item);
    }
  }

  /**
   * Load the feed from an array
   * 
   * Loads the data for the feed from an associative array of tags. In addition,
   * an array of feed items can be provided under the key 'items'. The following
   * tags are allowed:
   * <ul>
   *   <li>{@link RssCommon::$title title}</li>
   *   <li>{@link RssCommon::$link link}</li>
   *   <li>{@link RssCommon::$description description}</li>
   *   <li>{@link RssFeed::$language language}</li>
   *   <li>{@link RssFeed::$copyright copyright}</li>
   *   <li>{@link RssFeed::$managingEditor managingEditor}</li>
   *   <li>{@link RssFeed::$webMaster webMaster}</li>
   *   <li>{@link RssCommon::$pubDate pubDate}</li>
   *   <li>{@link RssFeed::$lastBuildDate lastBuildDate}</li>
   *   <li>{@link RssCommon::$category category}</li>
   *   <li>{@link RssFeed::$generator generator}</li>
   *   <li>{@link RssFeed::$docs docs}</li>
   *   <li>{@link RssFeed::$cloud cloud}</li>
   *   <li>{@link RssFeed::$ttl ttl}</li>
   *   <li>{@link RssFeed::$image image}</li>
   *   <li>{@link RssFeed::$rating rating}</li>
   *   <li>{@link RssFeed::$textInput textInput}</li>
   *   <li>{@link RssFeed::$skipHours skipHours}</li>
   *   <li>{@link RssFeed::$skipDays skipDays}</li>
   * </ul>
   * 
   * The array of items must be compatible with the {@link addItems} method's parameter.
   * 
   * @param array $array An array of tags and/or items
   * @author Jure Merhar <dev@merhar.si>
   * @uses addItems()
   * @uses RssCommon::fromArray()
   */
  public function fromArray($array)
  {
    parent::fromArray($array);
    if (array_key_exists('items', $array)) $this->addItems($array['items']);
  }

  /**
   * Enable image size
   * 
   * Sets {@link $imageSize} to true.
   *
   * @author Jure Merhar <dev@merhar.si>
   * @uses $imageSize
   */
  public function enableImageSize()
  {
    $this->imageSize = true;
  }


  /**
   * Set encoding
   * 
   * The setter method for {@link $encoding}.
   * 
   * @param string $v The new value of encoding
   * @author Jure Merhar <dev@merhar.si>
   * @uses $encoding
   */
  public function setEncoding($v)
  {
    $this->encoding = $v;
  }

  /**
   * Set version
   * 
   * The setter method for {@link $version}.
   *
   * @param string $v The new value of version
   * @author Jure Merhar <dev@merhar.si>
   * @uses $version
   */
  public function setVersion($v)
  {
    $this->version = $v;
  }

  /**
   * Set atomLink
   * 
   * The setter method for {@link $atomLink}.
   *
   * @param string $v The new value of atomLink
   * @author Jure Merhar <dev@merhar.si>
   * @uses $atomLink
   */
  public function setAtomLink($v)
  {
    $this->atomLink = $v;
  }

  /**
   * Set language
   * 
   * The setter method for {@link $language}.
   *
   * @param string $v The new value of language
   * @author Jure Merhar <dev@merhar.si>
   * @uses $language
   */
  public function setLanguage($v)
  {
    $this->language = $v;
  }

  /**
   * Set copyright
   * 
   * The setter method for {@link $copyright}.
   *
   * @param string $v The new value of copyright
   * @author Jure Merhar <dev@merhar.si>
   * @uses $copyright
   */
  public function setCopyright($v)
  {
    $this->copyright = $v;
  }

  /**
   * Set managingEditor
   * 
   * The setter method for {@link $managingEditor}.
   *
   * @param string $v The new value of managingEditor
   * @author Jure Merhar <dev@merhar.si>
   * @uses $managingEditor
   */
  public function setManagingEditor($v)
  {
    $this->managingEditor = $v;
  }

  /**
   * Set webMaster
   * 
   * The setter method for {@link $webMaster}.
   *
   * @param string $v The new value of webMaster
   * @author Jure Merhar <dev@merhar.si>
   * @uses $webMaster
   */
  public function setWebMaster($v)
  {
    $this->webMaster = $v;
  }

  /**
   * Set lastBuildDate
   * 
   * The setter method for {@link $lastBuildDate}.
   *
   * @param string $v The new value of lastBuildDate
   * @author Jure Merhar <dev@merhar.si>
   * @uses $lastBuildDate
   */
  public function setLastBuildDate($v)
  {
    $this->lastBuildDate = $this->convertDate($v);
  }

  /**
   * Set generator
   * 
   * The setter method for {@link $generator}.
   *
   * @param string $v The new value of generator
   * @author Jure Merhar <dev@merhar.si>
   * @uses $generator
   */
  public function setGenerator($v)
  {
    $this->generator = $v;
  }

  /**
   * Set docs
   * 
   * The setter method for {@link $docs}.
   *
   * @param string $v The new value of docs
   * @author Jure Merhar <dev@merhar.si>
   * @uses $docs
   */
  public function setDocs($v)
  {
    $this->docs = $v;
  }

  /**
   * Set cloud
   * 
   * The setter method for {@link $cloud}.
   *
   * @param array $v The new value of cloud
   * @author Jure Merhar <dev@merhar.si>
   * @uses $cloud
   * @throws {@link RssException}
   */
  public function setCloud($v)
  {
    if (!is_array($v)) throw new RssException('Please provide an array of cloud parameters');
    if (isset($v['url'])) {
      $parts = parse_url($v['url']);
      $trans = array('host' => 'domain', 'port' => 'port', 'path' => 'path');
      foreach ($trans as $key => $value) {
        if (!isset($v[$value]) && isset($parts[$key])) $v[$value] = $parts[$key];
      }
    }
    if (!isset($v['port'])) $v['port'] = '80';
    if (!isset($v['protocol'])) $v['protocol'] = 'xml-rpc';
    if (!isset($v['domain']) || !isset($v['path']) || !isset($v['registerProcedure'])) {
      throw new RssException('Insufficient cloud parameters provided');
    }
    $this->cloud = $v;
  }

  /**
   * Set ttl
   * 
   * The setter method for {@link $ttl}.
   *
   * @param string $v The new value of ttl
   * @author Jure Merhar <dev@merhar.si>
   * @uses $ttl
   */
  public function setTtl($v)
  {
    $this->ttl = $v;
  }

  /**
   * Set image
   * 
   * The setter method for {@link $image}.
   *
   * @param string $v The new value of image
   * @author Jure Merhar <dev@merhar.si>
   * @uses $image
   */
  public function setImage($v)
  {
    $this->image = $v;
  }

  /**
   * Set rating
   * 
   * The setter method for {@link $rating}.
   *
   * @param string $v The new value of rating
   * @author Jure Merhar <dev@merhar.si>
   * @uses $rating
   */
  public function setRating($v)
  {
    $this->rating = $v;
  }

  /**
   * Set textInput
   * 
   * The setter method for {@link $textInput}.
   *
   * @param array $v The new value of textInput
   * @author Jure Merhar <dev@merhar.si>
   * @uses $textInput
   * @throws {@link RssException}
   */
  public function setTextInput($v)
  {
    if (!is_array($v)) throw new RssException('Please provide an array of textInput parameters');
    if (!isset($v['title']) || !isset($v['description']) || !isset($v['name']) || !isset($v['link'])) {
      throw new RssException('Insufficient textInput parameters provided');
    }
    $this->textInput = $v;
  }

  /**
   * Set skipHours
   * 
   * The setter method for {@link $skipHours}.
   *
   * @param array $v The new value of skipHours
   * @author Jure Merhar <dev@merhar.si>
   * @uses $skipHours
   * @throws {@link RssException}
   */
  public function setSkipHours($v)
  {
    if (!is_array($v)) throw new RssException('Please provide an array of hours');
    foreach ($v as $h) {
      if (!preg_match('/^\d{2}$/', $h) || ($h > 23)) throw new RssException('Invalid hours');
    }
    $this->skipHours = $v;
  }

  /**
   * Set skipDays
   * 
   * The setter method for {@link $skipDays}.
   *
   * @param array $v The new value of skipDays
   * @author Jure Merhar <dev@merhar.si>
   * @uses $skipDays
   * @throws {@link RssException}
   */
  public function setSkipDays($v)
  {
    if (!is_array($v)) throw new RssException('Please provide an array of days');
    $days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
    foreach ($v as $d) {
      if (!in_array($d, $days)) throw new RssException('Invalid days');
    }
    $this->skipDays = $v;
  }


  /**
   * Get encoding
   * 
   * The getter method for {@link $encoding}.
   * 
   * @author Jure Merhar <dev@merhar.si>
   * @return string The value of encoding
   * @uses $encoding
   */
  public function getEncoding()
  {
    return $this->encoding;
  }

  /**
   * Get version
   * 
   * The getter method for {@link $version}.
   * 
   * @author Jure Merhar <dev@merhar.si>
   * @return string The value of version
   * @uses $version
   */
  public function getVersion()
  {
    return $this->version;
  }

  /**
   * Get atomLink
   * 
   * The getter method for {@link $atomLink}.
   * 
   * @author Jure Merhar <dev@merhar.si>
   * @return string The value of atomLink
   * @uses $atomLink
   */
  public function getAtomLink()
  {
    return $this->atomLink;
  }

  /**
   * Get language
   * 
   * The getter method for {@link $language}.
   * 
   * @param bool $output Retrieve in XML format
   * @author Jure Merhar <dev@merhar.si>
   * @return string The value of language
   * @uses $language
   */
  public function getLanguage($output = false)
  {
    return $this->get('language', $output);
  }

  /**
   * Get copyright
   * 
   * The getter method for {@link $copyright}.
   * 
   * @param bool $output Retrieve in XML format
   * @author Jure Merhar <dev@merhar.si>
   * @return string The value of copyright
   * @uses $copyright
   */
  public function getCopyright($output = false)
  {
    return $this->get('copyright', $output);
  }

  /**
   * Get managingEditor
   * 
   * The getter method for {@link $managingEditor}.
   * 
   * @param bool $output Retrieve in XML format
   * @author Jure Merhar <dev@merhar.si>
   * @return string The value of managingEditor
   * @uses $managingEditor
   */
  public function getManagingEditor($output = false)
  {
    return $this->get('managingEditor', $output);
  }

  /**
   * Get webMaster
   * 
   * The getter method for {@link $webMaster}.
   * 
   * @param bool $output Retrieve in XML format
   * @author Jure Merhar <dev@merhar.si>
   * @return string The value of webMaster
   * @uses $webMaster
   */
  public function getWebMaster($output = false)
  {
    return $this->get('webMaster', $output);
  }

  /**
   * Get lastBuildDate
   * 
   * The getter method for {@link $lastBuildDate}.
   * 
   * @param bool $output Retrieve in XML format
   * @author Jure Merhar <dev@merhar.si>
   * @return string The value of lastBuildDate
   * @uses $lastBuildDate
   */
  public function getLastBuildDate($output = false)
  {
    return $this->get('lastBuildDate', $output);
  }

  /**
   * Get generator
   * 
   * The getter method for {@link $generator}.
   * 
   * @param bool $output Retrieve in XML format
   * @author Jure Merhar <dev@merhar.si>
   * @return string The value of generator
   * @uses $generator
   */
  public function getGenerator($output = false)
  {
    return $this->get('generator', $output);
  }

  /**
   * Get docs
   * 
   * The getter method for {@link $docs}.
   * 
   * @param bool $output Retrieve in XML format
   * @author Jure Merhar <dev@merhar.si>
   * @return string The value of docs
   * @uses $docs
   */
  public function getDocs($output = false)
  {
    return $this->get('docs', $output);
  }

  /**
   * Get cloud
   * 
   * The getter method for {@link $cloud}.
   * 
   * @param bool $output Retrieve in XML format
   * @author Jure Merhar <dev@merhar.si>
   * @return string|array The value of cloud
   * @uses $cloud
   */
  public function getCloud($output = false)
  {
    return $this->get('cloud', $output);
  }

  /**
   * Get ttl
   * 
   * The getter method for {@link $ttl}.
   * 
   * @param bool $output Retrieve in XML format
   * @author Jure Merhar <dev@merhar.si>
   * @return string The value of ttl
   * @uses $ttl
   */
  public function getTtl($output = false)
  {
    return $this->get('ttl', $output);
  }

  /**
   * Get image
   * 
   * The getter method for {@link $image}.
   * 
   * @param bool $output Retrieve in XML format
   * @author Jure Merhar <dev@merhar.si>
   * @return string The value of image
   * @uses $image
   * @throws {@link RssException}
   */
  public function getImage($output = false)
  {
    $image = $this->get('image', false);
    if (!$output) return $image;
    $image = $this->createUrl($image);
    $size = $this->imageSize ? @getimagesize($image) : false;
    $xml  = $this->tabs(2) . '<image>' . $this->nl;
    $xml .= $this->tabs(3) . '<url>' . $this->escape($image) . '</url>' . $this->nl;
    $xml .= $this->tabs(1) . $this->getTagValue('title');
    $xml .= $this->tabs(1) . $this->getTagValue('link');
    if ($size) {
      list($width, $height) = $size;
      if (($width > 144) || ($height > 400)) {
        throw new RssException('Image dimensions too large. The maximum allowed size is 144x400.');
      }
      $xml .= $this->tabs(3) . '<width>'  . $width  . '</width>'  . $this->nl;
      $xml .= $this->tabs(3) . '<height>' . $height . '</height>' . $this->nl;
    }
    $xml .= $this->tabs(1) . $this->getTagValue('description');
    $xml .= $this->tabs(2) . '</image>' . $this->nl;
    return $xml;
  }

  /**
   * Get rating
   * 
   * The getter method for {@link $rating}.
   * 
   * @param bool $output Retrieve in XML format
   * @author Jure Merhar <dev@merhar.si>
   * @return string The value of rating
   * @uses $rating
   */
  public function getRating($output = false)
  {
    return $this->get('rating', $output);
  }

  /**
   * Get textInput
   * 
   * The getter method for {@link $textInput}.
   * 
   * @param bool $output Retrieve in XML format
   * @author Jure Merhar <dev@merhar.si>
   * @return string|array The value of textInput
   * @uses $textInput
   */
  public function getTextInput($output = false)
  {
    $textInput = $this->get('textInput', false);
    if (!$output) return $textInput;
    $xml  = $this->tabs(2) . '<textInput>' . $this->nl;
    $vars = array('title', 'description', 'name', 'link');
    foreach ($vars as $var) {
      $xml .= $this->tabs(3) . "<$var>" . $this->escape($textInput[$var]) . "</$var>" . $this->nl;
    }
    $xml .= $this->tabs(2) . '</textInput>' . $this->nl;
    return $xml;
  }

  /**
   * Get skipHours
   * 
   * The getter method for {@link $skipHours}.
   * 
   * @param bool $output Retrieve in XML format
   * @author Jure Merhar <dev@merhar.si>
   * @return string|array The value of skipHours
   * @uses $skipHours
   */
  public function getSkipHours($output = false)
  {
    $skipHours = $this->get('skipHours', false);
    if (!$output) return $skipHours;
    $xml  = $this->tabs(2) . '<skipHours>' . $this->nl;
    foreach ($skipHours as $h) {
      $xml .= $this->tabs(3) . "<hour>$h</hour>" . $this->nl;
    }
    $xml .= $this->tabs(2) . '</skipHours>' . $this->nl;
    return $xml;
  }

  /**
   * Get skipDays
   * 
   * The getter method for {@link $skipDays}.
   * 
   * @param bool $output Retrieve in XML format
   * @author Jure Merhar <dev@merhar.si>
   * @return string|array The value of skipDays
   * @uses $skipDays
   */
  public function getSkipDays($output = false)
  {
    $skipDays = $this->get('skipDays', false);
    if (!$output) return $skipDays;
    $xml  = $this->tabs(2) . '<skipDays>' . $this->nl;
    foreach ($skipDays as $d) {
      $xml .= $this->tabs(3) . "<day>$d</day>" . $this->nl;
    }
    $xml .= $this->tabs(2) . '</skipDays>' . $this->nl;
    return $xml;
  }


  /* Iterator Interface */

  /**
   * Current item
   * 
   * Returns the current RSS feed item. This method is also used as part of
   * the Iterator interface to allow using the object of the RssFeed class
   * in a foreach loop.
   * 
   * @author Jure Merhar <dev@merhar.si>
   * @return RssItem|bool The current item
   * @uses $items
   * @see next()
   * @see rewind()
   * @see key()
   * @see valid()
   */
  public function current()
  {
    return current($this->items);
  }

  /**
   * Next item
   * 
   * Moves to the next RSS feed item. This method is also used as part of
   * the Iterator interface to allow using the object of the RssFeed class
   * in a foreach loop.
   * 
   * @author Jure Merhar <dev@merhar.si>
   * @return RssItem|bool The next item or false
   * @uses $items
   * @see current()
   * @see rewind()
   * @see key()
   * @see valid()
   */
  public function next()
  {
    return next($this->items);
  }

  /**
   * First item
   * 
   * Moves to the first RSS feed item. This method is also used as part of
   * the Iterator interface to allow using the object of the RssFeed class
   * in a foreach loop.
   * 
   * @author Jure Merhar <dev@merhar.si>
   * @return RssItem|bool The first item or false
   * @uses $items
   * @see current()
   * @see next()
   * @see key()
   * @see valid()
   */
  public function rewind()
  {
    return reset($this->items);
  }

  /**
   * Current key
   * 
   * Returns the current item's key. This method is also used as part of
   * the Iterator interface to allow using the object of the RssFeed class
   * in a foreach loop.
   * 
   * @author Jure Merhar <dev@merhar.si>
   * @return int The current key
   * @uses $items
   * @see current()
   * @see next()
   * @see rewind()
   * @see valid()
   */
  public function key()
  {
    return key($this->items);
  }

  /**
   * Valid item
   * 
   * Returns true if the current item is valid. This method is also used as part of
   * the Iterator interface to allow using the object of the RssFeed class
   * in a foreach loop.
   * 
   * @author Jure Merhar <dev@merhar.si>
   * @return bool true if the current item is valid, false otherwise
   * @uses $items
   * @see current()
   * @see next()
   * @see rewind()
   * @see key()
   */
  public function valid()
  {
    return !is_null($this->key());
  }


  /* Countable Interface */

  /**
   * Count items
   * 
   * Returns the number of RSS feed items. This method is also used as part of the
   * Countable interface to allow using the PHP {@link http://www.php.net/count count()}
   * function on an object of the RssFeed class.
   * 
   * @author Jure Merhar <dev@merhar.si>
   * @return int The number of items
   * @uses $items
   */
  public function count()
  {
    return count($this->items);
  }

}
<?php

namespace Steadweb\Rss\Drivers;

use Steadweb\Rss\Rss;
use Steadweb\Rss\Interfaces\FeedInterface;
use \Iterator;
use \RuntimeException;

class SimpleXml implements FeedInterface, Iterator
{
	/**
	 * @var null
	 */
	protected $title = null;

	/**
	 * @var null
	 */
	protected $uri = null;

	/**
	 * @var null
	 */
	protected $description = null;

	/**
	 * @var null
	 */
	protected $lastupdated = null;

	/**
	 * @var array
	 */
	protected $items = array();

	/**
	 * @var int
	 */
	private $position = 0;

	/**
	 * Create a new feed based on a URI.
	 *
	 * @param $uri
	 * @param array $items
	 * @param string $title
	 * @param string $description
	 * @param int $lastupdated
	 * @throws RuntimeException
	 */
	public function __construct($uri, array $items = [], $title = null, $description = null, $lastupdated = null)
	{
		if( ! is_string($uri)) {
			throw new RuntimeException('URI must be a string.');
		}

		$this->uri = $uri;
		$this->title = $title;
		$this->description = $description;
		$this->lastupdated = $lastupdated;

		$feed_items = [];

		foreach($items as $object) {
			// Construct a datetime object
			$date = new \DateTime(date(Rss::TIMESTAMP_FORMAT, $object->published));

			$feed_items[] = new SimpleXml\Item($object->title, $object->uri, $object->description, $date->format(Rss::TIMESTAMP_FORMAT));
		}

		$this->items = $feed_items;
	}

	/**
	 * Get the feed URI
	 *
	 * @return string
	 */
	public function getFeedUri()
	{
		return $this->uri;
	}

	/**
	 * Get the feed title
	 *
	 * @return string
	 */
	public function getFeedTitle()
	{
		return $this->title;
	}

	/**
	 * Get the feed description
	 *
	 * @return string
	 */
	public function getFeedDescription()
	{
		return $this->description;
	}

	/**
	 * Get the feed last updated
	 *
	 * @return int
	 */
	public function getFeedLastUpdated()
	{
		return $this->lastupdated;
	}


	/**
	 * Parse results of the feed.
	 *
	 * @throws \Exception
	 * @internal param $url
	 * @return \SimpleXMLElement
	 */
	public function parse()
	{
		// Parse the feed and return
		try {
			// Parse the RSS feed
			if($data = simplexml_load_file($this->getFeedUri())) {

				// Information about the feed
				$this->title = $data->channel->title;
				$this->description = $data->channel->description;
				$this->lastupdated = $data->channel->lastBuildDate;

				// Parse the items
				$this->items = $this->parseItems($data->channel);

				return $this;
			}
		} catch(\Exception $e) {
			throw $e;
		}

		throw new RuntimeException('Unable to parse RSS feed ' . $this->getFeedUri());
	}

	/**
	 * Parse the items from the feed
	 *
	 * @param \SimpleXMLElement $channel
	 * @return array
	 */
	private function parseItems(\SimpleXMLElement $channel)
	{
		$feed_items = [];

		foreach($channel->item as $item) {
			$feed_items[] = new SimpleXml\Item($item->title, $item->link, strip_tags($item->description), $item->pubDate);
		}

		return $feed_items;
	}

	/**
	 * Try to find the property if it exists, otherwise throw an exception.
	 *
	 * @param $name
	 * @return mixed
	 * @throws PropertyDoesNotExistException
	 */
	public function __get($name)
	{
		if(property_exists($this, $name)) {
			return $this->{$name};
		}

		throw new PropertyDoesNotExistException("{$name} does not exist.");
	}

	// =================================== Iterator methods ===================================

	public function current()
	{
		return $this->items[$this->position];
	}

	public function next()
	{
		++$this->position;
	}

	public function key()
	{
		return $this->position;
	}

	public function valid()
	{
		return isset($this->items[$this->position]);
	}

	public function rewind()
	{
		$this->position = 0;
	}
}

class PropertyDoesNotExistException extends \Exception {}

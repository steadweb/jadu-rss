<?php

namespace Steadweb\Rss;

use \Steadweb\Rss\Interfaces\StorageInterface;
use \Steadweb\Rss\Interfaces\FeedInterface;
use \Iterator;

class Rss implements Iterator
{
	/**
	 * @var string
	 */
	public static $version = '0.1.0';

	/**
	 * @var
	 */
	protected $storage;

	/**
	 * @var array
	 */
	protected $feeds = array();

	/**
	 * @var int
	 */
	private $position = 0;

	/**
	 * @var string
	 */
	private $driver;

	/**
	 * TS format according to RSS 2.0 Specification
	 * http://asg.web.cmu.edu/rfc/rfc822.html#sec-5
	 *
	 * @var string
	 */
	const TIMESTAMP_FORMAT = 'D, d M Y H:i:s T';

	/**
	 * Create a new feed based on a URI. Pass a storage option
	 *
	 * @param StorageInterface $storage
	 * @param string $driver
	 */
	public function __construct(StorageInterface $storage, $driver = 'Steadweb\Rss\Drivers\SimpleXml')
	{
		$this->storage = $storage;
		$this->driver = $driver;

		// cache the feeds
		// chaining
		return $this->refresh();
	}

	/**
	 * Fetch all the feeds and cache into $feeds
	 *
	 * @return $this
	 */
	public function feeds()
	{
		if( ! $this->feeds) {
			$feeds = $this->storage->get();

			// adapt the feeds into \Steadweb\Rss\Feed classes
			// @todo make this more aware that storage might return a different structure.
			array_walk($feeds, function($feed) {
				$this->feeds[] = new $this->driver($feed->uri, $feed->items, $feed->title, $feed->description, $feed->lastupdated);
			});
		}

		// chaining
		return $this;
	}

	/**
	 * Proxy method for storage. Add a new feed to the RSS container
	 *
	 * @param FeedInterface $feed
	 * @return $this
	 */
	public function add(FeedInterface $feed)
	{
		$this->storage->save($feed);

		return $this->refresh();
	}

	/**
	 * Add many feeds at once to your RSS container.
	 *
	 * @param array $feeds
	 * @return $this
	 */
	public function addMany(array $feeds = [])
	{
		foreach($feeds as $feed) {
			$this->add($feed);
		}

		return $this->refresh();
	}

	/**
	 * Update all feeds in the RSS container.
	 *
	 * @internal param \Steadweb\Rss\Interfaces\FeedInterface $feed
	 * @return $this
	 */
	public function update()
	{
		foreach($this->feeds as $feed) {
			$this->storage->save($feed);
		}

		return $this->refresh();
	}

	/**
	 * Remove a feed from the RSS container.
	 *
	 * @param FeedInterface $feed
	 */
	public function remove(FeedInterface $feed)
	{
		return $this->storage->remove($feed);
	}

	/**
	 * Refresh all feeds. This will order feed items correctly based on the
	 * timestamp as well.
	 *
	 * @return $this
	 */
	private function refresh()
	{
		// Finally, get the newly updated results.
		$this->feeds = [];

		// chaining
		return $this->feeds();
	}


	// =================================== Iterator methods ===================================

	public function current()
	{
		return $this->feeds[$this->position];
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
		return isset($this->feeds[$this->position]);
	}

	public function rewind()
	{
		$this->position = 0;
	}
}


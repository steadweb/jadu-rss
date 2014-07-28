<?php

namespace Steadweb\Rss\Storage;

use RuntimeException;
use Steadweb\Rss\Rss;
use Steadweb\Rss\Feed;
use Steadweb\Rss\Interfaces\FeedInterface;
use Steadweb\Rss\Interfaces\StorageInterface;
use \PDO;

class MySQL implements StorageInterface
{
	/**
	 * @var null
	 */
	protected $connection = null;

	/**
	 * MySQL (PDO) connection handler.
	 *
	 * @param $host
	 * @param $database
	 * @param $username
	 * @param $password
	 * @param array $options
	 * @throws \Exception
	 * @throws \PDOException
	 * @return \Steadweb\Rss\Storage\MySQL
	 */
	public function __construct($host, $database, $username, $password, $options = [])
	{
		// Construct DSN
		$dsn = "mysql:host={$host};dbname={$database}";

		// Try to return a connection
		try
		{
			// cache the connection
			$this->connection = new \PDO($dsn, $username, $password, $options);

			return $this->connection;
		}
		catch(\PDOException $e)
		{
			throw $e;
		}
	}

	/**
	 * Get a list of feeds and their items
	 *
	 * @return bool
	 * @throws \Exception
	 * @throws \PDOException
	 */
	public function get()
	{
		$feeds = [];

		// Find all the feeds
		$db = $this->connection->prepare('SELECT * FROM feed');

		try
		{
			if($db->execute())
			{
				// Foreach feed, find it's cached items.
				foreach($db->fetchAll(PDO::FETCH_OBJ) as $feed)
				{
					$feed->items = array();
					$feed->items = $this->getFeedItems((int)$feed->id);
					$feeds[] = $feed;
				}
			}
		}
		catch(\PDOException $e)
		{
			throw $e;
		}

		return $feeds;
	}

	/**
	 * Delete a feed from storage
	 *
	 * @param FeedInterface $feed
	 * @return bool
	 */
	public function remove(FeedInterface $feed)
	{
		$uri = $feed->getFeedUri();

		$db = $this->connection->prepare('DELETE FROM feed WHERE uri = :uri');
		$db->bindParam(':uri', $uri, PDO::PARAM_STR);
		return $db->execute();
	}

	/**
	 * Add a feed if it's not already cached in storage
	 *
	 * @param FeedInterface $feed
	 * @throws \RuntimeException
	 * @return \Steadweb\Rss\Interfaces\FeedInterface
	 */
	public function save(FeedInterface $feed)
	{
		// If exists, do nothing.
		try {
			if( ! $this->feedExists($feed->getFeedUri()) && $feed->parse()) {

				// Feed details
				$uri = $feed->getFeedUri();
				$title = $feed->getFeedTitle();
				$description = $feed->getFeedDescription();
				$lastupdated = $feed->getFeedLastUpdated();

				// Timestamp
				$timestamp = time();

				// Prepare the feed
				$db = $this->connection->prepare('INSERT INTO feed (uri, title, description, lastupdated, created_at, updated_at) VALUES(:uri, :title, :description, :lastupdated, :created, :updated)');

				// Bind params
				$db->bindParam(':uri', $uri, PDO::PARAM_STR);
				$db->bindParam(':title', $title, PDO::PARAM_STR);
				$db->bindParam(':description', $description, PDO::PARAM_STR);
				$db->bindParam(':lastupdated', $lastupdated, PDO::PARAM_INT);
				$db->bindParam(':created', $timestamp, PDO::PARAM_INT);
				$db->bindParam(':updated', $timestamp, PDO::PARAM_INT);

				// Basic validation checking
				if( ! $db->execute()) {
					return false;
				}
			}
		} catch(\RuntimeException $e) {
			throw $e;
		}

		// Save feed items
		$this->saveFeedItems($this->getFeedId($feed->getFeedUri()), $feed);

		return $feed;
	}

	/**
	 * Save feed items
	 *
	 * @param $feed_id
	 * @param FeedInterface $feed
	 */
	private function saveFeedItems($feed_id, FeedInterface $feed)
	{
		// Stop the original feed from being affected by cloning
		// a new feed. We'll update the original one shortly.
		$updatedFeed = clone $feed;

		// parse the new set of results
		$updatedFeed->parse();

		// Perform lot's of queries, this could hinder performance if their are lot's of feeds.
		foreach($updatedFeed->items as $item) {

			// URI is already cached, not point saving again.
			if($this->feedItemExists($item->getUri())) {
				continue;
			}

			// Prepare each item
			$db = $this->connection->prepare('INSERT INTO feed__items (feed_id, title, uri, description, published) VALUES (:feed_id, :title, :uri, :description, :published)');

			$uri = $item->getUri();
			$title = $item->getTitle();
			$description = $item->getDescription();
			$published = $item->getPublishDate();

			// Construct a datetime object
			$date = \DateTime::createFromFormat(Rss::TIMESTAMP_FORMAT, $published);
			$timestamp = $date->getTimestamp();

			// Bind params
			$db->bindParam(':feed_id', $feed_id);
			$db->bindParam(':uri', $uri);
			$db->bindParam(':title', $title);
			$db->bindParam(':description', $description);
			$db->bindParam(':published', $timestamp);

			// Some sort of error reporting would be nice..
			$db->execute();
		}
	}

	/**
	 * Find all feed items. We should attempt to cache these as well.
	 *
	 * @param $feed_id
	 * @return array
	 * @throws RuntimeException
	 */
	private function getFeedItems($feed_id)
	{
		if( ! is_int($feed_id)) {
			throw new \RuntimeException('Feed ID must be an integer.');
		}

		$db = $this->connection->prepare('SELECT title, uri, description, published FROM feed__items WHERE feed_id = :feed_id ORDER BY published DESC');
		$db->bindParam(':feed_id', $feed_id, PDO::PARAM_INT);

		if($db->execute()) {
			return $db->fetchAll(PDO::FETCH_OBJ);
		}
	}


	/**
	 * Find a feed based on the URI
	 *
	 * @param $uri
	 * @return bool
	 * @internal param $url
	 */
	private function feedExists($uri)
	{
		return $this->exists('feed', $uri);
	}

	/**
	 * Find a feed item based on the URI
	 *
	 * @param $uri
	 * @return bool
	 */
	private function feedItemExists($uri)
	{
		return $this->exists('feed__items', $uri);
	}

	/**
	 * Abstracted exists method out, utilized across multiple tables.
	 * Tables are static.
	 *
	 * @param $table
	 * @param $uri
	 * @return bool
	 */
	private function exists($table, $uri)
	{
		// In this instance, the only allowed tables are feed and feed_items
		$allowed = ['feed', 'feed__items'];

		if(in_array($table, $allowed)) {
			$db = $this->connection->prepare("SELECT * FROM {$table} WHERE uri = :uri");
			$db->bindParam(':uri', $uri);

			if($db->execute()) {
				return (bool) $db->rowCount();
			}
		}

		return false;
	}

	/**
	 * Get the internal feed ID based on the URI
	 *
	 * @param $uri
	 * @return bool
	 */
	private function getFeedId($uri)
	{
		$db = $this->connection->prepare("SELECT id FROM feed WHERE uri = :uri LIMIT 1");
		$db->bindParam(':uri', $uri);

		if($db->execute() && ($data = $db->fetch(PDO::FETCH_OBJ))) {
			return $data->id;
		}

		return false;
	}
}
